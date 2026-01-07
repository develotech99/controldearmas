<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Modelo para el sistema de alertas del inventario
 * Maneja notificaciones para stock bajo, vencimientos, etc.
 */
class Alerta extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_alertas';
    protected $primaryKey = 'alerta_id';
    
    // Solo created_at, no updated_at según el schema
    const CREATED_AT = 'alerta_fecha';
    const UPDATED_AT = null;
    
    public $timestamps = false;

    protected $fillable = [
        'alerta_tipo',
        'alerta_titulo',
        'alerta_mensaje',
        'alerta_prioridad',
        'alerta_producto_id',
        'alerta_usuario_id',
        'alerta_para_todos',
        'alerta_vista',
        'alerta_resuelta',
        'email_enviado'
    ];

    protected $casts = [
        'alerta_producto_id' => 'integer',
        'alerta_usuario_id' => 'integer',
        'alerta_para_todos' => 'boolean',
        'alerta_vista' => 'boolean',
        'alerta_resuelta' => 'boolean',
        'email_enviado' => 'boolean',
        'alerta_fecha' => 'datetime'
    ];

    // CONSTANTES para tipos de alerta
    const TIPO_STOCK_BAJO = 'stock_bajo';
    const TIPO_STOCK_AGOTADO = 'stock_agotado';
    const TIPO_PRECIO_VENCIDO = 'precio_vencido';
    const TIPO_SERIE_DUPLICADA = 'serie_duplicada';
    const TIPO_PRODUCTO_VENCIDO = 'producto_vencido';
    const TIPO_SISTEMA = 'sistema';

    const TIPOS_VALIDOS = [
        self::TIPO_STOCK_BAJO,
        self::TIPO_STOCK_AGOTADO,
        self::TIPO_PRECIO_VENCIDO,
        self::TIPO_SERIE_DUPLICADA,
        self::TIPO_PRODUCTO_VENCIDO,
        self::TIPO_SISTEMA
    ];

    // CONSTANTES para prioridades
    const PRIORIDAD_BAJA = 'baja';
    const PRIORIDAD_MEDIA = 'media';
    const PRIORIDAD_ALTA = 'alta';
    const PRIORIDAD_CRITICA = 'critica';

    const PRIORIDADES_VALIDAS = [
        self::PRIORIDAD_BAJA,
        self::PRIORIDAD_MEDIA,
        self::PRIORIDAD_ALTA,
        self::PRIORIDAD_CRITICA
    ];

    // ========================
    // RELACIONES
    // ========================

    /**
     * Relación con el producto (opcional)
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'alerta_producto_id', 'producto_id');
    }

    /**
     * Relación con el usuario específico (opcional)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alerta_usuario_id', 'user_id');
    }

    /**
     * Relación muchos a muchos con roles específicos
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Rol::class,
            'pro_alertas_roles',
            'alerta_id',
            'rol_id',
            'alerta_id',
            'id'
        );
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Scope para alertas no vistas
     */
    public function scopeNoVistas($query)
    {
        return $query->where('alerta_vista', false);
    }

    /**
     * Scope para alertas no resueltas
     */
    public function scopeNoResueltas($query)
    {
        return $query->where('alerta_resuelta', false);
    }

    /**
     * Scope para alertas por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('alerta_tipo', $tipo);
    }

    /**
     * Scope para alertas por prioridad
     */
    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('alerta_prioridad', $prioridad);
    }

    /**
     * Scope para alertas de alta prioridad
     */
    public function scopeAltaPrioridad($query)
    {
        return $query->whereIn('alerta_prioridad', [self::PRIORIDAD_ALTA, self::PRIORIDAD_CRITICA]);
    }

    /**
     * Scope para alertas para todos los roles
     */
    public function scopeParaTodos($query)
    {
        return $query->where('alerta_para_todos', true);
    }

    /**
     * Scope para alertas de un usuario específico
     */
    public function scopeParaUsuario($query, $usuarioId)
    {
        return $query->where('alerta_usuario_id', $usuarioId)
                    ->orWhere('alerta_para_todos', true);
    }

    /**
     * Scope para alertas de un rol específico
     */
    public function scopeParaRol($query, $rolId)
    {
        return $query->where('alerta_para_todos', true)
                    ->orWhereHas('roles', function($q) use ($rolId) {
                        $q->where('rol_id', $rolId);
                    });
    }

    /**
     * Scope para alertas recientes
     */
    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('alerta_fecha', '>=', now()->subDays($dias));
    }

    // ========================
    // MÉTODOS DE NEGOCIO
    // ========================

    /**
     * Marca la alerta como vista
     */
    public function marcarComoVista(): bool
    {
        $this->alerta_vista = true;
        return $this->save();
    }

    /**
     * Marca la alerta como resuelta
     */
    public function marcarComoResuelta(): bool
    {
        $this->alerta_resuelta = true;
        $this->alerta_vista = true;
        return $this->save();
    }

    /**
     * Marca que se envió email
     */
    public function marcarEmailEnviado(): bool
    {
        $this->email_enviado = true;
        return $this->save();
    }

    /**
     * Asigna roles específicos a la alerta
     */
    public function asignarRoles(array $rolesIds): void
    {
        $this->alerta_para_todos = false;
        $this->save();
        $this->roles()->sync($rolesIds);
    }

    /**
     * Hace la alerta visible para todos
     */
    public function hacerParaTodos(): bool
    {
        $this->alerta_para_todos = true;
        $this->roles()->detach(); // Quitar roles específicos
        return $this->save();
    }

    // ========================
    // MÉTODOS DE VERIFICACIÓN
    // ========================

    /**
     * Verifica si la alerta está vista
     */
    public function estaVista(): bool
    {
        return $this->alerta_vista === true;
    }

    /**
     * Verifica si la alerta está resuelta
     */
    public function estaResuelta(): bool
    {
        return $this->alerta_resuelta === true;
    }

    /**
     * Verifica si es de alta prioridad
     */
    public function esAltaPrioridad(): bool
    {
        return in_array($this->alerta_prioridad, [self::PRIORIDAD_ALTA, self::PRIORIDAD_CRITICA]);
    }

    /**
     * Verifica si es crítica
     */
    public function esCritica(): bool
    {
        return $this->alerta_prioridad === self::PRIORIDAD_CRITICA;
    }

    /**
     * Verifica si debe enviarse email
     */
    public function debeEnviarEmail(): bool
    {
        return !$this->email_enviado && $this->esAltaPrioridad();
    }

    /**
     * Verifica si un usuario puede ver esta alerta
     */
    public function puedeVerUsuario(User $usuario): bool
    {
        // Si es para todos, puede verla
        if ($this->alerta_para_todos) {
            return true;
        }

        // Si es para un usuario específico
        if ($this->alerta_usuario_id === $usuario->user_id) {
            return true;
        }

        // Si el rol del usuario está en los roles permitidos
        if ($usuario->user_rol && $this->roles()->where('rol_id', $usuario->user_rol)->exists()) {
            return true;
        }

        return false;
    }

    // ========================
    // ATRIBUTOS VIRTUALES
    // ========================

    /**
     * Tipo formateado para mostrar
     */
    public function getTipoFormateadoAttribute(): string
    {
        $tipos = [
            self::TIPO_STOCK_BAJO => 'Stock Bajo',
            self::TIPO_STOCK_AGOTADO => 'Stock Agotado',
            self::TIPO_PRECIO_VENCIDO => 'Precio Vencido',
            self::TIPO_SERIE_DUPLICADA => 'Serie Duplicada',
            self::TIPO_PRODUCTO_VENCIDO => 'Producto Vencido',
            self::TIPO_SISTEMA => 'Sistema'
        ];

        return $tipos[$this->alerta_tipo] ?? ucfirst(str_replace('_', ' ', $this->alerta_tipo));
    }

    /**
     * Prioridad formateada
     */
    public function getPrioridadFormateadaAttribute(): string
    {
        return ucfirst($this->alerta_prioridad);
    }

    /**
     * Clase CSS según la prioridad
     */
    public function getPrioridadClaseAttribute(): string
    {
        $clases = [
            self::PRIORIDAD_BAJA => 'bg-blue-100 text-blue-800',
            self::PRIORIDAD_MEDIA => 'bg-yellow-100 text-yellow-800',
            self::PRIORIDAD_ALTA => 'bg-orange-100 text-orange-800',
            self::PRIORIDAD_CRITICA => 'bg-red-100 text-red-800'
        ];

        return $clases[$this->alerta_prioridad] ?? 'bg-gray-100 text-gray-800';
    }

    /**
     * Icono según el tipo de alerta
     */
    public function getTipoIconoAttribute(): string
    {
        $iconos = [
            self::TIPO_STOCK_BAJO => 'fas fa-exclamation-triangle',
            self::TIPO_STOCK_AGOTADO => 'fas fa-times-circle',
            self::TIPO_PRECIO_VENCIDO => 'fas fa-dollar-sign',
            self::TIPO_SERIE_DUPLICADA => 'fas fa-clone',
            self::TIPO_PRODUCTO_VENCIDO => 'fas fa-calendar-times',
            self::TIPO_SISTEMA => 'fas fa-cog'
        ];

        return $iconos[$this->alerta_tipo] ?? 'fas fa-bell';
    }

    /**
     * Estado de la alerta (nueva, vista, resuelta)
     */
    public function getEstadoAttribute(): string
    {
        if ($this->alerta_resuelta) {
            return 'resuelta';
        } elseif ($this->alerta_vista) {
            return 'vista';
        }
        
        return 'nueva';
    }

    /**
     * Clase CSS según el estado
     */
    public function getEstadoClaseAttribute(): string
    {
        $clases = [
            'nueva' => 'bg-blue-50 border-blue-200',
            'vista' => 'bg-gray-50 border-gray-200',
            'resuelta' => 'bg-green-50 border-green-200'
        ];

        return $clases[$this->estado] ?? 'bg-white border-gray-200';
    }

    /**
     * Fecha formateada
     */
    public function getFechaFormateadaAttribute(): string
    {
        return $this->alerta_fecha->format('d/m/Y H:i');
    }

    /**
     * Hace cuántos días se generó
     */
    public function getDiasGeneradaAttribute(): int
    {
        return $this->alerta_fecha->diffInDays(now());
    }

    /**
     * Tiempo transcurrido en texto
     */
    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->alerta_fecha->diffForHumans();
    }

    // ========================
    // MÉTODOS ESTÁTICOS
    // ========================

    /**
     * Crear alerta de stock bajo
     */
    public static function crearStockBajo(Producto $producto, int $stockActual): self
    {
        return self::create([
            'alerta_tipo' => self::TIPO_STOCK_BAJO,
            'alerta_titulo' => 'Stock Bajo: ' . $producto->producto_nombre,
            'alerta_mensaje' => "El producto '{$producto->producto_nombre}' tiene stock bajo. Stock actual: {$stockActual}, mínimo: {$producto->producto_stock_minimo}",
            'alerta_prioridad' => self::PRIORIDAD_MEDIA,
            'alerta_producto_id' => $producto->producto_id,
            'alerta_para_todos' => true,
            'alerta_fecha' => now()
        ]);
    }

    /**
     * Crear alerta de stock agotado
     */
    public static function crearStockAgotado(Producto $producto): self
    {
        return self::create([
            'alerta_tipo' => self::TIPO_STOCK_AGOTADO,
            'alerta_titulo' => 'Stock Agotado: ' . $producto->producto_nombre,
            'alerta_mensaje' => "El producto '{$producto->producto_nombre}' está completamente agotado.",
            'alerta_prioridad' => self::PRIORIDAD_ALTA,
            'alerta_producto_id' => $producto->producto_id,
            'alerta_para_todos' => true,
            'alerta_fecha' => now()
        ]);
    }

    /**
     * Crear alerta de serie duplicada
     */
    public static function crearSerieDuplicada(string $numeroSerie, Producto $producto): self
    {
        return self::create([
            'alerta_tipo' => self::TIPO_SERIE_DUPLICADA,
            'alerta_titulo' => 'Serie Duplicada Detectada',
            'alerta_mensaje' => "Se detectó la serie duplicada '{$numeroSerie}' en el producto '{$producto->producto_nombre}'",
            'alerta_prioridad' => self::PRIORIDAD_CRITICA,
            'alerta_producto_id' => $producto->producto_id,
            'alerta_para_todos' => false, // Solo para administradores
            'alerta_fecha' => now()
        ]);
    }

    /**
     * Crear alerta del sistema
     */
    public static function crearSistema(string $titulo, string $mensaje, string $prioridad = self::PRIORIDAD_MEDIA): self
    {
        return self::create([
            'alerta_tipo' => self::TIPO_SISTEMA,
            'alerta_titulo' => $titulo,
            'alerta_mensaje' => $mensaje,
            'alerta_prioridad' => $prioridad,
            'alerta_para_todos' => true,
            'alerta_fecha' => now()
        ]);
    }

    /**
     * Obtener alertas para un usuario específico
     */
    public static function paraUsuario(User $usuario, bool $soloNoVistas = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where(function($q) use ($usuario) {
            $q->where('alerta_para_todos', true)
              ->orWhere('alerta_usuario_id', $usuario->user_id);
            
            if ($usuario->user_rol) {
                $q->orWhereHas('roles', function($roleQuery) use ($usuario) {
                    $roleQuery->where('rol_id', $usuario->user_rol);
                });
            }
        });

        if ($soloNoVistas) {
            $query->noVistas();
        }

        return $query->orderBy('alerta_fecha', 'desc')->get();
    }

    /**
     * Contar alertas no vistas para un usuario
     */
    public static function contarNoVistasPorUsuario(User $usuario): int
    {
        return self::paraUsuario($usuario, true)->count();
    }

    /**
     * Procesar alertas automáticas de stock
     */
    public static function procesarAlertasStock(): int
    {
        $alertasGeneradas = 0;
        
        // Obtener productos con stock bajo que no tengan alerta reciente
        $productosStockBajo = StockActual::stockBajo()
            ->whereDoesntHave('producto.alertas', function($q) {
                $q->where('alerta_tipo', self::TIPO_STOCK_BAJO)
                  ->where('alerta_fecha', '>=', now()->subDays(1))
                  ->where('alerta_resuelta', false);
            })
            ->with('producto')
            ->get();

        foreach ($productosStockBajo as $stock) {
            self::crearStockBajo($stock->producto, $stock->stock_cantidad_disponible);
            $alertasGeneradas++;
        }

        // Productos agotados
        $productosAgotados = StockActual::sinStock()
            ->whereDoesntHave('producto.alertas', function($q) {
                $q->where('alerta_tipo', self::TIPO_STOCK_AGOTADO)
                  ->where('alerta_fecha', '>=', now()->subDays(1))
                  ->where('alerta_resuelta', false);
            })
            ->with('producto')
            ->get();

        foreach ($productosAgotados as $stock) {
            self::crearStockAgotado($stock->producto);
            $alertasGeneradas++;
        }

        return $alertasGeneradas;
    }
}