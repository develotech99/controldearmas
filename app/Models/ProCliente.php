<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ProCliente extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_clientes';
    protected $primaryKey = 'cliente_id';
    public $timestamps = true;

    protected $fillable = [
        'cliente_nombre1',
        'cliente_nombre2',
        'cliente_apellido1',
        'cliente_apellido2',
        'cliente_dpi',
        'cliente_nit',
        'cliente_telefono',
        'cliente_direccion',
        'cliente_correo',
        'cliente_tipo_dpi',
        'cliente_situacion'
    ];

    protected $casts = [
        'cliente_situacion' => 'integer'
    ];

    public function ventas(): HasMany
    {
        return $this->hasMany(ProVenta::class, 'ven_cliente', 'cliente_id');
    }

    // Accessors
    public function getNombreCompletoAttribute()
    {
        return trim("{$this->cliente_nombre1} {$this->cliente_nombre2} {$this->cliente_apellido1} {$this->cliente_apellido2}");
    }

    public function scopeActivos($query)
    {
        return $query->where('cliente_situacion', 1);
    }
}