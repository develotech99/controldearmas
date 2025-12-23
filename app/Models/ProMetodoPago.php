<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class ProMetodoPago extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_metodos_pago';
    protected $primaryKey = 'metpago_id';
    public $timestamps = true;

    protected $fillable = [
        'metpago_descripcion',
        'metpago_situacion'
    ];

    protected $casts = [
        'metpago_situacion' => 'integer'
    ];

    public function detallesPago(): HasMany
    {
        return $this->hasMany(ProDetallePago::class, 'det_pago_metodo_pago', 'metpago_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('metpago_situacion', 1);
    }
}