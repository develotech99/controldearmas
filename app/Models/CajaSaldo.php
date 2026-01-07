<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;

class CajaSaldo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'caja_saldos';
    protected $primaryKey = 'caja_saldo_id';

    // Usamos timestamp de BD, no los de Laravel
    public $timestamps = false;

    protected $fillable = [
        'caja_saldo_metodo_pago',
        'caja_saldo_moneda',
        'caja_saldo_monto_actual',
    ];

    protected $casts = [
        'caja_saldo_metodo_pago'   => 'integer',
        'caja_saldo_monto_actual'  => 'decimal:2',
        'caja_saldo_actualizado'   => 'datetime',
    ];

    // Relaciones
    public function metodoPago()
    {
        // Ajusta el namespace del modelo si lo tienes diferente
        return $this->belongsTo(\App\Models\ProMetodoPago::class, 'caja_saldo_metodo_pago', 'metpago_id');
    }

    // Scopes útiles
    public function scopeDeMetodo(Builder $q, int $metodoId, string $moneda = 'GTQ'): Builder
    {
        return $q->where('caja_saldo_metodo_pago', $metodoId)
            ->where('caja_saldo_moneda', $moneda);
    }

    // Helpers de dominio
    public static function ensureRow(int $metodoId, string $moneda = 'GTQ'): self
    {
        return static::firstOrCreate(
            ['caja_saldo_metodo_pago' => $metodoId, 'caja_saldo_moneda' => $moneda],
            ['caja_saldo_monto_actual' => 0]
        );
    }

    public function addAmount(float $monto): self
    {
        $this->increment('caja_saldo_monto_actual', $monto);
        return $this->refresh();
    }

    public function subtractAmount(float $monto): self
    {
        $this->decrement('caja_saldo_monto_actual', $monto);
        return $this->refresh();
    }
}
