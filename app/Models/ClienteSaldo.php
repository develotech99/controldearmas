<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ClienteSaldo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_clientes_saldo';
    protected $primaryKey = 'saldo_id';

    protected $fillable = [
        'saldo_cliente_id',
        'saldo_monto'
    ];

    protected $casts = [
        'saldo_monto' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'saldo_cliente_id', 'cliente_id');
    }
}
