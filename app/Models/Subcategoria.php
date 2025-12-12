<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use HasFactory;

    protected $table = 'pro_subcategorias';
    protected $primaryKey = 'subcategoria_id';

    protected $fillable = [
        'subcategoria_nombre',
        'subcategoria_idcategoria',
        'subcategoria_situacion'
    ];

    protected $casts = [
        'subcategoria_idcategoria' => 'integer',
        'subcategoria_situacion' => 'integer',
    ];

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'subcategoria_idcategoria', 'categoria_id');
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'producto_subcategoria_id', 'subcategoria_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('subcategoria_situacion', 1);
    }

    public function scopeInactivas($query)
    {
        return $query->where('subcategoria_situacion', 0);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where('subcategoria_nombre', 'like', '%' . $termino . '%');
    }

    public function scopePorCategoria($query, $categoriaId)
    {
        return $query->where('subcategoria_idcategoria', $categoriaId);
    }

    // Accessors
    public function getEstadoTextoAttribute()
    {
        return $this->subcategoria_situacion == 1 ? 'Activo' : 'Inactivo';
    }

    public function getNombreCompletoAttribute()
    {
        return $this->categoria->categoria_nombre . ' - ' . $this->subcategoria_nombre;
    }
}