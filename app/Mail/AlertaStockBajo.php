<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaStockBajo extends Mailable
{
    use Queueable, SerializesModels;

    public $alertasAgrupadas;
    public $nombreAdmin;

    public function __construct($alertasAgrupadas, $nombreAdmin)
    {
        $this->alertasAgrupadas = $alertasAgrupadas;
        $this->nombreAdmin = $nombreAdmin;
    }

    public function build()
    {
        $totalAlertas = 
            $this->alertasAgrupadas['critica']->count() +
            $this->alertasAgrupadas['alta']->count() +
            $this->alertasAgrupadas['media']->count();

        return $this->subject("⚠️ Reporte Diario de Stock - Control de Armas - {$totalAlertas} alertas pendientes")
                    ->view('emails.alerta-stock-bajo');
    }
}