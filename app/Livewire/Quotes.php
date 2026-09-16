<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductChild;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Quotes extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterBranch = '';
    public $dateFrom = '';
    public $dateTo = '';

    public $showDetailModal = false;
    public $selectedQuote = null;

    public $showCancelModal = false;
    public $cancelQuoteId = null;

    // Recovery feature — only for softwaremik@gmail.com
    public $isRecoveryUser = false;
    public $showRecoveryModal = false;
    public $recoveryPhase = 'idle'; // idle | scanned | processing | done
    public $orphanCount = 0;
    public $recoveryResults = [];

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        // Only the recovery user can see and use the recovery button
        $this->isRecoveryUser = auth()->user()->email === 'softwaremik@gmail.com';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterBranch(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function viewQuote($quoteId): void
    {
        $this->selectedQuote = Quote::with([
            'customer.taxDocument',
            'user',
            'branch',
            'items',
            'convertedToSale',
        ])->find($quoteId);

        $this->showDetailModal = true;
    }

    public function closeDetail(): void
    {
        $this->showDetailModal = false;
        $this->selectedQuote = null;
    }

    /**
     * Convert quote to sale by redirecting to POS with quote_id parameter.
     * The POS will pre-load the cart with this quote's items and prices.
     */
    public function convertToSale($quoteId): void
    {
        $quote = Quote::find($quoteId);
        if (!$quote) {
            $this->dispatch('notify', message: 'Cotización no encontrada', type: 'error');
            return;
        }

        if ($quote->status !== 'draft') {
            $this->dispatch('notify', message: 'Esta cotización ya no se puede convertir', type: 'error');
            return;
        }

        // Redirect to POS with the quote ID and branch ID as query params
        $params = ['from_quote' => $quote->id];
        if ($quote->branch_id) {
            $params['branch_id'] = $quote->branch_id;
        }
        $this->redirect(route('pos', $params), navigate: false);
    }

    public function openCancelModal($quoteId): void
    {
        $this->cancelQuoteId = $quoteId;
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
        $this->cancelQuoteId = null;
    }

    public function confirmCancel(): void
    {
        $quote = Quote::find($this->cancelQuoteId);
        if (!$quote) {
            $this->closeCancelModal();
            return;
        }

        if ($quote->status !== 'draft') {
            $this->dispatch('notify', message: 'Solo se pueden cancelar cotizaciones en estado borrador', type: 'error');
            $this->closeCancelModal();
            return;
        }

        // Release reserved inventory before cancelling
        $quote->releaseInventory();

        $oldValues = $quote->toArray();
        $quote->update(['status' => 'cancelled']);

        ActivityLogService::logUpdate(
            'quotes',
            $quote,
            $oldValues,
            "Cotización {$quote->quote_number} cancelada"
        );

        $this->dispatch('notify', message: 'Cotización cancelada y stock liberado', type: 'success');
        $this->closeCancelModal();
    }

    public function printQuote($quoteId): void
    {
        $this->dispatch('print-quote', quoteId: $quoteId);
    }

    // ─── Recovery methods ───────────────────────────────────────────────

    /**
     * Scan for orphan quotes: status = 'converted' but the linked sale no longer exists.
     */
    public function scanOrphanQuotes(): void
    {
        if (!$this->isRecoveryUser) {
            return;
        }

        // Find converted quotes whose sale was deleted (NULL or non-existent)
        $orphans = Quote::where('status', 'converted')
            ->where(function ($q) {
                $q->whereNull('converted_to_sale_id')
                  ->orWhereDoesntHave('convertedToSale');
            })
            ->get();

        $this->orphanCount = $orphans->count();
        $this->recoveryPhase = 'scanned';
        $this->recoveryResults = [];
        $this->showRecoveryModal = true;
    }

    /**
     * Re-create sales for all orphan converted quotes.
     * - NO inventory movements
     * - NO stock changes
     * - NO payments
     * - payment_type = credit, payment_status = pending
     * - created_at = quote's converted_at (or created_at)
     */
    public function executeRecovery(): void
    {
        if (!$this->isRecoveryUser) {
            return;
        }

        $this->recoveryPhase = 'processing';
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        $orphans = Quote::with(['items', 'customer'])
            ->where('status', 'converted')
            ->where(function ($q) {
                $q->whereNull('converted_to_sale_id')
                  ->orWhereDoesntHave('convertedToSale');
            })
            ->get();

        foreach ($orphans as $quote) {
            try {
                DB::beginTransaction();

                // Use the quote's conversion date, fallback to created_at
                $saleDate = $quote->converted_at ?? $quote->created_at;

                // Create the sale record
                $sale = new Sale();
                $sale->branch_id = $quote->branch_id;
                $sale->cash_reconciliation_id = null; // No reconciliation
                $sale->customer_id = $quote->customer_id;
                $sale->user_id = $quote->user_id;
                $sale->seller_id = $quote->user_id;
                $sale->invoice_number = Sale::generateInvoiceNumber($quote->branch_id ?? 1);
                $sale->subtotal = $quote->subtotal;
                $sale->tax_total = $quote->tax_total;
                $sale->discount = $quote->discount;
                $sale->total = $quote->total;
                $sale->status = 'completed';
                $sale->payment_type = 'credit';
                $sale->payment_status = 'pending';
                $sale->credit_amount = $quote->total;
                $sale->paid_amount = 0;
                $sale->payment_due_date = null;
                $sale->notes = "Factura recuperada desde cotización {$quote->quote_number}";
                $sale->global_discount_type = $quote->global_discount_type;
                $sale->global_discount_value = $quote->global_discount_value;
                $sale->global_discount_amount = $quote->global_discount_amount;
                $sale->global_discount_reason = $quote->global_discount_reason;
                $sale->source = 'pos';
                $sale->created_at = $saleDate;
                $sale->updated_at = $saleDate;
                $sale->save();

                // Create sale items from quote items — NO inventory changes
                foreach ($quote->items as $qItem) {
                    $unitCost = 0;

                    if ($qItem->product_id) {
                        $product = Product::find($qItem->product_id);
                        if ($product) {
                            if ($qItem->product_child_id) {
                                $child = ProductChild::find($qItem->product_child_id);
                                $unitCost = $child ? $child->getAverageCost() : 0;
                            } else {
                                $unitCost = $product->average_cost > 0
                                    ? $product->average_cost
                                    : $product->purchase_price;
                            }
                        }
                    }

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $qItem->product_id,
                        'product_child_id' => $qItem->product_child_id,
                        'service_id' => $qItem->service_id,
                        'combo_id' => $qItem->combo_id,
                        'product_name' => $qItem->product_name,
                        'product_sku' => $qItem->product_sku,
                        'unit_price' => $qItem->unit_price,
                        'unit_cost' => $unitCost,
                        'quantity' => $qItem->quantity,
                        'tax_rate' => $qItem->tax_rate,
                        'tax_amount' => $qItem->tax_amount,
                        'subtotal' => $qItem->subtotal,
                        'discount_type' => $qItem->discount_type,
                        'discount_type_value' => $qItem->discount_type_value,
                        'discount_amount' => $qItem->discount_amount,
                        'discount_reason' => $qItem->discount_reason,
                        'total' => $qItem->total,
                    ]);
                }

                // Link the quote back to the newly created sale
                $quote->update([
                    'converted_to_sale_id' => $sale->id,
                ]);

                ActivityLogService::logCreate(
                    'sales',
                    $sale,
                    "Factura {$sale->invoice_number} recuperada desde cotización {$quote->quote_number} (crédito, sin inventario)"
                );

                DB::commit();

                $successCount++;
                $results[] = [
                    'status' => 'success',
                    'quote' => $quote->quote_number,
                    'invoice' => $sale->invoice_number,
                    'total' => $sale->total,
                    'customer' => $quote->customer
                        ? ($quote->customer->business_name ?: $quote->customer->first_name . ' ' . $quote->customer->last_name)
                        : 'Sin cliente',
                ];
            } catch (\Throwable $e) {
                DB::rollBack();
                $errorCount++;
                $results[] = [
                    'status' => 'error',
                    'quote' => $quote->quote_number,
                    'invoice' => null,
                    'total' => $quote->total,
                    'customer' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        $this->recoveryResults = $results;
        $this->recoveryPhase = 'done';

        if ($successCount > 0) {
            $this->dispatch('notify',
                message: "Recuperación completada: {$successCount} factura(s) creada(s)" . ($errorCount > 0 ? ", {$errorCount} error(es)" : ''),
                type: $errorCount > 0 ? 'warning' : 'success'
            );
        } else {
            $this->dispatch('notify',
                message: 'No se pudo crear ninguna factura',
                type: 'error'
            );
        }
    }

    public function closeRecoveryModal(): void
    {
        $this->showRecoveryModal = false;
        $this->recoveryPhase = 'idle';
        $this->recoveryResults = [];
        $this->orphanCount = 0;
    }

    // ─── End recovery methods ──────────────────────────────────────────

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $query = Quote::with(['customer', 'user', 'branch', 'convertedToSale'])
            ->orderByDesc('created_at');

        if ($isSuperAdmin) {
            if ($this->filterBranch) {
                $query->where('quotes.branch_id', $this->filterBranch);
            }
        } else {
            $query->where('quotes.branch_id', $user->branch_id);
        }

        if ($this->dateFrom) {
            $query->whereDate('quotes.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('quotes.created_at', '<=', $this->dateTo);
        }
        if ($this->filterStatus) {
            $query->where('quotes.status', $this->filterStatus);
        }
        if ($this->search) {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('quotes.quote_number', 'like', "%{$term}%")
                  ->orWhereHas('customer', function ($c) use ($term) {
                      $c->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('business_name', 'like', "%{$term}%")
                        ->orWhere('document_number', 'like', "%{$term}%");
                  });
            });
        }

        $quotes = $query->paginate(20);

        $branches = $isSuperAdmin
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        // Stats for the period
        $statsBase = Quote::query();
        if ($isSuperAdmin) {
            if ($this->filterBranch) {
                $statsBase->where('quotes.branch_id', $this->filterBranch);
            }
        } else {
            $statsBase->where('quotes.branch_id', $user->branch_id);
        }
        if ($this->dateFrom) {
            $statsBase->whereDate('quotes.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $statsBase->whereDate('quotes.created_at', '<=', $this->dateTo);
        }

        $totalQuotes = (clone $statsBase)->count();
        $totalAmount = (clone $statsBase)->sum('total');
        $convertedCount = (clone $statsBase)->where('quotes.status', 'converted')->count();

        return view('livewire.quotes', [
            'quotes' => $quotes,
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
            'totalQuotes' => $totalQuotes,
            'totalAmount' => $totalAmount,
            'convertedCount' => $convertedCount,
        ]);
    }
}
