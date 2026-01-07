<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewSaleNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $ventaData;

    /**
     * Create a new message instance.
     */
    public function __construct($ventaData)
    {
        $this->ventaData = $ventaData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nueva Venta Realizada - #' . $this->ventaData['ven_id'],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.new_sale',
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
