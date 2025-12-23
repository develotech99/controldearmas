<?php
// app/Models/Promocion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use OwenIt\Auditing\Contracts\Auditable;

class Promocion extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    
    protected $table = 'pro_promociones';
    protected $primaryKey = 'promo_id';
    
    protected $fillable = [
        'promo_producto_id',
        'promo_nombre',
        'promo_tipo',
        'promo_valor',
        'promo_precio_original',
        'promo_precio_descuento',
        'promo_fecha_inicio',
        'promo_fecha_fin',
        'promo_justificacion',
        'promo_usuario_id',        // NUEVO CAMPO
        'promo_situacion'
    ];

    protected $casts = [
        'promo_valor' => 'decimal:2',
        'promo_precio_original' => 'decimal:2',
        'promo_precio_descuento' => 'decimal:2',
        'promo_fecha_inicio' => 'date',
        'promo_fecha_fin' => 'date',
        'promo_situacion' => 'integer'
    ];

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'promo_producto_id', 'producto_id');
    }

    /**
     * Relación con el usuario que creó la promoción
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'promo_usuario_id', 'user_id');
    }

    /**
     * Scope para promociones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('promo_situacion', 1)
                    ->where('promo_fecha_inicio', '<=', Carbon::now())
                    ->where('promo_fecha_fin', '>=', Carbon::now());
    }

    /**
     * Scope para promociones vigentes
     */
    public function scopeVigentes($query)
    {
        return $query->where('promo_situacion', 1);
    }

    /**
     * Scope para promociones por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('promo_tipo', $tipo);
    }

    /**
     * Scope para promociones próximas a vencer
     */
    public function scopeProximasAVencer($query, $dias = 7)
    {
        return $query->where('promo_situacion', 1)
                    ->where('promo_fecha_fin', '<=', Carbon::now()->addDays($dias))
                    ->where('promo_fecha_fin', '>=', Carbon::now());
    }

    /**
     * Scope para promociones expiradas
     */
    public function scopeExpiradas($query)
    {
        return $query->where('promo_fecha_fin', '<', Carbon::now());
    }

    /**
     * Verifica si la promoción está activa actualmente
     */
    public function getEstaActivaAttribute()
    {
        $now = Carbon::now();
        return $this->promo_situacion == 1 && 
               $now->between($this->promo_fecha_inicio, $this->promo_fecha_fin);
    }

    /**
     * Verifica si la promoción ha expirado
     */
    public function getHaExpiradoAttribute()
    {
        return Carbon::now()->gt($this->promo_fecha_fin);
    }

    /**
     * Verifica si la promoción aún no ha iniciado
     */
    public function getEsFuturaAttribute()
    {
        return Carbon::now()->lt($this->promo_fecha_inicio);
    }

    /**
     * Obtiene el estado de la promoción
     */
    public function getEstadoAttribute()
    {
        if ($this->promo_situacion != 1) {
            return 'inactiva';
        }
        
        $now = Carbon::now();
        
        if ($now->lt($this->promo_fecha_inicio)) {
            return 'programada';
        } elseif ($now->gt($this->promo_fecha_fin)) {
            return 'expirada';
        } else {
            return 'activa';
        }
    }

    /**
     * Obtiene días restantes para que expire la promoción
     */
    public function getDiasRestantesAttribute()
    {
        if ($this->ha_expirado) {
            return 0;
        }
        
        return Carbon::now()->diffInDays($this->promo_fecha_fin, false);
    }

    /**
     * Calcula el precio con descuento
     */
    public function calcularPrecioDescuento($precioOriginal)
    {
        if ($this->promo_tipo === 'porcentaje') {
            return $precioOriginal - ($precioOriginal * ($this->promo_valor / 100));
        } else {
            return max(0, $precioOriginal - $this->promo_valor); // No permitir precios negativos
        }
    }

    /**
     * Calcula el porcentaje de descuento
     */
    public function getPorcentajeDescuentoAttribute()
    {
        if ($this->promo_precio_original > 0 && $this->promo_precio_descuento > 0) {
            return (($this->promo_precio_original - $this->promo_precio_descuento) / $this->promo_precio_original) * 100;
        }
        return $this->promo_tipo === 'porcentaje' ? $this->promo_valor : 0;
    }

    /**
     * Obtiene el ahorro en dinero
     */
    public function getAhorroAttribute()
    {
        return $this->promo_precio_original - $this->promo_precio_descuento;
    }

    /**
     * Obtiene la duración de la promoción en días
     */
    public function getDuracionDiasAttribute()
    {
        return $this->promo_fecha_inicio->diffInDays($this->promo_fecha_fin) + 1;
    }

    /**
     * Formatea el descuento para mostrar
     */
    public function getDescuentoFormateadoAttribute()
    {
        if ($this->promo_tipo === 'porcentaje') {
            return number_format($this->promo_valor, 0) . '% OFF';
        } else {
            return 'Q' . number_format($this->promo_valor, 2) . ' OFF';
        }
    }

    /**
     * Verifica si es válida para aplicar
     */
    public function esValida()
    {
        return $this->esta_activa && 
               $this->promo_valor > 0 && 
               $this->promo_precio_original > 0 && 
               $this->promo_precio_descuento > 0 &&
               $this->promo_precio_descuento < $this->promo_precio_original;
    }

    /**
     * Actualiza automáticamente el precio con descuento
     */
    protected static function booted()
    {
        static::saving(function ($promocion) {
            // Calcular precio con descuento automáticamente si no se especifica
            if (is_null($promocion->promo_precio_descuento) && $promocion->promo_precio_original > 0) {
                $promocion->promo_precio_descuento = $promocion->calcularPrecioDescuento($promocion->promo_precio_original);
            }
            
            // Validar que las fechas sean coherentes
            if ($promocion->promo_fecha_fin < $promocion->promo_fecha_inicio) {
                throw new \InvalidArgumentException('La fecha de fin no puede ser anterior a la fecha de inicio');
            }
            
            // Validar que el descuento sea lógico
            if ($promocion->promo_precio_descuento >= $promocion->promo_precio_original) {
                throw new \InvalidArgumentException('El precio con descuento debe ser menor al precio original');
            }
        });

        // Desactivar promociones expiradas automáticamente
        static::retrieved(function ($promocion) {
            if ($promocion->ha_expirado && $promocion->promo_situacion == 1) {
                $promocion->update(['promo_situacion' => 0]);
            }
        });
    }
}