<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preventa extends Model
{
    protected $table = 'pro_preventas';
    protected $primaryKey = 'prev_id';

    protected $fillable = [
        'prev_cliente_id',
        'prev_producto_id',
        'prev_cantidad',
        'prev_monto_pagado',
        'prev_fecha',
        'prev_observaciones',
        'prev_estado'
    ];

    protected $casts = [
        'prev_fecha' => 'datetime',
        'prev_monto_pagado' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'prev_cliente_id', 'cliente_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'prev_producto_id', 'producto_id');
    }
}
