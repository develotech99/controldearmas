<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ventas extends Model
{
    use HasFactory;

    protected $table = 'pro_ventas';
    protected $primaryKey = 'ven_id';
    public $timestamps = true;

    protected $fillable = [
        'ven_user',
        'ven_fecha',
        'ven_cliente',
        'ven_empresa_id', // Nueva columna
        'ven_total_vendido',
        'ven_descuento',
        'ven_situacion',
        'ven_observaciones',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'ven_cliente', 'cliente_id');
    }

    public function empresa()
    {
        return $this->belongsTo(ClienteEmpresa::class, 'ven_empresa_id', 'emp_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVentas::class, 'det_ven_id', 'ven_id');
    }
}