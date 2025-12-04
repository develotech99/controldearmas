<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    use HasFactory;
    protected $table = 'pro_clientes';
    protected $primaryKey = 'cliente_id';
    public $timestamps = true;

    // Definir los campos que son asignables masivamente
    protected $fillable = [
        'cliente_nombre1',
        'cliente_nombre2',
        'cliente_apellido1',
        'cliente_apellido2',
        'cliente_dpi',
        'cliente_nit',
        'cliente_direccion',
        'cliente_telefono',
        'cliente_correo',
        'cliente_tipo',
        'cliente_situacion',
        'cliente_user_id',
        'cliente_nom_empresa',
        'cliente_nom_vendedor',
        'cliente_cel_vendedor',
        'cliente_ubicacion',
        'cliente_pdf_licencia',
    ];




    public function scopeActivos($query)
    {
        return $query->where('cliente_situacion', 1);
    }

    public function empresas()
    {
        return $this->hasMany(ClienteEmpresa::class, 'emp_cliente_id', 'cliente_id');
    }

    public function saldo()
    {
        return $this->hasOne(ClienteSaldo::class, 'saldo_cliente_id', 'cliente_id');
    }
}
