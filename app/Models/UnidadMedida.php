<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UnidadMedida extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_unidades_medida';
    protected $primaryKey = 'unidad_id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unidad_nombre',
        'unidad_abreviacion',
        'unidad_tipo',
        'unidad_situacion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unidad_situacion' => 'integer',
    ];

    /**
     * Scope para obtener solo unidades activas
     */
    public function scopeActivos($query)
    {
        return $query->where('unidad_situacion', 1);
    }

    /**
     * Accessor para obtener el estado como texto
     */
    public function getEstadoTextoAttribute()
    {
        return $this->unidad_situacion == 1 ? 'Activo' : 'Inactivo';
    }

    /**
     * Accessor para mostrar nombre completo con abreviación
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->unidad_nombre} ({$this->unidad_abreviacion})";
    }

    /**
     * Relación con calibres
     */
    public function calibres()
    {
        return $this->hasMany(Calibre::class, 'calibre_unidad_id', 'unidad_id');
    }

    /**
     * Tipos de unidad disponibles
     */
    public static function getTipos()
    {
        return [
            'longitud' => 'Longitud',
            'peso' => 'Peso',
            'volumen' => 'Volumen',
            'otro' => 'Otro'
        ];
    }
}