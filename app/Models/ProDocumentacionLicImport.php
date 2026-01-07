<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ProDocumentacionLicImport extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    protected $table = 'pro_documentacion_lic_import';
    protected $primaryKey = 'doclicimport_id';

    protected $fillable = [
        'doclicimport_ruta',
        'doclicimport_num_lic',
        'doclicimport_situacion',
    ];

    public function licencia()
    {
        return $this->belongsTo(ProLicenciaParaImportacion::class, 'doclicimport_num_lic', 'lipaimp_id');
    }
}
