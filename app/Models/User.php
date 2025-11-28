<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
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

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withPivot('branch_id')
            ->withTimestamps();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Get user's role (global or for specific branch)
    public function getRole(?int $branchId = null): ?Role
    {
        $query = $this->roles();
        
        if ($branchId) {
            $query->wherePivot('branch_id', $branchId);
        } else {
            $query->wherePivotNull('branch_id');
        }
        
        return $query->first();
    }

    // Check if user has a specific permission
    public function hasPermission(string $permission, ?int $branchId = null): bool
    {
        // Check ALL user roles (both global and branch-specific)
        foreach ($this->roles as $role) {
            // Super admin has all permissions
            if ($role->name === 'super_admin') {
                return true;
            }

            // Check if this role has the permission
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    // Check if user is super admin
    public function isSuperAdmin(): bool
    {
        return $this->roles()->where('name', 'super_admin')->exists();
    }

    // Check if user can access a specific branch
    public function canAccessBranch(int $branchId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->branch_id === $branchId;
    }

    // Get all permissions for user
    public function getAllPermissions(): array
    {
        $permissions = [];
        
        foreach ($this->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[$permission->name] = true;
            }
        }
        
        return array_keys($permissions);
    }
}
