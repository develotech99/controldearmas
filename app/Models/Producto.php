<?php
// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Producto extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_productos';
    protected $primaryKey = 'producto_id';
    public $timestamps = true; // Cambiado a true porque agregaste timestamps

    protected $fillable = [
        'producto_nombre',
        'producto_descripcion',
        'pro_codigo_sku',
        'producto_codigo_barra',
        'producto_categoria_id',
        'producto_subcategoria_id',
        'producto_marca_id',
        'producto_modelo_id',
        'producto_calibre_id',
        'producto_madein',
        'producto_requiere_serie',
        'producto_stock_minimo',
        'producto_stock_maximo',
        'producto_situacion', 
        'producto_requiere_stock'
    ];

    protected $casts = [
        'producto_requiere_serie' => 'boolean',
        'producto_requiere_stock' => 'boolean', 
        'producto_situacion' => 'integer',
        'producto_stock_minimo' => 'integer',
        'producto_stock_maximo' => 'integer'
    ];

    // ========================================
    // RELACIONES CON TABLAS DE INVENTARIO
    // ========================================
    /**
     * Relación con las asignaciones de licencias
     */
    public function asignacionesLicencias(): HasMany
    {
        return $this->hasMany(LicenciaAsignacionProducto::class, 'asignacion_producto_id', 'producto_id')
                    ->where('asignacion_situacion', 1);
    }
        /**
     * Relación con las fotos del producto
     */
    public function fotos(): HasMany
    {
        return $this->hasMany(ProductoFoto::class, 'foto_producto_id', 'producto_id')
                    ->where('foto_situacion', 1)
                    ->orderBy('foto_orden');
    }

    /**
     * Obtiene la foto principal del producto
     */
    public function fotoPrincipal()
    {
        return $this->fotos()->where('foto_principal', true)->first();
    }

    /**
     * Relación con las series del producto
     */
    public function series(): HasMany
    {
        return $this->hasMany(SerieProducto::class, 'serie_producto_id', 'producto_id')
                    ->where('serie_situacion', 1);
    }

    /**
     * Series disponibles para venta/egreso
     */
    public function seriesDisponibles(): HasMany
    {
        return $this->series()->where('serie_estado', 'disponible');
    }

    /**
     * Relación con los movimientos del producto
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'mov_producto_id', 'producto_id')
                    ->where('mov_situacion', 1);
    }

    /**
     * Relación con los precios del producto
     */
    public function precios(): HasMany
    {
        return $this->hasMany(Precio::class, 'precio_producto_id', 'producto_id');
    }

    /**
     * Obtiene el precio actual del producto
     */
    public function precioActual()
    {
        return $this->precios()
                    ->where('precio_situacion', 1)
                    ->latest('precio_fecha_asignacion')
                    ->first();
    }

    /**
     * Relación con las promociones del producto
     */
    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class, 'promo_producto_id', 'producto_id');
    }

    /**
     * Obtiene las promociones activas del producto
     */
    public function promocionesActivas(): HasMany
    {
        return $this->promociones()
                    ->where('promo_situacion', 1)
                    ->where('promo_fecha_inicio', '<=', now())
                    ->where('promo_fecha_fin', '>=', now());
    }

    /**
     * Relación con el stock actual
     */
    public function stockActual(): BelongsTo
    {
        return $this->belongsTo(StockActual::class, 'producto_id', 'stock_producto_id');
    }

    /**
     * Relación con las alertas del producto
     */
    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'alerta_producto_id', 'producto_id');
    }

    // ========================================
    // RELACIONES CON TABLAS MAESTRAS
    // ========================================

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'producto_categoria_id', 'categoria_id');
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(Subcategoria::class, 'producto_subcategoria_id', 'subcategoria_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'producto_marca_id', 'marca_id');
    }

    public function modelo(): BelongsTo
    {
        return $this->belongsTo(Modelo::class, 'producto_modelo_id', 'modelo_id');
    }

    public function calibre(): BelongsTo
    {
        return $this->belongsTo(Calibre::class, 'producto_calibre_id', 'calibre_id');
    }

    public function paisFabricacion(): BelongsTo
    {
        return $this->belongsTo(Pais::class, 'producto_madein', 'pais_id');
    }



    // ========================================
    // ATRIBUTOS CALCULADOS (GETTERS)
    // ========================================

    /**
     * Calcula el stock actual del producto
     * Usa tabla pro_stock_actual si existe, sino calcula dinámicamente
     */
    public function getStockDisponibleAttribute()
    {
        // Intentar obtener desde tabla de stock actual
        $stockRecord = $this->stockActual;
        if ($stockRecord) {
            return $stockRecord->stock_cantidad_disponible;
        }

        // Calcular dinámicamente si no existe el registro
        if ($this->producto_requiere_serie) {
            return $this->seriesDisponibles()->count();
        }

        // Para productos sin serie, calcular desde movimientos
        $ingresos = $this->movimientos()
            ->whereIn('mov_tipo', ['ingreso', 'ajuste_positivo'])
            ->sum('mov_cantidad');

        $egresos = $this->movimientos()
            ->whereIn('mov_tipo', ['egreso', 'venta', 'ajuste_negativo', 'merma'])
            ->sum('mov_cantidad');

        return max(0, $ingresos - $egresos);
    }

    /**
     * Obtiene el stock total del producto
     */
    public function getStockTotalAttribute()
    {
        $stockRecord = $this->stockActual;
        return $stockRecord ? $stockRecord->stock_cantidad_total : $this->stock_disponible;
    }

    /**
     * Obtiene el precio de venta efectivo (con promoción si aplica)
     */
    public function getPrecioVentaEfectivoAttribute()
    {
        $precioActual = $this->precioActual();
        if (!$precioActual) {
            return 0;
        }

        // Verificar si tiene promoción activa
        $promocionActiva = $this->promocionesActivas()->first();
        if ($promocionActiva) {
            return $promocionActiva->promo_precio_descuento;
        }

        // Si tiene precio especial, usarlo, sino el precio regular
        return $precioActual->precio_especial ?? $precioActual->precio_venta;
    }

    /**
     * Obtiene el precio de costo actual
     */
    public function getPrecioCostoActualAttribute()
    {
        $precioActual = $this->precioActual();
        return $precioActual ? $precioActual->precio_costo : 0;
    }

    /**
     * Calcula el margen de ganancia actual
     */
    public function getMargenGananciaAttribute()
    {
        $precioActual = $this->precioActual();
        if (!$precioActual || $precioActual->precio_costo <= 0) {
            return 0;
        }

        $precioVenta = $this->precio_venta_efectivo;
        return (($precioVenta - $precioActual->precio_costo) / $precioActual->precio_costo) * 100;
    }

    /**
     * Obtiene el estado del stock (normal, bajo, agotado)
     */
    public function getEstadoStockAttribute()
    {
        $stock = $this->stock_disponible;
        
        if ($stock === 0) {
            return 'agotado';
        } elseif ($this->producto_stock_minimo > 0 && $stock <= $this->producto_stock_minimo) {
            return 'bajo';
        } else {
            return 'normal';
        }
    }

    /**
     * Verifica si el producto tiene promoción activa
     */
    public function getTienePromocionActivaAttribute()
    {
        return $this->promocionesActivas()->exists();
    }

    /**
     * Obtiene el valor total del inventario para este producto
     */
    public function getValorInventarioAttribute()
    {
        $stock = $this->stock_disponible;
        $precio = $this->precio_costo_actual;
        return $stock * $precio;
    }

    /**
     * Obtiene información completa del producto para mostrar
     */
    public function getNombreCompletoAttribute()
    {
        $nombre = $this->producto_nombre;
        
        if ($this->marca) {
            $nombre = $this->marca->marca_descripcion . ' ' . $nombre;
        }
        
        if ($this->modelo) {
            $nombre .= ' ' . $this->modelo->modelo_descripcion;
        }
        
        if ($this->calibre) {
            $nombre .= ' ' . $this->calibre->calibre_nombre;
        }
        
        return $nombre;
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope para productos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('producto_situacion', 1);
    }

    /**
     * Scope para productos que requieren serie
     */
    public function scopeConSerie($query)
    {
        return $query->where('producto_requiere_serie', true);
    }

    /**
     * Scope para productos sin serie
     */
    public function scopeSinSerie($query)
    {
        return $query->where('producto_requiere_serie', false);
    }


    /**
     * Scope para productos con stock bajo
     */
    public function scopeStockBajo($query)
    {
        return $query->whereColumn('stock_disponible', '<=', 'producto_stock_minimo')
                     ->where('producto_stock_minimo', '>', 0);
    }

    /**
     * Scope para buscar por SKU o código de barra
     */
    public function scopeBuscarPorCodigo($query, $codigo)
    {
        return $query->where('pro_codigo_sku', 'LIKE', "%{$codigo}%")
                     ->orWhere('producto_codigo_barra', 'LIKE', "%{$codigo}%");
    }

    // ========================================
    // MÉTODOS DE NEGOCIO
    // ========================================

    /**
     * Verifica si el producto tiene stock disponible
     */
    public function tieneStock($cantidad = 1)
    {
        return $this->stock_disponible >= $cantidad;
    }

    /**
     * Verifica si el producto necesita reposición
     */
    public function necesitaReposicion()
    {
        return $this->producto_stock_minimo > 0 && 
               $this->stock_disponible <= $this->producto_stock_minimo;
    }

    /**
     * Genera un SKU automático para el producto
     */
    public static function generarSKU($categoria, $marca, $modelo = null, $calibre = null)
    {
        // Implementar lógica de generación de SKU
        // Ejemplo: ARM-GLK-G19G5-9MM-001
        $base = strtoupper(substr($categoria, 0, 3)) . '-' .
                strtoupper(substr($marca, 0, 3));
        
        if ($modelo) {
            $base .= '-' . strtoupper(substr($modelo, 0, 5));
        }
        
        if ($calibre) {
            $base .= '-' . strtoupper(substr($calibre, 0, 4));
        }
        
        // Buscar el siguiente número secuencial
        $ultimo = static::where('pro_codigo_sku', 'LIKE', $base . '-%')
                        ->latest('producto_id')
                        ->first();
        
        $numero = 1;
        if ($ultimo) {
            $partes = explode('-', $ultimo->pro_codigo_sku);
            $numero = intval(end($partes)) + 1;
        }
        
        return $base . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Método toString para representación del producto
     */
    public function __toString()
    {
        return $this->nombre_completo . ' (SKU: ' . $this->pro_codigo_sku . ')';
    }
}