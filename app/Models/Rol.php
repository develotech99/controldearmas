<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Rol extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'roles';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];


    

    public function usuarios()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

}