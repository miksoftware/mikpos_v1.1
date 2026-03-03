<?php

namespace App\Livewire;

use App\Models\Sale;
use App\Models\SaleReprint;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Branch;
use App\Models\CashReconciliation;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Services\FactusService;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Sales extends Component
{
    use WithPagination;

    public $search = '';
    public $filterStatus = '';
    public $filterElectronic = '';
    public $filterBranch = '';
    public $dateFrom = '';
    public $dateTo = '';
    
    public $showDetailModal = false;
    public $selectedSale = null;
    
    public $showReprintsModal = false;
    public $selectedSaleReprints = [];
    
    public $showCreditNoteModal = false;
    public $creditNoteType = 'total';
    public $creditNoteReason = '';
    public $creditNoteCorrectionCode = '2';
    public $creditNoteItems = [];
    public $isProcessingCreditNote = false;
    
    public $showRefundModal = false;
    public $refundType = 'total';
    public $refundReason = '';
    public $refundItems = [];
    public $isProcessingRefund = false;
    
    public $showHistoryModal = false;
    public $historyType = '';
    public $historyItems = [];
    
    public $isRetrying = false;

    // Cancel & Replicate
    public $replicateAfter = false;
    public $showReplicateConfigModal = false;
    public $replicateSaleId = null;
    public $replicateCustomerId = null;
    public $replicateCustomerSearch = '';
    public $replicateCustomerResults = [];
    public $replicateSelectedCustomer = null;
    public $replicatePayments = [];
    public $replicateIsCredit = false;
    public $isProcessingReplicate = false;

    public $correctionConcepts = [
        '1' => 'Devolución parcial de bienes y/o no aceptación parcial del servicio',
        '2' => 'Anulación de factura electrónica',
        '3' => 'Rebaja o descuento parcial o total',
        '4' => 'Ajuste de precio',
        '5' => 'Otros',
    ];

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function viewSale($saleId)
    {
        $this->selectedSale = Sale::with([
            'customer.taxDocument',
            'user',
            'branch',
            'items.product',
            'payments.paymentMethod',
            'cashReconciliation.cashRegister',
            'reprints.user',
            'creditNotes.user',
            'refunds.user',
        ])->find($saleId);
        
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSale = null;
    }

    public function reprintReceipt($saleId)
    {
        $sale = Sale::find($saleId);
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }

        SaleReprint::create([
            'sale_id' => $saleId,
            'user_id' => auth()->id(),
            'type' => 'pos',
            'ip_address' => request()->ip(),
        ]);

        ActivityLogService::logCreate('sales', $sale, "Reimpresión de recibo POS: {$sale->invoice_number}");
        $this->dispatch('print-receipt', saleId: $saleId);
        $this->dispatch('notify', message: 'Abriendo recibo para impresión...', type: 'success');
    }

    public function viewElectronicPdf($saleId)
    {
        $sale = Sale::find($saleId);
        if (!$sale || !$sale->dian_public_url) {
            $this->dispatch('notify', message: 'PDF no disponible', type: 'error');
            return;
        }

        SaleReprint::create([
            'sale_id' => $saleId,
            'user_id' => auth()->id(),
            'type' => 'electronic_pdf',
            'ip_address' => request()->ip(),
        ]);

        ActivityLogService::logCreate('sales', $sale, "Visualización de factura electrónica: {$sale->dian_number}");
        $this->dispatch('open-url', url: $sale->dian_public_url);
    }

    public function viewReprints($saleId)
    {
        $sale = Sale::with(['reprints.user'])->find($saleId);
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }
        $this->selectedSaleReprints = $sale->reprints;
        $this->showReprintsModal = true;
    }

    public function closeReprintsModal()
    {
        $this->showReprintsModal = false;
        $this->selectedSaleReprints = [];
    }

    // ==================== CREDIT NOTE METHODS ====================

    public function openCreditNoteModal($saleId)
    {
        $sale = Sale::with(['items', 'creditNotes.items'])->find($saleId);
        
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }

        if (!$sale->is_electronic || !$sale->cufe) {
            $this->dispatch('notify', message: 'Solo se pueden crear notas crédito para facturas electrónicas validadas', type: 'error');
            return;
        }

        $creditedQuantities = [];
        foreach ($sale->creditNotes as $cn) {
            foreach ($cn->items as $item) {
                $creditedQuantities[$item->sale_item_id] = ($creditedQuantities[$item->sale_item_id] ?? 0) + $item->quantity;
            }
        }

        $this->creditNoteItems = [];
        foreach ($sale->items as $item) {
            $credited = $creditedQuantities[$item->id] ?? 0;
            $remaining = $item->quantity - $credited;
            
            if ($remaining > 0) {
                $this->creditNoteItems[] = [
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'unit_price' => (float) $item->unit_price,
                    'original_quantity' => $item->quantity,
                    'credited_quantity' => $credited,
                    'remaining_quantity' => $remaining,
                    'quantity' => $remaining,
                    'tax_rate' => (float) $item->tax_rate,
                    'selected' => true,
                ];
            }
        }

        if (empty($this->creditNoteItems)) {
            $this->dispatch('notify', message: 'Esta factura ya tiene notas crédito por el total', type: 'warning');
            return;
        }

        $this->selectedSale = $sale;
        $this->creditNoteType = 'total';
        $this->creditNoteReason = '';
        $this->creditNoteCorrectionCode = '2';
        $this->showCreditNoteModal = true;
    }

    public function updatedCreditNoteType($value)
    {
        if ($value === 'total') {
            foreach ($this->creditNoteItems as $index => $item) {
                $this->creditNoteItems[$index]['selected'] = true;
                $this->creditNoteItems[$index]['quantity'] = $item['remaining_quantity'];
            }
        }
    }

    public function closeCreditNoteModal()
    {
        $this->showCreditNoteModal = false;
        $this->creditNoteItems = [];
        $this->creditNoteReason = '';
        $this->replicateAfter = false;
    }

    public function getCreditNoteTotalProperty()
    {
        $total = 0;
        foreach ($this->creditNoteItems as $item) {
            if (!empty($item['selected']) && $item['quantity'] > 0) {
                $subtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                $tax = $subtotal * ((float) $item['tax_rate'] / 100);
                $total += $subtotal + $tax;
            }
        }
        return $total;
    }

    public function processCreditNote()
    {
        $this->validate([
            'creditNoteReason' => 'required|min:10',
            'creditNoteCorrectionCode' => 'required|in:1,2,3,4,5',
        ], [
            'creditNoteReason.required' => 'Debe indicar el motivo de la nota crédito',
            'creditNoteReason.min' => 'El motivo debe tener al menos 10 caracteres',
        ]);

        $hasSelectedItems = false;
        foreach ($this->creditNoteItems as $item) {
            if (!empty($item['selected']) && $item['quantity'] > 0) {
                $hasSelectedItems = true;
                break;
            }
        }

        if (!$hasSelectedItems) {
            $this->dispatch('notify', message: 'Debe seleccionar al menos un producto', type: 'error');
            return;
        }

        $this->isProcessingCreditNote = true;

        try {
            DB::beginTransaction();

            $sale = Sale::with('items')->find($this->selectedSale->id);
            
            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($this->creditNoteItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0) {
                    $itemSubtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $itemTax = $itemSubtotal * ((float) $item['tax_rate'] / 100);
                    $subtotal += $itemSubtotal;
                    $taxTotal += $itemTax;
                }
            }

            $creditNote = CreditNote::create([
                'sale_id' => $sale->id,
                'branch_id' => $sale->branch_id,
                'user_id' => auth()->id(),
                'number' => CreditNote::generateNumber($sale->branch_id),
                'type' => $this->creditNoteType,
                'correction_concept_code' => $this->creditNoteCorrectionCode,
                'reason' => $this->creditNoteReason,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'status' => 'pending',
            ]);

            foreach ($this->creditNoteItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0) {
                    $itemSubtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $itemTax = $itemSubtotal * ((float) $item['tax_rate'] / 100);
                    
                    CreditNoteItem::create([
                        'credit_note_id' => $creditNote->id,
                        'sale_item_id' => $item['sale_item_id'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'product_sku' => $item['product_sku'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'original_quantity' => $item['original_quantity'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $itemTax,
                        'subtotal' => $itemSubtotal,
                        'total' => $itemSubtotal + $itemTax,
                    ]);
                }
            }

            // Register cash movement for the credit note (expense)
            $this->registerCashMovement($sale->branch_id, $creditNote->total, 'expense', "Nota Crédito {$creditNote->number}");

            // Return inventory for credit note items
            foreach ($this->creditNoteItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0 && !empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        InventoryMovement::createMovement(
                            'refund',
                            $product,
                            'in',
                            (float) $item['quantity'],
                            (float) $item['unit_price'],
                            "Nota Crédito {$creditNote->number} - Venta {$sale->invoice_number}",
                            $creditNote,
                            $sale->branch_id
                        );

                        $product->increment('current_stock', (float) $item['quantity']);
                    }
                }
            }

            // Send to DIAN via Factus
            $factusService = new FactusService();
            
            if ($factusService->isEnabled()) {
                try {
                    $factusService->createCreditNote($creditNote);
                    $this->dispatch('notify', message: 'Nota crédito creada y validada por DIAN', type: 'success');
                } catch (\Exception $e) {
                    $this->dispatch('notify', message: 'Nota crédito creada pero falló validación DIAN', type: 'warning');
                }
            } else {
                $this->dispatch('notify', message: 'Nota crédito creada (facturación electrónica deshabilitada)', type: 'warning');
            }

            ActivityLogService::logCreate('sales', $creditNote, "Nota crédito {$creditNote->number} creada para factura {$sale->invoice_number}");

            // Adjust credit sale amounts if applicable
            if ($sale->payment_type === 'credit') {
                $this->adjustCreditSaleAfterReturn($sale, $creditNote->total);
            }

            DB::commit();

            // If replicate requested, open config modal instead of creating sale immediately
            if ($this->replicateAfter) {
                $this->replicateAfter = false;
                $this->closeCreditNoteModal();
                $this->closeDetailModal();
                $this->openReplicateConfigModal($sale);
                $this->dispatch('notify', message: 'Nota crédito creada. Configure la nueva venta.', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Nota crédito creada y validada por DIAN', type: 'success');
                $this->closeCreditNoteModal();
                $this->closeDetailModal();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al crear nota crédito: ' . $e->getMessage(), type: 'error');
        }

        $this->isProcessingCreditNote = false;
    }

    public function retryCreditNote($creditNoteId)
    {
        $creditNote = CreditNote::find($creditNoteId);
        
        if (!$creditNote) {
            $this->dispatch('notify', message: 'Nota crédito no encontrada', type: 'error');
            return;
        }

        try {
            $factusService = new FactusService();
            
            if (!$factusService->isEnabled()) {
                $this->dispatch('notify', message: 'Facturación electrónica no está habilitada', type: 'error');
                return;
            }

            $factusService->createCreditNote($creditNote);
            $this->dispatch('notify', message: 'Nota crédito validada por DIAN', type: 'success');
            
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error de validación DIAN', type: 'error');
        }

        // Refresh selected sale
        if ($this->selectedSale) {
            $this->selectedSale = Sale::with([
                'customer.taxDocument', 'user', 'branch', 'items.product',
                'payments.paymentMethod', 'cashReconciliation.cashRegister',
                'reprints.user', 'creditNotes.user', 'refunds.user',
            ])->find($this->selectedSale->id);
        }
    }

    // ==================== REFUND METHODS (POS) ====================

    public function openRefundModal($saleId)
    {
        $sale = Sale::with(['items', 'refunds.items'])->find($saleId);
        
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }

        if ($sale->is_electronic && $sale->cufe) {
            $this->dispatch('notify', message: 'Para facturas electrónicas use Nota Crédito', type: 'error');
            return;
        }

        $refundedQuantities = [];
        foreach ($sale->refunds as $refund) {
            foreach ($refund->items as $item) {
                $refundedQuantities[$item->sale_item_id] = ($refundedQuantities[$item->sale_item_id] ?? 0) + $item->quantity;
            }
        }

        $this->refundItems = [];
        foreach ($sale->items as $item) {
            $refunded = $refundedQuantities[$item->id] ?? 0;
            $remaining = $item->quantity - $refunded;
            
            if ($remaining > 0) {
                $this->refundItems[] = [
                    'sale_item_id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'unit_price' => (float) $item->unit_price,
                    'original_quantity' => $item->quantity,
                    'refunded_quantity' => $refunded,
                    'remaining_quantity' => $remaining,
                    'quantity' => $remaining,
                    'tax_rate' => (float) $item->tax_rate,
                    'selected' => true,
                ];
            }
        }

        if (empty($this->refundItems)) {
            $this->dispatch('notify', message: 'Esta venta ya tiene devoluciones por el total', type: 'warning');
            return;
        }

        $this->selectedSale = $sale;
        $this->refundType = 'total';
        $this->refundReason = '';
        $this->showRefundModal = true;
    }

    public function updatedRefundType($value)
    {
        if ($value === 'total') {
            foreach ($this->refundItems as $index => $item) {
                $this->refundItems[$index]['selected'] = true;
                $this->refundItems[$index]['quantity'] = $item['remaining_quantity'];
            }
        }
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
        $this->refundItems = [];
        $this->refundReason = '';
        $this->replicateAfter = false;
    }

    public function getRefundTotalProperty()
    {
        $total = 0;
        foreach ($this->refundItems as $item) {
            if (!empty($item['selected']) && $item['quantity'] > 0) {
                $subtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                $tax = $subtotal * ((float) $item['tax_rate'] / 100);
                $total += $subtotal + $tax;
            }
        }
        return $total;
    }

    public function processRefund()
    {
        $this->validate([
            'refundReason' => 'required|min:5',
        ], [
            'refundReason.required' => 'Debe indicar el motivo de la devolución',
            'refundReason.min' => 'El motivo debe tener al menos 5 caracteres',
        ]);

        $hasSelectedItems = false;
        foreach ($this->refundItems as $item) {
            if (!empty($item['selected']) && $item['quantity'] > 0) {
                $hasSelectedItems = true;
                break;
            }
        }

        if (!$hasSelectedItems) {
            $this->dispatch('notify', message: 'Debe seleccionar al menos un producto', type: 'error');
            return;
        }

        $this->isProcessingRefund = true;

        try {
            DB::beginTransaction();

            $sale = Sale::find($this->selectedSale->id);
            
            // Get current open cash reconciliation for user's branch
            $user = auth()->user();
            $cashReconciliation = $this->getOpenCashReconciliation($user);

            $subtotal = 0;
            $taxTotal = 0;
            
            foreach ($this->refundItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0) {
                    $itemSubtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $itemTax = $itemSubtotal * ((float) $item['tax_rate'] / 100);
                    $subtotal += $itemSubtotal;
                    $taxTotal += $itemTax;
                }
            }

            $refund = Refund::create([
                'sale_id' => $sale->id,
                'branch_id' => $sale->branch_id,
                'user_id' => auth()->id(),
                'cash_reconciliation_id' => $cashReconciliation?->id,
                'number' => Refund::generateNumber($sale->branch_id),
                'type' => $this->refundType,
                'reason' => $this->refundReason,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $subtotal + $taxTotal,
                'status' => 'completed',
            ]);

            foreach ($this->refundItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0) {
                    $itemSubtotal = (float) $item['unit_price'] * (float) $item['quantity'];
                    $itemTax = $itemSubtotal * ((float) $item['tax_rate'] / 100);
                    
                    RefundItem::create([
                        'refund_id' => $refund->id,
                        'sale_item_id' => $item['sale_item_id'],
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'product_sku' => $item['product_sku'],
                        'unit_price' => $item['unit_price'],
                        'quantity' => $item['quantity'],
                        'original_quantity' => $item['original_quantity'],
                        'tax_rate' => $item['tax_rate'],
                        'tax_amount' => $itemTax,
                        'subtotal' => $itemSubtotal,
                        'total' => $itemSubtotal + $itemTax,
                    ]);
                }
            }

            // Register cash movement for the refund (expense)
            $this->registerCashMovement($sale->branch_id, $refund->total, 'expense', "Devolución {$refund->number}");

            // Return inventory for refunded products
            foreach ($this->refundItems as $item) {
                if (!empty($item['selected']) && $item['quantity'] > 0 && !empty($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        InventoryMovement::createMovement(
                            'refund',
                            $product,
                            'in',
                            (float) $item['quantity'],
                            (float) $item['unit_price'],
                            "Devolución {$refund->number} - Venta {$sale->invoice_number}",
                            $refund,
                            $sale->branch_id
                        );

                        $product->increment('current_stock', (float) $item['quantity']);
                    }
                }
            }

            ActivityLogService::logCreate('sales', $refund, "Devolución {$refund->number} creada para venta {$sale->invoice_number}");

            // Adjust credit sale amounts if applicable
            if ($sale->payment_type === 'credit') {
                $this->adjustCreditSaleAfterReturn($sale, $refund->total);
            }

            DB::commit();

            $this->dispatch('print-refund', refundId: $refund->id);

            // If replicate requested, open config modal instead of creating sale immediately
            if ($this->replicateAfter) {
                $this->replicateAfter = false;
                $this->closeRefundModal();
                $this->closeDetailModal();
                $this->openReplicateConfigModal($sale);
                $this->dispatch('notify', message: 'Devolución registrada. Configure la nueva venta.', type: 'success');
            } else {
                $this->dispatch('notify', message: 'Devolución registrada correctamente', type: 'success');
                $this->closeRefundModal();
                $this->closeDetailModal();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al crear devolución: ' . $e->getMessage(), type: 'error');
        }

        $this->isProcessingRefund = false;
    }

    // ==================== REPLICATE CONFIG MODAL ====================

    public function openReplicateConfigModal(Sale $sale)
    {
        $sale->load(['customer', 'payments.paymentMethod']);

        $this->replicateSaleId = $sale->id;
        $this->replicateCustomerId = $sale->customer_id;
        $this->replicateSelectedCustomer = $sale->customer;
        $this->replicateCustomerSearch = '';
        $this->replicateCustomerResults = [];
        $this->replicateIsCredit = $sale->payment_type === 'credit';

        // Load original payments
        $this->replicatePayments = [];
        foreach ($sale->payments as $payment) {
            $this->replicatePayments[] = [
                'method_id' => $payment->payment_method_id,
                'amount' => (float) $payment->amount,
                'reference' => $payment->reference,
            ];
        }

        // Ensure at least one payment row
        if (empty($this->replicatePayments)) {
            $this->replicatePayments[] = ['method_id' => '', 'amount' => $this->replicateIsCredit ? 0 : (float) $sale->total, 'reference' => ''];
        }

        $this->showReplicateConfigModal = true;
    }

    public function updatedReplicateCustomerSearch()
    {
        if (strlen($this->replicateCustomerSearch) < 2) {
            $this->replicateCustomerResults = [];
            return;
        }

        $search = $this->replicateCustomerSearch;
        $sale = Sale::find($this->replicateSaleId);
        $branchId = $sale ? $sale->branch_id : null;

        $this->replicateCustomerResults = Customer::where('branch_id', $branchId)
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            })
            ->limit(5)
            ->get();
    }

    public function selectReplicateCustomer($customerId)
    {
        $customer = Customer::find($customerId);
        if ($customer) {
            $this->replicateCustomerId = $customer->id;
            $this->replicateSelectedCustomer = $customer;
            $this->replicateCustomerSearch = '';
            $this->replicateCustomerResults = [];
            // Reset credit if new customer doesn't have credit
            if (!$customer->has_credit) {
                $this->replicateIsCredit = false;
            }
        }
    }

    public function clearReplicateCustomer()
    {
        $this->replicateCustomerId = null;
        $this->replicateSelectedCustomer = null;
        $this->replicateIsCredit = false;
    }

    public function addReplicatePayment()
    {
        $this->replicatePayments[] = ['method_id' => '', 'amount' => 0, 'reference' => ''];
    }

    public function removeReplicatePayment($index)
    {
        unset($this->replicatePayments[$index]);
        $this->replicatePayments = array_values($this->replicatePayments);
    }

    public function closeReplicateConfigModal()
    {
        $this->showReplicateConfigModal = false;
        $this->replicateSaleId = null;
        $this->replicateCustomerId = null;
        $this->replicateSelectedCustomer = null;
        $this->replicateCustomerSearch = '';
        $this->replicateCustomerResults = [];
        $this->replicatePayments = [];
        $this->replicateIsCredit = false;
    }

    public function confirmReplicate()
    {
        $sale = Sale::with(['items', 'payments.paymentMethod'])->find($this->replicateSaleId);

        if (!$sale) {
            $this->dispatch('notify', message: 'Venta original no encontrada', type: 'error');
            return;
        }

        // Validate credit: customer must have credit enabled
        if ($this->replicateIsCredit) {
            $customer = Customer::find($this->replicateCustomerId);
            if (!$customer || !$customer->has_credit) {
                $this->dispatch('notify', message: 'El cliente seleccionado no tiene crédito habilitado', type: 'error');
                return;
            }
        }

        // Validate payments
        $validPayments = collect($this->replicatePayments)
            ->filter(fn($p) => (float) ($p['amount'] ?? 0) > 0 && !empty($p['method_id']));

        $totalPayments = $validPayments->sum(fn($p) => (float) $p['amount']);

        if (!$this->replicateIsCredit) {
            // Cash sale: payments must cover total
            if ($validPayments->isEmpty()) {
                $this->dispatch('notify', message: 'Debe agregar al menos un método de pago', type: 'error');
                return;
            }
            if ($totalPayments < (float) $sale->total) {
                $this->dispatch('notify', message: 'El total de pagos no cubre el total de la venta ($' . number_format($sale->total, 0, ',', '.') . ')', type: 'error');
                return;
            }
        }

        $this->isProcessingReplicate = true;

        try {
            DB::beginTransaction();

            $newSale = $this->replicateSale(
                $sale,
                $this->replicateCustomerId,
                $validPayments->isNotEmpty() ? $validPayments->values()->all() : null,
                $this->replicateIsCredit,
                $totalPayments
            );

            DB::commit();

            $this->dispatch('notify', message: "Nueva venta {$newSale->invoice_number} creada", type: 'success');
            $this->dispatch('print-receipt', saleId: $newSale->id);
            $this->closeReplicateConfigModal();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al crear venta: ' . $e->getMessage(), type: 'error');
        }

        $this->isProcessingReplicate = false;
    }

    // ==================== REPLICATE HELPER ====================

    /**
     * Replicate a sale creating a new one with the same items and payments.
     */
    protected function replicateSale(Sale $originalSale, ?int $customerId = null, ?array $payments = null, bool $isCredit = false, float $paidAmount = 0): Sale
    {
        $user = auth()->user();
        $cashReconciliation = $this->getOpenCashReconciliation($user);

        $total = (float) $originalSale->total;

        // Determine payment type and status
        if ($isCredit) {
            $paymentType = 'credit';
            $creditAmount = $total;
            $effectivePaid = $paidAmount;
            if ($effectivePaid >= $total) {
                $paymentStatus = 'paid';
            } elseif ($effectivePaid > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }
        } else {
            $paymentType = 'cash';
            $creditAmount = 0;
            $effectivePaid = $total;
            $paymentStatus = 'paid';
        }

        $newSale = Sale::create([
            'branch_id' => $originalSale->branch_id,
            'cash_reconciliation_id' => $cashReconciliation?->id,
            'customer_id' => $customerId ?? $originalSale->customer_id,
            'user_id' => auth()->id(),
            'invoice_number' => Sale::generateInvoiceNumber($originalSale->branch_id),
            'subtotal' => $originalSale->subtotal,
            'tax_total' => $originalSale->tax_total,
            'discount' => $originalSale->discount,
            'total' => $total,
            'status' => 'completed',
            'payment_type' => $paymentType,
            'payment_status' => $paymentStatus,
            'credit_amount' => $creditAmount,
            'paid_amount' => $effectivePaid,
            'payment_due_date' => $isCredit ? now()->addDays(30) : null,
            'notes' => "Replicada de {$originalSale->invoice_number}",
        ]);

        // Replicate items and update stock
        foreach ($originalSale->items as $item) {
            SaleItem::create([
                'sale_id' => $newSale->id,
                'product_id' => $item->product_id,
                'product_child_id' => $item->product_child_id,
                'service_id' => $item->service_id,
                'combo_id' => $item->combo_id,
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'tax_rate' => $item->tax_rate,
                'tax_amount' => $item->tax_amount,
                'subtotal' => $item->subtotal,
                'discount_type' => $item->discount_type,
                'discount_type_value' => $item->discount_type_value,
                'discount_amount' => $item->discount_amount,
                'discount_reason' => $item->discount_reason,
                'total' => $item->total,
            ]);

            // Decrement stock for products
            if ($item->product_id) {
                $product = Product::find($item->product_id);
                if ($product) {
                    InventoryMovement::createMovement(
                        'sale', $product, 'out', (float) $item->quantity,
                        (float) $item->unit_price,
                        "Venta #{$newSale->invoice_number} (replicada de {$originalSale->invoice_number})",
                        $newSale, $originalSale->branch_id
                    );
                    $product->decrement('current_stock', (float) $item->quantity);
                }
            }

            // Handle combo stock
            if ($item->combo_id) {
                $combo = \App\Models\Combo::with(['items.product'])->find($item->combo_id);
                if ($combo) {
                    foreach ($combo->items as $comboItem) {
                        if ($comboItem->product_id) {
                            $product = Product::find($comboItem->product_id);
                            if ($product) {
                                $totalQty = (float) $comboItem->quantity * (float) $item->quantity;
                                InventoryMovement::createMovement(
                                    'sale', $product, 'out', $totalQty,
                                    (float) $comboItem->unit_price,
                                    "Venta #{$newSale->invoice_number} (Combo: {$combo->name})",
                                    $newSale, $originalSale->branch_id
                                );
                                $product->decrement('current_stock', $totalQty);
                            }
                        }
                    }
                    $combo->incrementSales((int) $item->quantity);
                }
            }
        }

        // Create payments (use overrides if provided)
        if ($payments) {
            $saleTotal = (float) $originalSale->total;
            $paymentsSoFar = 0;

            foreach ($payments as $i => $payment) {
                $isLast = ($i === count($payments) - 1);
                if ($isLast) {
                    $paymentAmount = round($saleTotal - $paymentsSoFar, 2);
                } else {
                    $paymentAmount = min((float) $payment['amount'], round($saleTotal - $paymentsSoFar, 2));
                }

                if ($paymentAmount > 0) {
                    SalePayment::create([
                        'sale_id' => $newSale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount' => $paymentAmount,
                        'reference' => $payment['reference'] ?? null,
                    ]);
                    $paymentsSoFar += $paymentAmount;
                }
            }
        } else {
            foreach ($originalSale->payments as $payment) {
                SalePayment::create([
                    'sale_id' => $newSale->id,
                    'payment_method_id' => $payment->payment_method_id,
                    'amount' => $payment->amount,
                    'reference' => $payment->reference,
                ]);
            }
        }

        // Process electronic invoice if the original was electronic
        if ($originalSale->is_electronic) {
            $factusService = new FactusService();
            if ($factusService->isEnabled()) {
                try {
                    $factusService->createInvoice($newSale);
                } catch (\Exception $e) {
                    // Log but don't fail - can retry later
                }
            }
        }

        ActivityLogService::logCreate('sales', $newSale, "Venta {$newSale->invoice_number} replicada de {$originalSale->invoice_number}");

        return $newSale;
    }

    // ==================== HELPER METHODS ====================

    /**
     * Adjust credit sale amounts after a refund or credit note.
     * Reduces credit_amount by the returned total and updates payment_status.
     */
    protected function adjustCreditSaleAfterReturn(Sale $sale, float $returnTotal): void
    {
        $sale = $sale->fresh();

        // Calculate total returned (all refunds + credit notes)
        $totalRefunded = $sale->refunds()->where('status', 'completed')->sum('total');
        $totalCredited = $sale->creditNotes()->whereIn('status', ['pending', 'validated'])->sum('total');
        $totalReturned = (float) $totalRefunded + (float) $totalCredited;

        // New effective credit amount = original total - total returned
        $newCreditAmount = max(0, (float) $sale->total - $totalReturned);

        // Determine new payment status
        $paidAmount = (float) $sale->paid_amount;
        if ($newCreditAmount <= 0) {
            // Everything was returned, mark as paid
            $newPaymentStatus = 'paid';
        } elseif ($paidAmount >= $newCreditAmount) {
            // Paid amount covers remaining credit
            $newPaymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $newPaymentStatus = 'partial';
        } else {
            $newPaymentStatus = 'pending';
        }

        $sale->update([
            'credit_amount' => $newCreditAmount,
            'payment_status' => $newPaymentStatus,
        ]);
    }

    /**
     * Get open cash reconciliation for user.
     */
    protected function getOpenCashReconciliation($user): ?CashReconciliation
    {
        // First try to find by user's assigned cash register
        $cashRegister = CashRegister::where('user_id', $user->id)->first();
        
        if ($cashRegister) {
            return CashReconciliation::where('cash_register_id', $cashRegister->id)
                ->where('status', 'open')
                ->first();
        }

        // If no assigned register, find any open reconciliation opened by this user
        return CashReconciliation::where('opened_by', $user->id)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Register a cash movement for refund/credit note.
     */
    protected function registerCashMovement(int $branchId, float $amount, string $type, string $description): void
    {
        $user = auth()->user();
        $cashReconciliation = $this->getOpenCashReconciliation($user);

        if ($cashReconciliation) {
            CashMovement::create([
                'cash_reconciliation_id' => $cashReconciliation->id,
                'type' => $type,
                'amount' => $amount,
                'concept' => $description,
                'notes' => null,
                'user_id' => $user->id,
            ]);
        }
    }

    // ==================== HISTORY METHODS ====================

    public function viewCreditNotes($saleId)
    {
        $sale = Sale::with(['creditNotes.user', 'creditNotes.items'])->find($saleId);
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }
        $this->historyItems = $sale->creditNotes;
        $this->historyType = 'credit_notes';
        $this->showHistoryModal = true;
    }

    public function viewRefunds($saleId)
    {
        $sale = Sale::with(['refunds.user', 'refunds.items'])->find($saleId);
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }
        $this->historyItems = $sale->refunds;
        $this->historyType = 'refunds';
        $this->showHistoryModal = true;
    }

    public function closeHistoryModal()
    {
        $this->showHistoryModal = false;
        $this->historyItems = [];
        $this->historyType = '';
    }

    public function viewCreditNotePdf($creditNoteId)
    {
        $creditNote = CreditNote::find($creditNoteId);
        if (!$creditNote || !$creditNote->dian_public_url) {
            $this->dispatch('notify', message: 'PDF no disponible', type: 'error');
            return;
        }
        $this->dispatch('open-url', url: $creditNote->dian_public_url);
    }

    public function printRefund($refundId)
    {
        $this->dispatch('print-refund', refundId: $refundId);
    }

    // ==================== RETRY ELECTRONIC ====================

    public function retryElectronicInvoice($saleId)
    {
        $sale = Sale::find($saleId);
        if (!$sale) {
            $this->dispatch('notify', message: 'Venta no encontrada', type: 'error');
            return;
        }

        $this->isRetrying = true;

        try {
            $factusService = new FactusService();
            if (!$factusService->isEnabled()) {
                $this->dispatch('notify', message: 'Facturación electrónica no está habilitada', type: 'error');
                $this->isRetrying = false;
                return;
            }

            $factusService->createInvoice($sale);
            $this->dispatch('notify', message: 'Factura electrónica enviada correctamente', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error de validación DIAN', type: 'error');
        }

        if ($this->selectedSale && $this->selectedSale->id === $saleId) {
            $this->selectedSale = Sale::with([
                'customer.taxDocument', 'user', 'branch', 'items.product',
                'payments.paymentMethod', 'cashReconciliation.cashRegister',
                'reprints.user', 'creditNotes.user', 'refunds.user',
            ])->find($saleId);
        }

        $this->isRetrying = false;
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->roles->first()?->name === 'super_admin';
        
        $query = Sale::with(['customer', 'user', 'branch', 'payments.paymentMethod'])
            ->withCount(['reprints', 'creditNotes', 'refunds'])
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($sq) use ($search) {
                    $sq->where('invoice_number', 'like', "%{$search}%")
                       ->orWhere('dian_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function ($cq) use ($search) {
                           $cq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('business_name', 'like', "%{$search}%")
                              ->orWhere('document_number', 'like', "%{$search}%")
                              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
                       });
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterElectronic !== '', function ($q) {
                if ($this->filterElectronic === 'electronic') {
                    $q->where('is_electronic', true)->whereNotNull('cufe');
                } elseif ($this->filterElectronic === 'pos') {
                    $q->where('is_electronic', false);
                } elseif ($this->filterElectronic === 'failed') {
                    $q->where('is_electronic', true)->whereNull('cufe');
                }
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));
        
        if ($isSuperAdmin) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }
        
        $sales = $query->latest()->paginate(15);
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->get() : collect();
        
        $todaySales = Sale::whereDate('created_at', today())
            ->when(!$isSuperAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->sum('total');
        
        $todayCount = Sale::whereDate('created_at', today())
            ->when(!$isSuperAdmin, fn($q) => $q->where('branch_id', $user->branch_id))
            ->count();

        return view('livewire.sales', [
            'sales' => $sales,
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
            'todaySales' => $todaySales,
            'todayCount' => $todayCount,
            'paymentMethods' => PaymentMethod::where('is_active', true)->get(),
        ]);
    }
}
