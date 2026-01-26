<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SystemDocument;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InventoryTransfers extends Component
{
    use WithPagination;

    public $search = '';
    
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isViewModalOpen = false;
    public $documentToDelete = null;
    public $viewingDocument = null;

    // Form fields
    public $from_branch_id = '';
    public $to_branch_id = '';
    public $notes = '';
    public $items = [];

    public $productSearch = '';
    public $showProductDropdown = false;

    public function mount()
    {
        // Default from_branch to user's branch if available
        $this->from_branch_id = auth()->user()->branch_id ?? '';
    }

    public function render()
    {
        $transferDoc = SystemDocument::findByCode('transfer');
        
        $documents = InventoryMovement::query()
            ->when($transferDoc, fn($q) => $q->where('system_document_id', $transferDoc->id))
            ->when(!$transferDoc, fn($q) => $q->whereRaw('1 = 0'))
            ->where('movement_type', 'out') // Only show outgoing (origin) movements
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('document_number', 'like', "%{$this->search}%")
                    ->orWhere('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$this->search}%"));
            }))
            ->select('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->with(['user', 'branch'])
            ->groupBy('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->latest('created_at')
            ->paginate(15);

        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        $products = Product::query()
            ->active()
            ->when($this->productSearch, fn($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                ->orWhere('sku', 'like', "%{$this->productSearch}%"))
            ->limit(10)
            ->get();

        return view('livewire.inventory-transfers', [
            'documents' => $documents,
            'branches' => $branches,
            'products' => $products,
            'hasTransferDocument' => (bool) $transferDoc,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('inventory_transfers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function updatedProductSearch()
    {
        $this->showProductDropdown = strlen($this->productSearch) >= 2;
    }

    public function addProduct($productId)
    {
        $product = Product::find($productId);
        if (!$product) return;

        foreach ($this->items as $item) {
            if ($item['product_id'] == $productId) {
                $this->dispatch('notify', message: 'Este producto ya está en la lista', type: 'warning');
                return;
            }
        }

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'current_stock' => $product->current_stock ?? 0,
            'quantity' => 1,
        ];

        $this->productSearch = '';
        $this->showProductDropdown = false;
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateQuantity($index, $quantity)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = max(1, (int) $quantity);
        }
    }

    public function store()
    {
        if (!auth()->user()->hasPermission('inventory_transfers.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
        ], [
            'from_branch_id.required' => 'Selecciona la sucursal de origen',
            'to_branch_id.required' => 'Selecciona la sucursal de destino',
            'to_branch_id.different' => 'La sucursal destino debe ser diferente a la origen',
        ]);

        if (empty($this->items)) {
            $this->dispatch('notify', message: 'Debes agregar al menos un producto', type: 'error');
            return;
        }

        // Validate stock
        foreach ($this->items as $item) {
            if ($item['current_stock'] < $item['quantity']) {
                $this->dispatch('notify', message: "Stock insuficiente para {$item['name']}. Stock: {$item['current_stock']}", type: 'error');
                return;
            }
        }

        $transferDoc = SystemDocument::findByCode('transfer');
        if (!$transferDoc) {
            $this->dispatch('notify', message: 'No existe el documento de traslado. Créalo en Documentos Sistema.', type: 'error');
            return;
        }

        $fromBranch = Branch::find($this->from_branch_id);
        $toBranch = Branch::find($this->to_branch_id);

        DB::beginTransaction();
        try {
            $documentNumber = $transferDoc->generateNextNumber();

            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $stockBefore = $product->current_stock ?? 0;
                $stockAfter = $stockBefore - $item['quantity'];

                // Create OUT movement (from origin branch)
                InventoryMovement::create([
                    'system_document_id' => $transferDoc->id,
                    'document_number' => $documentNumber,
                    'product_id' => $product->id,
                    'branch_id' => $this->from_branch_id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'out',
                    'quantity' => $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $product->purchase_price,
                    'total_cost' => $product->purchase_price * $item['quantity'],
                    'reference_type' => Branch::class,
                    'reference_id' => $this->to_branch_id,
                    'notes' => "Traslado a {$toBranch->name}" . ($this->notes ? ": {$this->notes}" : ''),
                    'movement_date' => now(),
                ]);

                // Create IN movement (to destination branch)
                InventoryMovement::create([
                    'system_document_id' => $transferDoc->id,
                    'document_number' => $documentNumber,
                    'product_id' => $product->id,
                    'branch_id' => $this->to_branch_id,
                    'user_id' => auth()->id(),
                    'movement_type' => 'in',
                    'quantity' => $item['quantity'],
                    'stock_before' => $stockBefore, // Note: In a multi-branch stock system, this would be different
                    'stock_after' => $stockAfter + $item['quantity'], // This is simplified
                    'unit_cost' => $product->purchase_price,
                    'total_cost' => $product->purchase_price * $item['quantity'],
                    'reference_type' => Branch::class,
                    'reference_id' => $this->from_branch_id,
                    'notes' => "Recibido de {$fromBranch->name}" . ($this->notes ? ": {$this->notes}" : ''),
                    'movement_date' => now(),
                ]);

                // Update product stock (global stock decreases as it moves)
                $product->current_stock = $stockAfter;
                $product->save();
            }

            DB::commit();

            $itemCount = count($this->items);
            $totalQty = collect($this->items)->sum('quantity');
            
            $firstMovement = InventoryMovement::where('document_number', $documentNumber)->first();
            if ($firstMovement) {
                ActivityLogService::logCreate(
                    'inventory_transfers', 
                    $firstMovement, 
                    "Traslado {$documentNumber}: {$itemCount} productos ({$totalQty} unidades) de {$fromBranch->name} a {$toBranch->name}"
                );
            }

            $this->isModalOpen = false;
            $this->dispatch('notify', message: "Traslado {$documentNumber} registrado correctamente");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function viewDocument($documentNumber)
    {
        $this->viewingDocument = InventoryMovement::where('document_number', $documentNumber)
            ->with(['product', 'user', 'branch'])
            ->get();
        $this->isViewModalOpen = true;
    }

    public function confirmDelete($documentNumber)
    {
        if (!auth()->user()->hasPermission('inventory_transfers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->documentToDelete = $documentNumber;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('inventory_transfers.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $movements = InventoryMovement::where('document_number', $this->documentToDelete)->get();
        
        if ($movements->isEmpty()) {
            $this->dispatch('notify', message: 'Documento no encontrado', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        DB::beginTransaction();
        try {
            $firstMovement = $movements->first();
            
            // Only reverse the OUT movements (to restore stock)
            foreach ($movements->where('movement_type', 'out') as $movement) {
                $product = $movement->product;
                if ($product) {
                    $product->current_stock += $movement->quantity;
                    $product->save();
                }
            }

            // Delete all movements for this document
            InventoryMovement::where('document_number', $this->documentToDelete)->delete();

            DB::commit();

            if ($firstMovement) {
                ActivityLogService::logDelete(
                    'inventory_transfers', 
                    $firstMovement, 
                    "Traslado eliminado: {$this->documentToDelete}"
                );
            }

            $this->isDeleteModalOpen = false;
            $this->dispatch('notify', message: 'Traslado eliminado y stock revertido');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    private function resetForm()
    {
        $this->from_branch_id = auth()->user()->branch_id ?? '';
        $this->to_branch_id = '';
        $this->notes = '';
        $this->items = [];
        $this->productSearch = '';
        $this->showProductDropdown = false;
    }
}
