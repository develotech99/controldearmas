<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Pais extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_paises';
    protected $primaryKey = 'pais_id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'pais_descripcion',
        'pais_situacion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'pais_situacion' => 'integer',
    ];

    /**
     * Scope para obtener solo países activos
     */
    public function scopeActivos($query)
    {
        return $query->where('pais_situacion', 1);
    }

    /**
     * Accessor para obtener el estado como texto
     */
    public function getEstadoTextoAttribute()
    {
        return $this->pais_situacion == 1 ? 'Activo' : 'Inactivo';
    }
}