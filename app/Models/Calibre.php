<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Calibre extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_calibres';
    protected $primaryKey = 'calibre_id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'calibre_nombre',
        'calibre_unidad_id',
        'calibre_equivalente_mm',
        'calibre_situacion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'calibre_unidad_id' => 'integer',
        'calibre_equivalente_mm' => 'decimal:2',
        'calibre_situacion' => 'integer',
    ];

    /**
     * Scope para obtener solo calibres activos
     */
    public function scopeActivos($query)
    {
        return $query->where('calibre_situacion', 1);
    }

    /**
     * Accessor para obtener el estado como texto
     */
    public function getEstadoTextoAttribute()
    {
        return $this->calibre_situacion == 1 ? 'Activo' : 'Inactivo';
    }

    /**
     * Accessor para mostrar nombre completo con unidad
     */
    public function getNombreCompletoAttribute()
    {
        $unidad = $this->unidadMedida ? $this->unidadMedida->unidad_abreviacion : '';
        return "{$this->calibre_nombre} {$unidad}";
    }

    /**
     * Accessor para mostrar equivalente formateado
     */
    public function getEquivalenteFormateadoAttribute()
    {
        if ($this->calibre_equivalente_mm) {
            return "{$this->calibre_equivalente_mm} mm";
        }
        return 'No especificado';
    }

    /**
     * Relación con unidad de medida
     */
    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'calibre_unidad_id', 'unidad_id');
    }
}