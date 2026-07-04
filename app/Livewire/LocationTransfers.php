<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Location;
use App\Models\LocationTransfer;
use App\Models\LocationTransferItem;
use App\Models\Product;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class LocationTransfers extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterBranch = '';
    public string $filterDateFrom = '';
    public string $filterDateTo = '';

    // Create modal
    public bool $isModalOpen = false;
    public string $branch_id = '';
    public string $from_location_id = '';
    public string $to_location_id = '';
    public string $notes = '';
    public array $items = [];

    // Product search
    public string $productSearch = '';
    public bool $showProductDropdown = false;

    // View modal
    public bool $isViewModalOpen = false;
    public ?LocationTransfer $viewingTransfer = null;

    // Delete modal
    public bool $isDeleteModalOpen = false;
    public ?int $deleteId = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branch_id = (string) ($user->isSuperAdmin() ? '' : $user->branch_id);
        $this->filterDateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->filterDateTo = now()->format('Y-m-d');
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterBranch(): void { $this->resetPage(); }
    public function updatingFilterDateFrom(): void { $this->resetPage(); }
    public function updatingFilterDateTo(): void { $this->resetPage(); }

    // ─── Create modal ─────────────────────────────────────────────────────────

    public function create(): void
    {
        $this->resetForm();
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $this->branch_id = (string) $user->branch_id;
        }
        $this->isModalOpen = true;
    }

    public function updatedBranchId(): void
    {
        $this->from_location_id = '';
        $this->to_location_id = '';
        $this->items = [];
        $this->productSearch = '';
        $this->showProductDropdown = false;
    }

    public function updatedFromLocationId(): void
    {
        // Reload items stock quantities for the new origin location
        foreach ($this->items as $i => $item) {
            $qty = $this->getLocationProductQty((int) $this->from_location_id, $item['product_id']);
            $this->items[$i]['location_stock'] = $qty;
        }
    }

    public function updatedProductSearch(): void
    {
        $this->showProductDropdown = strlen(trim($this->productSearch)) >= 2;
    }

    public function addProduct(int $productId): void
    {
        foreach ($this->items as $item) {
            if ($item['product_id'] === $productId) {
                $this->dispatch('notify', message: 'Ese producto ya está en la lista', type: 'warning');
                $this->productSearch = '';
                $this->showProductDropdown = false;
                return;
            }
        }

        $product = Product::find($productId);
        if (!$product) return;

        $locationStock = $this->from_location_id
            ? $this->getLocationProductQty((int) $this->from_location_id, $productId)
            : 0;

        $this->items[] = [
            'product_id'     => $product->id,
            'name'           => $product->name,
            'sku'            => $product->sku,
            'location_stock' => $locationStock,
            'quantity'       => 1,
        ];

        $this->productSearch = '';
        $this->showProductDropdown = false;
    }

    public function removeItem(int $index): void
    {
        array_splice($this->items, $index, 1);
        $this->items = array_values($this->items);
    }

    public function updateQuantity(int $index, $quantity): void
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = max(0.001, (float) $quantity);
        }
    }

    public function store(): void
    {
        $this->validate([
            'branch_id'        => 'required|exists:branches,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id'   => 'required|exists:locations,id|different:from_location_id',
        ], [
            'from_location_id.required'  => 'Selecciona la ubicación de origen',
            'to_location_id.required'    => 'Selecciona la ubicación de destino',
            'to_location_id.different'   => 'La ubicación destino debe ser diferente a la origen',
        ]);

        if (empty($this->items)) {
            $this->dispatch('notify', message: 'Agrega al menos un producto', type: 'error');
            return;
        }

        // Validate stock per item
        foreach ($this->items as $item) {
            $available = $this->getLocationProductQty((int) $this->from_location_id, $item['product_id']);
            if ((float) $item['quantity'] > $available) {
                $this->dispatch('notify', message: "Stock insuficiente para \"{$item['name']}\" en la ubicación origen (disponible: {$available})", type: 'error');
                return;
            }
        }

        DB::beginTransaction();
        try {
            $transfer = LocationTransfer::create([
                'transfer_number'  => LocationTransfer::generateTransferNumber(),
                'branch_id'        => $this->branch_id,
                'from_location_id' => $this->from_location_id,
                'to_location_id'   => $this->to_location_id,
                'user_id'          => auth()->id(),
                'notes'            => $this->notes ?: null,
            ]);

            foreach ($this->items as $item) {
                LocationTransferItem::create([
                    'location_transfer_id' => $transfer->id,
                    'product_id'           => $item['product_id'],
                    'quantity'             => $item['quantity'],
                ]);

                // Subtract from origin
                $this->adjustLocationProduct(
                    (int) $this->from_location_id,
                    $item['product_id'],
                    -(float) $item['quantity']
                );

                // Add to destination
                $this->adjustLocationProduct(
                    (int) $this->to_location_id,
                    $item['product_id'],
                    (float) $item['quantity']
                );
            }

            DB::commit();

            ActivityLogService::logCreate(
                'location_transfers',
                $transfer,
                "Traslado de ubicación {$transfer->transfer_number}: " . count($this->items) . ' producto(s)'
            );

            $this->isModalOpen = false;
            $this->resetForm();
            $this->dispatch('notify', message: "Traslado {$transfer->transfer_number} registrado correctamente", type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ─── View modal ───────────────────────────────────────────────────────────

    public function viewTransfer(int $id): void
    {
        $this->viewingTransfer = LocationTransfer::with([
            'fromLocation',
            'toLocation',
            'user',
            'items.product',
        ])->findOrFail($id);
        $this->isViewModalOpen = true;
    }

    // ─── Delete modal ─────────────────────────────────────────────────────────

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        $transfer = LocationTransfer::with('items')->findOrFail($this->deleteId);

        DB::beginTransaction();
        try {
            // Revert movements
            foreach ($transfer->items as $item) {
                // Restore to origin
                $this->adjustLocationProduct(
                    $transfer->from_location_id,
                    $item->product_id,
                    (float) $item->quantity
                );
                // Remove from destination
                $this->adjustLocationProduct(
                    $transfer->to_location_id,
                    $item->product_id,
                    -(float) $item->quantity
                );
            }

            ActivityLogService::logDelete(
                'location_transfers',
                $transfer,
                "Traslado de ubicación {$transfer->transfer_number} eliminado y revertido"
            );

            $transfer->delete();

            DB::commit();

            $this->isDeleteModalOpen = false;
            $this->deleteId = null;
            $this->dispatch('notify', message: 'Traslado eliminado y stock revertido', type: 'success');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Get stock quantity for a product at a specific location.
     */
    private function getLocationProductQty(int $locationId, int $productId): float
    {
        $row = DB::table('location_products')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->first();

        return $row ? (float) $row->quantity : 0.0;
    }

    /**
     * Add (positive) or subtract (negative) quantity from a location-product pivot.
     * Creates the row if it doesn't exist.
     */
    private function adjustLocationProduct(int $locationId, int $productId, float $delta): void
    {
        $existing = DB::table('location_products')
            ->where('location_id', $locationId)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $newQty = max(0, (float) $existing->quantity + $delta);
            DB::table('location_products')
                ->where('id', $existing->id)
                ->update(['quantity' => $newQty, 'updated_at' => now()]);
        } else {
            DB::table('location_products')->insert([
                'location_id' => $locationId,
                'product_id'  => $productId,
                'quantity'    => max(0, $delta),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    private function resetForm(): void
    {
        $user = auth()->user();
        $this->branch_id        = $user->isSuperAdmin() ? '' : (string) $user->branch_id;
        $this->from_location_id = '';
        $this->to_location_id   = '';
        $this->notes            = '';
        $this->items            = [];
        $this->productSearch    = '';
        $this->showProductDropdown = false;
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $query = LocationTransfer::with(['fromLocation', 'toLocation', 'user'])
            ->withCount('items')
            ->orderByDesc('created_at');

        if (!$isSuperAdmin) {
            $query->where('location_transfers.branch_id', $user->branch_id);
        } elseif ($this->filterBranch) {
            $query->where('location_transfers.branch_id', $this->filterBranch);
        }

        if ($this->filterDateFrom) {
            $query->whereDate('location_transfers.created_at', '>=', $this->filterDateFrom);
        }
        if ($this->filterDateTo) {
            $query->whereDate('location_transfers.created_at', '<=', $this->filterDateTo);
        }
        if (trim($this->search)) {
            $term = trim($this->search);
            $query->where(function ($q) use ($term) {
                $q->where('location_transfers.transfer_number', 'like', "%{$term}%")
                  ->orWhere('location_transfers.notes', 'like', "%{$term}%")
                  ->orWhereHas('fromLocation', fn($l) => $l->where('name', 'like', "%{$term}%"))
                  ->orWhereHas('toLocation', fn($l) => $l->where('name', 'like', "%{$term}%"));
            });
        }

        $transfers = $query->paginate(15);

        $branches = $isSuperAdmin
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        // Locations for the create form (filtered by selected branch)
        $formBranchId = $this->branch_id ?: ($isSuperAdmin ? null : $user->branch_id);
        $formLocations = $formBranchId
            ? Location::active()->where('branch_id', $formBranchId)->orderBy('name')->get()
            : collect();

        // Products for search
        $searchProducts = strlen(trim($this->productSearch)) >= 2
            ? Product::active()
                ->when($formBranchId, fn($q) => $q->where('branch_id', $formBranchId))
                ->where(function ($q) {
                    $search = trim($this->productSearch);
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                })
                ->limit(10)
                ->get()
            : collect();

        return view('livewire.location-transfers', [
            'transfers'    => $transfers,
            'branches'     => $branches,
            'formLocations' => $formLocations,
            'searchProducts' => $searchProducts,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
