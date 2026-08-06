<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleQrSnapshot extends Model
{
    protected $fillable = [
        'sale_id',
        'qr_token',
        'sale_snapshot',
        'import_declarations',
    ];

    protected function casts(): array
    {
        return [
            'sale_snapshot'       => 'array',
            'import_declarations' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Build and persist a snapshot for the given sale.
     * If a snapshot already exists for this sale, it is returned as-is (permanent).
     */
    public static function createForSale(Sale $sale): self
    {
        $existing = self::where('sale_id', $sale->id)->first();
        if ($existing) {
            return $existing;
        }

        // Load all needed relations
        $sale->load([
            'branch.department',
            'branch.municipality',
            'customer.taxDocument',
            'customer.municipality',
            'customer.department',
            'user',
            'items.product',
            'payments.paymentMethod',
            'cashReconciliation.cashRegister',
        ]);

        // Build sale snapshot array
        $saleSnapshot = [
            'id'             => $sale->id,
            'invoice_number' => $sale->invoice_number,
            'dian_number'    => $sale->dian_number,
            'is_electronic'  => $sale->is_electronic,
            'cufe'           => $sale->cufe,
            'qr_code'        => $sale->qr_code,
            'source'         => $sale->source,
            'status'         => $sale->status,
            'payment_type'   => $sale->payment_type,
            'subtotal'       => $sale->subtotal,
            'tax_total'      => $sale->tax_total,
            'discount'       => $sale->discount,
            'total'          => $sale->total,
            'notes'          => $sale->notes,
            'global_discount_type'   => $sale->global_discount_type,
            'global_discount_value'  => $sale->global_discount_value,
            'global_discount_amount' => $sale->global_discount_amount,
            'global_discount_reason' => $sale->global_discount_reason,
            'created_at'     => $sale->created_at?->toISOString(),

            'branch' => [
                'name'    => $sale->branch?->name,
                'tax_id'  => $sale->branch?->tax_id,
                'address' => $sale->branch?->address,
                'phone'   => $sale->branch?->phone,
                'municipality' => $sale->branch?->municipality?->name,
                'department'   => $sale->branch?->department?->name,
                'receipt_header' => $sale->branch?->receipt_header,
            ],

            'customer' => $sale->customer ? [
                'full_name'        => $sale->customer->full_name,
                'document_number'  => $sale->customer->document_number,
                'customer_type'    => $sale->customer->customer_type,
                'tax_document_abbreviation' => $sale->customer->taxDocument?->abbreviation,
                'municipality'     => $sale->customer->municipality?->name,
                'department'       => $sale->customer->department?->name,
                'address'          => $sale->customer->address,
            ] : null,

            'user_name' => $sale->user?->name,

            'cash_register_name' => $sale->cashReconciliation?->cashRegister?->name,

            'items' => $sale->items->filter(fn($i) => !$i->is_unavailable)->map(fn($item) => [
                'product_name'        => $item->product_name,
                'product_sku'         => $item->product_sku,
                'quantity'            => $item->quantity,
                'unit_price'          => $item->unit_price,
                'tax_rate'            => $item->tax_rate,
                'tax_amount'          => $item->tax_amount,
                'subtotal'            => $item->subtotal,
                'discount_type'       => $item->discount_type,
                'discount_type_value' => $item->discount_type_value,
                'discount_amount'     => $item->discount_amount,
                'discount_reason'     => $item->discount_reason,
                'total'               => $item->total,
                'import_code'         => $item->product?->import_code,
            ])->values()->toArray(),

            'payments' => $sale->payments->map(fn($p) => [
                'payment_method_name' => $p->paymentMethod?->name,
                'amount'              => $p->amount,
            ])->values()->toArray(),
        ];

        // Build import declarations snapshot - group by import_code (same code = same file)
        $importDeclarations = [];
        $seenCodes = [];

        foreach ($sale->items->filter(fn($i) => !$i->is_unavailable) as $item) {
            $product = $item->product;
            if (!$product || !$product->import_code || !$product->import_declaration) {
                continue;
            }

            $code = $product->import_code;

            if (!isset($seenCodes[$code])) {
                $seenCodes[$code] = count($importDeclarations);
                $importDeclarations[] = [
                    'import_code'   => $code,
                    'product_names' => [],
                    'file_path'     => $product->import_declaration,
                ];
            }

            $idx = $seenCodes[$code];
            if (!in_array($item->product_name, $importDeclarations[$idx]['product_names'])) {
                $importDeclarations[$idx]['product_names'][] = $item->product_name;
            }
        }

        return self::create([
            'sale_id'             => $sale->id,
            'qr_token'            => bin2hex(random_bytes(32)),
            'sale_snapshot'       => $saleSnapshot,
            'import_declarations' => empty($importDeclarations) ? null : $importDeclarations,
        ]);
    }
}
