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
class InventoryAdjustments extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public ?string $filterBranch = null;
    
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isViewModalOpen = false;
    public $documentToDelete = null;
    public $viewingDocument = null;

    public $notes = '';
    public $items = []; // Each item has: product_id, name, sku, current_stock, quantity, type (in/out)
    public ?int $branch_id = null;

    public $productSearch = '';
    public $showProductDropdown = false;

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $adjustmentDoc = SystemDocument::findByCode('adjustment');
        
        $query = InventoryMovement::query()
            ->when($adjustmentDoc, fn($q) => $q->where('system_document_id', $adjustmentDoc->id))
            ->when(!$adjustmentDoc, fn($q) => $q->whereRaw('1 = 0'));

        // Apply branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $documents = $query
            ->when($this->search, fn($q) => $q->where(function($query) {
                $query->where('document_number', 'like', "%{$this->search}%")
                    ->orWhere('notes', 'like', "%{$this->search}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->filterType, fn($q) => $q->where('movement_type', $this->filterType))
            ->select('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('SUM(CASE WHEN movement_type = "in" THEN quantity ELSE 0 END) as total_in')
            ->selectRaw('SUM(CASE WHEN movement_type = "out" THEN quantity ELSE 0 END) as total_out')
            ->with(['user', 'branch'])
            ->groupBy('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->latest('created_at')
            ->paginate(15);

        $products = Product::query()
            ->active()
            ->when($this->productSearch, fn($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                ->orWhere('sku', 'like', "%{$this->productSearch}%"))
            ->limit(10)
            ->get();

        return view('livewire.inventory-adjustments', [
            'documents' => $documents,
            'products' => $products,
            'hasAdjustmentDocument' => (bool) $adjustmentDoc,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        
        // Set default branch for users with assigned branch
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        
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
            'type' => 'in', // Default to entrada
        ];

        $this->productSearch = '';
        $this->showProductDropdown = false;
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItemType($index, $type)
    {
        if (isset($this->items[$index]) && in_array($type, ['in', 'out'])) {
            $this->items[$index]['type'] = $type;
        }
    }

    public function updateQuantity($index, $quantity)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = max(1, (int) $quantity);
        }
    }

    public function store()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        // Validate branch selection for super_admin
        if ($this->needsBranchSelection && !$this->branch_id) {
            $this->dispatch('notify', message: 'Debe seleccionar una sucursal', type: 'error');
            return;
        }

        if (empty($this->items)) {
            $this->dispatch('notify', message: 'Debes agregar al menos un producto', type: 'error');
            return;
        }

        // Validate stock for outgoing items
        foreach ($this->items as $item) {
            if ($item['type'] === 'out' && $item['current_stock'] < $item['quantity']) {
                $this->dispatch('notify', message: "Stock insuficiente para {$item['name']}. Stock: {$item['current_stock']}", type: 'error');
                return;
            }
        }

        $adjustmentDoc = SystemDocument::findByCode('adjustment');
        if (!$adjustmentDoc) {
            $this->dispatch('notify', message: 'No existe el documento de ajuste. Créalo en Documentos Sistema.', type: 'error');
            return;
        }

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        DB::beginTransaction();
        try {
            $documentNumber = $adjustmentDoc->generateNextNumber();

            foreach ($this->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $stockBefore = $product->current_stock ?? 0;
                $stockAfter = $item['type'] === 'in' 
                    ? $stockBefore + $item['quantity'] 
                    : $stockBefore - $item['quantity'];

                InventoryMovement::create([
                    'system_document_id' => $adjustmentDoc->id,
                    'document_number' => $documentNumber,
                    'product_id' => $product->id,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'movement_type' => $item['type'],
                    'quantity' => $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $product->purchase_price,
                    'total_cost' => $product->purchase_price * $item['quantity'],
                    'notes' => $this->notes,
                    'movement_date' => now(),
                ]);

                $product->current_stock = $stockAfter;
                $product->save();
            }

            DB::commit();

            $totalIn = collect($this->items)->where('type', 'in')->sum('quantity');
            $totalOut = collect($this->items)->where('type', 'out')->sum('quantity');
            $itemCount = count($this->items);
            
            // Log using the first movement created
            $firstMovement = InventoryMovement::where('document_number', $documentNumber)->first();
            if ($firstMovement) {
                ActivityLogService::logCreate(
                    'inventory_adjustments', 
                    $firstMovement, 
                    "Ajuste {$documentNumber}: {$itemCount} productos (+{$totalIn}/-{$totalOut})"
                );
            }

            $this->isModalOpen = false;
            $this->dispatch('notify', message: "Ajuste {$documentNumber} registrado correctamente");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function viewDocument($documentNumber)
    {
        $this->viewingDocument = InventoryMovement::where('document_number', $documentNumber)
            ->with(['product', 'user'])
            ->get();
        $this->isViewModalOpen = true;
    }

    public function confirmDelete($documentNumber)
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->documentToDelete = $documentNumber;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.delete')) {
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
            // Save first movement for logging before deletion
            $firstMovement = $movements->first();
            
            foreach ($movements as $movement) {
                $product = $movement->product;
                if ($product) {
                    // Reverse: if was 'in', subtract; if was 'out', add back
                    $product->current_stock = $movement->movement_type === 'in'
                        ? $product->current_stock - $movement->quantity
                        : $product->current_stock + $movement->quantity;
                    $product->save();
                }
                $movement->delete();
            }

            DB::commit();

            // Log using the saved reference
            if ($firstMovement) {
                ActivityLogService::logDelete(
                    'inventory_adjustments', 
                    $firstMovement, 
                    "Ajuste eliminado: {$this->documentToDelete}"
                );
            }

            $this->isDeleteModalOpen = false;
            $this->dispatch('notify', message: 'Ajuste eliminado y stock revertido');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterBranch = null;
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->notes = '';
        $this->items = [];
        $this->branch_id = null;
        $this->productSearch = '';
        $this->showProductDropdown = false;
    }
}
