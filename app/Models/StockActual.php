<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para el stock actual de productos
 * Tabla de consulta rápida para inventario en tiempo real
 */
class StockActual extends Model
{
    use HasFactory;

    protected $table = 'pro_stock_actual';
    protected $primaryKey = 'stock_id';
    
    // Solo tiene updated_at, no created_at
    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';
    
    public $timestamps = true;

    protected $fillable = [
        'stock_producto_id',
        'stock_cantidad_total',
        'stock_cantidad_disponible',
        'stock_cantidad_reservada',
        'stock_valor_total',
        'stock_ultimo_movimiento'
    ];

    protected $casts = [
        'stock_producto_id' => 'integer',
        'stock_cantidad_total' => 'integer',
        'stock_cantidad_disponible' => 'integer',
        'stock_cantidad_reservada' => 'integer',
        'stock_valor_total' => 'decimal:2',
        'stock_ultimo_movimiento' => 'datetime'
    ];

    // ========================
    // RELACIONES
    // ========================

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'stock_producto_id', 'producto_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope para productos con stock disponible
     */
    public function scopeConStock($query)
    {
        // FIX: Calcular disponible real (Total - Reservado)
        return $query->whereRaw('(stock_cantidad_total - stock_cantidad_reservada) > 0');
    }

    /**
     * Scope para productos sin stock
     */
    public function scopeSinStock($query)
    {
        // FIX: Calcular disponible real (Total - Reservado)
        return $query->whereRaw('(stock_cantidad_total - stock_cantidad_reservada) <= 0');
    }

    /**
     * Scope para productos con stock bajo
     * Compara con el stock_minimo del producto
     */
    public function scopeStockBajo($query)
    {
        return $query->whereHas('producto', function($q) {
            // FIX: Calcular disponible real (Total - Reservado)
            $q->whereRaw('(pro_stock_actual.stock_cantidad_total - pro_stock_actual.stock_cantidad_reservada) <= pro_productos.producto_stock_minimo');
        });
    }

    /**
     * Scope para productos con stock reservado
     */
    public function scopeConReservas($query)
    {
        return $query->where('stock_cantidad_reservada', '>', 0);
    }

