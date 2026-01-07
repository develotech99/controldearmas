<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use Notifiable;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;

    const CREATED_AT = 'user_fecha_creacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'user_primer_nombre',
        'user_segundo_nombre',
        'user_primer_apellido',
        'user_segundo_apellido',
        'email',
        'password',
        'user_dpi_dni',
        'user_rol',
        'user_foto',
        'user_token',
        'user_fecha_verificacion',
        'user_situacion',
        'remember_token', 
        'user_empresa',
    ];

    protected $hidden = ['password', 'remember_token', 'user_token'];

    protected function casts(): array
    {
        return [
            'user_fecha_verificacion' => 'datetime',
            'user_fecha_creacion'     => 'datetime',
            'password' => 'hashed', 
        ];
    }

    // Nombre completo virtual
    protected function name(): Attribute
    {
        return Attribute::get(function () {
            return trim(
                implode(' ', array_filter([
                    $this->user_primer_nombre,
                    $this->user_segundo_nombre,
                    $this->user_primer_apellido,
                    $this->user_segundo_apellido,
                ]))
            );
        });
    }

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'user_rol', 'id');
    }

    // Relación con comisiones
    public function comisiones()
    {
        return $this->hasMany(ProPorcentajeVendedor::class, 'porc_vend_user_id', 'user_id');
    }

    // Relación con ventas (como vendedor)
    public function ventas()
    {
        return $this->hasMany(ProVenta::class, 'ven_user', 'user_id');
    }

    

    // Asegurar que Laravel sepa el nombre de la columna remember_token
    public function getRememberTokenName()
    {
        return 'remember_token';
    }
    
}