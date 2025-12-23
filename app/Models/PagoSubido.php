<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;

class PagoSubido extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    // Tabla y PK personalizados
    protected $table = 'pro_pagos_subidos';
    protected $primaryKey = 'ps_id';
    public $timestamps = true;

    // Estados
    public const ESTADO_PENDIENTE  = 'PENDIENTE_VALIDACION';
    public const ESTADO_VALIDADO   = 'VALIDADO';
    public const ESTADO_RECHAZADO  = 'RECHAZADO';

    protected $fillable = [
        'ps_venta_id',
        'ps_cliente_user_id',
        'ps_estado',
        'ps_canal',
        'ps_fecha_comprobante',
        'ps_monto_comprobante',
        'ps_monto_total_cuotas_front',
        'ps_diferencia',
        'ps_banco_id',
        'ps_banco_nombre',
        'ps_referencia',
        'ps_concepto',
        'ps_cuotas_json',
        'ps_imagen_path',
        'ps_validado_por',
        'ps_fecha_validacion',
        'ps_observaciones',
        'ps_checksum',
    ];


    protected $casts = [
        'ps_fecha_comprobante'         => 'datetime',
        'ps_fecha_validacion'          => 'datetime',
        'ps_cuotas_json'               => 'array',  
        'ps_monto_comprobante'         => 'decimal:2',
        'ps_monto_total_cuotas_front'  => 'decimal:2',
        'ps_diferencia'                => 'decimal:2',
    ];


    public function venta()
    {

        return $this->belongsTo(Ventas::class, 'ps_venta_id', 'ven_id');
    }

    public function cliente()
    {
        // Nota: tu users usa PK 'user_id'
        return $this->belongsTo(User::class, 'ps_cliente_user_id', 'user_id');
    }

    public function validador()
    {
        return $this->belongsTo(\App\Models\User::class, 'ps_validado_por', 'user_id');
    }


    protected $appends = ['imagen_url'];

    public function getImagenUrlAttribute()
    {
        return $this->ps_imagen_path
            ? Storage::disk('public')->url($this->ps_imagen_path)
            : null;
    }


    public function scopePendientes($q) { return $q->where('ps_estado', self::ESTADO_PENDIENTE); }
    public function scopeValidados($q)  { return $q->where('ps_estado', self::ESTADO_VALIDADO); }
    public function scopeVenta($q, $ventaId) { return $q->where('ps_venta_id', $ventaId); }
    public function scopeRef($q, $ref) { return $q->where('ps_referencia', $ref); }


    public function marcarValidado(int $adminUserId, ?string $obs = null): void
    {
        $this->update([
            'ps_estado'          => self::ESTADO_VALIDADO,
            'ps_validado_por'    => $adminUserId,
            'ps_fecha_validacion'=> now(),
            'ps_observaciones'   => $obs,
        ]);
    }

    public function marcarRechazado(int $adminUserId, ?string $obs = null): void
    {
        $this->update([
            'ps_estado'          => self::ESTADO_RECHAZADO,
            'ps_validado_por'    => $adminUserId,
            'ps_fecha_validacion'=> now(),
            'ps_observaciones'   => $obs,
        ]);
    }
}
