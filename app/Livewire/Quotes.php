<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Quote;
use App\Services\ActivityLogService;
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

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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

        // Redirect to POS with the quote ID as query param
        $this->redirect(route('pos', ['from_quote' => $quote->id]), navigate: false);
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

        $oldValues = $quote->toArray();
        $quote->update(['status' => 'cancelled']);

        ActivityLogService::logUpdate(
            'quotes',
            $quote,
            $oldValues,
            "Cotización {$quote->quote_number} cancelada"
        );

        $this->dispatch('notify', message: 'Cotización cancelada', type: 'success');
        $this->closeCancelModal();
    }

    public function printQuote($quoteId): void
    {
        $this->dispatch('print-quote', quoteId: $quoteId);
    }

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
