<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo pivote para la relación entre Alertas y Roles
 * Define qué roles pueden ver cada alerta específica
 */
class AlertaRol extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_alertas_roles';
    protected $primaryKey = 'alerta_rol_id';
    
    // No necesita timestamps
    public $timestamps = false;

    protected $fillable = [
        'alerta_id',
        'rol_id'
    ];

    protected $casts = [
        'alerta_id' => 'integer',
        'rol_id' => 'integer'
    ];

    // ========================
    // RELACIONES
    // ========================

    /**
     * Relación con la alerta
     */
    public function alerta(): BelongsTo
    {
        return $this->belongsTo(Alerta::class, 'alerta_id', 'alerta_id');
    }

    /**
     * Relación con el rol
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'id');
    }

    // ========================
    // MÉTODOS ESTÁTICOS
    // ========================

    /**
     * Asignar múltiples roles a una alerta
     */
    public static function asignarRoles(int $alertaId, array $rolesIds): void
    {
        // Eliminar asignaciones existentes
        self::where('alerta_id', $alertaId)->delete();
        
        // Crear nuevas asignaciones
        foreach ($rolesIds as $rolId) {
            self::create([
                'alerta_id' => $alertaId,
                'rol_id' => $rolId
            ]);
        }
    }

    /**
     * Obtener roles asignados a una alerta
     */
    public static function rolesDeAlerta(int $alertaId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('alerta_id', $alertaId)
            ->with('rol')
            ->get()
            ->pluck('rol');
    }

    /**
     * Obtener alertas para un rol específico
     */
    public static function alertasDeRol(int $rolId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('rol_id', $rolId)
            ->with('alerta')
            ->get()
            ->pluck('alerta');
    }
}