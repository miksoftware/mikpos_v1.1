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
        'payment_details',
        'contact_type',
        'contact_id',
        'description',
        'amount',
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
            'payment_details' => 'array',
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

    public function getExpenseNumberAttribute(): string
    {
        return 'EXP-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getContactDetailsAttribute(): ?array
    {
        if (!$this->contact_type || !$this->contact_id) {
            return null;
        }

        if ($this->contact_type === 'customer') {
            $customer = Customer::with(['taxDocument', 'municipality', 'department'])->find($this->contact_id);
            if (!$customer) return null;
            return [
                'type' => 'Cliente',
                'name' => $customer->customer_type === 'juridico' ? $customer->business_name : $customer->full_name,
                'document_type' => $customer->taxDocument?->abbreviation ?? 'Doc',
                'document_number' => $customer->document_number,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->municipality?->name,
                'department' => $customer->department?->name,
                'email' => $customer->email,
            ];
        }

        if ($this->contact_type === 'supplier') {
            $supplier = Supplier::with(['taxDocument', 'municipality', 'department'])->find($this->contact_id);
            if (!$supplier) return null;
            return [
                'type' => 'Proveedor',
                'name' => $supplier->name,
                'document_type' => $supplier->taxDocument?->abbreviation ?? 'Doc',
                'document_number' => $supplier->document_number,
                'phone' => $supplier->phone,
                'address' => $supplier->address,
                'city' => $supplier->municipality?->name,
                'department' => $supplier->department?->name,
                'email' => $supplier->email,
            ];
        }

        return null;
    }
}

