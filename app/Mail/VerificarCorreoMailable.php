<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificarCorreoMailable extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public string $link;

    public function __construct(User $user, string $link)
    {
        $this->user = $user;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject('Registro en la pagina - Control de Armas')
            ->view('emails.verificacion', [
                'user' => $this->user,
                'link' => $this->link,
            ]);
    }
}
