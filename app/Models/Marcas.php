<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Marcas extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_marcas';
    
    protected $primaryKey = 'marca_id';

    protected $fillable = [
        'marca_descripcion',
        'marca_situacion'
    ];


    public function scopeActivos($query)
    {
        return $query->where('marca_situacion', 1);
    }
    
}