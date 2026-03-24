<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EcommerceNewOrderNotification extends Mailable
{

    public function __construct(public Sale $sale) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nuevo pedido e-commerce #{$this->sale->invoice_number}",
        );
    }

    public function content(): Content
    {
        $this->sale->load(['items', 'customer', 'ecommerceOrder.shippingDepartment', 'ecommerceOrder.shippingMunicipality', 'payments.paymentMethod']);

        return new Content(
            view: 'emails.ecommerce.new-order-notification',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'order' => $this->sale->ecommerceOrder,
            ],
        );
    }
}
