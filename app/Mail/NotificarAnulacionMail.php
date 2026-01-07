<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificarAnulacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $payload;
    public $logoBase64;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($payload)
    {
        $this->payload = $payload;
        
        // Embed logo
        $path = public_path('images/pro_armas.png');
        if (file_exists($path)) {
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $this->logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
            $this->logoBase64 = '';
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Factura Anulada - Venta #' . ($this->payload['venta_id'] ?? 'N/A'))
                    ->view('emails.NotificarAnulacion');
    }
}
