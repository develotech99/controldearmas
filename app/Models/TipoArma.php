<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class TipoArma extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    // Nombre de la tabla
    protected $table = 'pro_clases_pistolas';

    // Clave primaria
    protected $primaryKey = 'clase_id';

    protected $fillable = [
        'clase_descripcion',
        'clase_situacion',
    ];
    
    public function scopeActivos($query)
    {
        return $query->where('clase_situacion', 1);
    }
    
}
