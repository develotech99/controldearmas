<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Mime\Email; // <-- importante

class NotificarpagoMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;
    protected $comprobante;

    public function __construct(array $payload, $comprobante = null)
    {
        $this->payload     = $payload;
        $this->comprobante = $comprobante;
    }

    public function build()
    {

        $mail = $this->subject('Pago enviado - Venta #' . $this->payload['venta_id'])
            ->view('emails.NotificarPago')
            ->with(array_merge($this->payload, [
                'logoCid' => 'cid:logo-proarmas',
            ]));

        if ($this->comprobante instanceof UploadedFile) {
            $mail->attach(
                $this->comprobante->getRealPath(),
                [
                    'as'   => 'comprobante_venta_' . $this->payload['venta_id'] . '.' . $this->comprobante->getClientOriginalExtension(),
                    'mime' => $this->comprobante->getClientMimeType(),
                ]
            );
        } elseif (is_string($this->comprobante)) {
             // Check if it's a relative path in storage/app/public or absolute
             $path = $this->comprobante;
             if (!file_exists($path) && file_exists(storage_path('app/public/' . $path))) {
                 $path = storage_path('app/public/' . $path);
             }
             
             if (file_exists($path)) {
                $mail->attach($path, [
                    'as'   => 'comprobante_venta_' . $this->payload['venta_id'] . '.jpg',
                    'mime' => 'image/jpeg',
                ]);
             }
        }

        $logoPath = public_path('images/pro_armas.png'); 

        if (is_file($logoPath)) {
            $this->withSymfonyMessage(function (Email $message) use ($logoPath) {
                $message->embedFromPath($logoPath, 'logo-proarmas');
            });
        }

        return $mail;
    }
}
