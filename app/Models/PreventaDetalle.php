<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventaDetalle extends Model
{
    protected $table = 'pro_detalle_preventas';
    protected $primaryKey = 'det_prev_id';

    protected $fillable = [
        'prev_id',
        'producto_id',
        'det_cantidad',
        'det_precio_unitario',
        'det_subtotal'
    ];

    public function preventa()
    {
        return $this->belongsTo(Preventa::class, 'prev_id', 'prev_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'producto_id');
    }
}
