<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteEmpresa extends Model
{
    use HasFactory;

    protected $table = 'pro_clientes_empresas';
    protected $primaryKey = 'emp_id';
    public $timestamps = true;


    public function ventas()
    {
        return $this->hasMany(Ventas::class, 'ven_empresa_id', 'emp_id');
    }
}
