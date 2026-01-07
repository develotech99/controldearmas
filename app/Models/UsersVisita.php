<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class UsersVisita extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'users_visitas';
    protected $primaryKey = 'visita_id';

    protected $fillable = [
        'visita_user',
        'visita_fecha',
        'visita_estado',
        'visita_venta',
        'visita_descripcion',
    ];

    protected $casts = [
        'visita_fecha' => 'datetime',
        'visita_venta' => 'decimal:2',
    ];

    // Relación: Una visita pertenece a un cliente
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'visita_user', 'cliente_id');
    }

    // Relación: Una visita tiene muchos historiales
    public function historiales()
    {
        return $this->hasMany(UsersHistorialVisitas::class, 'hist_visita_id', 'visita_id');
    }
}