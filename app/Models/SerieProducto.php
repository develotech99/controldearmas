<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo para las series de productos
 * Maneja productos individuales que requieren número de serie único
 */
class SerieProducto extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_series_productos';
    protected $primaryKey = 'serie_id';
    
    // CORREGIDO: Activar timestamps porque la migración los incluye
    public $timestamps = true;

    protected $fillable = [
        'serie_producto_id',
        'serie_asignacion_id', 
        'serie_numero_serie',
        'serie_estado',
        'serie_fecha_ingreso',
        'serie_observaciones',    //Campo faltante
        'serie_situacion', 
        'serie_tiene_tenencia',     
        'serie_monto_tenencia',
    ];

    protected $casts = [
        'serie_fecha_ingreso' => 'datetime',
        'serie_situacion' => 'integer', 
        'serie_tiene_tenencia' => 'boolean',
        'serie_monto_tenencia' => 'decimal:2',
    ];

    //Estados válidos como constantes
    const ESTADO_DISPONIBLE = 'disponible';
    const ESTADO_RESERVADO = 'reservado';
    const ESTADO_VENDIDO = 'vendido';
    const ESTADO_BAJA = 'baja';

    const ESTADOS_VALIDOS = [
        self::ESTADO_DISPONIBLE,
        self::ESTADO_RESERVADO,
        self::ESTADO_VENDIDO,
        self::ESTADO_BAJA
    ];

    /**
     * Relación con el producto principal
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'serie_producto_id', 'producto_id');
    }

    /**
     * Relación con la asignación licencia-producto
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(LicenciaAsignacionProducto::class, 'serie_asignacion_id', 'asignacion_id');
    }
    /**
     *Relación con movimientos de inventario
     * Una serie puede tener múltiples movimientos
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'mov_serie_id', 'serie_id');
    }

    // ========================
    // SCOPES BÁSICOS
    // ========================

    /**
     * Scope para series disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('serie_estado', self::ESTADO_DISPONIBLE)
                    ->where('serie_situacion', 1);
    }

    /**
     *Scope para series activas (no eliminadas)
     */
    public function scopeActivas($query)
    {
        return $query->where('serie_situacion', 1);
    }

    /**
     *Scope para series vendidas
     */
    public function scopeVendidas($query)
    {
        return $query->where('serie_estado', self::ESTADO_VENDIDO)
                    ->where('serie_situacion', 1);
    }

    /**
     *Scope para series reservadas
     */
    public function scopeReservadas($query)
    {
        return $query->where('serie_estado', self::ESTADO_RESERVADO)
                    ->where('serie_situacion', 1);
    }

    /**
     *Scope por estado específico
     */
    public function scopePorEstado($query, $estado)
    {
        return $query->where('serie_estado', $estado)
                    ->where('serie_situacion', 1);
    }

    /**
     *Scope búsqueda por número de serie
     */
    public function scopeBuscarSerie($query, $numeroSerie)
    {
        return $query->where('serie_numero_serie', 'LIKE', "%{$numeroSerie}%");
    }

    // ========================
    // MÉTODOS ÚTILES
    // ========================

    /**
     *Verifica si la serie está disponible
     */
    public function estaDisponible(): bool
    {
        return $this->serie_estado === self::ESTADO_DISPONIBLE && 
               $this->serie_situacion == 1;
    }

    /**
     *Verifica si la serie está vendida
     */
    public function estaVendida(): bool
    {
        return $this->serie_estado === self::ESTADO_VENDIDO;
    }

    /**
     *Cambia el estado de la serie
     */
    public function cambiarEstado(string $nuevoEstado, string $observacion = null): bool
    {
        if (!in_array($nuevoEstado, self::ESTADOS_VALIDOS)) {
            return false;
        }

        $this->serie_estado = $nuevoEstado;
        
        if ($observacion) {
            $this->serie_observaciones = $observacion;
        }

        return $this->save();
    }

    /**
     *Reservar serie
     */
    public function reservar(string $observacion = null): bool
    {
        if (!$this->estaDisponible()) {
            return false;
        }

        return $this->cambiarEstado(self::ESTADO_RESERVADO, $observacion);
    }

    /**
     *Vender serie
     */
    public function vender(string $observacion = null): bool
    {
        if (!in_array($this->serie_estado, [self::ESTADO_DISPONIBLE, self::ESTADO_RESERVADO])) {
            return false;
        }

        return $this->cambiarEstado(self::ESTADO_VENDIDO, $observacion);
    }

    /**
     *Dar de baja serie
     */
    public function darDeBaja(string $observacion = null): bool
    {
        return $this->cambiarEstado(self::ESTADO_BAJA, $observacion);
    }

    /**
     *Liberar serie reservada
     */
    public function liberar(string $observacion = null): bool
    {
        if ($this->serie_estado !== self::ESTADO_RESERVADO) {
            return false;
        }

        return $this->cambiarEstado(self::ESTADO_DISPONIBLE, $observacion);
    }

    /**
     *Obtener último movimiento de esta serie
     */
    public function ultimoMovimiento()
    {
        return $this->movimientos()->latest('mov_fecha')->first();
    }

    /**
     *Obtener historial completo de la serie
     */
    public function historialCompleto()
    {
        return $this->movimientos()
                   ->with('usuario')
                   ->orderBy('mov_fecha', 'desc')
                   ->get();
    }

    // ========================
    // MÉTODOS DE FORMATEO
    // ========================

    /**
     *Obtiene el estado formateado para mostrar
     */
    public function getEstadoFormateadoAttribute(): string
    {
        $estados = [
            self::ESTADO_DISPONIBLE => 'Disponible',
            self::ESTADO_RESERVADO => 'Reservado',
            self::ESTADO_VENDIDO => 'Vendido',
            self::ESTADO_BAJA => 'Baja'
        ];

        return $estados[$this->serie_estado] ?? 'Desconocido';
    }

    /**
     *Clase CSS según el estado
     */
    public function getEstadoClaseAttribute(): string
    {
        $clases = [
            self::ESTADO_DISPONIBLE => 'bg-green-100 text-green-800',
            self::ESTADO_RESERVADO => 'bg-yellow-100 text-yellow-800',
            self::ESTADO_VENDIDO => 'bg-blue-100 text-blue-800',
            self::ESTADO_BAJA => 'bg-red-100 text-red-800'
        ];

        return $clases[$this->serie_estado] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     *Nombre completo del producto con serie
     */
    public function getNombreCompletoAttribute(): string
    {
        if ($this->producto) {
            return $this->producto->producto_nombre . ' - S/N: ' . $this->serie_numero_serie;
        }
        
        return 'S/N: ' . $this->serie_numero_serie;
    }

    /**
     *Días desde ingreso
     */
    public function getDiasDesdeIngresoAttribute(): int
    {
        return $this->serie_fecha_ingreso->diffInDays(now());
    }
}