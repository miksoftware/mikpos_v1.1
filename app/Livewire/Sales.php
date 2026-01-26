<?php

namespace App\Livewire;

use App\Models\Sale;
use App\Models\Branch;
use App\Services\FactusService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

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
    
    // Detail modal
    public $showDetailModal = false;
    public $selectedSale = null;
    
    // Retry electronic invoice
    public $isRetrying = false;

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
        ])->find($saleId);
        
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedSale = null;
    }

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

            $response = $factusService->createInvoice($sale);
            
            $this->dispatch('notify', message: 'Factura electrónica enviada correctamente', type: 'success');
            
        } catch (\Exception $e) {
            // Error is already saved in dian_response by FactusService
            $this->dispatch('notify', message: 'Error de validación DIAN', type: 'error');
        }

        // Always refresh selected sale to show updated error info
        if ($this->selectedSale && $this->selectedSale->id === $saleId) {
            $this->selectedSale = Sale::with([
                'customer.taxDocument',
                'user',
                'branch',
                'items.product',
                'payments.paymentMethod',
                'cashReconciliation.cashRegister',
            ])->find($saleId);
        }

        $this->isRetrying = false;
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->roles->first()?->name === 'super_admin';
        
        $query = Sale::with(['customer', 'user', 'branch', 'payments.paymentMethod'])
            ->when($this->search, function ($q) {
                $q->where(function ($sq) {
                    $sq->where('invoice_number', 'like', "%{$this->search}%")
                       ->orWhere('dian_number', 'like', "%{$this->search}%")
                       ->orWhereHas('customer', function ($cq) {
                           $cq->where('first_name', 'like', "%{$this->search}%")
                              ->orWhere('last_name', 'like', "%{$this->search}%")
                              ->orWhere('business_name', 'like', "%{$this->search}%")
                              ->orWhere('document_number', 'like', "%{$this->search}%");
                       });
                });
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterElectronic !== '', function ($q) {
                if ($this->filterElectronic === 'electronic') {
                    // All electronic invoices (validated)
                    $q->where('is_electronic', true)->whereNotNull('cufe');
                } elseif ($this->filterElectronic === 'pos') {
                    // Only POS (not electronic)
                    $q->where('is_electronic', false);
                } elseif ($this->filterElectronic === 'failed') {
                    // Electronic but failed (no cufe)
                    $q->where('is_electronic', true)->whereNull('cufe');
                }
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo));
        
        // Branch filtering
        if ($isSuperAdmin) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }
        
        $sales = $query->latest()->paginate(15);
        
        // Get branches for filter (super admin only)
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->get() : collect();
        
        // Stats
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
        ]);
    }
}
