<?php

namespace App\Mail;

use App\Models\Sale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class EcommercePaymentMethodChanged extends Mailable
{
    public function __construct(
        public Sale $sale,
        public string $oldPaymentMethod,
        public string $newPaymentMethod,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Actualización de tu pedido #{$this->sale->invoice_number} - Cambio de método de pago",
        );
    }

    public function content(): Content
    {
        $this->sale->load(['items', 'customer', 'ecommerceOrder', 'branch', 'payments.paymentMethod']);

        return new Content(
            view: 'emails.ecommerce.payment-method-changed',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'branch' => $this->sale->branch,
                'oldPaymentMethod' => $this->oldPaymentMethod,
                'newPaymentMethod' => $this->newPaymentMethod,
                'reason' => $this->reason,
            ],
        );
    }
}
