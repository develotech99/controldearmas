<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Facturacion extends Model
{
    use SoftDeletes;

    protected $table = 'facturacion';
    protected $primaryKey = 'fac_id';

    protected $fillable = [
        'fac_venta_id',
        'fac_uuid',
        'fac_referencia',
        'fac_serie',
        'fac_numero',
        'fac_estado',
        'fac_tipo_documento',
        'fac_nit_receptor',
        'fac_cui_receptor',
        'fac_receptor_nombre',
        'fac_receptor_direccion',
        'fac_receptor_email',
        'fac_receptor_telefono',
        'fac_fecha_emision',
        'fac_fecha_certificacion',
        'fac_fecha_anulacion',
        'fac_subtotal',
        'fac_descuento',
        'fac_impuestos',
        'fac_total',
        'fac_moneda',
        'fac_xml_enviado_path',
        'fac_xml_certificado_path',
        'fac_xml_anulacion_path',
        'fac_uuid_anulacion',
        'fac_motivo_anulacion',
        'fac_errores',
        'fac_alertas',
        'fac_observaciones',
        'fac_operacion',
        'fac_vendedor',
        'fac_usuario_id',
        'fac_fecha_operacion',
        'fac_veces_impreso',
        'fac_fecha_primera_impresion',
        'fac_fecha_ultima_impresion',
        'fac_enviado_email',
        'fac_fecha_envio_email',
    ];

    protected $casts = [
        'fac_fecha_emision' => 'datetime',
        'fac_fecha_certificacion' => 'datetime',
        'fac_fecha_anulacion' => 'datetime',
        'fac_fecha_operacion' => 'datetime',
        'fac_fecha_primera_impresion' => 'datetime',
        'fac_fecha_ultima_impresion' => 'datetime',
        'fac_fecha_envio_email' => 'datetime',
        'fac_subtotal' => 'decimal:2',
        'fac_descuento' => 'decimal:2',
        'fac_impuestos' => 'decimal:2',
        'fac_total' => 'decimal:2',
        'fac_errores' => 'array',
        'fac_alertas' => 'array',
        'fac_enviado_email' => 'boolean',
        'fac_veces_impreso' => 'integer',
    ];

    /**
     * Convierte fechas del formato d/m/Y H:i:s a formato compatible con Carbon
     */
    private function parseFechaFEL($value)
    {
        if (is_string($value) && preg_match('/^\d{2}\/\d{2}\/\d{4}/', $value)) {
            // Detectar si tiene hora o solo fecha
            if (strpos($value, ':') !== false) {
                return Carbon::createFromFormat('d/m/Y H:i:s', $value);
            } else {
                return Carbon::createFromFormat('d/m/Y', $value);
            }
        }
        return $value;
    }

    // Mutadores para fac_fecha_emision
    public function setFacFechaEmisionAttribute($value)
    {
        $this->attributes['fac_fecha_emision'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_certificacion
    public function setFacFechaCertificacionAttribute($value)
    {
        $this->attributes['fac_fecha_certificacion'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_anulacion
    public function setFacFechaAnulacionAttribute($value)
    {
        $this->attributes['fac_fecha_anulacion'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_operacion
    public function setFacFechaOperacionAttribute($value)
    {
        $this->attributes['fac_fecha_operacion'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_primera_impresion
    public function setFacFechaPrimeraImpresionAttribute($value)
    {
        $this->attributes['fac_fecha_primera_impresion'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_ultima_impresion
    public function setFacFechaUltimaImpresionAttribute($value)
    {
        $this->attributes['fac_fecha_ultima_impresion'] = $this->parseFechaFEL($value);
    }

    // Mutadores para fac_fecha_envio_email
    public function setFacFechaEnvioEmailAttribute($value)
    {
        $this->attributes['fac_fecha_envio_email'] = $this->parseFechaFEL($value);
    }

    public function detalle()
    {
        return $this->hasMany(FacturacionDetalle::class, 'det_fac_factura_id', 'fac_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'fac_usuario_id', 'user_id');
    }

    public function venta()
    {
        return $this->belongsTo(\App\Models\Ventas::class, 'fac_venta_id', 'ven_id');
    }
}