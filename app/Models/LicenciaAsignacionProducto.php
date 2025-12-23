<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo para la asignación de productos a licencias de importación
 * Conecta productos del inventario con licencias específicas
 */
class LicenciaAsignacionProducto extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_licencia_asignacion_producto';
    protected $primaryKey = 'asignacion_id';
    public $timestamps = true;

    protected $fillable = [
        'asignacion_producto_id',
        'asignacion_licencia_id',
        'asignacion_cantidad',
        'asignacion_serie_id',        // AGREGAR ESTE
        'asignacion_observaciones',
        'asignacion_situacion'
    ];

    protected $casts = [
        'asignacion_cantidad' => 'integer',
        'asignacion_situacion' => 'integer'
    ];

    // ========================================
    // RELACIONES
    // ========================================

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'asignacion_producto_id', 'producto_id');
    }

    /**
     * Relación con la licencia de importación
     */
    public function licencia(): BelongsTo
    {
        return $this->belongsTo(LicenciaImportacion::class, 'asignacion_licencia_id', 'lipaimp_id');
    }

    /**
     * Relación con las series asignadas a esta combinación producto-licencia
     */
    public function series(): HasMany
    {
        return $this->hasMany(SerieProducto::class, 'serie_asignacion_id', 'asignacion_id')
                    ->where('serie_situacion', 1);
    }

    /**
 * Relación con la serie específica asignada
 */
public function serie(): BelongsTo
{
    return $this->belongsTo(SerieProducto::class, 'asignacion_serie_id', 'serie_id');
}

    /**
     * Series disponibles de esta asignación
     */
    public function seriesDisponibles(): HasMany
    {
        return $this->series()->where('serie_estado', 'disponible');
    }

    /**
     * Series vendidas de esta asignación
     */
    public function seriesVendidas(): HasMany
    {
        return $this->series()->where('serie_estado', 'vendido');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Scope para asignaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('asignacion_situacion', 1);
    }


    /**
 * Asignar series específicas a una licencia - VERSIÓN SIMPLIFICADA
 */
