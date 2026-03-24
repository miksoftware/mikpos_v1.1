<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcommerceOrderPlaced extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pedido #{$this->sale->invoice_number} recibido",
        );
    }

    public function content(): Content
    {
        $this->sale->load(['items', 'customer', 'ecommerceOrder.shippingDepartment', 'ecommerceOrder.shippingMunicipality', 'payments.paymentMethod', 'branch']);

        return new Content(
            view: 'emails.ecommerce.order-placed',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'order' => $this->sale->ecommerceOrder,
                'branch' => $this->sale->branch,
            ],
        );
    }
}
