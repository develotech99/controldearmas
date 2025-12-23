<?php
// app/Models/Precio.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Precio extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    
    protected $table = 'pro_precios';
    protected $primaryKey = 'precio_id';
    
    protected $fillable = [
        'precio_producto_id',
        'precio_costo',
        'precio_venta',
        'precio_venta_empresa',
        'precio_margen',
        'precio_margen_empresa',
        'precio_especial',
        'precio_moneda',            // NUEVO CAMPO
        'precio_justificacion',
        'precio_fecha_asignacion',
        'precio_usuario_id',        // NUEVO CAMPO
        'precio_situacion'
    ];

    protected $casts = [
        'precio_costo' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_venta_empresa' => 'decimal:2',
        'precio_margen' => 'decimal:2',
        'precio_margen_empresa' => 'decimal:2',
        'precio_especial' => 'decimal:2',
        'precio_fecha_asignacion' => 'date',
        'precio_situacion' => 'integer'
    ];

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'precio_producto_id', 'producto_id');
    }

    /**
     * Relación con el usuario que asignó el precio
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'precio_usuario_id', 'user_id');
    }

    /**
     * Scope para precios activos
     */
    public function scopeActivos($query)
    {
        return $query->where('precio_situacion', 1);
    }

    /**
     * Scope para precios por moneda
     */
    public function scopePorMoneda($query, $moneda = 'GTQ')
    {
        return $query->where('precio_moneda', $moneda);
    }

    /**
     * Scope para precios con descuento especial
     */
    public function scopeConDescuento($query)
    {
        return $query->whereNotNull('precio_especial')
                     ->where('precio_especial', '>', 0);
    }

    /**
     * Calcula el margen de ganancia automáticamente
     */
    public function calcularMargen()
    {
        if ($this->precio_costo > 0) {
            $precioVenta = $this->precio_efectivo;
            return (($precioVenta - $this->precio_costo) / $this->precio_costo) * 100;
        }
        return 0;
    }

    /**
     * Obtiene el precio efectivo (especial si existe, sino el regular)
     */
    public function getPrecioEfectivoAttribute()
    {
        return $this->precio_especial && $this->precio_especial > 0 
               ? $this->precio_especial 
               : $this->precio_venta;
    }

    /**
     * Verifica si tiene precio especial activo
     */
    public function getTienePrecioEspecialAttribute()
    {
        return !is_null($this->precio_especial) && $this->precio_especial > 0;
    }

    /**
     * Obtiene el descuento en porcentaje si tiene precio especial
     */
    public function getPorcentajeDescuentoAttribute()
    {
        if (!$this->tiene_precio_especial || $this->precio_venta <= 0) {
            return 0;
        }
        
        return (($this->precio_venta - $this->precio_especial) / $this->precio_venta) * 100;
    }

    /**
     * Obtiene el monto del descuento
     */
    public function getMontoDescuentoAttribute()
    {
        if (!$this->tiene_precio_especial) {
            return 0;
        }
        
        return $this->precio_venta - $this->precio_especial;
    }

    /**
     * Formatea el precio con símbolo de moneda
     */
    public function getPrecioFormateadoAttribute()
    {
        $simbolos = [
            'GTQ' => 'Q',
            'USD' => '$',
            'EUR' => '€'
        ];
        
        $simbolo = $simbolos[$this->precio_moneda] ?? $this->precio_moneda;
        return $simbolo . number_format($this->precio_efectivo, 2);
    }

    /**
     * Obtiene información del cambio de precio anterior
     */
    public function getPrecioAnterior()
    {
        return static::where('precio_producto_id', $this->precio_producto_id)
                     ->where('precio_fecha_asignacion', '<', $this->precio_fecha_asignacion)
                     ->where('precio_situacion', 1)
                     ->orderBy('precio_fecha_asignacion', 'desc')
                     ->first();
    }

    /**
     * Verifica si el precio subió respecto al anterior
     */
    public function getSubioPrecioAttribute()
    {
        $anterior = $this->getPrecioAnterior();
        return $anterior ? $this->precio_venta > $anterior->precio_venta : false;
    }

    /**
     * Calcula la variación respecto al precio anterior
     */
    public function getVariacionPrecioAttribute()
    {
        $anterior = $this->getPrecioAnterior();
        if (!$anterior || $anterior->precio_venta <= 0) {
            return 0;
        }
        
        return (($this->precio_venta - $anterior->precio_venta) / $anterior->precio_venta) * 100;
    }

    /**
     * Actualiza automáticamente el margen cuando se guardan cambios
     */
    protected static function booted()
    {
        static::saving(function ($precio) {
            // Calcular margen automáticamente si no se especifica
            if (is_null($precio->precio_margen) && $precio->precio_costo > 0) {
                $precio->precio_margen = $precio->calcularMargen();
            }
            
            // Asignar fecha actual si no se especifica
            if (is_null($precio->precio_fecha_asignacion)) {
                $precio->precio_fecha_asignacion = now()->toDateString();
            }
            
            // Asignar moneda por defecto
            if (is_null($precio->precio_moneda)) {
                $precio->precio_moneda = 'GTQ';
            }
        });
    }

    /**
     * Scope para obtener el precio más reciente de cada producto
     */
    public function scopePreciosActuales($query)
    {
        return $query->whereIn('precio_id', function($subquery) {
            $subquery->select(\DB::raw('MAX(precio_id)'))
                     ->from('pro_precios')
                     ->where('precio_situacion', 1)
                     ->groupBy('precio_producto_id');
        });
    }
}