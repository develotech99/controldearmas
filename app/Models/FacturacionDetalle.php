<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacturacionDetalle extends Model
{
    protected $table = 'facturacion_detalle';
    protected $primaryKey = 'det_fac_id';

    protected $fillable = [
        'det_fac_factura_id',
        'det_fac_tipo',
        'det_fac_producto_codigo',
        'det_fac_producto_id',
        'det_fac_producto_desc',
        'det_fac_cantidad',
        'det_fac_unidad_medida',
        'det_fac_precio_unitario',
        'det_fac_descuento',
        'det_fac_monto_gravable',
        'det_fac_tipo_impuesto',
        'det_fac_impuesto',
        'det_fac_total',
    ];

    protected $casts = [
        'det_fac_cantidad' => 'decimal:2',
        'det_fac_precio_unitario' => 'decimal:2',
        'det_fac_descuento' => 'decimal:2',
        'det_fac_monto_gravable' => 'decimal:2',
        'det_fac_impuesto' => 'decimal:2',
        'det_fac_total' => 'decimal:2',
    ];

    public function factura()
    {
        return $this->belongsTo(Facturacion::class, 'det_fac_factura_id', 'fac_id');
    }
}