public static function asignarSeries($productoId, $licenciaId, $seriesIds, $observaciones = null)
{
    $asignaciones = [];
    
    foreach ($seriesIds as $serieId) {
        $asignaciones[] = static::create([
            'asignacion_producto_id' => $productoId,
            'asignacion_licencia_id' => $licenciaId,
            'asignacion_cantidad' => 1, // Cada serie es 1 unidad
            'asignacion_serie_id' => $serieId,        // NUEVO CAMPO
            'asignacion_observaciones' => $observaciones,  // NUEVO CAMPO
            'asignacion_situacion' => 1
        ]);
    }
    
    return $asignaciones;
}
    /**
     * Scope por producto específico
     */
    public function scopePorProducto($query, $productoId)
    {
        return $query->where('asignacion_producto_id', $productoId);
    }

    /**
     * Scope por licencia específica
     */
    public function scopePorLicencia($query, $licenciaId)
    {
        return $query->where('asignacion_licencia_id', $licenciaId);
    }

    /**
     * Scope con relaciones cargadas
     */
    public function scopeConRelaciones($query)
    {
        return $query->with(['producto', 'licencia']);
    }

    // ========================================
    // ATRIBUTOS CALCULADOS
    // ========================================

    /**
     * Obtiene la cantidad de series registradas para esta asignación
     */
    public function getCantidadSeriesRegistradasAttribute(): int
    {
        return $this->series()->count();
    }

    /**
     * Obtiene la cantidad de series disponibles
     */
    public function getCantidadSeriesDisponiblesAttribute(): int
    {
        return $this->seriesDisponibles()->count();
    }

    /**
     * Obtiene la cantidad de series vendidas
     */
    public function getCantidadSeriesVendidasAttribute(): int
    {
        return $this->seriesVendidas()->count();
    }

    /**
     * Calcula el porcentaje de cumplimiento de la asignación
     */
    public function getPorcentajeCumplimientoAttribute(): float
    {
        if ($this->asignacion_cantidad === 0) {
            return 0;
        }

        return ($this->cantidad_series_registradas / $this->asignacion_cantidad) * 100;
    }

    /**
     * Verifica si la asignación está completa
     */
    public function getEstaCompletaAttribute(): bool
    {
        return $this->cantidad_series_registradas >= $this->asignacion_cantidad;
    }

    /**
     * Calcula cuántas series faltan por registrar
     */
    public function getSeriesFaltantesAttribute(): int
    {
        return max(0, $this->asignacion_cantidad - $this->cantidad_series_registradas);
    }

    /**
     * Obtiene el estado de la asignación en texto
     */
    public function getEstadoTextoAttribute(): string
    {
        if ($this->asignacion_situacion === 0) {
            return 'Inactiva';
        }

        if ($this->esta_completa) {
            return 'Completa';
        }

        if ($this->cantidad_series_registradas > 0) {
            return 'Parcial';
        }

        return 'Pendiente';
    }

    /**
     * Clase CSS para el estado
     */
    public function getEstadoClaseAttribute(): string
    {
        $clases = [
            'Inactiva' => 'bg-gray-100 text-gray-800',
            'Completa' => 'bg-green-100 text-green-800',
            'Parcial' => 'bg-yellow-100 text-yellow-800',
            'Pendiente' => 'bg-blue-100 text-blue-800'
        ];

        return $clases[$this->estado_texto] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Descripción completa de la asignación
     */
    public function getDescripcionCompletaAttribute(): string
    {
        $producto = $this->producto ? $this->producto->producto_nombre : 'Producto desconocido';
        $licencia = $this->licencia ? $this->licencia->lipaimp_descripcion : 'Licencia desconocida';
        
        return "{$producto} - {$licencia} ({$this->asignacion_cantidad} unidades)";
    }

    // ========================================
    // MÉTODOS DE NEGOCIO
    // ========================================

    /**
     * Incrementa la cantidad asignada
     */
    public function incrementarCantidad(int $cantidad): bool
    {
        if ($cantidad <= 0) {
            return false;
        }

        $this->asignacion_cantidad += $cantidad;
        return $this->save();
    }

    /**
     * Verifica si se pueden agregar más series
     */
    public function puedeAgregarSeries(int $cantidad = 1): bool
    {
        return ($this->cantidad_series_registradas + $cantidad) <= $this->asignacion_cantidad;
    }

    /**
     * Obtiene las próximas series que deben registrarse
     */
    public function getProximasSeriesParaRegistrar(int $cantidad = 1): array
    {
        if (!$this->puedeAgregarSeries($cantidad)) {
            return [];
        }

        // Retornar array con información para generar series
        $proximasSeries = [];
        $siguienteNumero = $this->cantidad_series_registradas + 1;

        for ($i = 0; $i < $cantidad; $i++) {
            $proximasSeries[] = [
                'numero_sugerido' => $siguienteNumero + $i,
                'producto_nombre' => $this->producto->producto_nombre ?? '',
                'licencia_codigo' => $this->licencia->lipaimp_id ?? ''
            ];
        }

        return $proximasSeries;
    }

    /**
     * Busca asignación existente o crea nueva
     */
    public static function buscarOCrear(int $productoId, int $licenciaId, int $cantidad): self
    {
        $asignacion = static::where('asignacion_producto_id', $productoId)
                           ->where('asignacion_licencia_id', $licenciaId)
                           ->where('asignacion_situacion', 1)
                           ->first();

        if ($asignacion) {
            // Incrementar cantidad existente
            $asignacion->incrementarCantidad($cantidad);
            return $asignacion;
        }

        // Crear nueva asignación
        return static::create([
            'asignacion_producto_id' => $productoId,
            'asignacion_licencia_id' => $licenciaId,
            'asignacion_cantidad' => $cantidad,
            'asignacion_situacion' => 1
        ]);
    }

    /**
     * Obtiene resumen de asignaciones por licencia
     */
    public static function resumenPorLicencia(int $licenciaId): array
    {
        $asignaciones = static::activas()
                             ->porLicencia($licenciaId)
                             ->conRelaciones()
                             ->get();

        return [
            'total_productos' => $asignaciones->count(),
            'total_cantidad_asignada' => $asignaciones->sum('asignacion_cantidad'),
            'total_series_registradas' => $asignaciones->sum('cantidad_series_registradas'),
            'productos_completos' => $asignaciones->where('esta_completa', true)->count(),
            'productos_pendientes' => $asignaciones->where('esta_completa', false)->count(),
            'porcentaje_global' => $asignaciones->avg('porcentaje_cumplimiento')
        ];
    }

    /**
     * Obtiene resumen de asignaciones por producto
     */
    public static function resumenPorProducto(int $productoId): array
    {
        $asignaciones = static::activas()
                             ->porProducto($productoId)
                             ->conRelaciones()
                             ->get();

        return [
            'total_licencias' => $asignaciones->count(),
            'total_cantidad_asignada' => $asignaciones->sum('asignacion_cantidad'),
            'total_series_registradas' => $asignaciones->sum('cantidad_series_registradas'),
            'licencias_completas' => $asignaciones->where('esta_completa', true)->count(),
            'licencias_pendientes' => $asignaciones->where('esta_completa', false)->count()
        ];
    }
    
}