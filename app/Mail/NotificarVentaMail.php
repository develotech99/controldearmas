<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

class NotificarVentaMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build()
    {
        $mail = $this->subject('Nueva Venta Realizada - Folio #' . $this->payload['venta_id'])
            ->view('emails.NotificarVenta')
            ->with(array_merge($this->payload, [
                'logoCid' => 'cid:logo-proarmas',
            ]));

        $logoPath = public_path('images/pro_armas.png');

        if (is_file($logoPath)) {
            $this->withSymfonyMessage(function (Email $message) use ($logoPath) {
                $message->embedFromPath($logoPath, 'logo-proarmas');
            });
        }

        return $mail;
    }
}
