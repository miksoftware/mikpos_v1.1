<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'slug',
        'name',
        'logo',
        'tax_id',
        'department_id',
        'municipality_id',
        'province',
        'city',
        'address',
        'phone',
        'email',
        'ticket_prefix',
        'invoice_prefix',
        'receipt_prefix',
        'credit_note_prefix',
        'activity_number',
        'authorization_date',
        'receipt_header',
        'show_in_pos',
        'ecommerce_enabled',
        'show_stock_in_shop',
        'quotes_reserve_inventory',
        'print_qr',
        'enable_costo_fe',
        'tax_exempt_preserves_price',
        'is_active',
    ];

    protected static function booted()
    {
        static::saving(function ($branch) {
            if (empty($branch->slug) && !empty($branch->name)) {
                $base = \Illuminate\Support\Str::slug($branch->name);
                $slug = $base ?: ('sucursal-' . ($branch->id ?? rand(100, 999)));
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $branch->id ?? 0)->exists()) {
                    $slug = "{$base}-{$count}";
                    $count++;
                }
                $branch->slug = $slug;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'authorization_date' => 'date',
            'show_in_pos' => 'boolean',
            'ecommerce_enabled' => 'boolean',
            'show_stock_in_shop' => 'boolean',
            'quotes_reserve_inventory' => 'boolean',
            'print_qr' => 'boolean',
            'enable_costo_fe' => 'boolean',
            'tax_exempt_preserves_price' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getShopUrlAttribute(): string
    {
        $slug = $this->slug ?: \Illuminate\Support\Str::slug($this->name ?: 'sucursal-' . $this->id);
        return url("/{$slug}/shop");
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->where('is_active', true);
    }

    /**
     * Get the active ecommerce branch.
     * Resolves dynamically from:
     * 1. Application container binding
     * 2. Route slug parameter ({branch_slug})
     * 3. Customer session ('ecommerce_branch_id')
     * 4. Database fallback: first active branch with ecommerce_enabled = true.
     * 
     * No dependency on .env!
     */
    public static function getEcommerceBranch(?string $slug = null): ?self
    {
        if (app()->bound('ecommerce_branch')) {
            $bound = app('ecommerce_branch');
            if ($bound instanceof self && $bound->is_active && $bound->ecommerce_enabled) {
                return $bound;
            }
        }

        $targetSlug = $slug ?: request()->route('branch_slug');

        if ($targetSlug) {
            $branch = self::where(function ($q) use ($targetSlug) {
                $q->where('slug', $targetSlug)->orWhere('code', $targetSlug);
            })
            ->where('is_active', true)
            ->where('ecommerce_enabled', true)
            ->first();

            if ($branch) {
                return $branch;
            }
        }

        $sessionBranchId = session('ecommerce_branch_id');
        if ($sessionBranchId) {
            $branch = self::where('id', $sessionBranchId)
                ->where('is_active', true)
                ->where('ecommerce_enabled', true)
                ->first();

            if ($branch) {
                return $branch;
            }
        }

        // Fallback: First active branch with ecommerce_enabled in DB
        return self::where('is_active', true)
            ->where('ecommerce_enabled', true)
            ->first();
    }

    /**
     * Get the ID of the active ecommerce branch.
     */
    public static function getEcommerceBranchId(): ?int
    {
        return self::getEcommerceBranch()?->id;
    }
}
