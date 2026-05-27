<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PromotionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Promotion $promotion,
        public Customer $customer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->promotion->subject,
        );
    }

    public function content(): Content
    {
        $this->promotion->loadMissing('branch');

        return new Content(
            view: 'emails.promotion',
            with: [
                'promotion' => $this->promotion,
                'customer'  => $this->customer,
                'branch'    => $this->promotion->branch,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
