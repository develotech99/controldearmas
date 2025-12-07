<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteDocumento extends Model
{
    use HasFactory;

    protected $table = 'pro_clientes_documentos';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'numero_documento',
        'numero_secundario',
        'fecha_vencimiento',
        'imagen_path',
        'estado'
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'estado' => 'boolean'
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente_id', 'cliente_id');
    }
}
