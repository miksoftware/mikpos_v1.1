<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcommerceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer_id',
        'shipping_department_id',
        'shipping_municipality_id',
        'shipping_address',
        'shipping_phone',
        'customer_notes',
        'rejection_reason',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function shippingDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'shipping_department_id');
    }

    public function shippingMunicipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class, 'shipping_municipality_id');
    }
}
