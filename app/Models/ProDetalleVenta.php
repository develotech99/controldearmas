<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ProDetalleVenta extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_detalle_ventas';
    protected $primaryKey = 'det_id';
    public $timestamps = false;

    protected $fillable = [
        'det_ven_id',
        'det_producto_id',
        'det_cantidad',
        'det_precio',
        'det_descuento',
        'det_situacion',
        'det_cantidad_facturada'
    ];

    protected $casts = [
        'det_cantidad' => 'integer',
        'det_precio' => 'decimal:2',
        'det_descuento' => 'decimal:2'
    ];

    // ================================
    // RELACIONES
    // ================================

    /**
     * Relación con la venta
     */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(ProVenta::class, 'det_ven_id', 'ven_id');
    }

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'det_producto_id', 'producto_id');
    }

    // ================================
    // SCOPES
    // ================================

    public function scopeActivos($query)
    {
        return $query->where('det_situacion', 'ACTIVO');
    }

    public function scopeAnulados($query)
    {
        return $query->where('det_situacion', 'ANULADO');
    }

    // ================================
    // ACCESSORS
    // ================================

    public function getSubtotalAttribute()
    {
        return ($this->det_cantidad * $this->det_precio) - $this->det_descuento;
    }

    public function getDescuentoPercentAttribute()
    {
        if ($this->det_precio <= 0) return 0;
        return ($this->det_descuento / ($this->det_cantidad * $this->det_precio)) * 100;
    }

    // ================================
    // MÉTODOS ESTÁTICOS
    // ================================

    /**
     * Productos más vendidos en un período
     */
    public static function productosMasVendidos($fechaInicio, $fechaFin, $limit = 10)
    {
        return static::join('pro_ventas', 'pro_detalle_ventas.det_ven_id', '=', 'pro_ventas.ven_id')
                    ->join('pro_productos', 'pro_detalle_ventas.det_producto_id', '=', 'pro_productos.producto_id')
                    ->whereBetween('pro_ventas.ven_fecha', [$fechaInicio, $fechaFin])
                    ->where('pro_ventas.ven_situacion', 1)
                    ->where('pro_detalle_ventas.det_situacion', 'ACTIVO')
                    ->select([
                        'pro_productos.producto_id',
                        'pro_productos.producto_nombre',
                        'pro_productos.pro_codigo_sku',
                        \DB::raw('SUM(pro_detalle_ventas.det_cantidad) as total_vendido'),
                        \DB::raw('SUM(pro_detalle_ventas.det_cantidad * pro_detalle_ventas.det_precio - pro_detalle_ventas.det_descuento) as total_ingresos')
                    ])
                    ->groupBy('pro_productos.producto_id', 'pro_productos.producto_nombre', 'pro_productos.pro_codigo_sku')
                    ->orderByDesc('total_vendido')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Resumen de ventas por categoría
     */
    public static function ventasPorCategoria($fechaInicio, $fechaFin)
    {
        return static::join('pro_ventas', 'pro_detalle_ventas.det_ven_id', '=', 'pro_ventas.ven_id')
                    ->join('pro_productos', 'pro_detalle_ventas.det_producto_id', '=', 'pro_productos.producto_id')
                    ->join('pro_categorias', 'pro_productos.producto_categoria_id', '=', 'pro_categorias.categoria_id')
                    ->whereBetween('pro_ventas.ven_fecha', [$fechaInicio, $fechaFin])
                    ->where('pro_ventas.ven_situacion', 1)
                    ->where('pro_detalle_ventas.det_situacion', 'ACTIVO')
                    ->select([
                        'pro_categorias.categoria_id',
                        'pro_categorias.categoria_nombre',
                        \DB::raw('SUM(pro_detalle_ventas.det_cantidad) as total_productos'),
                        \DB::raw('SUM(pro_detalle_ventas.det_cantidad * pro_detalle_ventas.det_precio - pro_detalle_ventas.det_descuento) as total_monto')
                    ])
                    ->groupBy('pro_categorias.categoria_id', 'pro_categorias.categoria_nombre')
                    ->orderByDesc('total_monto')
                    ->get();
    }
}