<?php

namespace App\Mail;

use App\Models\PrintFormatSetting;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EcommerceOrderStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public string $newStatus,
        public ?string $reason = null,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabels = [
            'completed' => 'aprobado',
            'rejected' => 'rechazado',
        ];
        $label = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return new Envelope(
            subject: "Tu pedido #{$this->sale->invoice_number} ha sido {$label}",
        );
    }

    public function content(): Content
    {
        $this->sale->load([
            'items',
            'customer.taxDocument',
            'customer.municipality',
            'customer.department',
            'ecommerceOrder.shippingDepartment',
            'ecommerceOrder.shippingMunicipality',
            'branch.department',
            'branch.municipality',
            'payments.paymentMethod',
            'user',
            'cashReconciliation.cashRegister',
        ]);

        return new Content(
            view: 'emails.ecommerce.order-status-changed',
            with: [
                'sale' => $this->sale,
                'customer' => $this->sale->customer,
                'order' => $this->sale->ecommerceOrder,
                'branch' => $this->sale->branch,
                'newStatus' => $this->newStatus,
                'reason' => $this->reason,
            ],
        );
    }

    public function attachments(): array
    {
        if ($this->newStatus !== 'completed') {
            return [];
        }

        try {
            $sale = $this->sale;
            $sale->load([
                'items',
                'customer.taxDocument',
                'customer.municipality',
                'customer.department',
                'ecommerceOrder.shippingDepartment',
                'ecommerceOrder.shippingMunicipality',
                'branch.department',
                'branch.municipality',
                'payments.paymentMethod',
                'user',
                'cashReconciliation.cashRegister',
            ]);

            $format = PrintFormatSetting::getFormat('pos');
            $view = $format === 'letter' ? 'receipts.pos-receipt-letter' : 'receipts.pos-receipt';

            $pdf = Pdf::loadView($view, compact('sale'));

            if ($format === 'letter') {
                $pdf->setPaper('letter');
            } else {
                $pdf->setPaper([0, 0, 226.77, 800], 'portrait');
            }

            $filename = "Factura-{$sale->invoice_number}.pdf";

            return [
                \Illuminate\Mail\Mailables\Attachment::fromData(
                    fn () => $pdf->output(),
                    $filename
                )->withMime('application/pdf'),
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generando PDF para email: ' . $e->getMessage());
            return [];
        }
    }
}
