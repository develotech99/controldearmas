<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersUbicacion extends Model
{
    use HasFactory;

    protected $table = 'users_ubicaciones';
    protected $primaryKey = 'ubi_id';

    protected $fillable = [
        'ubi_user',
        'empresa_id',
        'ubi_latitud',
        'ubi_longitud',
        'ubi_descripcion',
        'ubi_foto'
    ];
    
    public function cliente(){
        return $this->belongsTo(Clientes::class, 'ubi_user', 'cliente_id');
    }

    public function empresa() {
        return $this->belongsTo(ClienteEmpresa::class, 'empresa_id', 'emp_id');
    }
}