    /**
     * Scope por categoría de producto
     */
    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->whereHas('producto', function($q) use ($categoriaId) {
            $q->where('producto_categoria_id', $categoriaId);
        });
    }

    // ========================
    // MÉTODOS DE NEGOCIO
    // ========================

    /**
     * Actualiza el stock basado en movimientos
     */
    public function actualizarDesdeMovimientos(): self
    {
        $resumen = Movimiento::resumenPorProducto($this->stock_producto_id);
        
        $this->stock_cantidad_total = $resumen['stock_calculado'];
        $this->stock_cantidad_disponible = max(0, $resumen['stock_calculado'] - $this->stock_cantidad_reservada);
        $this->stock_ultimo_movimiento = now();
        
        return $this;
    }

    /**
     * Reserva una cantidad específica
     */
    public function reservar(int $cantidad): bool
    {
        if ($cantidad <= 0 || $cantidad > $this->stock_cantidad_disponible) {
            return false;
        }

        $this->stock_cantidad_disponible -= $cantidad;
        $this->stock_cantidad_reservada += $cantidad;
        
        return $this->save();
    }

    /**
     * Libera una cantidad reservada
     */
    public function liberarReserva(int $cantidad): bool
    {
        if ($cantidad <= 0 || $cantidad > $this->stock_cantidad_reservada) {
            return false;
        }

        $this->stock_cantidad_reservada -= $cantidad;
        $this->stock_cantidad_disponible += $cantidad;
        
        return $this->save();
    }

    /**
     * Incrementa el stock (por ingresos)
     */
    public function incrementar(int $cantidad, float $valorUnitario = null): bool
    {
        if ($cantidad <= 0) {
            return false;
        }

        $this->stock_cantidad_total += $cantidad;
        $this->stock_cantidad_disponible += $cantidad;
        
        if ($valorUnitario) {
            $this->stock_valor_total += ($cantidad * $valorUnitario);
        }
        
        $this->stock_ultimo_movimiento = now();
        
        return $this->save();
    }

    /**
     * Decrementa el stock (por egresos)
     */
    public function decrementar(int $cantidad, float $valorUnitario = null): bool
    {
        if ($cantidad <= 0 || $cantidad > $this->stock_cantidad_disponible) {
            return false;
        }

        $this->stock_cantidad_total -= $cantidad;
        $this->stock_cantidad_disponible -= $cantidad;
        
        if ($valorUnitario) {
            $this->stock_valor_total -= ($cantidad * $valorUnitario);
        }
        
        $this->stock_ultimo_movimiento = now();
        
        return $this->save();
    }

    // ========================
    // MÉTODOS DE VERIFICACIÓN
    // ========================

    /**
     * Verifica si hay stock disponible
     */
    public function tieneStock(int $cantidad = 1): bool
    {
        return $this->stock_cantidad_disponible >= $cantidad;
    }

    /**
     * Verifica si el stock está bajo el mínimo
     */
    public function estaStockBajo(): bool
    {
        if (!$this->producto || !$this->producto->producto_stock_minimo) {
            return false;
        }

        return $this->stock_cantidad_disponible <= $this->producto->producto_stock_minimo;
    }

    /**
     * Verifica si está agotado
     */
    public function estaAgotado(): bool
    {
        return $this->stock_cantidad_disponible <= 0;
    }

    /**
     * Verifica si está sobre el máximo
     */
    public function estaSobreMaximo(): bool
    {
        if (!$this->producto || !$this->producto->producto_stock_maximo) {
            return false;
        }

        return $this->stock_cantidad_total > $this->producto->producto_stock_maximo;
    }

    // ========================
    // ATRIBUTOS VIRTUALES
    // ========================

    /**
     * Porcentaje de stock disponible vs total
     */
    public function getPorcentajeDisponibleAttribute(): float
    {
        if ($this->stock_cantidad_total <= 0) {
            return 0;
        }

        return round(($this->stock_cantidad_disponible / $this->stock_cantidad_total) * 100, 2);
    }

    /**
     * Porcentaje de stock reservado vs total
     */
    public function getPorcentajeReservadoAttribute(): float
    {
        if ($this->stock_cantidad_total <= 0) {
            return 0;
        }

        return round(($this->stock_cantidad_reservada / $this->stock_cantidad_total) * 100, 2);
    }

    /**
     * Estado del stock (bueno, bajo, agotado, excesivo)
     */
    public function getEstadoStockAttribute(): string
    {
        if ($this->estaAgotado()) {
            return 'agotado';
        } elseif ($this->estaStockBajo()) {
            return 'bajo';
        } elseif ($this->estaSobreMaximo()) {
            return 'excesivo';
        }

        return 'bueno';
    }

    /**
     * Clase CSS según el estado del stock
     */
    public function getEstadoClaseAttribute(): string
    {
        $clases = [
            'bueno' => 'bg-green-100 text-green-800',
            'bajo' => 'bg-yellow-100 text-yellow-800',
            'agotado' => 'bg-red-100 text-red-800',
            'excesivo' => 'bg-blue-100 text-blue-800'
        ];

        return $clases[$this->estado_stock] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Valor promedio por unidad
     */
    public function getValorPromedioAttribute(): float
    {
        if ($this->stock_cantidad_total <= 0) {
            return 0;
        }

        return round($this->stock_valor_total / $this->stock_cantidad_total, 2);
    }

    /**
     * Valor total formateado
     */
    public function getValorFormateadoAttribute(): string
    {
        return 'Q ' . number_format($this->stock_valor_total, 2);
    }

    /**
     * Último movimiento hace X días
     */
    public function getDiasUltimoMovimientoAttribute(): int
    {
        if (!$this->stock_ultimo_movimiento) {
            return 0;
        }

        return $this->stock_ultimo_movimiento->diffInDays(now());
    }

    // ========================
    // MÉTODOS ESTÁTICOS
    // ========================

    /**
     * Crear o actualizar stock para un producto
     */
    public static function actualizarStock(int $productoId): self
    {
        $stock = self::firstOrCreate(
            ['stock_producto_id' => $productoId],
            [
                'stock_cantidad_total' => 0,
                'stock_cantidad_disponible' => 0,
                'stock_cantidad_reservada' => 0,
                'stock_valor_total' => 0
            ]
        );

        return $stock->actualizarDesdeMovimientos()->save() ? $stock : $stock;
    }

    /**
     * Obtener productos con alertas de stock
     */
    public static function productosConAlertas(): \Illuminate\Database\Eloquent\Collection
    {
        return self::stockBajo()
            ->orWhere('stock_cantidad_disponible', '<=', 0)
            ->with('producto')
            ->get();
    }

    /**
     * Resumen general de inventario
     */
    public static function resumenGeneral(): array
    {
        $total = self::count();
        $conStock = self::conStock()->count();
        $sinStock = self::sinStock()->count();
        $stockBajo = self::stockBajo()->count();
        $valorTotal = self::sum('stock_valor_total');

        return [
            'total_productos' => $total,
            'con_stock' => $conStock,
            'sin_stock' => $sinStock,
            'stock_bajo' => $stockBajo,
            'valor_total' => $valorTotal,
            'porcentaje_disponible' => $total > 0 ? round(($conStock / $total) * 100, 2) : 0
        ];
    }
}