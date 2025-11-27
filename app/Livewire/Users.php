<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Branch;
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
            ->where('name', 'like', '%'.$this->search.'%')
            ->orWhere('email', 'like', '%'.$this->search.'%')
            ->with('branch')
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
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
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
            'role' => $this->role,
            'branch_id' => $this->branch_id,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(['id' => $this->userId], $data);

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $this->userId ? 'Usuario actualizado correctamente' : 'Usuario creado correctamente');
    }

    public function confirmDelete($id)
    {
        $this->userIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
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
