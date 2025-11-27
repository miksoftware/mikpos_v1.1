<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_BRANCH_ADMIN = 'branch_admin';
    const ROLE_SUPERVISOR = 'supervisor';
    const ROLE_CASHIER = 'cashier';

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'role',
        'phone',
        'is_active',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // Role check helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isBranchAdmin(): bool
    {
        return $this->role === self::ROLE_BRANCH_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_BRANCH_ADMIN]);
    }

    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->branch_id === $branchId;
    }
}
