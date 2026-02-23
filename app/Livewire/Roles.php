<?php

namespace App\Livewire;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Roles extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $roleIdToDelete = null;

    // Form properties
    public $roleId;
    public $name;
    public $display_name;
    public $description;
    public $is_active = true;
    public $is_system = false;
    public $selectedPermissions = [];

    public function render()
    {
        $roles = Role::query()
            ->when(trim($this->search), fn($q) => $q->where('display_name', 'like', "%" . trim($this->search) . "%"))
            ->withCount('permissions', 'users')
            ->latest()
            ->paginate(10);

        $modules = Module::with('permissions')->orderBy('order')->get();

        return view('livewire.roles', [
            'roles' => $roles,
            'modules' => $modules,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('roles.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear roles', type: 'error');
            return;
        }

        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('roles.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar roles', type: 'error');
            return;
        }

        $this->resetValidation();
        $role = Role::with('permissions')->findOrFail($id);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->display_name = $role->display_name;
        $this->description = $role->description;
        $this->is_active = $role->is_active;
        $this->is_system = $role->is_system;
        $this->selectedPermissions = $role->permissions->pluck('id')->toArray();

        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->roleId;
        $permission = $isNew ? 'roles.create' : 'roles.edit';

        if (!auth()->user()->hasPermission($permission)) {
            $this->dispatch('notify', message: 'No tienes permiso para realizar esta acción', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|alpha_dash|unique:roles,name,' . $this->roleId,
            'display_name' => 'required|min:3',
        ]);

        $oldValues = null;

        if (!$isNew) {
            $role = Role::find($this->roleId);
            $oldValues = $role->toArray();
        }

        $role = Role::updateOrCreate(
            ['id' => $this->roleId],
            [
                'name' => strtolower($this->name),
                'display_name' => $this->display_name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]
        );

        $role->permissions()->sync($this->selectedPermissions);

        // Log activity
        if ($isNew) {
            ActivityLogService::logCreate('roles', $role, "Rol '{$role->display_name}' creado");
        } else {
            ActivityLogService::logUpdate('roles', $role, $oldValues, "Rol '{$role->display_name}' actualizado");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Rol creado correctamente' : 'Rol actualizado correctamente');
    }

    public function confirmDelete($id)
    {
        if (!auth()->user()->hasPermission('roles.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar roles', type: 'error');
            return;
        }

        $role = Role::find($id);
        if ($role->is_system) {
            $this->dispatch('notify', message: 'No se pueden eliminar roles del sistema', type: 'error');
            return;
        }
        $this->roleIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('roles.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar roles', type: 'error');
            return;
        }

        $role = Role::find($this->roleIdToDelete);

        if ($role->is_system) {
            $this->dispatch('notify', message: 'No se pueden eliminar roles del sistema', type: 'error');
            return;
        }

        ActivityLogService::logDelete('roles', $role, "Rol '{$role->display_name}' eliminado");
        $role->delete();

        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Rol eliminado correctamente');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('roles.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para modificar roles', type: 'error');
            return;
        }

        $role = Role::find($id);

        if ($role->is_system && $role->name === 'super_admin') {
            $this->dispatch('notify', message: 'No se puede desactivar el rol de Super Admin', type: 'error');
            return;
        }

        $oldValues = $role->toArray();
        $role->is_active = !$role->is_active;
        $role->save();

        $status = $role->is_active ? 'activado' : 'desactivado';
        ActivityLogService::logUpdate('roles', $role, $oldValues, "Rol '{$role->display_name}' {$status}");

        $this->dispatch('notify', message: "Rol {$status} correctamente");
    }

    public function togglePermission($permissionId)
    {
        if (in_array($permissionId, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionId]);
        } else {
            $this->selectedPermissions[] = $permissionId;
        }
    }

    public function selectAllModule($moduleId)
    {
        $permissions = Permission::where('module_id', $moduleId)->pluck('id')->toArray();
        $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $permissions));
    }

    public function deselectAllModule($moduleId)
    {
        $permissions = Permission::where('module_id', $moduleId)->pluck('id')->toArray();
        $this->selectedPermissions = array_diff($this->selectedPermissions, $permissions);
    }

    private function resetForm()
    {
        $this->roleId = null;
        $this->name = '';
        $this->display_name = '';
        $this->description = '';
        $this->is_active = true;
        $this->is_system = false;
        $this->selectedPermissions = [];
    }
}
