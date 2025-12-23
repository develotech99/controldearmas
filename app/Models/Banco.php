<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Banco extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_bancos';
    protected $primaryKey = 'banco_id';

    protected $fillable = [
        'banco_nombre',
        'banco_activo',
    ];

    protected $casts = [
        'banco_activo' => 'boolean',
    ];
}
