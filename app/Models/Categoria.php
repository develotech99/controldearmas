<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Categoria extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_categorias';
    protected $primaryKey = 'categoria_id';

    protected $fillable = [
        'categoria_nombre',
        'categoria_situacion'
    ];

    protected $casts = [
        'categoria_situacion' => 'integer',
    ];

    // Relaciones
    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class, 'subcategoria_idcategoria', 'categoria_id');
    }

    // Scopes
    public function scopeActivas($query)
    {
        return $query->where('categoria_situacion', 1);
    }

    public function scopeInactivas($query)
    {
        return $query->where('categoria_situacion', 0);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where('categoria_nombre', 'like', '%' . $termino . '%');
    }

    // Accessors
    public function getEstadoTextoAttribute()
    {
        return $this->categoria_situacion == 1 ? 'Activo' : 'Inactivo';
    }

    public function getSubcategoriasActivasAttribute()
    {
        return $this->subcategorias()->where('subcategoria_situacion', 1)->count();
    }
}