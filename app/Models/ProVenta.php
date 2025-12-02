<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProVenta extends Model 
{
    protected $table = 'pro_ventas';
    protected $primaryKey = 'ven_id';
    public $timestamps = false;

    protected $fillable = [
        'ven_user',
        'ven_fecha',
        'ven_cliente',
        'ven_total_vendido',
        'ven_descuento',
        'ven_situacion',
        'ven_observaciones'
    ];

    protected $casts = [
        'ven_fecha' => 'date',
        'ven_total_vendido' => 'decimal:2',
        'ven_descuento' => 'decimal:2',
    ];

    // *** RELACIÓN CON CLIENTE (ESTA ES LA QUE FALTABA) ***
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(ProCliente::class, 'ven_cliente', 'cliente_id');
    }

    // Relación con el vendedor
    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ven_user', 'user_id');
    }

    // Relación con comisiones
    public function comisiones(): HasMany
    {
        return $this->hasMany(ProPorcentajeVendedor::class, 'porc_vend_ven_id', 'ven_id');
    }

    // Relación con detalle de ventas
    public function detalleVentas(): HasMany
    {
        return $this->hasMany(ProDetalleVenta::class, 'det_ven_id', 'ven_id');
    }

    // Relación con pagos
    public function pagos(): HasMany
    {
        return $this->hasMany(ProPago::class, 'pago_ven_id', 'ven_id');
    }

    // Scope para ventas activas
    public function scopeActivas($query)
    {
        return $query->where('ven_situacion', 1);
    }

    // Accessor para total con descuento aplicado
    public function getTotalFinalAttribute()
    {
        return $this->ven_total_vendido - $this->ven_descuento;
    }
}