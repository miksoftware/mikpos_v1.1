<?php

namespace App\Livewire;

use App\Models\ProductionOrder;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProductionOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';

    public bool $isViewModalOpen = false;
    public ?ProductionOrder $selectedOrder = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = ProductionOrder::with(['items.product', 'user', 'branch'])
            ->withSum('items as total_cost', 'total_cost')
            ->withSum('items as quantity_to_produce', 'quantity_to_produce')
            ->when($this->search, function ($query) {
                $query->whereHas('items.product', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->when(!auth()->user()->isSuperAdmin(), function ($query) {
                $query->where('branch_id', auth()->user()->branch_id);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);

        return view('livewire.production-orders', [
            'orders' => $orders,
        ]);
    }

    public function sortByColumn(string $column)
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function viewDetails(int $id)
    {
        if (!auth()->user()->hasPermission('production.view')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->selectedOrder = ProductionOrder::with([
            'items.product', 'items.location', 'user', 'branch', 'details.product'
        ])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    public function closeViewModal()
    {
        $this->isViewModalOpen = false;
        $this->selectedOrder = null;
    }

    public function cancelOrder(int $id)
    {
        if (!auth()->user()->hasPermission('production.cancel')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $order = ProductionOrder::find($id);
        if (!$order) {
            $this->dispatch('notify', message: 'Orden no encontrada', type: 'error');
            return;
        }

        if ($order->status !== 'completed') {
            $this->dispatch('notify', message: 'Solo se pueden cancelar órdenes completadas', type: 'error');
            return;
        }

        try {
            if ($order->cancel()) {
                ActivityLogService::logUpdate(
                    'production_orders',
                    $order,
                    ['status' => 'completed'],
                    "Orden de Producción #{$order->id} cancelada"
                );

                if ($this->selectedOrder && $this->selectedOrder->id === $order->id) {
                    $this->closeViewModal();
                }

                $this->dispatch('notify', message: 'Orden de producción cancelada. El inventario fue revertido.');
            } else {
                $this->dispatch('notify', message: 'No se pudo cancelar la orden', type: 'error');
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error al cancelar: ' . $e->getMessage(), type: 'error');
        }
    }
}
