<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ProLicenciaParaImportacion extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_licencias_para_importacion';
    protected $primaryKey = 'lipaimp_id';
    public $incrementing = true;     // ✅ Ahora es Auto Increment
    protected $keyType = 'int';
    public $timestamps = true;

    // Estados
    public const SITUACION_PENDIENTE   = 1;
    public const SITUACION_AUTORIZADO  = 2;
    public const SITUACION_RECHAZADO   = 3;
    public const SITUACION_EN_TRANSITO = 4;
    public const SITUACION_RECIBIDO    = 5;

        // ✅ Mapa de estados
    public const ESTADOS = [

            1 => 'Pendiente',
        2 => 'Autorizado',
        3 => 'Rechazado',
        4 => 'En tránsito',
        5 => 'Recibido',
        6 => 'Vencido',
        7 => 'Recibido vencido',

    ];

    protected $fillable = [
        // 'lipaimp_id', // Ya no es fillable porque es auto-increment
        'lipaimp_numero', // ✅ Nuevo campo
        'lipaimp_poliza',
        'lipaimp_descripcion',
        'lipaimp_fecha_emision',
        'lipaimp_fecha_vencimiento',
        'lipaimp_observaciones',
        'lipaimp_situacion',
    ];

    protected $casts = [
        'lipaimp_id'                => 'integer',
        'lipaimp_numero'            => 'string',
        'lipaimp_poliza'            => 'string',
        'lipaimp_descripcion'       => 'string',
        'lipaimp_fecha_emision'     => 'date',
        'lipaimp_fecha_vencimiento' => 'date',
        'lipaimp_observaciones'     => 'string',
        'lipaimp_situacion'         => 'integer',
    ];

    protected $appends = ['situacion_texto', 'esta_vigente', 'cantidad_total_armas'];

    /* ------------ Relaciones ------------ */

    // FK en pro_armas_licenciadas: arma_licencia_id → lipaimp_id
    public function armas()
    {
        return $this->hasMany(\App\Models\ProArmaLicenciada::class, 'arma_num_licencia', 'lipaimp_id');
    }

    /* ------------ Accessors ------------ */

    public function getSituacionTextoAttribute(): string
    {
        return match ((int) $this->lipaimp_situacion) {
            self::SITUACION_PENDIENTE   => 'Pendiente',
            self::SITUACION_AUTORIZADO  => 'Autorizado',
            self::SITUACION_RECHAZADO   => 'Rechazado',
            self::SITUACION_EN_TRANSITO => 'En tránsito',
            self::SITUACION_RECIBIDO    => 'Recibido',
            default => 'Desconocido',
        };
    }

    public function getEstaVigenteAttribute(): bool
    {
        if (empty($this->lipaimp_fecha_vencimiento)) return true;
        return Carbon::today()->lessThanOrEqualTo(Carbon::parse($this->lipaimp_fecha_vencimiento));
    }

    // Para la columna “Cantidad” del listado (suma de arma_cantidad)
    public function getCantidadTotalArmasAttribute(): int
    {
        // Usa colección cargada si existe; si no, consulta (evita N+1 si usas ->with('armas'))
        return $this->relationLoaded('armas')
            ? (int) $this->armas->sum('arma_cantidad')
            : (int) $this->armas()->sum('arma_cantidad');
    }

    /* ------------ Scopes ------------ */

    public function scopeSituacion($query, int $situacion)
    {
        return $query->where('lipaimp_situacion', $situacion);
    }

    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('lipaimp_fecha_vencimiento')
              ->orWhere('lipaimp_fecha_vencimiento', '>=', Carbon::today()->toDateString());
        });
    }

    public function scopeExpiradas($query)
    {
        return $query->whereNotNull('lipaimp_fecha_vencimiento')
                     ->where('lipaimp_fecha_vencimiento', '<', Carbon::today()->toDateString());
    }

    public function scopeBuscar($query, ?string $term)
    {
        if (!$term) return $query;
        $q = trim($term);

        return $query->where(function ($sub) use ($q) {
            $sub->where('lipaimp_descripcion', 'like', "%{$q}%")
                ->orWhere('lipaimp_observaciones', 'like', "%{$q}%")
                ->orWhere('lipaimp_numero', 'like', "%{$q}%") // ✅ Buscar por número oficial
                ->orWhere('lipaimp_id', $q); // búsqueda por ID exacto (opcional)
        });
    }
    // app/Models/ProLicenciaParaImportacion.php
public function documentos()
{
    return $this->hasMany(\App\Models\ProDocumentacionLicImport::class, 'doclicimport_num_lic', 'lipaimp_id');
}

}
