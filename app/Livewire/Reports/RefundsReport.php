<?php

namespace App\Livewire\Reports;

use App\Models\Branch;
use App\Models\CreditNote;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class RefundsReport extends Component
{
    use WithPagination;

    // Filters
    public string $dateRange = 'month';
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?int $selectedBranchId = null;
    public string $filterType = 'all'; // all | refund | credit_note
    public string $search = '';

    // Summary
    public float $totalRefundsAmount = 0;       // refunds.status = completed
    public int $totalRefundsCount = 0;
    public float $totalCreditNotesAmount = 0;   // credit_notes.status in (pending, validated)
    public int $totalCreditNotesCount = 0;
    public float $grandTotal = 0;
    public int $grandCount = 0;
    public float $partialTotal = 0;             // returns where type = 'partial'
    public float $totalTotal = 0;               // returns where type = 'total'
    public int $partialCount = 0;
    public int $totalCount = 0;

    // Charts
    public array $byDay = [];
    public array $byReason = [];
    public array $byBranch = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }
    }

    public function updatedDateRange($value): void
    {
        switch ($value) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'yesterday':
                $this->startDate = now()->subDay()->format('Y-m-d');
                $this->endDate = now()->subDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->startOfWeek()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'last_month':
                $this->startDate = now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->endDate = now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->startDate = now()->startOfQuarter()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'year':
                $this->startDate = now()->startOfYear()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
        }
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedBranchId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->dateRange = 'month';
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->filterType = 'all';
        $this->search = '';
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    private function applyBranchFilter($query, string $table): void
    {
        if ($this->selectedBranchId) {
            $query->where("{$table}.branch_id", $this->selectedBranchId);
        } elseif (!auth()->user()->isSuperAdmin()) {
            $query->where("{$table}.branch_id", auth()->user()->branch_id);
        }
    }

    /**
     * Build the merged collection of refunds + credit notes for the listing.
     * Each row is a stdClass with normalized fields.
     */
    private function buildMergedCollection()
    {
        $rows = collect();

        // Refunds
        if ($this->filterType !== 'credit_note') {
            $refundsQuery = Refund::with(['sale.customer', 'user', 'branch'])
                ->where('refunds.status', 'completed')
                ->whereDate('refunds.created_at', '>=', $this->startDate)
                ->whereDate('refunds.created_at', '<=', $this->endDate);
            $this->applyBranchFilter($refundsQuery, 'refunds');

            if ($this->search) {
                $term = $this->search;
                $refundsQuery->where(function ($q) use ($term) {
                    $q->where('refunds.number', 'like', "%{$term}%")
                      ->orWhereHas('sale', function ($sq) use ($term) {
                          $sq->where('invoice_number', 'like', "%{$term}%");
                      })
                      ->orWhereHas('sale.customer', function ($cq) use ($term) {
                          $cq->where('first_name', 'like', "%{$term}%")
                             ->orWhere('last_name', 'like', "%{$term}%")
                             ->orWhere('business_name', 'like', "%{$term}%")
                             ->orWhere('document_number', 'like', "%{$term}%");
                      });
                });
            }

            foreach ($refundsQuery->get() as $r) {
                $rows->push((object) [
                    'kind' => 'refund',
                    'kind_label' => 'Devolución',
                    'id' => $r->id,
                    'number' => $r->number,
                    'date' => $r->created_at,
                    'sale_invoice' => $r->sale?->invoice_number,
                    'customer_name' => $r->sale?->customer?->full_name,
                    'customer_doc' => $r->sale?->customer?->document_number,
                    'type' => $r->type, // total | partial
                    'reason' => $r->reason,
                    'subtotal' => (float) $r->subtotal,
                    'tax_total' => (float) $r->tax_total,
                    'total' => (float) $r->total,
                    'user' => $r->user?->name,
                    'branch_name' => $r->branch?->name,
                    'branch_id' => $r->branch_id,
                    'status' => $r->status,
                    'receipt_url' => route('refund-receipt.show', $r->id),
                ]);
            }
        }

        // Credit Notes
        if ($this->filterType !== 'refund') {
            $cnQuery = CreditNote::with(['sale.customer', 'user', 'branch'])
                ->whereIn('credit_notes.status', ['pending', 'validated'])
                ->whereDate('credit_notes.created_at', '>=', $this->startDate)
                ->whereDate('credit_notes.created_at', '<=', $this->endDate);
            $this->applyBranchFilter($cnQuery, 'credit_notes');

            if ($this->search) {
                $term = $this->search;
                $cnQuery->where(function ($q) use ($term) {
                    $q->where('credit_notes.number', 'like', "%{$term}%")
                      ->orWhere('credit_notes.dian_number', 'like', "%{$term}%")
                      ->orWhereHas('sale', function ($sq) use ($term) {
                          $sq->where('invoice_number', 'like', "%{$term}%");
                      })
                      ->orWhereHas('sale.customer', function ($cq) use ($term) {
                          $cq->where('first_name', 'like', "%{$term}%")
                             ->orWhere('last_name', 'like', "%{$term}%")
                             ->orWhere('business_name', 'like', "%{$term}%")
                             ->orWhere('document_number', 'like', "%{$term}%");
                      });
                });
            }

            foreach ($cnQuery->get() as $c) {
                $rows->push((object) [
                    'kind' => 'credit_note',
                    'kind_label' => 'Nota Crédito',
                    'id' => $c->id,
                    'number' => $c->dian_number ?? $c->number,
                    'internal_number' => $c->number,
                    'date' => $c->created_at,
                    'sale_invoice' => $c->sale?->invoice_number,
                    'customer_name' => $c->sale?->customer?->full_name,
                    'customer_doc' => $c->sale?->customer?->document_number,
                    'type' => $c->type, // total | partial
                    'reason' => $c->reason,
                    'subtotal' => (float) $c->subtotal,
                    'tax_total' => (float) $c->tax_total,
                    'total' => (float) $c->total,
                    'user' => $c->user?->name,
                    'branch_name' => $c->branch?->name,
                    'branch_id' => $c->branch_id,
                    'status' => $c->status,
                    'receipt_url' => null,
                ]);
            }
        }

        return $rows->sortByDesc('date')->values();
    }

    private function calculateSummary($rows): void
    {
        $refunds = $rows->where('kind', 'refund');
        $creditNotes = $rows->where('kind', 'credit_note');

        $this->totalRefundsAmount = (float) $refunds->sum('total');
        $this->totalRefundsCount = $refunds->count();
        $this->totalCreditNotesAmount = (float) $creditNotes->sum('total');
        $this->totalCreditNotesCount = $creditNotes->count();
        $this->grandTotal = $this->totalRefundsAmount + $this->totalCreditNotesAmount;
        $this->grandCount = $this->totalRefundsCount + $this->totalCreditNotesCount;

        $partials = $rows->where('type', 'partial');
        $totals = $rows->where('type', 'total');
        $this->partialTotal = (float) $partials->sum('total');
        $this->totalTotal = (float) $totals->sum('total');
        $this->partialCount = $partials->count();
        $this->totalCount = $totals->count();
    }

    private function loadCharts($rows): void
    {
        // By day
        $byDayMap = [];
        foreach ($rows as $row) {
            $date = $row->date->format('Y-m-d');
            if (!isset($byDayMap[$date])) {
                $byDayMap[$date] = ['date' => $date, 'amount' => 0, 'count' => 0];
            }
            $byDayMap[$date]['amount'] += $row->total;
            $byDayMap[$date]['count']++;
        }
        ksort($byDayMap);
        $this->byDay = array_values(array_map(fn($d) => [
            'label' => \Carbon\Carbon::parse($d['date'])->format('d M'),
            'amount' => round($d['amount'], 2),
            'count' => $d['count'],
        ], $byDayMap));

        // By reason (top 8)
        $byReason = [];
        foreach ($rows as $row) {
            $reason = trim((string) ($row->reason ?? '')) ?: 'Sin razón';
            if (!isset($byReason[$reason])) {
                $byReason[$reason] = ['reason' => $reason, 'amount' => 0, 'count' => 0];
            }
            $byReason[$reason]['amount'] += $row->total;
            $byReason[$reason]['count']++;
        }
        usort($byReason, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $this->byReason = array_slice(array_map(fn($r) => [
            'reason' => $r['reason'],
            'amount' => round($r['amount'], 2),
            'count' => $r['count'],
        ], $byReason), 0, 8);

        // By branch
        $byBranchMap = [];
        foreach ($rows as $row) {
            $branch = $row->branch_name ?: '—';
            if (!isset($byBranchMap[$branch])) {
                $byBranchMap[$branch] = ['name' => $branch, 'amount' => 0, 'count' => 0];
            }
            $byBranchMap[$branch]['amount'] += $row->total;
            $byBranchMap[$branch]['count']++;
        }
        usort($byBranchMap, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $this->byBranch = array_values(array_map(fn($b) => [
            'name' => $b['name'],
            'amount' => round($b['amount'], 2),
            'count' => $b['count'],
        ], $byBranchMap));
    }

    public function exportExcel()
    {
        if (!auth()->user()->hasPermission('reports.export')) {
            $this->dispatch('notify', message: 'No tienes permiso para exportar', type: 'error');
            return;
        }

        return redirect()->route('reports.refunds.excel', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'branch_id' => $this->selectedBranchId,
            'filter_type' => $this->filterType,
            'search' => $this->search,
        ]);
    }

    public function render()
    {
        $allRows = $this->buildMergedCollection();
        $this->calculateSummary($allRows);
        $this->loadCharts($allRows);

        // Manual pagination on the merged collection
        $perPage = 20;
        $currentPage = $this->getPage() ?: 1;
        $offset = ($currentPage - 1) * $perPage;
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $allRows->slice($offset, $perPage)->values(),
            $allRows->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $branches = auth()->user()->isSuperAdmin()
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.reports.refunds-report', [
            'rows' => $paginated,
            'branches' => $branches,
            'isSuperAdmin' => auth()->user()->isSuperAdmin(),
        ]);
    }
}
