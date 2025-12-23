<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UsersHistorialVisita extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'users_historial_visitas';
    protected $primaryKey = 'hist_id';

    protected $fillable = [
        'hist_visita_id',
        'hist_fecha_actualizacion',
        'hist_estado_anterior',
        'hist_estado_nuevo',
        'hist_total_venta_anterior',
        'hist_total_venta_nuevo',
        'hist_descripcion',
    ];

    protected $casts = [
        'hist_fecha_actualizacion' => 'datetime',
        'hist_total_venta_anterior' => 'decimal:2',
        'hist_total_venta_nuevo' => 'decimal:2',
    ];

    // Relación: Un historial pertenece a una visita
    public function visita()
    {
        return $this->belongsTo(UsersVisitas::class, 'hist_visita_id', 'visita_id');
    }
}