<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ProPorcentajeVendedor extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_porcentaje_vendedor';
    protected $primaryKey = 'porc_vend_id';
    public $timestamps = false;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'porc_vend_user_id',
        'porc_vend_ven_id',
        'porc_vend_porcentaje',
        'porc_vend_cantidad_ganancia',
        'porc_vend_monto_base',
        'porc_vend_fecha_asignacion',
        'porc_vend_estado',
        'porc_vend_fecha_pago',
        'porc_vend_situacion',
        'porc_vend_observaciones'
    ];

    protected $casts = [
        'porc_vend_fecha_asignacion' => 'date',
        'porc_vend_fecha_pago' => 'date',
        'porc_vend_porcentaje' => 'decimal:2',
        'porc_vend_cantidad_ganancia' => 'decimal:2',
        'porc_vend_monto_base' => 'decimal:2',
    ];

    // Relación con el vendedor
    public function vendedor()
    {
        return $this->belongsTo(User::class, 'porc_vend_user_id', 'user_id');
    }

    // Relación con la venta
    public function venta()
    {
        return $this->belongsTo(ProVenta::class, 'porc_vend_ven_id', 'ven_id');
    }

    //========================================
    // SCOPES ADICIONALES PARA REPORTES
    // ========================================

    // Scope para comisiones activas
    public function scopeActivas($query)
    {
        return $query->where('porc_vend_situacion', 'ACTIVO');
    }

    // Scope para filtrar por estado
    public function scopePorEstado($query, $estado)
    {
        if ($estado) {
            $query->where('porc_vend_estado', $estado);
        }
        return $query;
    }

    // Scope para filtrar por fecha
    public function scopePorFecha($query, $fechaInicio, $fechaFin)
    {
        if ($fechaInicio) {
            $query->whereDate('porc_vend_fecha_asignacion', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->whereDate('porc_vend_fecha_asignacion', '<=', $fechaFin);
        }
        return $query;
    }

      // Scopes
        public function scopePendientes($query)
        {
            return $query->where('porc_vend_estado', 'PENDIENTE');
        }

        public function scopePagados($query)
        {
            return $query->where('porc_vend_estado', 'PAGADO');
        }

        public function scopeActivos($query)
        {
            return $query->where('porc_vend_situacion', 'ACTIVO');
        }
}