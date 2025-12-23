<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class MetodoPago extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_metodos_pago';
    protected $primaryKey = 'metpago_id';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'metpago_descripcion',
        'metpago_situacion',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metpago_situacion' => 'integer',
    ];

    /**
     * Scope para obtener solo métodos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('metpago_situacion', 1);
    }

    /**
     * Accessor para obtener el estado como texto
     */
    public function getEstadoTextoAttribute()
    {
        return $this->metpago_situacion == 1 ? 'Activo' : 'Inactivo';
    }
}