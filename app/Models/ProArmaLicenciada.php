<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Calibre;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ProArmaLicenciada extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_armas_licenciadas';
    protected $primaryKey = 'arma_lic_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // 👈 tu tabla no tiene created_at/updated_at

    // 👇 Renombramos para evitar colisiones
     protected $fillable = [
        'arma_num_licencia',   // FK → pro_licencias_para_importacion.lipaimp_id
        'arma_sub_cat',        // FK → pro_subcategorias.subcategoria_id
        'arma_modelo',         // FK → pro_modelo.modelo_id
        'arma_empresa',  
        'arma_calibre',   // <-- agrega      // FK → pro_empresas_de_importacion.empresaimp_id
        'arma_largo_canon',
        'arma_cantidad',
    ];
    protected $casts = [
        'arma_num_licencia' => 'integer',
        'arma_sub_cat'      => 'integer',
        'arma_modelo'       => 'integer',
        'arma_empresa'      => 'integer',
        'arma_largo_canon'  => 'decimal:2',
        'arma_cantidad'     => 'integer',
    ];

    protected $appends = ['subcategoria_nombre', 'modelo_nombre', 'empresa_nombre'];

    /* ------------ Relaciones ------------ */

    public function licencia()
    {
        return $this->belongsTo(ProLicenciaParaImportacion::class, 'arma_num_licencia', 'lipaimp_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'arma_sub_cat', 'subcategoria_id');
    }

    // 👇 Renombrada: evita choque con algún atributo "modelo"
    public function modelo()
    {
        return $this->belongsTo(ProModelo::class, 'arma_modelo', 'modelo_id');
    }

    public function empresa()
    {
        return $this->belongsTo(ProEmpresaDeImportacion::class, 'arma_empresa', 'empresaimp_id');
    }



public function calibre()
{
    return $this->belongsTo(Calibre::class, 'arma_calibre', 'calibre_id');
    // Si tu columna fuera 'arma_calibre' usa:
    // return $this->belongsTo(ProCalibre::class, 'arma_calibre', 'calibre_id');
}

    public function getSubcategoriaNombreAttribute(): ?string
    {
        return $this->subcategoria?->subcategoria_nombre
            ?? $this->subcategoria?->nombre
            ?? null;
    }

    public function getModeloNombreAttribute(): ?string
    {
        // Tu tabla de modelos (según el controller) usa "modelo_descripcion"
        return $this->modelo?->modelo_descripcion
            ?? $this->modelo?->modelo_nombre
            ?? $this->modelo?->nombre
            ?? null;
    }

    public function getEmpresaNombreAttribute(): ?string
    {
        return $this->empresa?->empresaimp_razon_social
            ?? $this->empresa?->empresaimp_descripcion
            ?? $this->empresa?->nombre
            ?? null;
    }
}
