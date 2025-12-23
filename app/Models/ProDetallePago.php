<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;


class ProDetallePago extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_detalle_pagos';
    protected $primaryKey = 'det_pago_id';
    public $timestamps = true;

    protected $fillable = [
        'det_pago_pago_id',
        'det_pago_cuota_id',
        'det_pago_fecha',
        'det_pago_monto',
        'det_pago_metodo_pago',
        'det_pago_banco_id',
        'det_pago_numero_autorizacion',
        'det_pago_imagen_boucher',
        'det_pago_tipo_pago',
        'det_pago_estado',
        'det_pago_observaciones',
        'det_pago_usuario_registro'
    ];

    protected $casts = [
        'det_pago_fecha' => 'date',
        'det_pago_monto' => 'decimal:2'
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(ProPago::class, 'det_pago_pago_id', 'pago_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(ProMetodoPago::class, 'det_pago_metodo_pago', 'metpago_id');
    }

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(ProCuota::class, 'det_pago_cuota_id', 'cuota_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'det_pago_usuario_registro', 'user_id');
    }
}