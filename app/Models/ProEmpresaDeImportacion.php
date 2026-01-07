<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ProEmpresaDeImportacion extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    // Especificar el nombre de la tabla
    protected $table = 'pro_empresas_de_importacion';
    
    // Especificar la primary key personalizada
    protected $primaryKey = 'empresaimp_id';
    
    // Si tu primary key no es auto-incrementable, descomenta la siguiente línea:
    // public $incrementing = false;
    
    // Si tu primary key no es un entero, especifica el tipo:
    // protected $keyType = 'string';

    protected $fillable = [
        'empresaimp_pais',
        'empresaimp_descripcion', 
        'empresaimp_situacion'
    ];

    protected $casts = [
        'empresaimp_situacion' => 'integer'
    ];

    // Relación con el modelo País
    public function pais()
    {
        return $this->belongsTo(Pais::class, 'empresaimp_pais', 'pais_id');
    }
}