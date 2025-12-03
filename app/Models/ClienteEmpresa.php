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

    protected $fillable = [
        'emp_cliente_id',
        'emp_nombre',
        'emp_nit',
        'emp_direccion',
        'emp_telefono',
        'emp_situacion',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'emp_cliente_id', 'cliente_id');
    }

    public function ventas()
    {
        return $this->hasMany(Ventas::class, 'ven_empresa_id', 'emp_id');
    }
}
