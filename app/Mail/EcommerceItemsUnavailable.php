<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcommerceItemsUnavailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public array $unavailableItemNames,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Actualización de tu pedido #{$this->sale->invoice_number} - Productos no disponibles",
        );
    }

    public function content(): Content
    {
        $this->sale->load(['items', 'customer', 'ecommerceOrder', 'branch']);

        return new Content(
            view: 'emails.ecommerce.items-unavailable',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'branch' => $this->sale->branch,
                'unavailableItemNames' => $this->unavailableItemNames,
            ],
        );
    }
}
