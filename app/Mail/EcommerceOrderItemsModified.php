<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EcommerceOrderItemsModified extends Mailable
{
    /**
     * @param  array  $changes  Array of ['product_name', 'old_quantity', 'new_quantity', 'reason']
     */
    public function __construct(
        public Sale $sale,
        public array $changes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Actualización de tu pedido #{$this->sale->invoice_number} - Cambio de cantidades",
        );
    }

    public function content(): Content
    {
        $this->sale->load(['items', 'customer', 'ecommerceOrder', 'branch']);

        return new Content(
            view: 'emails.ecommerce.order-items-modified',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'branch' => $this->sale->branch,
                'changes' => $this->changes,
            ],
        );
    }
}
