<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'payment_method_id',
        'contact_type',
        'contact_id',
        'description',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function contact()
    {
        if ($this->contact_type === 'customer') {
            return $this->belongsTo(Customer::class, 'contact_id');
        }
        if ($this->contact_type === 'supplier') {
            return $this->belongsTo(Supplier::class, 'contact_id');
        }
        return $this->belongsTo(Supplier::class, 'contact_id')->whereRaw('1 = 0');
    }

    public function getContactNameAttribute(): ?string
    {
        if (!$this->contact_type || !$this->contact_id) {
            return null;
        }
        if ($this->contact_type === 'customer') {
            $customer = Customer::find($this->contact_id);
            return $customer?->full_name;
        }
        if ($this->contact_type === 'supplier') {
            $supplier = Supplier::find($this->contact_id);
            return $supplier?->name;
        }
        return null;
    }
}
