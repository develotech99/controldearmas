<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preventa extends Model
{
    protected $table = 'pro_preventas';
    protected $primaryKey = 'prev_id';

    protected $fillable = [
        'prev_cliente_id',
        'prev_fecha',
        'prev_total',
        'prev_monto_pagado',
        'prev_observaciones',
        'prev_estado'
    ];

    protected $casts = [
        'prev_fecha' => 'datetime',
        'prev_total' => 'decimal:2',
        'prev_monto_pagado' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'prev_cliente_id', 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(PreventaDetalle::class, 'prev_id', 'prev_id');
    }
}
