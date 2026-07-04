<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Location;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Locations extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterBranch = '';
    public string $filterStatus = '';

    // Modal
    public bool $isModalOpen = false;
    public ?int $itemId = null;
    public string $name = '';
    public string $code = '';
    public string $description = '';
    public string $branch_id = '';
    public bool $is_active = true;

    // Delete modal
    public bool $isDeleteModalOpen = false;
    public ?int $deleteId = null;

    // Products modal
    public bool $isProductsModalOpen = false;
    public ?Location $viewingLocation = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterBranch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $this->branch_id = (string) $user->branch_id;
        }
    }

    public function create(): void
    {
        $this->resetForm();
        $user = auth()->user();
        if (!$user->isSuperAdmin()) {
            $this->branch_id = (string) $user->branch_id;
        }
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        $location = Location::findOrFail($id);
        $this->itemId = $location->id;
        $this->name = $location->name;
        $this->code = $location->code ?? '';
        $this->description = $location->description ?? '';
        $this->branch_id = (string) $location->branch_id;
        $this->is_active = $location->is_active;
        $this->isModalOpen = true;
    }

    public function store(): void
    {
        $this->validate([
            'name'      => 'required|min:2|max:100',
            'code'      => 'nullable|max:20',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'name.required'      => 'El nombre es obligatorio',
            'name.min'           => 'El nombre debe tener al menos 2 caracteres',
            'branch_id.required' => 'La sucursal es obligatoria',
        ]);

        if ($this->itemId) {
            $location = Location::findOrFail($this->itemId);
            $oldValues = $location->toArray();
            $location->update([
                'name'        => $this->name,
                'code'        => $this->code ?: null,
                'description' => $this->description ?: null,
                'branch_id'   => $this->branch_id,
                'is_active'   => $this->is_active,
            ]);
            ActivityLogService::logUpdate('locations', $location, $oldValues, "Ubicación '{$location->name}' actualizada");
            $this->dispatch('notify', message: 'Ubicación actualizada correctamente', type: 'success');
        } else {
            $location = Location::create([
                'name'        => $this->name,
                'code'        => $this->code ?: null,
                'description' => $this->description ?: null,
                'branch_id'   => $this->branch_id,
                'is_active'   => $this->is_active,
            ]);
            ActivityLogService::logCreate('locations', $location, "Ubicación '{$location->name}' creada");
            $this->dispatch('notify', message: 'Ubicación creada correctamente', type: 'success');
        }

        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function toggleStatus(int $id): void
    {
        $location = Location::findOrFail($id);
        $oldValues = $location->toArray();
        $location->update(['is_active' => !$location->is_active]);
        $label = $location->is_active ? 'activada' : 'desactivada';
        ActivityLogService::logUpdate('locations', $location, $oldValues, "Ubicación '{$location->name}' {$label}");
        $this->dispatch('notify', message: "Ubicación {$label}", type: 'success');
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        $location = Location::withCount('products')->findOrFail($this->deleteId);

        if ($location->products_count > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar una ubicación que tiene productos asignados', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('locations', $location, "Ubicación '{$location->name}' eliminada");
        $location->delete();

        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
        $this->dispatch('notify', message: 'Ubicación eliminada correctamente', type: 'success');
    }

    public function viewProducts(int $id): void
    {
        $this->viewingLocation = Location::with(['products' => fn($q) => $q->orderBy('name')])->findOrFail($id);
        $this->isProductsModalOpen = true;
    }

    private function resetForm(): void
    {
        $this->itemId      = null;
        $this->name        = '';
        $this->code        = '';
        $this->description = '';
        $this->is_active   = true;
        $this->resetValidation();

        $user = auth()->user();
        $this->branch_id = $user->isSuperAdmin() ? '' : (string) $user->branch_id;
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $query = Location::withCount('products')
            ->with('branch')
            ->orderBy('branch_id')
            ->orderBy('name');

        if (!$isSuperAdmin) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($this->filterBranch) {
            $query->where('branch_id', $this->filterBranch);
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === '1');
        }

        if (trim($this->search)) {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $locations = $query->paginate(15);

        $branches = $isSuperAdmin
            ? Branch::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('livewire.locations', [
            'locations'   => $locations,
            'branches'    => $branches,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
