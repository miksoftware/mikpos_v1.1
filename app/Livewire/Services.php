<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Service;
use App\Models\Category;
use App\Models\Tax;
use App\Models\Branch;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Services extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $categoryFilter = '';
    public $statusFilter = '';
    
    // Branch selection for super_admin
    public $selectedBranchId = null;

    // Form fields
    public $serviceId = null;
    public $name = '';
    public $description = '';
    public $category_id = '';
    public $tax_id = '';
    public $cost = 0;
    public $sale_price = 0;
    public $price_includes_tax = true;
    public $is_active = true;
    public $has_commission = false;
    public $commission_type = 'fixed';
    public $commission_value = 0;
    public $image = null;
    public $currentImage = null;

    // Modals
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $deleteId = null;

    protected function rules()
    {
        return [
            'name' => 'required|min:2|max:255',
            'description' => 'nullable|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'cost' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'price_includes_tax' => 'boolean',
            'is_active' => 'boolean',
            'has_commission' => 'boolean',
            'commission_type' => 'in:fixed,percentage',
            'commission_value' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|max:2048',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $service = Service::findOrFail($id);
        
        $this->serviceId = $service->id;
        $this->name = $service->name;
        $this->description = $service->description;
        $this->category_id = $service->category_id ?? '';
        $this->tax_id = $service->tax_id ?? '';
        $this->cost = $service->cost;
        $this->sale_price = $service->sale_price;
        $this->price_includes_tax = $service->price_includes_tax;
        $this->is_active = $service->is_active;
        $this->has_commission = $service->has_commission;
        $this->commission_type = $service->commission_type;
        $this->commission_value = $service->commission_value;
        $this->currentImage = $service->image;
        
        $this->isModalOpen = true;
    }

    public function store()
    {
        $this->validate();

        $user = auth()->user();
        
        // Determine branch_id
        $branchId = $user->branch_id;
        if (!$branchId && $user->isSuperAdmin()) {
            if (!$this->selectedBranchId) {
                $this->dispatch('notify', message: 'Debes seleccionar una sucursal', type: 'error');
                return;
            }
            $branchId = $this->selectedBranchId;
        }

        $data = [
            'branch_id' => $branchId,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'category_id' => $this->category_id ?: null,
            'tax_id' => $this->tax_id ?: null,
            'cost' => $this->cost,
            'sale_price' => $this->sale_price,
            'price_includes_tax' => $this->price_includes_tax,
            'is_active' => $this->is_active,
            'has_commission' => $this->has_commission,
            'commission_type' => $this->commission_type,
            'commission_value' => $this->has_commission ? $this->commission_value : 0,
        ];

        // Handle image upload
        if ($this->image) {
            $data['image'] = $this->image->store('services', 'public');
        }

        if ($this->serviceId) {
            // Update
            $service = Service::findOrFail($this->serviceId);
            $oldValues = $service->toArray();

            // Delete old image if new one uploaded
            if ($this->image && $service->image) {
                Storage::disk('public')->delete($service->image);
            }

            $service->update($data);

            ActivityLogService::logUpdate('services', $service, $oldValues, "Servicio '{$service->name}' actualizado");
            $this->dispatch('notify', message: 'Servicio actualizado correctamente', type: 'success');
        } else {
            // Create
            $service = new Service($data);
            $service->generateSku();
            $service->save();

            ActivityLogService::logCreate('services', $service, "Servicio '{$service->name}' creado");
            $this->dispatch('notify', message: 'Servicio creado correctamente', type: 'success');
        }

        $this->closeModal();
    }

    public function confirmDelete($id)
    {
        $this->deleteId = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        $service = Service::findOrFail($this->deleteId);
        $serviceName = $service->name;

        // Check for associated sales
        if (DB::table('sale_items')->where('service_id', $service->id)->exists()) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene ventas asociadas. Desactívelo en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        // Delete image if exists
        if ($service->image) {
            Storage::disk('public')->delete($service->image);
        }

        $service->delete();

        ActivityLogService::logDelete('services', $service, "Servicio '{$serviceName}' eliminado");
        $this->dispatch('notify', message: 'Servicio eliminado correctamente', type: 'success');

        $this->isDeleteModalOpen = false;
        $this->deleteId = null;
    }

    public function toggleStatus($id)
    {
        $service = Service::findOrFail($id);
        $oldValues = $service->toArray();
        
        $service->is_active = !$service->is_active;
        $service->save();

        $status = $service->is_active ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('services', $service, $oldValues, "Servicio '{$service->name}' {$status}");
        $this->dispatch('notify', message: "Servicio {$status}", type: 'success');
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->serviceId = null;
        $this->name = '';
        $this->description = '';
        $this->category_id = '';
        $this->tax_id = '';
        $this->cost = 0;
        $this->sale_price = 0;
        $this->price_includes_tax = true;
        $this->is_active = true;
        $this->has_commission = false;
        $this->commission_type = 'fixed';
        $this->commission_value = 0;
        $this->image = null;
        $this->currentImage = null;
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        
        // Determine branch for filtering
        $branchId = $user->branch_id;
        if ($isSuperAdmin && $this->selectedBranchId) {
            $branchId = $this->selectedBranchId;
        }

        $query = Service::with(['category', 'tax']);
        
        // Only filter by branch if we have one
        if ($branchId) {
            $query->forBranch($branchId);
        } elseif (!$isSuperAdmin) {
            // Non-super_admin without branch - show nothing
            $query->whereRaw('1 = 0');
        }
        // Super admin without selected branch sees all services

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->categoryFilter) {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->statusFilter !== '') {
            $query->where('is_active', $this->statusFilter === '1');
        }

        $services = $query->orderBy('name')->paginate(10);

        // Get branches for super_admin selector
        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();

        return view('livewire.services', [
            'services' => $services,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'taxes' => Tax::where('is_active', true)->orderBy('name')->get(),
            'branches' => $branches,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }
}
