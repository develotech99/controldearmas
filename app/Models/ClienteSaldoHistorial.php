<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ClienteSaldoHistorial extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_clientes_saldo_historial';
    protected $primaryKey = 'hist_id';

    protected $fillable = [
        'hist_cliente_id',
        'hist_tipo',
        'hist_monto',
        'hist_saldo_anterior',
        'hist_saldo_nuevo',
        'hist_referencia',
        'hist_observaciones'
    ];

    protected $casts = [
        'hist_monto' => 'decimal:2',
        'hist_saldo_anterior' => 'decimal:2',
        'hist_saldo_nuevo' => 'decimal:2'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'hist_cliente_id', 'cliente_id');
    }
}
