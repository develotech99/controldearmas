<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentUploadedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $pagoData;
    public $filePath;

    /**
     * Create a new message instance.
     */
    public function __construct($pagoData, $filePath = null)
    {
        $this->pagoData = $pagoData;
        $this->filePath = $filePath;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo Pago Subido - Venta #' . $this->pagoData['venta_id'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_uploaded',
            with: ['logoCid' => 'cid:logo-proarmas'],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->filePath) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromStorageDisk('public', $this->filePath)
                ->as('comprobante.jpg')
                ->withMime('image/jpeg');
        }

        $logoPath = public_path('images/pro_armas.png');
        if (file_exists($logoPath)) {
            $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($logoPath)
                ->as('logo.png')
                ->withMime('image/png')
                ->withContentId('logo-proarmas');
        }

        return $attachments;
    }


}
