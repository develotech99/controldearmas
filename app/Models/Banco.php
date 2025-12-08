<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;

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
