<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Lote extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'pro_lotes';
    protected $primaryKey = 'lote_id';

    protected $fillable = [
        'lote_codigo',
        'lote_producto_id',
        'lote_fecha',
        'lote_descripcion',
        'lote_cantidad_total',
        'lote_cantidad_disponible',
        'lote_usuario_id',
        'lote_situacion'
    ];

    protected $casts = [
        'lote_fecha' => 'datetime',
        'lote_cantidad_total' => 'integer',
        'lote_cantidad_disponible' => 'integer',
        'lote_situacion' => 'integer'
    ];

    // ========================
    // RELACIONES
    // ========================

    /**
     * Relación con el producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'lote_producto_id', 'producto_id');
    }

    /**
     * Relación con el usuario que creó el lote
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'lote_usuario_id', 'user_id');
    }

    /**
     * Movimientos asociados a este lote
     */
    public function movimientos()
    {
        return $this->hasMany(Movimiento::class, 'mov_lote_id', 'lote_id');
    }

    // ========================
    // SCOPES
    // ========================

    /**
     * Lotes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('lote_situacion', 1);
    }

    /**
     * Lotes con stock disponible
     */
    public function scopeConStock($query)
    {
        return $query->where('lote_cantidad_disponible', '>', 0);
    }

    /**
     * Lotes de un producto específico
     */
    public function scopeDeProducto($query, $productoId)
    {
        return $query->where('lote_producto_id', $productoId);
    }

    /**
     * Buscar lotes por código
     */
    public function scopeBuscarPorCodigo($query, $codigo)
    {
        return $query->where('lote_codigo', 'LIKE', "%{$codigo}%");
    }

    // ========================
    // MÉTODOS ESTÁTICOS
    // ========================

    /**
     * Buscar o crear lote para un producto
     */
    public static function buscarOCrearParaProducto($productoId, $codigoLote, $cantidad, $descripcion = null, $usuarioId = null)
    {
        // Buscar lote existente
        $loteExistente = static::activos()
            ->where('lote_codigo', $codigoLote)
            ->where('lote_producto_id', $productoId)
            ->first();

        if ($loteExistente) {
            // Actualizar cantidades del lote existente
            $loteExistente->lote_cantidad_total += $cantidad;
            $loteExistente->lote_cantidad_disponible += $cantidad;
            $loteExistente->save();
            
            return $loteExistente;
        }

        // Crear nuevo lote
        return static::create([
            'lote_codigo' => $codigoLote,
            'lote_producto_id' => $productoId,
            'lote_fecha' => now(),
            'lote_descripcion' => $descripcion,
            'lote_cantidad_total' => $cantidad,
            'lote_cantidad_disponible' => $cantidad,
            'lote_usuario_id' => $usuarioId,
            'lote_situacion' => 1
        ]);
    }

    /**
     * Generar código de lote automático
     */
    public static function generarCodigoAutomatico($productoId)
    {
        $producto = Producto::find($productoId);
        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }

        $fecha = now();
        $año = $fecha->format('Y');
        $mes = $fecha->format('m');
        
        // Obtener código de marca
        $marcaCode = 'AUTO';
        if ($producto->marca) {
            $marcaCode = strtoupper(substr($producto->marca->marca_descripcion, 0, 3));
        }
        
        // Buscar el siguiente secuencial
        $patron = "L{$año}-{$mes}-{$marcaCode}-%";
        $ultimoLote = static::where('lote_codigo', 'LIKE', $patron)
            ->orderBy('lote_codigo', 'desc')
            ->first();
        
        $secuencial = 1;
        if ($ultimoLote) {
            $partes = explode('-', $ultimoLote->lote_codigo);
            if (count($partes) >= 4) {
                $secuencial = intval(end($partes)) + 1;
            }
        }
        
        return sprintf('L%s-%s-%s-%03d', $año, $mes, $marcaCode, $secuencial);
    }

    // ========================
    // MÉTODOS DE INSTANCIA
    // ========================

    /**
     * Descontar cantidad del lote
     */
    public function descontarCantidad($cantidad)
    {
        if ($cantidad > $this->lote_cantidad_disponible) {
            throw new \Exception("No hay suficiente stock disponible en el lote. Disponible: {$this->lote_cantidad_disponible}");
        }

        $this->lote_cantidad_disponible -= $cantidad;
        $this->save();

        return $this;
    }

    /**
     * Agregar cantidad al lote
     */
    public function agregarCantidad($cantidad)
    {
        $this->lote_cantidad_total += $cantidad;
        $this->lote_cantidad_disponible += $cantidad;
        $this->save();

        return $this;
    }

    /**
     * Verificar si el lote está agotado
     */
    public function estaAgotado()
    {
        return $this->lote_cantidad_disponible <= 0;
    }

    /**
     * Obtener porcentaje de stock utilizado
     */
    public function getPorcentajeUtilizado()
    {
        if ($this->lote_cantidad_total <= 0) {
            return 0;
        }

        $utilizado = $this->lote_cantidad_total - $this->lote_cantidad_disponible;
        return ($utilizado / $this->lote_cantidad_total) * 100;
    }
}