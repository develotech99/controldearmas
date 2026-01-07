<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ProPago extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_pagos';
    protected $primaryKey = 'pago_id';
    public $timestamps = true;

    protected $fillable = [
        'pago_venta_id',
        'pago_monto_total',
        'pago_monto_pagado',
        'pago_monto_pendiente',
        'pago_tipo_pago',
        'pago_cantidad_cuotas',
        'pago_abono_inicial',
        'pago_estado',
        'pago_fecha_inicio',
        'pago_fecha_completado',
        'pago_observaciones'
    ];

    protected $casts = [
        'pago_fecha_inicio' => 'date',
        'pago_fecha_completado' => 'date',
        'pago_monto_total' => 'decimal:2',
        'pago_monto_pagado' => 'decimal:2',
        'pago_monto_pendiente' => 'decimal:2',
        'pago_abono_inicial' => 'decimal:2'
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(ProVenta::class, 'pago_venta_id', 'ven_id');
    }

    public function detallesPago(): HasMany
    {
        return $this->hasMany(ProDetallePago::class, 'det_pago_pago_id', 'pago_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(ProCuota::class, 'cuota_control_id', 'pago_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('pago_estado', 'PENDIENTE');
    }

    public function scopeCompletados($query)
    {
        return $query->where('pago_estado', 'COMPLETADO');
    }
}