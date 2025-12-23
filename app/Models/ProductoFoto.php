<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo para las fotos de productos
 */
class ProductoFoto extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    
    protected $table = 'pro_productos_fotos';
    protected $primaryKey = 'foto_id';
    public $timestamps = false; // Solo tiene created_at, no updated_at

    protected $fillable = [
        'foto_producto_id',
        'foto_url',
        'foto_alt_text',        // NUEVO CAMPO
        'foto_principal',
        'foto_orden',           // NUEVO CAMPO
        'foto_situacion'
    ];

    protected $casts = [
        'foto_principal' => 'boolean',
        'foto_situacion' => 'integer',
        'foto_orden' => 'integer'
    ];

    // Campos de fecha personalizados
    protected $dates = ['created_at'];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // No tiene updated_at

    /**
     * Relación con el producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'foto_producto_id', 'producto_id');
    }

    /**
     * Scope para fotos activas
     */
    public function scopeActivas($query)
    {
        return $query->where('foto_situacion', 1);
    }

    /**
     * Scope para fotos principales
     */
    public function scopePrincipales($query)
    {
        return $query->where('foto_principal', true);
    }

    /**
     * Scope ordenado por orden de visualización
     */
    public function scopeOrdenadas($query)
    {
        return $query->orderBy('foto_orden')->orderBy('foto_id');
    }

    /**
     * Obtiene la URL completa de la foto
     */
    public function getUrlCompletaAttribute()
    {
        // Si la URL ya es completa (http/https), devolverla tal como está
        if (str_starts_with($this->foto_url, 'http')) {
            return $this->foto_url;
        }
        
        // Si no, construir URL relativa al storage público
        return asset('storage/' . $this->foto_url);
    }

    /**
     * Verifica si es la foto principal
     */
    public function esPrincipal()
    {
        return $this->foto_principal === true;
    }

    /**
     * Establece como foto principal (y quita el flag de las demás)
     */
    public function establecerComoPrincipal()
    {
        // Quitar foto_principal de todas las demás fotos del mismo producto
        static::where('foto_producto_id', $this->foto_producto_id)
              ->where('foto_id', '!=', $this->foto_id)
              ->update(['foto_principal' => false]);
        
        // Establecer esta como principal
        $this->update(['foto_principal' => true]);
    }
}