<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteSaldoHistorial extends Model
{
    protected $table = 'pro_clientes_saldo_historial';
    protected $primaryKey = 'historial_id';

    protected $fillable = [
        'historial_cliente_id',
        'historial_tipo',
        'historial_monto',
        'historial_referencia',
        'historial_fecha'
    ];

    protected $casts = [
        'historial_fecha' => 'datetime',
        'historial_monto' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'historial_cliente_id', 'cliente_id');
    }
}
