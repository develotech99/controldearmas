<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ClienteEmpresa extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_clientes_empresas';
    protected $primaryKey = 'emp_id';
    public $timestamps = true;

    protected $fillable = [
        'emp_cliente_id',
        'emp_nombre',
        'emp_nit',
        'emp_direccion',
        'emp_nom_vendedor',
        'emp_cel_vendedor',
        'emp_telefono',
        'emp_licencia_compraventa',
        'emp_licencia_vencimiento',
        'emp_situacion',
    ];


    public function ventas()
    {
        return $this->hasMany(Ventas::class, 'ven_empresa_id', 'emp_id');
    }
}
