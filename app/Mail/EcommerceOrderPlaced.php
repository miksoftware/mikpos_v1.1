<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EcommerceOrderPlaced extends Mailable
{

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
