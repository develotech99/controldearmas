<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Marcas;
use OwenIt\Auditing\Contracts\Auditable;

class ProModelo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
      protected $table = 'pro_modelo';     // o 'pro_modelos' si fuera plural
    protected $primaryKey = 'modelo_id';
    public $timestamps = false;

    protected $fillable = [
        'modelo_descripcion',
        'modelo_situacion',
        'modelo_marca_id'
    ];

    protected $casts = [
        'modelo_situacion' => 'boolean',
        'modelo_marca_id' => 'integer',
    ];

    // Relaciones
public function marca()
{
    return $this->belongsTo(Marcas::class, 'modelo_marca_id', 'marca_id');
}
    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('modelo_situacion', 1);
    }

    public function scopeInactivos($query)
    {
        return $query->where('modelo_situacion', 0);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where('modelo_descripcion', 'like', '%' . $termino . '%');
    }

    public function scopePorMarca($query, $marcaId)
    {
        return $query->where('modelo_marca_id', $marcaId);
    }

    // Accessors
    public function getEstadoTextoAttribute()
    {
        return $this->modelo_situacion == 1 ? 'Activo' : 'Inactivo';
    }

    public function getNombreCompletoAttribute()
    {
        return $this->marca->marca_descripcion . ' ' . $this->modelo_descripcion;
    }
}