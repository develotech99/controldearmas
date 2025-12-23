<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ProCuota extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_cuotas';
    protected $primaryKey = 'cuota_id';
    public $timestamps = true;

    protected $fillable = [
        'cuota_control_id',
        'cuota_numero',
        'cuota_monto',
        'cuota_fecha_vencimiento',
        'cuota_estado',
        'cuota_fecha_pago',
        'cuota_observaciones'
    ];

    protected $casts = [
        'cuota_fecha_vencimiento' => 'date',
        'cuota_fecha_pago' => 'date',
        'cuota_monto' => 'decimal:2'
    ];

    public function controlPago(): BelongsTo
    {
        return $this->belongsTo(ProPago::class, 'cuota_control_id', 'pago_id');
    }

    public function detallesPago(): HasMany
    {
        return $this->hasMany(ProDetallePago::class, 'det_pago_cuota_id', 'cuota_id');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('cuota_estado', 'PENDIENTE');
    }

    public function scopeVencidas($query)
    {
        return $query->where('cuota_estado', 'VENCIDA');
    }

    public function scopePagadas($query)
    {
        return $query->where('cuota_estado', 'PAGADA');
    }
}