<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

// app/Models/PagoLicencia.php
class PagoLicencia extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_pagos_licencias';
    protected $primaryKey = 'pago_lic_id';
    
    protected $fillable = [
        'pago_lic_licencia_id',  // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
        'pago_lic_total',        // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
        'pago_lic_situacion'     // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
    ];

    protected $casts = [
        'pago_lic_total' => 'decimal:2',
        'pago_lic_situacion' => 'integer',
    ];

    public function metodos() 
    {
        return $this->hasMany(PagoMetodo::class, 'pagomet_pago_lic', 'pago_lic_id');
    }
}

// app/Models/PagoMetodo.php
class PagoMetodo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_pagos_lic_metodos';
    protected $primaryKey = 'pagomet_id';
    
    protected $fillable = [
        'pagomet_pago_lic',
        'pagomet_metodo',
        'pagomet_monto',
        'pagomet_moneda',
        'pagomet_referencia',
        'pagomet_banco',
        'pagomet_nota',
        'pagomet_situacion'
    ];

    protected $casts = [
        'pagomet_monto' => 'decimal:2',
        'pagomet_situacion' => 'integer',
    ];

    public function comprobantes() 
    {
        
        return $this->hasMany(PagoComprobante::class, 'comprob_pagomet_id', 'pagomet_id');
    }

    public function pagoLicencia()
    {
        return $this->belongsTo(PagoLicencia::class, 'pagomet_pago_lic', 'pago_lic_id');
    }
}

// app/Models/PagoComprobante.php
// app/Models/PagoComprobante.php
// app/Models/PagoComprobante.php


class PagoComprobante extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_comprobantes_pago_licencias'; // tu tabla real
    protected $primaryKey = 'comprob_id';
    public $timestamps = true;

    // IMPORTANTE: incluir el FK real en fillable (o usar $guarded = [])
    protected $fillable = [
        'comprob_pagomet_id',      // 👈 FK REAL
        'comprob_ruta',
        'comprob_nombre_original',
        'comprob_size_bytes',
        'comprob_mime',
        'comprob_situacion',
    ];

    // Alternativa de debug (si prefieres no batallar con fillable):
    // protected $guarded = []; // <- abre todo a asignación masiva

    public function metodo()
    {
        return $this->belongsTo(PagoMetodo::class, 'comprob_pagomet_id', 'pagomet_id');
    }
}
