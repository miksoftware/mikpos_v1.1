<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Users extends Component
{
    use WithPagination;

    public $search = '';
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $userIdToDelete = null;

    // Form properties
    public $userId;
    
    #[Rule('required|min:3')]
    public $name;

    #[Rule('required|email|unique:users,email')]
    public $email;

    #[Rule('required|min:6')]
    public $password;

    #[Rule('required|in:super_admin,branch_admin,supervisor,cashier')]
    public $role = 'cashier';

    #[Rule('nullable|exists:branches,id')]
    public $branch_id;

    public $phone;
    public $is_active = true;

    public function render()
    {
        $users = User::query()
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->with(['branch', 'roles'])
            ->latest()
            ->paginate(10);

        return view('livewire.users', [
            'users' => $users,
            'branches' => Branch::all(),
        ]);
    }

    public function create()
    {
        $this->resetValidation();
        $this->reset(['userId', 'name', 'email', 'password', 'role', 'branch_id', 'phone', 'is_active']);
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $this->resetValidation();
        $user = User::with('roles')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? 'cashier';
        $this->branch_id = $user->branch_id;
        $this->phone = $user->phone;
        $this->is_active = $user->is_active;
        $this->password = ''; // Don't populate password
        $this->isModalOpen = true;
    }

    public function store()
    {
        // Dynamic validation rules
        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'role' => 'required|in:super_admin,branch_admin,supervisor,cashier',
            'branch_id' => 'nullable|exists:branches,id',
        ];

        if (!$this->userId) {
            $rules['password'] = 'required|min:6';
        } else {
            $rules['password'] = 'nullable|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'branch_id' => $this->branch_id,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->userId], $data);

        // Sync role
        $role = Role::where('name', $this->role)->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $this->userId ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente');
    }

    public function confirmDelete($id)
    {
        // Prevent deleting the first user (super admin created during installation)
        if ((int) $id === 1) {
            $this->dispatch('notify', message: 'El usuario administrador principal no puede ser eliminado', type: 'error');
            return;
        }

        $this->userIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        // Double-check protection
        if ((int) $this->userIdToDelete === 1) {
            $this->dispatch('notify', message: 'El usuario administrador principal no puede ser eliminado', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        User::find($this->userIdToDelete)->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Usuario eliminado correctamente');
    }

    public function toggleStatus($id)
    {
        $user = User::find($id);
        $user->is_active = !$user->is_active;
        $user->save();
    }
}
