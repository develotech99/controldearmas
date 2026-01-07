<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

// app/Models/ProLicencia.php
class ProLicencia extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_licencias_para_importacion';
    protected $primaryKey = 'lipaimp_id';
    public $timestamps = false;

    public function pagos() {
        return $this->hasMany(ProPagoLicencia::class, 'pago_lic_licencia_id', 'lipaimp_id');
    }

    public function totalPagado() {
        return $this->hasOne(ProLicenciaTotalPagado::class, 'lic_id', 'lipaimp_id');
    }
}


///
// app/Models/ProPagoLicencia.php
class ProPagoLicencia extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_pagos_licencias';
    protected $primaryKey = 'pago_lic_id';

    public function metodos() {
        return $this->hasMany(ProPagoLicMetodo::class, 'pagomet_pago_lic', 'pago_lic_id');
    }

    public function licencia() {
        return $this->belongsTo(ProLicencia::class, 'pago_lic_licencia_id', 'lipaimp_id');
    }
}

// app/Models/ProPagoLicMetodo.php
class ProPagoLicMetodo extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_pagos_lic_metodos';
    protected $primaryKey = 'pagomet_id';

    public function pago() {
        return $this->belongsTo(ProPagoLicencia::class, 'pagomet_pago_lic', 'pago_lic_id');
    }
}

// app/Models/ProLicenciaTotalPagado.php
class ProLicenciaTotalPagado extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_licencias_total_pagado';
    protected $primaryKey = 'lic_id';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['lic_id', 'total_pagado', 'updated_at'];
}

