<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo para los movimientos de inventario
 * Registra todo el historial de entradas, salidas y ajustes
 */
class Movimiento extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_movimientos';
    protected $primaryKey = 'mov_id';
    
  
    public $timestamps = true;

    protected $fillable = [
        'mov_producto_id',
        'mov_tipo',
        'mov_origen',
        'mov_destino',           
        'mov_cantidad',
        'mov_precio_unitario',   
        'mov_valor_total',       
        'mov_fecha',
        'mov_usuario_id',
        'mov_lote_id',
        'mov_serie_id',          
        'mov_documento_referencia', 
        'mov_observaciones',
        'mov_situacion'
    ];

    protected $casts = [
        'mov_fecha' => 'datetime',
        'mov_cantidad' => 'integer',
        'mov_precio_unitario' => 'decimal:2',
        'mov_valor_total' => 'decimal:2',
        'mov_usuario_id' => 'integer',
        'mov_lote_id' => 'integer',
        'mov_serie_id' => 'integer',
        'mov_situacion' => 'integer'
    ];

    //  Constantes para tipos de movimiento
    const TIPO_INGRESO = 'ingreso';
    const TIPO_EGRESO = 'egreso';
    const TIPO_AJUSTE_POSITIVO = 'ajuste_positivo';
    const TIPO_AJUSTE_NEGATIVO = 'ajuste_negativo';
    const TIPO_VENTA = 'venta';
    const TIPO_DEVOLUCION = 'devolucion';
    const TIPO_MERMA = 'merma';
    const TIPO_TRANSFERENCIA = 'transferencia';

    const TIPOS_VALIDOS = [
        self::TIPO_INGRESO,
        self::TIPO_EGRESO,
        self::TIPO_AJUSTE_POSITIVO,
        self::TIPO_AJUSTE_NEGATIVO,
        self::TIPO_VENTA,
        self::TIPO_DEVOLUCION,
        self::TIPO_MERMA,
        self::TIPO_TRANSFERENCIA
    ];

    // Tipos que incrementan stock
    const TIPOS_INCREMENTO = [
        self::TIPO_INGRESO,
        self::TIPO_AJUSTE_POSITIVO,
        self::TIPO_DEVOLUCION
    ];

    // Tipos que decrementan stock
    const TIPOS_DECREMENTO = [
        self::TIPO_EGRESO,
        self::TIPO_AJUSTE_NEGATIVO,
        self::TIPO_VENTA,
        self::TIPO_MERMA,
        self::TIPO_TRANSFERENCIA
    ];

    // ========================
    // RELACIONES
    // ========================

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'mov_producto_id', 'producto_id');
    }

    /**
     * Relación con el lote (opcional)
     */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'mov_lote_id', 'lote_id');
    }

    /**
     * Relación con la serie específica (opcional)
     */
    public function serie(): BelongsTo
    {
        return $this->belongsTo(SerieProducto::class, 'mov_serie_id', 'serie_id');
    }

    /**
     * Relación con el usuario que realizó el movimiento
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mov_usuario_id', 'user_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope para movimientos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('mov_situacion', 1);
    }

    /**
     * Scope por tipo de movimiento
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('mov_tipo', $tipo);
    }

    /**
     *  Scope para movimientos de ingreso
     */
    public function scopeIngresos($query)
    {
        return $query->whereIn('mov_tipo', self::TIPOS_INCREMENTO);
    }

    /**
     *  Scope para movimientos de egreso
     */
    public function scopeEgresos($query)
    {
        return $query->whereIn('mov_tipo', self::TIPOS_DECREMENTO);
    }

    /**
     *  Scope por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('mov_fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     *  Scope por producto
     */
    public function scopeDelProducto($query, $productoId)
    {
        return $query->where('mov_producto_id', $productoId);
    }

    /**
     *  Scope por usuario
     */
    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('mov_usuario_id', $usuarioId);
    }

    /**
     *  Scope para movimientos recientes
     */
    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('mov_fecha', '>=', now()->subDays($dias));
    }

    /**
     *  Scope con valor (que tienen precio)
     */
    public function scopeConValor($query)
    {
        return $query->whereNotNull('mov_precio_unitario')
                    ->whereNotNull('mov_valor_total');
    }

    // ========================
    // MÉTODOS DE VALIDACIÓN
    // ========================

    /**
     *  Verifica si es un movimiento de incremento
     */
    public function esIncremento(): bool
    {
        return in_array($this->mov_tipo, self::TIPOS_INCREMENTO);
    }

    /**
     *  Verifica si es un movimiento de decremento
     */
    public function esDecremento(): bool
    {
        return in_array($this->mov_tipo, self::TIPOS_DECREMENTO);
    }

    /**
     *  Verifica si el movimiento está anulado
     */
    public function estaAnulado(): bool
    {
        return $this->mov_situacion == 0;
    }

    /**
     *  Verifica si tiene valor monetario
     */
    public function tieneValor(): bool
    {
        return !is_null($this->mov_precio_unitario) && !is_null($this->mov_valor_total);
    }

    // ========================
    // MÉTODOS DE NEGOCIO
    // ========================

    /**
     *  Anula el movimiento
     */
    public function anular(string $observacion = null): bool
    {
        $this->mov_situacion = 0;
        
        if ($observacion) {
            $this->mov_observaciones = ($this->mov_observaciones ? $this->mov_observaciones . ' | ' : '') . 'ANULADO: ' . $observacion;
        }

        return $this->save();
    }

    /**
     *  Calcula valor total automáticamente
     */
    public function calcularValorTotal(): self
    {
        if ($this->mov_precio_unitario && $this->mov_cantidad) {
            $this->mov_valor_total = $this->mov_precio_unitario * $this->mov_cantidad;
        }

        return $this;
    }

    // ========================
    // ATRIBUTOS VIRTUALES
    // ========================

    /**
     *  Tipo formateado para mostrar
     */
    public function getTipoFormateadoAttribute(): string
    {
        $tipos = [
            self::TIPO_INGRESO => 'Ingreso',
            self::TIPO_EGRESO => 'Egreso',
            self::TIPO_AJUSTE_POSITIVO => 'Ajuste (+)',
            self::TIPO_AJUSTE_NEGATIVO => 'Ajuste (-)',
            self::TIPO_VENTA => 'Venta',
            self::TIPO_DEVOLUCION => 'Devolución',
            self::TIPO_MERMA => 'Merma',
            self::TIPO_TRANSFERENCIA => 'Transferencia'
        ];

        return $tipos[$this->mov_tipo] ?? ucfirst($this->mov_tipo);
    }

    /**
     *  Clase CSS según el tipo de movimiento
     */
    public function getTipoClaseAttribute(): string
    {
        if ($this->esIncremento()) {
            return 'bg-green-100 text-green-800';
        } elseif ($this->esDecremento()) {
            return 'bg-red-100 text-red-800';
        }

        return 'bg-gray-100 text-gray-800';
    }

    /**
     *  Icono según el tipo
     */
    public function getTipoIconoAttribute(): string
    {
        $iconos = [
            self::TIPO_INGRESO => 'fas fa-plus-circle',
            self::TIPO_EGRESO => 'fas fa-minus-circle',
            self::TIPO_AJUSTE_POSITIVO => 'fas fa-plus',
            self::TIPO_AJUSTE_NEGATIVO => 'fas fa-minus',
            self::TIPO_VENTA => 'fas fa-shopping-cart',
            self::TIPO_DEVOLUCION => 'fas fa-undo',
            self::TIPO_MERMA => 'fas fa-exclamation-triangle',
            self::TIPO_TRANSFERENCIA => 'fas fa-exchange-alt'
        ];

        return $iconos[$this->mov_tipo] ?? 'fas fa-circle';
    }

    /**
     *  Fecha formateada
     */
    public function getFechaFormateadaAttribute(): string
    {
        return $this->mov_fecha->format('d/m/Y H:i');
    }

    /**
     *  Valor total formateado
     */
    public function getValorFormateadoAttribute(): string
    {
        if ($this->mov_valor_total) {
            return 'Q ' . number_format($this->mov_valor_total, 2);
        }

        return 'N/A';
    }

    /**
     *  Referencia completa (documento + observaciones)
     */
    public function getReferenciaCompletaAttribute(): string
    {
        $partes = array_filter([
            $this->mov_documento_referencia,
            $this->mov_observaciones
        ]);

        return implode(' | ', $partes) ?: 'N/A';
    }

    // ========================
    // MÉTODOS ESTÁTICOS
    // ========================

    /**
     *  Crear movimiento con validaciones
     */
    public static function crearMovimiento(array $datos): self
    {
        // Validar tipo
        if (!in_array($datos['mov_tipo'], self::TIPOS_VALIDOS)) {
            throw new \InvalidArgumentException('Tipo de movimiento no válido');
        }

        // Calcular valor total si se proporciona precio unitario
        if (isset($datos['mov_precio_unitario']) && isset($datos['mov_cantidad'])) {
            $datos['mov_valor_total'] = $datos['mov_precio_unitario'] * $datos['mov_cantidad'];
        }

        // Establecer fecha actual si no se proporciona
        if (!isset($datos['mov_fecha'])) {
            $datos['mov_fecha'] = now();
        }

        return self::create($datos);
    }

    /**
     *  Obtener resumen por producto
     */
    // public static function resumenPorProducto(int $productoId): array
    // {
    //     $ingresos = self::activos()
    //         ->delProducto($productoId)
    //         ->ingresos()
    //         ->sum('mov_cantidad');

    //     $egresos = self::activos()
    //         ->delProducto($productoId)
    //         ->egresos()
    //         ->sum('mov_cantidad');

    //     return [
    //         'ingresos' => $ingresos,
    //         'egresos' => $egresos,
    //         'stock_calculado' => $ingresos - $egresos
    //     ];
    // }

    // En app/Models/Movimiento.php
public static function resumenPorProducto($productoId)
{
    $ingresos = static::where('mov_producto_id', $productoId)
        ->whereIn('mov_tipo', ['ingreso', 'ajuste_positivo', 'devolucion'])
        ->where('mov_situacion', 1)
        ->sum('mov_cantidad');
    
    $egresos = static::where('mov_producto_id', $productoId)
        ->whereIn('mov_tipo', ['egreso', 'venta', 'ajuste_negativo'])
        ->where('mov_situacion', 1)
        ->sum('mov_cantidad');
    
    return [
        'ingresos' => $ingresos,
        'egresos' => $egresos,
        'stock_calculado' => $ingresos - $egresos
    ];
}
}