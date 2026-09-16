<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'branch_id',
        'user_id',
        'number',
        'type',
        'correction_concept_code',
        'reason',
        'subtotal',
        'tax_total',
        'total',
        'cufe',
        'qr_code',
        'dian_public_url',
        'dian_number',
        'dian_validated_at',
        'dian_response',
        'reference_code',
        'status',
        'refund_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'dian_validated_at' => 'datetime',
            'dian_response' => 'array',
        ];
    }

    // Relationships

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    // Methods

    public function isValidated(): bool
    {
        return $this->status === 'validated' && !empty($this->cufe);
    }

    /**
     * Generate next credit note number.
     */
    public static function generateNumber(int $branchId): string
    {
        $prefix = 'NC';
        $date = now()->format('Ymd');
        
        $lastNote = static::where('branch_id', $branchId)
            ->whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();
        
        $sequence = 1;
        if ($lastNote) {
            $parts = explode('-', $lastNote->number);
            if (count($parts) === 3) {
                $sequence = (int) $parts[2] + 1;
            }
        }
        
        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get correction concept name.
     */
    public function getCorrectionConceptNameAttribute(): string
    {
        $concepts = [
            '1' => 'Devolución parcial de los bienes y/o no aceptación parcial del servicio',
            '2' => 'Anulación de factura electrónica',
            '3' => 'Rebaja o descuento parcial o total',
            '4' => 'Ajuste de precio',
            '5' => 'Otros',
        ];
        
        return $concepts[$this->correction_concept_code] ?? 'Otros';
    }

    /**
     * Get extracted main DIAN error message.
     */
    public function getDianErrorMessageAttribute(): ?string
    {
        if (!$this->dian_response || !is_array($this->dian_response)) {
            return null;
        }

        return $this->dian_response['data']['message']
            ?? $this->dian_response['message']
            ?? $this->dian_response['error']
            ?? null;
    }

    /**
     * Get extracted DIAN detailed errors array.
     */
    public function getDianErrorsListAttribute(): array
    {
        if (!$this->dian_response || !is_array($this->dian_response)) {
            return [];
        }

        $rawErrors = $this->dian_response['data']['errors']
            ?? $this->dian_response['errors']
            ?? [];

        if (!is_array($rawErrors)) {
            return $rawErrors ? [(string) $rawErrors] : [];
        }

        $list = [];
        foreach ($rawErrors as $key => $val) {
            if (is_array($val)) {
                $val = implode(', ', $val);
            }
            if (is_string($key) && !is_numeric($key)) {
                $list[] = "{$key}: {$val}";
            } else {
                $list[] = (string) $val;
            }
        }

        if (empty($list) && isset($this->dian_response['error']) && is_string($this->dian_response['error'])) {
            $list[] = $this->dian_response['error'];
        }

        return $list;
    }
}
