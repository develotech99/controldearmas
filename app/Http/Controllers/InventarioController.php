<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoFoto;
use App\Models\SerieProducto;
use App\Models\Lote;
use App\Models\Alerta;
use App\Models\Precio;
use App\Models\Movimiento;
use App\Models\LicenciaAsignacionProducto;
use App\Models\StockActual;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Controlador principal para la gestión de inventario de armería
 * Maneja productos, stock, movimientos, series y precios
 */
class InventarioController extends Controller
{


    /**
     * Vista principal del módulo de inventario
     */
    public function index()
    {
        return view('inventario.index');
    }

    // ================================
    // PRODUCTOS
    // ================================

    /**
     * Obtener productos con información de stock
     */


/**
 * Obtener stock de múltiples productos
 */
public function obtenerStock(Request $request): JsonResponse
{
    try {
        $productosIds = $request->input('productos_ids', []);
        
        if (empty($productosIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No se proporcionaron IDs de productos'
            ]);
        }
        
  $stocks = DB::table('pro_productos as p')
    ->leftJoin('pro_stock_actual as sa', 'p.producto_id', '=', 'sa.stock_producto_id') // ← Corregido
    ->whereIn('p.producto_id', $productosIds)
    ->select([
        'p.producto_id',
        DB::raw('COALESCE(sa.stock_cantidad_disponible, 0) as stock_disponible'),
        DB::raw('COALESCE(sa.stock_cantidad_total, 0) as stock_total'),
        DB::raw('COALESCE(sa.stock_cantidad_reservada, 0) as stock_reservado') // ← Agregado
    ])
    ->get();
        
        return response()->json([
            'success' => true,
            'stocks' => $stocks
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener stock: ' . $e->getMessage()
        ], 500);
    }
}


    public function getProductosStock(Request $request): JsonResponse
{
    try {
        $query = Producto::activos()
            ->leftJoin('pro_categorias', 'pro_productos.producto_categoria_id', '=', 'pro_categorias.categoria_id')
            ->leftJoin('pro_subcategorias', 'pro_productos.producto_subcategoria_id', '=', 'pro_subcategorias.subcategoria_id')
            ->leftJoin('pro_marcas', 'pro_productos.producto_marca_id', '=', 'pro_marcas.marca_id')
            ->leftJoin('pro_modelo', 'pro_productos.producto_modelo_id', '=', 'pro_modelo.modelo_id')
            ->leftJoin('pro_stock_actual', 'pro_productos.producto_id', '=', 'pro_stock_actual.stock_producto_id')
            ->select([
                'pro_productos.producto_id',
                'pro_productos.producto_nombre',
                'pro_productos.pro_codigo_sku',
                'pro_productos.producto_codigo_barra',
                'pro_productos.producto_requiere_serie',
                'pro_productos.producto_stock_minimo',
                'pro_productos.producto_stock_maximo',
                'pro_categorias.categoria_nombre',
                'pro_subcategorias.subcategoria_nombre',
                'pro_marcas.marca_descripcion as marca_nombre',
                'pro_modelo.modelo_descripcion as modelo_nombre',
                'pro_stock_actual.stock_cantidad_total',
                'pro_stock_actual.stock_cantidad_disponible',
                'pro_stock_actual.stock_cantidad_reservada'
            ]);

        // Aplicar filtros si existen
        if ($request->filled('categoria')) {
            $query->where('pro_productos.producto_categoria_id', $request->categoria);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('pro_productos.producto_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('pro_productos.pro_codigo_sku', 'LIKE', "%{$search}%")
                  ->orWhere('pro_productos.producto_codigo_barra', 'LIKE', "%{$search}%");
            });
        }

        $productos = $query->orderBy('pro_productos.producto_nombre')->get();

        // AGREGAR FOTOS PRINCIPALES A CADA PRODUCTO
        $productosConFotos = $productos->map(function($producto) {
            $fotoPrincipal = ProductoFoto::activas()
                ->where('foto_producto_id', $producto->producto_id)
                ->where('foto_principal', true)
                ->first();

            return [
                'producto_id' => $producto->producto_id,
                'producto_nombre' => $producto->producto_nombre,
                'pro_codigo_sku' => $producto->pro_codigo_sku,
                'producto_codigo_barra' => $producto->producto_codigo_barra,
                'producto_requiere_serie' => $producto->producto_requiere_serie,
                'producto_stock_minimo' => $producto->producto_stock_minimo,
                'producto_stock_maximo' => $producto->producto_stock_maximo,
                'categoria_nombre' => $producto->categoria_nombre,
                'subcategoria_nombre' => $producto->subcategoria_nombre,
                'marca_nombre' => $producto->marca_nombre,
                'modelo_nombre' => $producto->modelo_nombre,
                'stock_cantidad_total' => $producto->stock_cantidad_total,
                // FIX: Calcular disponible real (Total - Reservado) para visualización correcta
                'stock_cantidad_disponible' => max(0, $producto->stock_cantidad_total - $producto->stock_cantidad_reservada),
                'stock_cantidad_reservada' => $producto->stock_cantidad_reservada,
                'foto_principal' => $fotoPrincipal ? $fotoPrincipal->url_completa : null
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $productosConFotos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar productos: ' . $e->getMessage()
        ], 500);
    }
}
public function egresar(Request $request): JsonResponse
{
    try {
        $request->validate([
            'producto_id' => 'required|exists:pro_productos,producto_id',
            'mov_tipo' => 'required|string',
            'mov_destino' => 'required|string',
            'mov_cantidad' => 'nullable|integer|min:1',
            'series_seleccionadas' => 'nullable|string',
            'origen_tipo' => 'nullable|in:lote,sin_lote',
            'lote_especifico_id' => 'nullable|integer|exists:pro_lotes,lote_id',
            'mov_observaciones' => 'nullable|string|max:250'
        ]);

        $producto = DB::table('pro_productos')->where('producto_id', $request->producto_id)->first();
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        DB::beginTransaction();

        if ($producto->producto_requiere_serie) {
            // ===============================
            // PRODUCTO CON SERIE
            // ===============================
            $seriesSeleccionadas = json_decode($request->series_seleccionadas, true);
            
            if (empty($seriesSeleccionadas)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Debe seleccionar al menos una serie'
                ], 400);
            }

            $seriesIds = array_column($seriesSeleccionadas, 'id');
            
            // Verificar que todas las series existen y están disponibles
            $seriesValidas = DB::table('pro_series_productos')
                ->whereIn('serie_id', $seriesIds)
                ->where('serie_producto_id', $producto->producto_id)
                ->where('serie_estado', 'disponible')
                ->where('serie_situacion', 1)
                ->count();

            if ($seriesValidas !== count($seriesIds)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más series no están disponibles para egreso'
                ], 400);
            }

            // Actualizar las series: cambiar estado y situación
            DB::table('pro_series_productos')
                ->whereIn('serie_id', $seriesIds)
                ->update([
                    'serie_estado' => 'egreso',
                    'serie_situacion' => 0,
                    'updated_at' => now()
                ]);

            // Registrar movimiento por cada serie
            foreach ($seriesSeleccionadas as $serie) {
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => $request->mov_tipo,
                    'mov_destino' => $request->mov_destino,
                    'mov_cantidad' => -1,
                    'mov_fecha' => now(),
                    'mov_usuario_id' => auth()->id(),
                    'mov_serie_id' => $serie['id'],
                    'mov_observaciones' => $request->mov_observaciones . " - Serie: " . $serie['numero'],
                    'mov_situacion' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Actualizar stock
            $stockActual = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();
            if ($stockActual) {
                DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $producto->producto_id)
                    ->decrement('stock_cantidad_disponible', count($seriesIds));
                
                DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $producto->producto_id)
                    ->decrement('stock_cantidad_total', count($seriesIds));
            }

            $mensaje = "Egreso registrado: " . count($seriesIds) . " serie(s) procesada(s)";

        } else {
            // ===============================
            // PRODUCTO SIN SERIE (CON LOTES)
            // ===============================
            $cantidad = $request->mov_cantidad;
            
            // Verificar stock disponible
            $stockActual = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();
            
            if (!$stockActual || $stockActual->stock_cantidad_disponible < $cantidad) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente para realizar el egreso'
                ], 400);
            }

            if ($request->origen_tipo === 'lote' && $request->lote_especifico_id) {
                // ===============================
                // EGRESO DESDE LOTE ESPECÍFICO
                // ===============================
                $lote = DB::table('pro_lotes')->where('lote_id', $request->lote_especifico_id)->first();
                
                if (!$lote || $lote->lote_cantidad_disponible < $cantidad) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'El lote no tiene suficiente stock disponible'
                    ], 400);
                }

                // Actualizar lote específico
                DB::table('pro_lotes')
                    ->where('lote_id', $request->lote_especifico_id)
                    ->decrement('lote_cantidad_disponible', $cantidad);

                // Registrar movimiento con referencia al lote
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => $request->mov_tipo,
                    'mov_origen' => $request->mov_destino,
                    'mov_cantidad' => -$cantidad,
                    'mov_fecha' => now(),
                    'mov_usuario_id' => auth()->id(),
                    'mov_lote_id' => $request->lote_especifico_id,
                    'mov_observaciones' => $request->mov_observaciones . " - Lote: {$lote->lote_codigo}",
                    'mov_situacion' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Si el lote se agotó, marcarlo como cerrado
                $loteActualizado = DB::table('pro_lotes')->where('lote_id', $request->lote_especifico_id)->first();
                if ($loteActualizado->lote_cantidad_disponible <= 0) {
                    DB::table('pro_lotes')
                        ->where('lote_id', $request->lote_especifico_id)
                        ->update(['lote_situacion' => 0]);
                }

                $mensaje = "Egreso registrado: {$cantidad} unidades del lote {$lote->lote_codigo}";

            } else {
                // ===============================
                // EGRESO DESDE STOCK SIN LOTE
                // ===============================
                
                // Registrar movimiento sin lote específico
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => $request->mov_tipo,
                    'mov_origen' => $request->mov_destino,
                    'mov_cantidad' => -$cantidad,
                    'mov_fecha' => now(),
                    'mov_usuario_id' => auth()->id(),
                    'mov_lote_id' => null,
                    'mov_observaciones' => $request->mov_observaciones . " - Stock general",
                    'mov_situacion' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $mensaje = "Egreso registrado: {$cantidad} unidades del stock general";
            }
            
            // Actualizar stock total (común para ambos casos)
            DB::table('pro_stock_actual')
                ->where('stock_producto_id', $producto->producto_id)
                ->decrement('stock_cantidad_disponible', $cantidad);
        }

        DB::commit();

        $producto = Producto::find($request->producto_id);
        $stockActual = StockActual::where('stock_producto_id', $request->producto_id)->first();
        if ($producto && $stockActual) {
            $this->verificarAlertasStock($producto, $stockActual);
}

        return response()->json([
            'success' => true,
            'message' => $mensaje
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Datos de validación incorrectos',
            'errors' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();
        
        $msg = 'Error al procesar egreso.';
        if (str_contains($e->getMessage(), 'Data too long')) {
            $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
        } elseif (config('app.debug')) {
            $msg .= ' ' . $e->getMessage();
        }
        
        return response()->json([
            'success' => false,
            'message' => $msg
        ], 500);
    }
}

// En InventarioController.php
public function getStockPorLotes($id): JsonResponse
{
    try {
        $producto = DB::table('pro_productos')->where('producto_id', $id)->first();
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $stockDetallado = [];

        if ($producto->producto_requiere_serie) {
            // Para productos con serie, mostrar series agrupadas por lote
            $seriesDisponibles = DB::table('pro_series_productos')
                ->where('serie_producto_id', $id)
                ->where('serie_estado', 'disponible')
                ->where('serie_situacion', 1)
                ->get();

            $stockDetallado = [
                'tipo' => 'series',
                'total_disponible' => $seriesDisponibles->count(),
                'series' => $seriesDisponibles
            ];
        } else {
            // Para productos sin serie, mostrar lotes + stock sin lote
            
            // 1. Stock por lotes
            $lotes = DB::table('pro_lotes')
                ->where('lote_producto_id', $id)
                ->where('lote_cantidad_disponible', '>', 0)
                ->where('lote_situacion', 1)
                ->orderBy('lote_fecha', 'asc')
                ->get();

            // 2. Calcular stock sin lote
            // 2. Calcular stock sin lote (FIX: Calcular disponible real)
            $stockData = DB::table('pro_stock_actual')
                ->where('stock_producto_id', $id)
                ->first();
            
            $stockTotal = $stockData ? max(0, $stockData->stock_cantidad_total - $stockData->stock_cantidad_reservada) : 0;

            $stockEnLotes = $lotes->sum('lote_cantidad_disponible');
            $stockSinLote = max(0, $stockTotal - $stockEnLotes);

            $stockDetallado = [
                'tipo' => 'cantidad',
                'total_disponible' => $stockTotal,
                'lotes' => $lotes->map(function($lote) {
                    return [
                        'lote_id' => $lote->lote_id,
                        'lote_codigo' => $lote->lote_codigo,
                        'cantidad_disponible' => $lote->lote_cantidad_disponible,
                        'fecha_ingreso' => $lote->lote_fecha
                    ];
                })->toArray(),
                'sin_lote' => $stockSinLote
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $stockDetallado
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar stock: ' . $e->getMessage()
        ], 500);
    }
}

public function getSeriesDisponibles($id): JsonResponse
{
    try {
        $series = SerieProducto::where('serie_producto_id', $id)
            ->where('serie_estado', 'disponible')
            ->where('serie_situacion', 1)
            ->orderBy('serie_numero_serie')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $series
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar series: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Buscar productos para el modal de ingreso
     */
    public function buscarProductos(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $productos = Producto::activos()
                ->leftJoin('pro_stock_actual', 'pro_productos.producto_id', '=', 'pro_stock_actual.stock_producto_id')
                ->where(function($q) use ($query) {
                    $q->where('producto_nombre', 'LIKE', "%{$query}%")
                      ->orWhere('pro_codigo_sku', 'LIKE', "%{$query}%")
                      ->orWhere('producto_codigo_barra', 'LIKE', "%{$query}%");
                })
                ->select([
                    'pro_productos.producto_id',
                    'pro_productos.producto_nombre',
                    'pro_productos.pro_codigo_sku',
                    'pro_productos.producto_requiere_serie',
                    'pro_stock_actual.stock_cantidad_disponible'
                ])
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalle de un producto específico
     */
    public function getProducto(Request $request, $id): JsonResponse
    {
        try {
            $producto = Producto::with(['stockActual'])
                ->leftJoin('pro_categorias', 'pro_productos.producto_categoria_id', '=', 'pro_categorias.categoria_id')
                ->leftJoin('pro_marcas', 'pro_productos.producto_marca_id', '=', 'pro_marcas.marca_id')
                ->select([
                    'pro_productos.*',
                    'pro_categorias.categoria_nombre',
                    'pro_marcas.marca_descripcion as marca_nombre'
                ])
                ->findOrFail($id);
    
            // AGREGAR: Verificar si tiene asignaciones de licencias activas
            $tieneAsignacionesLicencias = LicenciaAsignacionProducto::activas()
                ->where('asignacion_producto_id', $id)
                ->exists();
    
            // Agregar esta información al producto
            $productoData = $producto->toArray();
            $productoData['tiene_asignaciones_licencias'] = $tieneAsignacionesLicencias;
            $productoData['requiere_licencia'] = $tieneAsignacionesLicencias; // Para compatibilidad con JS
    
            return response()->json([
                'success' => true,
                'data' => $productoData
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    }

    /**
 * Obtener países activos
 */
public function getPaisesActivos(): JsonResponse
{
    try {
        $paises = DB::table('pro_paises')
            ->where('pais_situacion', 1)
            ->orderBy('pais_descripcion')
            ->get(['pais_id', 'pais_descripcion']);

        return response()->json([
            'success' => true,
            'data' => $paises
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar países'
        ], 500);
    }
}

    /**
     * Registrar nuevo producto
     */
   
     public function store(Request $request): JsonResponse
     {
         $validator = Validator::make($request->all(), [
             'producto_nombre' => 'required|string|max:100',
             'producto_descripcion' => 'nullable|string',
             'producto_categoria_id' => 'required|integer|exists:pro_categorias,categoria_id',
             'producto_subcategoria_id' => 'required|integer|exists:pro_subcategorias,subcategoria_id',
             'producto_marca_id' => 'required|integer|exists:pro_marcas,marca_id',
             'producto_modelo_id' => 'nullable|integer|exists:pro_modelo,modelo_id',
             'producto_calibre_id' => 'nullable|integer|exists:pro_calibres,calibre_id',
             'producto_madein' => 'nullable|integer|exists:pro_paises,pais_id',
             'producto_codigo_barra' => 'nullable|string|unique:pro_productos,producto_codigo_barra',
             'producto_requiere_serie' => 'boolean',
             'producto_requiere_stock' => 'boolean',
             'producto_es_importado' => 'boolean',
             'producto_stock_minimo' => 'nullable|integer|min:0',
             'producto_stock_maximo' => 'nullable|integer|min:0',
             'fotos.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,heic,heif|max:2048',
     
             // VALIDACIONES DE PRECIOS (OPCIONALES EN REGISTRO)
             'agregar_precios' => 'nullable|boolean',
             'precio_costo' => 'required_if:agregar_precios,1|nullable|numeric|min:0.01',
             'precio_venta' => 'required_if:agregar_precios,1|nullable|numeric|min:0.01|gt:precio_costo',
             'precio_especial' => 'nullable|numeric|min:0',
             'precio_moneda' => 'nullable|string|in:GTQ,USD,EUR',
             'precio_justificacion' => 'nullable|string|max:255',
         ]);
     
         if ($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'errors' => $validator->errors()
             ], 422);
         }
     
         DB::beginTransaction();
     
         try {
             // 1. Generar SKU único
             $sku = $this->generarSKU($request->producto_categoria_id, $request->producto_marca_id);
     
             // 2. Crear producto
             $producto = Producto::create([
                 'producto_nombre' => $request->producto_nombre,
                 'producto_descripcion' => $request->producto_descripcion,
                 'pro_codigo_sku' => $sku,
                 'producto_codigo_barra' => $request->producto_codigo_barra,
                 'producto_categoria_id' => $request->producto_categoria_id,
                 'producto_subcategoria_id' => $request->producto_subcategoria_id,
                 'producto_marca_id' => $request->producto_marca_id,
                 'producto_modelo_id' => $request->producto_modelo_id,
                 'producto_calibre_id' => $request->producto_calibre_id,
                 'producto_madein' => $request->producto_madein,
                 'producto_requiere_serie' => $request->has('producto_requiere_serie'),
                 'producto_requiere_stock' => $request->boolean('producto_requiere_stock'),
                 'producto_es_importado' => $request->has('producto_es_importado'),
                 'producto_stock_minimo' => $request->producto_stock_minimo ?? 0,
                 'producto_stock_maximo' => $request->producto_stock_maximo ?? 0,
                 'producto_situacion' => 1
             ]);
     
           // 3. Crear registro de stock inicial SOLO si requiere stock
           if ($producto->producto_requiere_stock) {
                StockActual::create([
                    'stock_producto_id' => $producto->producto_id,
                    'stock_cantidad_total' => 0,
                    'stock_cantidad_disponible' => 0,
                    'stock_cantidad_reservada' => 0,
                    'stock_valor_total' => 0
                ]);
            }

     
             // 4. REGISTRAR PRECIOS SI SE PROPORCIONARON
             if ($request->filled('agregar_precios') && $request->agregar_precios) {
                 $this->registrarPreciosProducto($request, $producto->producto_id);
             }
     
             // 5. Procesar fotos si existen
             if ($request->hasFile('fotos')) {
                 $this->procesarFotosNuevoProducto($request->file('fotos'), $producto->producto_id);
             }
     
             DB::commit();
     
             $mensaje = 'Producto registrado exitosamente';
             if ($request->filled('agregar_precios') && $request->agregar_precios) {
                 $mensaje .= ' con información de precios';
             }
     
             return response()->json([
                 'success' => true,
                 'message' => $mensaje,
                 'data' => $producto
             ]);
     
         } catch (\Exception $e) {
            DB::rollback();
            
            $msg = 'Error al registrar producto.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
     }
     
    /**
     * Generar SKU único para el producto
     */
    private function generarSKU($categoriaId, $marcaId): string
    {
        // Obtener iniciales de categoría y marca
        $categoria = DB::table('pro_categorias')->where('categoria_id', $categoriaId)->first();
        $marca = DB::table('pro_marcas')->where('marca_id', $marcaId)->first();
    
        // Validar que existan
        if (!$categoria || !$marca) {
            throw new \Exception('Categoría o marca no encontrada');
        }
    
        $categoriaCode = strtoupper(substr($categoria->categoria_nombre, 0, 3));
        $marcaCode = strtoupper(substr($marca->marca_descripcion, 0, 3));
    
        // Generar número secuencial
        $ultimoNumero = Producto::where('pro_codigo_sku', 'LIKE', "{$categoriaCode}-{$marcaCode}-%")
            ->orderBy('pro_codigo_sku', 'desc')
            ->first();
    
        $numero = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero->pro_codigo_sku);
            if (count($partes) >= 3) {
                $numero = intval(end($partes)) + 1;
            }
        }
    
        return sprintf('%s-%s-%04d', $categoriaCode, $marcaCode, $numero);
    }

    // ================================
    // MOVIMIENTOS E INGRESOS
    // ================================
    public function ingresar(Request $request): JsonResponse
    {
        // PASO 1: Obtener producto para verificar si requiere serie ANTES de validar
        $producto = Producto::find($request->producto_id);
        
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado'
            ], 404);
        }
    
        // PASO 2: Definir reglas de validación condicionales
        $rules = [
            'producto_id' => 'required|integer|exists:pro_productos,producto_id',
            'mov_tipo' => 'required|in:ingreso,ajuste_positivo,devolucion,transferencia',
            'mov_origen' => 'required|string|max:100',
            'mov_observaciones' => 'nullable|string|max:250',
            
            // Licencias (condicionales)
            'licencia_id' => 'nullable|integer|exists:pro_licencias_para_importacion,lipaimp_id',
            'producto_es_importado' => 'nullable|boolean',
            'licencia_id_registro' => 'required_if:producto_es_importado,1|nullable|integer|exists:pro_licencias_para_importacion,lipaimp_id',
            'cantidad_licencia' => 'required_if:producto_es_importado,1|nullable|integer|min:1',
        ];
    
        // PASO 3: Agregar validaciones específicas según tipo de producto
        if ($producto->producto_requiere_serie) {
            $rules['numeros_series'] = 'required|string';
        } else {
            $rules['mov_cantidad'] = 'required|integer|min:1';
            $rules['usar_lotes'] = 'nullable|boolean';
            
            if ($request->filled('usar_lotes') && $request->usar_lotes) {
                $rules['tipo_lote'] = 'required|in:automatico,manual,buscar';
                
                if ($request->tipo_lote === 'manual') {
                    $rules['numero_lote'] = 'required|string|max:100|unique:pro_lotes,lote_codigo';
                } elseif ($request->tipo_lote === 'buscar') {
                    $rules['lote_id'] = 'required|integer|exists:pro_lotes,lote_id';
                }
            }
        }
    
        // PASO 4: Ejecutar validación
        $validator = Validator::make($request->all(), $rules, [
            'numeros_series.required' => 'Los números de serie son obligatorios para este tipo de producto',
            'mov_cantidad.required' => 'La cantidad es obligatoria para productos sin número de serie',
            'licencia_id_registro.required_if' => 'Debe seleccionar una licencia para productos importados',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
    
        DB::beginTransaction();
    
        try {
            $cantidadIngreso = 0;
            $loteId = null;
            $mensajeAdicional = '';
    
            // PASO 5: Procesar según tipo de producto
            if ($producto->producto_requiere_serie) {
                // ===============================
                // PRODUCTOS CON SERIE - CORREGIDO
                // ===============================
                $series = array_filter(array_map('trim', explode("\n", $request->numeros_series)));
                
                if (empty($series)) {
                    DB::rollback();
                    return response()->json([
                        'success' => false,
                        'message' => 'Debe proporcionar al menos un número de serie válido'
                    ], 422);
                }
    
            // CORRECCIÓN: Verificar series duplicadas SOLO EN EL MISMO PRODUCTO
            $seriesExistentes = SerieProducto::with(['movimientos.usuario'])
                ->whereIn('serie_numero_serie', $series)
                ->where('serie_producto_id', $producto->producto_id)
                ->where('serie_situacion', 1)
                ->get();
            
            if ($seriesExistentes->isNotEmpty()) {
                $detallesDuplicados = $seriesExistentes->map(function ($serie) {
                    // Buscar el primer movimiento (ingreso)
                    $primerMovimiento = $serie->movimientos->sortBy('mov_fecha')->first();
                    $usuarioNombre = $primerMovimiento && $primerMovimiento->usuario 
                        ? $primerMovimiento->usuario->name . ' ' . $primerMovimiento->usuario->apellido 
                        : 'Usuario desconocido';
                    
                    return [
                        'serie' => $serie->serie_numero_serie,
                        'fecha_ingreso' => $serie->serie_fecha_ingreso ? $serie->serie_fecha_ingreso->format('d/m/Y H:i') : 'N/A',
                        'usuario' => $usuarioNombre,
                        'estado' => $serie->serie_estado,
                        'observaciones' => $serie->serie_observaciones ?? 'Sin observaciones'
                    ];
                });

                $seriesDuplicadas = $seriesExistentes->pluck('serie_numero_serie');
                
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más series ya existen para este producto.',
                    'series_duplicadas' => $seriesDuplicadas->toArray(),
                    'detalles_duplicados' => $detallesDuplicados->toArray()
                ], 422);
            }
    
                // CREAR UN MOVIMIENTO POR CADA SERIE
                foreach ($series as $numeroSerie) {
                    // 1. Crear el registro de serie
                    $serieCreada = SerieProducto::create([
                        'serie_producto_id' => $producto->producto_id,
                        'serie_numero_serie' => $numeroSerie,
                        'serie_estado' => 'disponible',
                        'serie_fecha_ingreso' => now(),
                        'serie_situacion' => 1
                    ]);
    
                    // 2. IMPORTANTE: Crear movimiento individual por cada serie
                    Movimiento::create([
                        'mov_producto_id' => $producto->producto_id,
                        'mov_tipo' => $request->mov_tipo,
                        'mov_origen' => $request->mov_origen,
                        'mov_cantidad' => 1, // SIEMPRE 1 para productos con serie
                        'mov_fecha' => now(),
                        'mov_usuario_id' => Auth::id() ?? 1,
                        'mov_serie_id' => $serieCreada->serie_id, // CRÍTICO: Relacionar la serie
                        'mov_lote_id' => null, // Series no usan lotes
                        'mov_observaciones' => $request->mov_observaciones . " - Serie: {$numeroSerie}",
                        'mov_situacion' => 1
                    ]);
                }
    
                $cantidadIngreso = count($series);
                $mensajeAdicional = " ({$cantidadIngreso} series procesadas)";
    
            } else {
                // ===============================
                // PRODUCTOS SIN SERIE
                // ===============================
                $cantidadIngreso = $request->mov_cantidad;
    
                // GESTIÓN DE LOTES (solo si está activada)
                if ($request->filled('usar_lotes') && $request->usar_lotes) {
                    
                    switch ($request->tipo_lote) {
                        case 'manual':
                            $lote = Lote::create([
                                'lote_codigo' => $request->numero_lote,
                                'lote_producto_id' => $producto->producto_id, 
                                'lote_fecha' => now(),
                                'lote_descripcion' => "Lote manual para {$producto->producto_nombre} - {$request->mov_origen}",
                                'lote_cantidad_total' => $cantidadIngreso, 
                                'lote_cantidad_disponible' => $cantidadIngreso, 
                                'lote_usuario_id' => Auth::id(),
                                'lote_situacion' => 1
                            ]);
                            $loteId = $lote->lote_id;
                            $mensajeAdicional = " (Lote: {$request->numero_lote})";
                            break;
                        
                        case 'automatico':
                            $codigoLoteAuto = $this->generarNumeroLoteAutomatico($producto);
                            $lote = Lote::create([
                                'lote_codigo' => $codigoLoteAuto,
                                'lote_producto_id' => $producto->producto_id, 
                                'lote_fecha' => now(),
                                'lote_descripcion' => "Lote automático para {$producto->producto_nombre} - {$request->mov_origen}",
                                'lote_cantidad_total' => $cantidadIngreso, 
                                'lote_cantidad_disponible' => $cantidadIngreso, 
                                'lote_usuario_id' => Auth::id(),
                                'lote_situacion' => 1
                            ]);
                            $loteId = $lote->lote_id;
                            $mensajeAdicional = " (Lote: {$codigoLoteAuto})";
                            break;
                        
                        case 'buscar':
                            $loteId = $request->lote_id;
                            $loteExistente = Lote::find($loteId);
                 
                            $loteExistente->lote_cantidad_total += $cantidadIngreso;
                            $loteExistente->lote_cantidad_disponible += $cantidadIngreso;
                            $loteExistente->save();
                            
                            $mensajeAdicional = " (agregado a lote: {$loteExistente->lote_codigo})";
                            break;
                    }
                }
    
                // CREAR UN SOLO MOVIMIENTO PARA PRODUCTOS SIN SERIE
                Movimiento::create([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => $request->mov_tipo,
                    'mov_origen' => $request->mov_origen,
                    'mov_cantidad' => $cantidadIngreso,
                    'mov_fecha' => now(),
                    'mov_usuario_id' => Auth::id() ?? 1,
                    'mov_lote_id' => $loteId,
                    'mov_serie_id' => null, // Productos sin serie no tienen serie_id
                    'mov_observaciones' => $request->mov_observaciones,
                    'mov_situacion' => 1
                ]);
            }
    
            // PASO 6: Procesar licencias si es producto importado (SOLO PARA SERIES)
            if ($request->filled('producto_es_importado') && $request->producto_es_importado && $request->filled('licencia_id_registro')) {
                
                // Solo procesar licencias para productos CON SERIE
                if ($producto->producto_requiere_serie && isset($series) && !empty($series)) {
                    
                    $seriesCreadas = SerieProducto::where('serie_producto_id', $producto->producto_id)
                        ->whereIn('serie_numero_serie', $series)
                        ->where('serie_situacion', 1)
                        ->pluck('serie_id')
                        ->toArray();
                        
                    LicenciaAsignacionProducto::asignarSeries(
                        $producto->producto_id,
                        $request->licencia_id_registro,
                        $seriesCreadas,
                        "Ingreso importado - " . $request->mov_origen
                    );
                    
                    $licencia = DB::table('pro_licencias_para_importacion')
                        ->where('lipaimp_id', $request->licencia_id_registro)
                        ->first();
                    
                    $polizaNumero = $licencia ? $licencia->lipaimp_poliza : $request->licencia_id_registro;
                    $mensajeAdicional .= " (asignado a licencia póliza: {$polizaNumero})";
                }
                // Si no requiere serie pero está marcado como importado, solo ignoramos la licencia
            }
    
            // PASO 7: Actualizar stock
            $this->actualizarStock($producto->producto_id);
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => "Ingreso procesado exitosamente{$mensajeAdicional}",
                'data' => [
                    'cantidad_ingresada' => $cantidadIngreso,
                    'lote_id' => $loteId,
                    'producto_requiere_serie' => $producto->producto_requiere_serie
                ]
            ]);
    
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            
            if ($e->getCode() == 23000) {
                // Duplicate entry error
                if (str_contains($e->getMessage(), 'unique_serie_per_product')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error: Una o más series ya existen para este producto. Verifique los números de serie.'
                    ], 422);
                }
                if (str_contains($e->getMessage(), 'pro_lotes_lote_codigo_unique')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error: El código de lote ya existe. Intente con otro código.'
                    ], 422);
                }
                 if (str_contains($e->getMessage(), 'Duplicate entry')) {
                     // Generic duplicate entry fallback
                      preg_match("/Duplicate entry '(.*)' for key/", $e->getMessage(), $matches);
                      $value = $matches[1] ?? 'valor duplicado';
                      return response()->json([
                        'success' => false,
                        'message' => "Error: Registro duplicado detectado ($value). Verifique que no esté ingresando datos ya existentes."
                    ], 422);
                 }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);

        } catch (\Exception $e) {
            DB::rollback();
            
            $msg = 'Error al procesar ingreso.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);
        }
    }

    private function registrarPreciosProducto(Request $request, $productoId): void
{
    $margen = 0;
    if ($request->precio_costo > 0 && $request->precio_venta > 0) {
        $margen = (($request->precio_venta - $request->precio_costo) / $request->precio_costo) * 100;
    }

    $margen_empresa = 0;
    if ($request->precio_costo > 0 && $request->precio_venta_empresa > 0) {
        $margen_empresa = (($request->precio_venta_empresa - $request->precio_costo) / $request->precio_costo) * 100;
    }

    Precio::create([
        'precio_producto_id' => $productoId,
        'precio_costo' => $request->precio_costo,
        'precio_venta' => $request->precio_venta,
        'precio_venta_empresa' => $request->precio_venta_empresa,
        'precio_margen' => round($margen, 2),
        'precio_margen_empresa' => round($margen_empresa, 2),
        'precio_especial' => $request->precio_especial,
        'precio_moneda' => $request->precio_moneda ?? 'GTQ',
        'precio_justificacion' => $request->precio_justificacion ?? 'Precio inicial',
        'precio_fecha_asignacion' => now()->toDateString(),
        'precio_usuario_id' => Auth::id(),
        'precio_situacion' => 1  // ACTIVO
    ]);
}



/**
 * Generar número de lote automático
 */
private function generarNumeroLoteAutomatico($producto): string
{
    $fecha = now();
    $año = $fecha->format('Y');
    $mes = $fecha->format('m');
    
    // Obtener código de marca usando la relación
    $marca = $producto->marca; // Asumiendo que tienes la relación definida
    $marcaCode = $marca ? strtoupper(substr($marca->marca_descripcion, 0, 3)) : 'AUTO';
    
    // Buscar el siguiente secuencial para este producto específico
    $patron = "L{$año}-{$mes}-{$marcaCode}-%";
    $ultimoLote = Lote::where('lote_codigo', 'LIKE', $patron)
        ->where('lote_producto_id', $producto->producto_id) // AGREGAR: Filtrar por producto
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

/**
 * Procesar precios del ingreso
 */
private function procesarPreciosIngreso(Request $request, $productoId, $movimientoId): void
{
    $margen = 0;
    if ($request->precio_costo > 0 && $request->precio_venta > 0) {
        $margen = (($request->precio_venta - $request->precio_costo) / $request->precio_costo) * 100;
    }
    $margen_empresa = 0;
    if ($request->precio_costo > 0 && $request->precio_venta_empresa > 0) {
        $margen_empresa = (($request->precio_venta_empresa - $request->precio_costo) / $request->precio_costo) * 100;
    }

    Precio::create([
        'precio_producto_id' => $productoId,
        'precio_costo' => $request->precio_costo,
        'precio_venta' => $request->precio_venta,
        'precio_venta_empresa' => $request->precio_venta_empresa,
        'precio_margen' => round($margen, 2),
        'precio_margen_empresa' => round($margen_empresa, 2),
        'precio_especial' => $request->precio_especial,
        'precio_moneda' => $request->precio_moneda ?? 'GTQ',
        'precio_justificacion' => $request->precio_justificacion ?? 'Precio inicial',
        'precio_fecha_asignacion' => now()->toDateString(),
        'precio_usuario_id' => Auth::id(),
        'precio_situacion' => 1  // ACTIVO
    ]);
}
    

    /**
     * Actualizar stock actual de un producto
     */
    private function actualizarStock($productoId): void
    {
        $stock = StockActual::firstOrCreate(
            ['stock_producto_id' => $productoId],
            [
                'stock_cantidad_total' => 0,
                'stock_cantidad_disponible' => 0,
                'stock_cantidad_reservada' => 0,
                'stock_valor_total' => 0
            ]
        );

        $producto = Producto::find($productoId);

        if ($producto->producto_requiere_serie) {
            // Para productos con serie, contar series disponibles
            $disponibles = SerieProducto::where('serie_producto_id', $productoId)
                ->where('serie_estado', SerieProducto::ESTADO_DISPONIBLE)
                ->where('serie_situacion', 1)
                ->count();

            $reservadas = SerieProducto::where('serie_producto_id', $productoId)
                ->where('serie_estado', SerieProducto::ESTADO_RESERVADO)
                ->where('serie_situacion', 1)
                ->count();

            $total = $disponibles + $reservadas;
        } else {
            // Para productos sin serie, calcular desde movimientos
            $resumen = Movimiento::resumenPorProducto($productoId);
            $total = $resumen['stock_calculado'];
            
            // ✅ FIX: Calcular reservados dinámicamente desde movimientos activos
            $reservadas = Movimiento::where('mov_producto_id', $productoId)
                ->where('mov_tipo', 'venta')
                ->where('mov_situacion', 3) // 3 = Reservado
                ->sum('mov_cantidad');

            $disponibles = max(0, $total - $reservadas);
        }

        $stock->update([
            'stock_cantidad_total' => $total,
            'stock_cantidad_disponible' => $disponibles,
            'stock_cantidad_reservada' => $reservadas,
            'stock_ultimo_movimiento' => now()
        ]);

        // Verificar alertas de stock
        $this->verificarAlertasStock($producto, $stock);
    }

    /**
     * Verificar y crear alertas de stock bajo/agotado
     */
    private function verificarAlertasStock($producto, $stock): void
    {
        $stockDisponible = $stock->stock_cantidad_disponible;
        $stockMinimo = $producto->producto_stock_minimo;

        // Verificar si ya existe una alerta reciente para este producto
        $alertaReciente = Alerta::where('alerta_producto_id', $producto->producto_id)
            ->whereIn('alerta_tipo', [Alerta::TIPO_STOCK_BAJO, Alerta::TIPO_STOCK_AGOTADO])
            ->where('alerta_fecha', '>=', now()->subDays(1))
            ->where('alerta_resuelta', false)
            ->exists();

        if ($alertaReciente) {
            return; // Ya existe una alerta reciente
        }

        if ($stockDisponible <= 0) {
            // Stock agotado
            Alerta::crearStockAgotado($producto);
        } elseif ($stockMinimo > 0 && $stockDisponible <= $stockMinimo) {
            // Stock bajo
            Alerta::crearStockBajo($producto, $stockDisponible);
        }
    }

    // ================================
    // ESTADÍSTICAS Y ALERTAS
    // ================================

    /**
     * Obtener estadísticas del inventario
     */
    public function getEstadisticas(): JsonResponse
    {
        try {
            $resumen = StockActual::resumenGeneral();

            return response()->json([
                'success' => true,
                'data' => [
                    'totalProductos' => $resumen['total_productos'],
                    'stockTotal' => $resumen['con_stock'],
                    'stockBajo' => $resumen['stock_bajo'],
                    'stockAgotado' => $resumen['sin_stock']
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener alertas de stock
     */
    public function getAlertasStock(): JsonResponse
    {
        try {
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 20); // 20 alertas por página
            
            $alertas = StockActual::with([
                    'producto:producto_id,producto_nombre,pro_codigo_sku,producto_stock_minimo'
                ])
                ->select([
                    'stock_producto_id',
                    'stock_cantidad_disponible'
                ])
                ->get()
                ->filter(function($stock) {
                    $stockMinimo = $stock->producto->producto_stock_minimo ?? 0;
                    return $stock->stock_cantidad_disponible <= $stockMinimo;
                })
                ->values();
    
            // Paginación manual
            $total = $alertas->count();
            $totalPages = ceil($total / $limit);
            $offset = ($page - 1) * $limit;
            $alertasPaginadas = $alertas->slice($offset, $limit)->values();
    
            return response()->json([
                'success' => true,
                'data' => $alertasPaginadas->map(function($stock) {
                    return [
                        'producto_id' => $stock->stock_producto_id,
                        'producto_nombre' => $stock->producto->producto_nombre ?? 'Producto desconocido',
                        'pro_codigo_sku' => $stock->producto->pro_codigo_sku ?? 'N/A',
                        'stock_actual' => $stock->stock_cantidad_disponible,
                        'producto_stock_minimo' => $stock->producto->producto_stock_minimo ?? 0
                    ];
                }),
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_items' => $total,
                    'per_page' => $limit
                ]
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar alertas: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // APIS PARA SELECTS
    // ================================

    /**
     * Obtener categorías activas
     */
    public function getCategoriasActivas(): JsonResponse
    {
        try {
            $categorias = DB::table('pro_categorias')
                ->where('categoria_situacion', 1)
                ->orderBy('categoria_nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categorias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar categorías'
            ], 500);
        }
    }

    /**
     * Obtener subcategorías por categoría
     */
    public function getSubcategoriasPorCategoria($categoriaId): JsonResponse
    {
        try {
            $subcategorias = DB::table('pro_subcategorias')
                ->where('subcategoria_idcategoria', $categoriaId)
                ->where('subcategoria_situacion', 1)
                ->orderBy('subcategoria_nombre')
                ->get();



            return response()->json([
                'success' => true,
                'data' => $subcategorias
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar subcategorías'
            ], 500);
        }
    }

    /**
     * Obtener marcas activas
     */
    public function getMarcasActivas(): JsonResponse
    {
        try {
            $marcas = DB::table('pro_marcas')
                ->where('marca_situacion', 1)
                ->orderBy('marca_descripcion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $marcas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar marcas'
            ], 500);
        }
    }

    /**
     * Obtener modelos por marca
     */
    public function getModelosPorMarca($marcaId): JsonResponse
    {
        try {
            $modelos = DB::table('pro_modelo')
                ->where('modelo_marca_id', $marcaId)
                ->where('modelo_situacion', 1)
                ->orderBy('modelo_descripcion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $modelos
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar modelos'
            ], 500);
        }
    }

    /**
     * Obtener calibres activos
     */
    public function getCalibresActivos(): JsonResponse
    {
        try {
            $calibres = DB::table('pro_calibres')
                ->leftJoin('pro_unidades_medida', 'pro_calibres.calibre_unidad_id', '=', 'pro_unidades_medida.unidad_id')
                ->where('pro_calibres.calibre_situacion', 1)
                ->select([
                    'pro_calibres.calibre_id',
                    'pro_calibres.calibre_nombre',
                    'pro_unidades_medida.unidad_abreviacion'
                ])
                ->orderBy('pro_calibres.calibre_nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $calibres
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar calibres'
            ], 500);
        }
    }

    // Agregar estos métodos al InventarioController existente

/**
 * Subir fotos de un producto - Optimizado con métodos del modelo
 */
public function subirFotos(Request $request, $productoId): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'fotos.*' => 'required|image|mimes:jpg,jpeg,png,webp,heic,heif|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    $producto = Producto::findOrFail($productoId);
    
    // Verificar límite usando el scope del modelo
    $fotosExistentes = ProductoFoto::activas()
        ->where('foto_producto_id', $productoId)
        ->count();
    
    $fotosNuevas = count($request->file('fotos', []));
    
    if (($fotosExistentes + $fotosNuevas) > 5) {
        return response()->json([
            'success' => false,
            'message' => 'El producto no puede tener más de 5 fotos. Actualmente tiene ' . $fotosExistentes
        ], 422);
    }

    DB::beginTransaction();

    try {
        $fotosGuardadas = [];
        $esPrimeraFoto = $fotosExistentes === 0;
        $siguienteOrden = $fotosExistentes + 1;

        foreach ($request->file('fotos') as $index => $archivo) {
            $nombreArchivo = 'producto_' . $productoId . '_' . time() . '_' . Str::random(8) . '.' . $archivo->getClientOriginalExtension();
            $ruta = $archivo->storeAs('productos', $nombreArchivo, 'public');

            $foto = ProductoFoto::create([
                'foto_producto_id' => $productoId,
                'foto_url' => 'productos/' . $nombreArchivo,
                'foto_principal' => $esPrimeraFoto && $index === 0,
                'foto_orden' => $siguienteOrden + $index,
                'foto_situacion' => 1
            ]);

            $fotosGuardadas[] = [
                'foto_id' => $foto->foto_id,
                'foto_url' => $foto->url_completa, // Usar el accessor del modelo
                'foto_principal' => $foto->foto_principal
            ];
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Fotos subidas exitosamente',
            'data' => $fotosGuardadas
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'success' => false,
            'message' => 'Error al subir fotos: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * Eliminar producto (soft delete)
 */// Método destroy mejorado con todas las validaciones necesarias

public function destroy($id): JsonResponse
{
    DB::beginTransaction();

    try {
        $producto = Producto::findOrFail($id);
        
        // VALIDACIÓN 1: Verificar stock actual
        $stockActual = StockActual::where('stock_producto_id', $id)->first();
        if ($stockActual && $stockActual->stock_cantidad_total > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un producto con stock actual (' . $stockActual->stock_cantidad_total . ' unidades)'
            ], 422);
        }

        // VALIDACIÓN 2: Verificar series registradas
        if ($producto->producto_requiere_serie) {
            $seriesActivas = SerieProducto::where('serie_producto_id', $id)
                ->where('serie_situacion', 1)
                ->whereIn('serie_estado', ['disponible', 'reservado'])
                ->count();
            
            if ($seriesActivas > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un producto con series registradas (' . $seriesActivas . ' series activas)'
                ], 422);
            }
        }

        // VALIDACIÓN 3: Verificar movimientos recientes (últimos 30 días)
        $movimientosRecientes = Movimiento::where('mov_producto_id', $id)
            ->where('mov_situacion', 1)
            ->where('mov_fecha', '>=', now()->subDays(30))
            ->count();
        
        if ($movimientosRecientes > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un producto con movimientos en los últimos 30 días'
            ], 422);
        }

        // VALIDACIÓN 4: Verificar lotes asociados
        $lotes = Lote::join('pro_movimientos', 'pro_lotes.lote_id', '=', 'pro_movimientos.mov_lote_id')
            ->where('pro_movimientos.mov_producto_id', $id)
            ->where('pro_lotes.lote_situacion', 1)
            ->count();
        
        if ($lotes > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar un producto asociado a lotes activos'
            ], 422);
        }

        // Si pasa todas las validaciones, proceder con eliminación
        
        // Eliminar fotos físicas
        $fotos = ProductoFoto::where('foto_producto_id', $id)->get();
        foreach ($fotos as $foto) {
            if (Storage::disk('public')->exists($foto->foto_url)) {
                Storage::disk('public')->delete($foto->foto_url);
            }
            $foto->delete();
        }

        // Eliminar precios asociados
        Precio::where('precio_producto_id', $id)->delete();

        // Eliminar stock actual
        if ($stockActual) {
            $stockActual->delete();
        }

        // Soft delete del producto (cambiar situación a 0)
        $producto->update([
            'producto_situacion' => 0,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente'
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar producto: ' . $e->getMessage()
        ], 500);
    }
}
/**
 * Obtener fotos de un producto
 */
public function getFotosProducto($productoId): JsonResponse
{
    try {
        $fotos = ProductoFoto::where('foto_producto_id', $productoId)
            ->where('foto_situacion', 1)
            ->orderBy('foto_principal', 'desc')
            ->orderBy('foto_id')
            ->get()
            ->map(function($foto) {
                return [
                    'foto_id' => $foto->foto_id,
                    'foto_url' => asset('storage/' . $foto->foto_url),
                    'foto_principal' => $foto->foto_principal
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $fotos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar fotos'
        ], 500);
    }
}

/**
 * Eliminar foto específica
 */
public function eliminarFoto($fotoId): JsonResponse
{
    try {
        $foto = ProductoFoto::findOrFail($fotoId);
        
        // Eliminar archivo físico
        if (Storage::disk('public')->exists($foto->foto_url)) {
            Storage::disk('public')->delete($foto->foto_url);
        }

        // Si era foto principal, establecer otra como principal
        if ($foto->foto_principal) {
            $nuevaPrincipal = ProductoFoto::where('foto_producto_id', $foto->foto_producto_id)
                ->where('foto_id', '!=', $fotoId)
                ->where('foto_situacion', 1)
                ->first();
            
            if ($nuevaPrincipal) {
                $nuevaPrincipal->update(['foto_principal' => true]);
            }
        }

        $foto->delete();

        return response()->json([
            'success' => true,
            'message' => 'Foto eliminada exitosamente'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al eliminar foto'
        ], 500);
    }
}

/**
 * Establecer foto como principal
 */
public function establecerFotoPrincipal($fotoId): JsonResponse
{
    try {
        $foto = ProductoFoto::findOrFail($fotoId);
        
        // Quitar foto_principal de todas las demás del mismo producto
        ProductoFoto::where('foto_producto_id', $foto->foto_producto_id)
            ->where('foto_id', '!=', $fotoId)
            ->update(['foto_principal' => false]);
        
        // Establecer esta como principal
        $foto->update(['foto_principal' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Foto principal actualizada'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar foto principal'
        ], 500);
    }
}


/**
 * Procesar fotos para nuevo producto
 */
private function procesarFotosNuevoProducto($fotos, $productoId)
{
    foreach ($fotos as $index => $archivo) {
        $nombreArchivo = 'producto_' . $productoId . '_' . time() . '_' . Str::random(8) . '.' . $archivo->getClientOriginalExtension();
        $ruta = $archivo->storeAs('productos', $nombreArchivo, 'public');

        ProductoFoto::create([
            'foto_producto_id' => $productoId,
            'foto_url' => 'productos/' . $nombreArchivo,
            'foto_principal' => $index === 0, // Primera foto es principal
            'foto_situacion' => 1
        ]);
    }
}






/**
 * Generar número de lote según configuración
 */
private function generarNumeroLote(Request $request, $producto): string
{
    if ($request->generar_lote === 'manual') {
        return $request->numero_lote;
    }

    // Generar automáticamente
    $fecha = now();
    $año = $fecha->format('Y');
    $mes = $fecha->format('m');
    
    // Obtener código de marca
    $marca = DB::table('pro_marcas')
        ->where('marca_id', $producto->producto_marca_id)
        ->first();
    
    $marcaCode = $marca ? strtoupper(substr($marca->marca_descripcion, 0, 3)) : 'AUTO';
    
    // Buscar el siguiente secuencial
    $patron = "L{$año}-{$mes}-{$marcaCode}-%";
    $ultimoLote = Lote::where('lote_codigo', 'LIKE', $patron)
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

/**
 * Actualizar cantidad usada en licencia (si aplica)
 */
// private function actualizarCantidadLicencia($licenciaId, $producto, $cantidad): void
// {
//     // Buscar el arma licenciada correspondiente
//     $armaLicenciada = DB::table('pro_armas_licenciadas')
//         ->where('arma_licencia_id', $licenciaId)
//         ->where('arma_marca_id', $producto->producto_marca_id)
//         ->where('arma_modelo_id', $producto->producto_modelo_id)
//         ->first();
    
//     if ($armaLicenciada) {
//         // Actualizar cantidad utilizada (esto depende de tu estructura de BD)
//         DB::table('pro_armas_licenciadas')
//             ->where('arma_id', $armaLicenciada->arma_id)
//             ->increment('arma_cantidad_utilizada', $cantidad);
//     }
// }


/**
 * 6. Obtener productos con sus asignaciones de licencias
 */
public function getProductosConLicencias(Request $request): JsonResponse
{
    try {
        $query = Producto::activos()
            ->with(['asignacionesLicencias.licencia'])
            ->leftJoin('pro_categorias', 'pro_productos.producto_categoria_id', '=', 'pro_categorias.categoria_id')
            ->leftJoin('pro_marcas', 'pro_productos.producto_marca_id', '=', 'pro_marcas.marca_id')
            ->select([
                'pro_productos.producto_id',
                'pro_productos.producto_nombre',
                'pro_productos.pro_codigo_sku',
                'pro_categorias.categoria_nombre',
                'pro_marcas.marca_descripcion as marca_nombre'
            ]);

        if ($request->filled('licencia_id')) {
            $query->whereHas('asignacionesLicencias', function($q) use ($request) {
                $q->where('asignacion_licencia_id', $request->licencia_id);
            });
        }

        $productos = $query->get();

        return response()->json([
            'success' => true,
            'data' => $productos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar productos: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Verificar si un producto requiere licencia
 */
public function verificarRequiereLicencia($productoId): JsonResponse
{
    try {
        $tieneAsignaciones = LicenciaAsignacionProducto::activas()
            ->where('asignacion_producto_id', $productoId)
            ->exists();

        return response()->json([
            'success' => true,
            'requiere_licencia' => $tieneAsignaciones,
            'data' => [
                'producto_id' => $productoId,
                'requiere_licencia' => $tieneAsignaciones
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al verificar licencia'
        ], 500);
    }
}



/**
 * Buscar licencias para el autocomplete
 */
public function buscarLicencias(Request $request): JsonResponse
{
    try {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $licencias = DB::table('pro_licencias_para_importacion')
            // ->where('lipaimp_situacion', 2) // Solo autorizadas
            ->where(function($q) use ($query) {
                $q->where('lipaimp_numero', 'LIKE', "%{$query}%")
                  ->orWhere('lipaimp_poliza', 'LIKE', "%{$query}%")
                  ->orWhere('lipaimp_descripcion', 'LIKE', "%{$query}%");
            })
            ->whereDate('lipaimp_fecha_vencimiento', '>=', now()) // No vencidas
            ->select([
                'lipaimp_id',
                'lipaimp_numero',
                'lipaimp_poliza', 
                'lipaimp_descripcion',
                'lipaimp_fecha_vencimiento'
            ])
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $licencias
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error en búsqueda de licencias: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * Obtener una licencia específica
 */
public function getLicencia($id): JsonResponse
{
    try {
        $licencia = DB::table('pro_licencias_para_importacion')
            ->where('lipaimp_id', $id)
            ->first();

        if (!$licencia) {
            return response()->json([
                'success' => false,
                'message' => 'Licencia no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $licencia
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener licencia'
        ], 500);
    }
}



// Agregar estos métodos a tu InventarioController

/**
 * Obtener detalle completo de un producto
 */
public function getDetalleProducto($id): JsonResponse
{
    try {
        $producto = Producto::with([
            'stockActual', 
            'fotos' => function($query) {
                $query->where('foto_situacion', 1)->where('foto_principal', true);
            },
            'precios' => function($query) {
                $query->where('precio_situacion', 1)->orderBy('precio_fecha_asignacion', 'desc');
            }
        ])
        ->leftJoin('pro_categorias', 'pro_productos.producto_categoria_id', '=', 'pro_categorias.categoria_id')
        ->leftJoin('pro_subcategorias', 'pro_productos.producto_subcategoria_id', '=', 'pro_subcategorias.subcategoria_id')
        ->leftJoin('pro_marcas', 'pro_productos.producto_marca_id', '=', 'pro_marcas.marca_id')
        ->leftJoin('pro_modelo', 'pro_productos.producto_modelo_id', '=', 'pro_modelo.modelo_id')
        ->leftJoin('pro_calibres', 'pro_productos.producto_calibre_id', '=', 'pro_calibres.calibre_id')
        ->leftJoin('pro_paises', 'pro_productos.producto_madein', '=', 'pro_paises.pais_id')
        ->select([
            'pro_productos.*',
            'pro_categorias.categoria_nombre',
            'pro_subcategorias.subcategoria_nombre',
            'pro_marcas.marca_descripcion as marca_nombre',
            'pro_modelo.modelo_descripcion as modelo_nombre',
            'pro_calibres.calibre_nombre',
            'pro_paises.pais_descripcion as pais_nombre'
        ])
        ->findOrFail($id);

        // Agregar datos adicionales
        $productoData = $producto->toArray();
        
        // Foto principal
        $fotoPrincipal = $producto->fotos->first();
        $productoData['foto_principal'] = $fotoPrincipal ? $fotoPrincipal->url_completa : null;
        
        // Stock actual
        $stock = $producto->stockActual;
        $productoData['stock_cantidad_total'] = $stock ? $stock->stock_cantidad_total : 0;
        // FIX: Calcular disponible real (Total - Reservado)
        $productoData['stock_cantidad_disponible'] = $stock ? max(0, $stock->stock_cantidad_total - $stock->stock_cantidad_reservada) : 0;
        $productoData['stock_cantidad_reservada'] = $stock ? $stock->stock_cantidad_reservada : 0;
        $productoData['stock_cantidad_reservada2'] = $stock ? $stock->stock_cantidad_reservada2 : 0;
        
        // Precios actuales
        $productoData['precios'] = $producto->precios->map(function($precio) {
            return [
                'precio_costo' => number_format($precio->precio_costo, 2),
                'precio_venta' => number_format($precio->precio_venta, 2),
                'precio_venta_empresa' => $precio->precio_venta_empresa ? number_format($precio->precio_venta_empresa, 2) : null,  // ✅ NUEVO
                'precio_especial' => $precio->precio_especial ? number_format($precio->precio_especial, 2) : null,
                'precio_margen' => number_format($precio->precio_margen, 1),
                'precio_margen_empresa' => $precio->precio_margen_empresa ? number_format($precio->precio_margen_empresa, 1) : null,  // ✅ NUEVO
                'precio_moneda' => $precio->precio_moneda,
                'precio_fecha_asignacion' => $precio->precio_fecha_asignacion
            ];
        });


        return response()->json([
            'success' => true,
            'data' => $productoData
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Producto no encontrado'
        ], 404);
    }
}

/**
 * Obtener movimientos recientes de un producto
 */
/**
 * Obtener movimientos de un producto - SIN LÍMITE para historial completo
 */
public function getMovimientosProducto($id): JsonResponse
{
    try {
        // CAMBIO: Quitar el límite por defecto o hacerlo opcional
        $limit = request('limit', null); // null = sin límite
        
        $query = Movimiento::where('mov_producto_id', $id)
            ->where('mov_situacion', 1)
            ->orderBy('mov_fecha', 'desc');
        
        // Solo aplicar límite si se especifica
        if ($limit !== null) {
            $query->limit($limit);
        }
        
        $movimientos = $query->get()
            ->map(function($movimiento) {
                return [
                    'mov_id' => $movimiento->mov_id,
                    'mov_tipo' => ucfirst($movimiento->mov_tipo),
                    'mov_origen' => $movimiento->mov_origen,
                    'mov_destino' => $movimiento->mov_destino ?? null, // AGREGAR destino
                    'mov_cantidad' => $movimiento->mov_cantidad,
                    'mov_fecha' => $movimiento->mov_fecha->format('Y-m-d H:i:s'),
                    'mov_observaciones' => $movimiento->mov_observaciones,
                    'mov_usuario_id' => $movimiento->mov_usuario_id, // AGREGAR para agrupar
                    'usuario_nombre' => $movimiento->usuario->name ?? 'Sistema'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $movimientos,
            'total' => $movimientos->count() // AGREGAR contador
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar movimientos: ' . $e->getMessage()
        ], 500);
    }
}
/**
 * Obtener series de un producto
 */
public function getSeriesProducto($id): JsonResponse
{
    try {
        $producto = Producto::findOrFail($id);
        
        if (!$producto->producto_requiere_serie) {
            return response()->json([
                'success' => false,
                'message' => 'Este producto no maneja series'
            ], 400);
        }

        $series = SerieProducto::where('serie_producto_id', $id)
            ->where(function($q) {
                $q->where('serie_situacion', 1)
                  ->orWhere('serie_estado', 'pendiente');
            })
            ->orderBy('serie_fecha_ingreso', 'desc')
            ->get()
            ->map(function($serie) {
                return [
                    'serie_id' => $serie->serie_id,
                    'serie_numero_serie' => $serie->serie_numero_serie,
                    'serie_estado' => $serie->serie_estado,
                    'serie_fecha_ingreso' => $serie->serie_fecha_ingreso->format('Y-m-d'),
                    'serie_observaciones' => $serie->serie_observaciones
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $series
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar series: ' . $e->getMessage()
        ], 500);
    }
}
            //edición de producto 


            // Agregar este método a tu InventarioController

/**
 * Actualizar un producto existente
 */
public function update(Request $request, $id): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'producto_nombre' => 'required|string|max:100',
        'producto_descripcion' => 'nullable|string',
        'producto_categoria_id' => 'required|integer|exists:pro_categorias,categoria_id',
        'producto_subcategoria_id' => 'required|integer|exists:pro_subcategorias,subcategoria_id',
        'producto_marca_id' => 'required|integer|exists:pro_marcas,marca_id',
        'producto_modelo_id' => 'nullable|integer|exists:pro_modelo,modelo_id',
        'producto_calibre_id' => 'nullable|integer|exists:pro_calibres,calibre_id',
        'producto_madein' => 'nullable|integer|exists:pro_paises,pais_id',
        'producto_codigo_barra' => 'nullable|string|unique:pro_productos,producto_codigo_barra,' . $id . ',producto_id',
        'producto_stock_minimo' => 'nullable|integer|min:0',
        'producto_stock_maximo' => 'nullable|integer|min:0'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        $producto = Producto::findOrFail($id);
        
        // Verificar que no se hayan hecho movimientos si se quiere cambiar información crítica
        $tieneMovimientos = Movimiento::where('mov_producto_id', $id)
            ->where('mov_situacion', 1)
            ->exists();

        // Campos que no se pueden cambiar si ya hay movimientos
        $camposCriticos = ['producto_categoria_id', 'producto_subcategoria_id'];
        
        if ($tieneMovimientos) {
            foreach ($camposCriticos as $campo) {
                if ($request->$campo != $producto->$campo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede cambiar la categoría o subcategoría de un producto con movimientos registrados'
                    ], 422);
                }
            }
        }

        // Actualizar producto
        $producto->update([
            'producto_nombre' => $request->producto_nombre,
            'producto_descripcion' => $request->producto_descripcion,
            'producto_categoria_id' => $request->producto_categoria_id,
            'producto_subcategoria_id' => $request->producto_subcategoria_id,
            'producto_marca_id' => $request->producto_marca_id,
            'producto_modelo_id' => $request->producto_modelo_id,
            'producto_calibre_id' => $request->producto_calibre_id,
            'producto_madein' => $request->producto_madein,
            'producto_codigo_barra' => $request->producto_codigo_barra,
            'producto_stock_minimo' => $request->producto_stock_minimo ?? 0,
            'producto_stock_maximo' => $request->producto_stock_maximo ?? 0
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado exitosamente',
            'data' => $producto
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar producto: ' . $e->getMessage()
        ], 500);
    }
}


//actualizar precio de un producto 

public function actualizarPrecios(Request $request, $id): JsonResponse
{
    $validator = Validator::make($request->all(), [
        'precio_costo' => 'required|numeric|min:0.01',
        'precio_venta' => 'required|numeric|min:0.01|gt:precio_costo',
        'precio_venta_empresa' => 'required|numeric|min:0.01|gt:precio_costo', // AGREGADO
        'precio_especial' => 'nullable|numeric|min:0',
        'precio_justificacion' => 'required|string|max:255',
        'precio_moneda' => 'nullable|string|in:GTQ,USD,EUR'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    DB::beginTransaction();

    try {
        $producto = Producto::findOrFail($id);
        
        // Calcular márgenes para ambos precios
        $margen = (($request->precio_venta - $request->precio_costo) / $request->precio_costo) * 100;
        $margen_empresa = (($request->precio_venta_empresa - $request->precio_costo) / $request->precio_costo) * 100;
        
        // Marcar precios anteriores como históricos
        Precio::where('precio_producto_id', $id)
              ->where('precio_situacion', 1)
              ->update(['precio_situacion' => 0]);
        
        // Crear nuevo precio
        $nuevoPrecio = Precio::create([
            'precio_producto_id' => $id,
            'precio_costo' => $request->precio_costo,
            'precio_venta' => $request->precio_venta,
            'precio_venta_empresa' => $request->precio_venta_empresa, // AGREGADO
            'precio_margen' => round($margen, 2),
            'precio_margen_empresa' => round($margen_empresa, 2), // AGREGADO
            'precio_especial' => $request->precio_especial,
            'precio_moneda' => $request->precio_moneda ?? 'GTQ',
            'precio_justificacion' => $request->precio_justificacion,
            'precio_fecha_asignacion' => now()->toDateString(),
            'precio_usuario_id' => Auth::id(),
            'precio_situacion' => 1
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Precios actualizados exitosamente',
            'data' => $nuevoPrecio
        ]);

    } catch (\Exception $e) {
        DB::rollback();
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar precios: ' . $e->getMessage()
        ], 500);
    }
}
/**
 * Obtener historial de precios de un producto
 */
public function getHistorialPrecios($id): JsonResponse
{
    try {
        $precios = Precio::where('precio_producto_id', $id)
            ->orderBy('precio_fecha_asignacion', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($precio) {
                return [
                    'precio_id' => $precio->precio_id,
                    'precio_costo' => number_format($precio->precio_costo, 2),
                    'precio_venta' => number_format($precio->precio_venta, 2),
                    'precio_venta_empresa' => $precio->precio_venta_empresa ? number_format($precio->precio_venta_empresa, 2) : null,  // ✅ NUEVO
                    'precio_especial' => $precio->precio_especial ? number_format($precio->precio_especial, 2) : null,
                    'precio_margen' => number_format($precio->precio_margen, 1),
                    'precio_margen_empresa' => $precio->precio_margen_empresa ? number_format($precio->precio_margen_empresa, 1) : null,  // ✅ NUEVO
                    'precio_moneda' => $precio->precio_moneda,
                    'precio_justificacion' => $precio->precio_justificacion,
                    'precio_fecha_asignacion' => $precio->precio_fecha_asignacion,
                    'precio_situacion' => $precio->precio_situacion,
                    'usuario_nombre' => $precio->usuario->name ?? 'Sistema'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $precios
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar historial de precios: ' . $e->getMessage()
        ], 500);
    }
}


/**
 * Obtener historial de movimientos con paginación
 */
/**
 * Datos para DataTable de movimientos
 */// En InventarioController.php
public function getMovimientos(Request $request): JsonResponse
{
    try {
        // Parámetros de DataTables
        $draw = $request->get('draw', 1);
        $start = $request->get('start', 0);
        $length = $request->get('length', 25);
        $search = $request->get('search.value', '');
        
        // Filtros personalizados
        $filtroProducto = $request->get('filtro_producto', '');
        $filtroTipo = $request->get('filtro_tipo', '');
        $filtroFecha = $request->get('filtro_fecha', '');

        $query = DB::table('pro_movimientos')
            ->leftJoin('pro_productos', 'pro_movimientos.mov_producto_id', '=', 'pro_productos.producto_id')
            ->leftJoin('users', 'pro_movimientos.mov_usuario_id', '=', 'users.user_id')
            ->leftJoin('pro_lotes', 'pro_movimientos.mov_lote_id', '=', 'pro_lotes.lote_id')
            ->leftJoin('pro_series_productos', 'pro_movimientos.mov_serie_id', '=', 'pro_series_productos.serie_id')
            ->where('pro_movimientos.mov_situacion', 1)
            ->select([
                'pro_movimientos.mov_id',
                'pro_movimientos.mov_fecha',
                'pro_movimientos.mov_tipo',
                'pro_movimientos.mov_origen',
                'pro_movimientos.mov_cantidad',
                'pro_productos.producto_nombre',
                'pro_productos.pro_codigo_sku as producto_sku',
                'users.user_primer_nombre',
                'users.user_primer_apellido',
                'pro_lotes.lote_codigo',
                'pro_series_productos.serie_numero_serie as serie_numero'
            ]);

        // Búsqueda general
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('pro_productos.producto_nombre', 'LIKE', "%{$search}%")
                  ->orWhere('pro_productos.pro_codigo_sku', 'LIKE', "%{$search}%")
                  ->orWhere('pro_movimientos.mov_tipo', 'LIKE', "%{$search}%");
            });
        }

        // Filtros específicos
        if (!empty($filtroProducto)) {
            $query->where(function($q) use ($filtroProducto) {
                $q->where('pro_productos.producto_nombre', 'LIKE', "%{$filtroProducto}%")
                  ->orWhere('pro_productos.pro_codigo_sku', 'LIKE', "%{$filtroProducto}%");
            });
        }

        if (!empty($filtroTipo)) {
            $query->where('pro_movimientos.mov_tipo', $filtroTipo);
        }

        if (!empty($filtroFecha)) {
            $query->whereDate('pro_movimientos.mov_fecha', $filtroFecha);
        }

        // Contar total sin filtros
        $totalRecords = DB::table('pro_movimientos')->where('mov_situacion', 1)->count();
        
        // Contar total con filtros
        $filteredRecords = $query->count();

        // Obtener datos paginados
        $movimientos = $query->orderBy('pro_movimientos.mov_fecha', 'desc')
            ->skip($start)
            ->take($length)
            ->get()
            ->map(function($mov) {
                return [
                    'mov_fecha' => $mov->mov_fecha,
                    'producto_nombre' => $mov->producto_nombre,
                    'producto_sku' => $mov->producto_sku,
                    'mov_tipo' => ucfirst($mov->mov_tipo),
                    'mov_cantidad' => $mov->mov_cantidad,
                    'mov_origen' => $mov->mov_origen,
                    'lote_codigo' => $mov->lote_codigo,
                    'serie_numero' => $mov->serie_numero,
                    'usuario_nombre' => $mov->user_primer_nombre ? 
                        $mov->user_primer_nombre . ' ' . $mov->user_primer_apellido : 'Sistema'
                ];
            });

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $movimientos
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'draw' => 0,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage()
        ]);
    }
}



/**
 * Buscar lotes existentes
 */
public function buscarLotes(Request $request)
{
    try {
        $query = $request->get('q');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Query muy corto'
            ]);
        }

        $lotes = DB::table('pro_lotes as l')
            ->select([
                'l.lote_id',
                'l.lote_codigo',
                'l.lote_descripcion',
                'l.lote_fecha',
                'l.lote_situacion',
                DB::raw('COALESCE(stock_info.cantidad_total, 0) as cantidad_total'),
                DB::raw('COALESCE(stock_info.cantidad_disponible, 0) as cantidad_disponible')
            ])
            ->leftJoin(DB::raw('(
                SELECT 
                    mov_lote_id,
                    SUM(CASE WHEN mov_tipo IN ("ingreso", "ajuste_positivo") THEN mov_cantidad ELSE 0 END) as cantidad_total,
                    SUM(CASE 
                        WHEN mov_tipo IN ("ingreso", "ajuste_positivo") THEN mov_cantidad 
                        WHEN mov_tipo IN ("egreso", "venta", "ajuste_negativo") THEN -mov_cantidad
                        ELSE 0 
                    END) as cantidad_disponible
                FROM pro_movimientos 
                WHERE mov_situacion = 1 AND mov_lote_id IS NOT NULL
                GROUP BY mov_lote_id
            ) as stock_info'), 'l.lote_id', '=', 'stock_info.mov_lote_id')
            ->where('l.lote_situacion', 1) // Solo lotes activos
            ->where('l.lote_codigo', 'LIKE', "%{$query}%")
            ->orderBy('l.lote_fecha', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lotes,
            'message' => 'Lotes encontrados'
        ]);

    } catch (\Exception $e) {
        Log::error('Error buscando lotes: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor',
            'data' => []
        ], 500);
    }
}

/**
 * Obtener detalle de un lote específico
 */
public function obtenerLote($id)
{
    try {
        $lote = DB::table('pro_lotes as l')
            ->select([
                'l.lote_id',
                'l.lote_codigo',
                'l.lote_descripcion', 
                'l.lote_fecha',
                'l.lote_situacion',
                DB::raw('COALESCE(stock_info.cantidad_total, 0) as cantidad_total'),
                DB::raw('COALESCE(stock_info.cantidad_disponible, 0) as cantidad_disponible'),
                DB::raw('COUNT(movimientos.mov_id) as total_movimientos')
            ])
            ->leftJoin(DB::raw('(
                SELECT 
                    mov_lote_id,
                    SUM(CASE WHEN mov_tipo IN ("ingreso", "ajuste_positivo") THEN mov_cantidad ELSE 0 END) as cantidad_total,
                    SUM(CASE 
                        WHEN mov_tipo IN ("ingreso", "ajuste_positivo") THEN mov_cantidad 
                        WHEN mov_tipo IN ("egreso", "venta", "ajuste_negativo") THEN -mov_cantidad
                        ELSE 0 
                    END) as cantidad_disponible
                FROM pro_movimientos 
                WHERE mov_situacion = 1 AND mov_lote_id IS NOT NULL
                GROUP BY mov_lote_id
            ) as stock_info'), 'l.lote_id', '=', 'stock_info.mov_lote_id')
            ->leftJoin('pro_movimientos as movimientos', 'l.lote_id', '=', 'movimientos.mov_lote_id')
            ->where('l.lote_id', $id)
            ->where('l.lote_situacion', 1)
            ->groupBy(['l.lote_id', 'l.lote_codigo', 'l.lote_descripcion', 'l.lote_fecha', 'l.lote_situacion', 'stock_info.cantidad_total', 'stock_info.cantidad_disponible'])
            ->first();

        if (!$lote) {
            return response()->json([
                'success' => false,
                'message' => 'Lote no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $lote,
            'message' => 'Lote obtenido exitosamente'
        ]);

    } catch (\Exception $e) {
        Log::error('Error obteniendo lote: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error interno del servidor'
        ], 500);
    }
}
public function getProductosExcel(Request $request): JsonResponse
{
    try {
        $productos = DB::table('pro_productos as p')
            ->leftJoin('pro_categorias as cat', 'p.producto_categoria_id', '=', 'cat.categoria_id')
            ->leftJoin('pro_subcategorias as sub', 'p.producto_subcategoria_id', '=', 'sub.subcategoria_id')
            ->leftJoin('pro_marcas as mar', 'p.producto_marca_id', '=', 'mar.marca_id')
            ->leftJoin('pro_modelo as mod', 'p.producto_modelo_id', '=', 'mod.modelo_id')
            ->leftJoin('pro_calibres as cal', 'p.producto_calibre_id', '=', 'cal.calibre_id')
            
            // JOIN con series (sin cambios)
            ->leftJoin('pro_series_productos as sp', function($join) {
                $join->on('p.producto_id', '=', 'sp.serie_producto_id')
                     ->where('sp.serie_situacion', '=', 1);
            })
            
            // CORREGIDO: Obtener licencia por SERIE específica (no por producto)
            ->leftJoin('pro_licencia_asignacion_producto as lap', function($join) {
                $join->on('sp.serie_id', '=', 'lap.asignacion_serie_id')
                     ->where('lap.asignacion_situacion', '=', 1);
            })
            
            // JOIN con licencia usando la asignación por serie
            ->leftJoin('pro_licencias_para_importacion as lic', 'lap.asignacion_licencia_id', '=', 'lic.lipaimp_id')
            
            // Stock calculado (sin cambios)
            ->leftJoin(DB::raw('(
                SELECT 
                    mov_producto_id,
                    SUM(CASE 
                        WHEN mov_tipo IN ("ingreso", "ajuste_positivo") THEN mov_cantidad 
                        WHEN mov_tipo IN ("egreso", "venta", "ajuste_negativo") THEN -mov_cantidad 
                        ELSE 0 
                    END) as stock_actual
                FROM pro_movimientos 
                WHERE mov_situacion = 1 
                GROUP BY mov_producto_id
            ) as stock_calc'), 'p.producto_id', '=', 'stock_calc.mov_producto_id')
            
            // Precios más recientes (sin cambios)
            ->leftJoin(DB::raw('(
                SELECT 
                    precio_producto_id,
                    precio_costo,
                    precio_venta,
                    precio_venta_empresa,
                    precio_especial,
                    precio_moneda
                FROM pro_precios p1
                WHERE precio_situacion = 1 
                AND precio_fecha_asignacion = (
                    SELECT MAX(precio_fecha_asignacion)
                    FROM pro_precios p2 
                    WHERE p2.precio_producto_id = p1.precio_producto_id 
                    AND p2.precio_situacion = 1
                )
            ) as pre'), 'p.producto_id', '=', 'pre.precio_producto_id')
            
            // Lotes (solo el más reciente por producto)
            ->leftJoin(DB::raw('(
                SELECT DISTINCT 
                    mov_producto_id, 
                    mov_lote_id
                FROM pro_movimientos m1
                WHERE mov_situacion = 1 AND mov_lote_id IS NOT NULL
                AND mov_id = (
                    SELECT MAX(mov_id)
                    FROM pro_movimientos m2 
                    WHERE m2.mov_producto_id = m1.mov_producto_id 
                    AND m2.mov_lote_id IS NOT NULL
                    AND m2.mov_situacion = 1
                )
            ) as mov_lote'), 'p.producto_id', '=', 'mov_lote.mov_producto_id')
            
            ->leftJoin('pro_lotes as lot', 'mov_lote.mov_lote_id', '=', 'lot.lote_id')
            
            ->select([
                'p.producto_id',
                'p.producto_nombre',
                'p.pro_codigo_sku as codigo',
                'cat.categoria_id',      
                'cat.categoria_nombre',
                'sub.subcategoria_nombre',
                'mar.marca_descripcion as marca_nombre',
                'mod.modelo_descripcion as modelo_nombre',
                'cal.calibre_nombre',
                
                // Serie específica
                DB::raw('CASE 
                    WHEN p.producto_requiere_serie = 1 THEN sp.serie_numero_serie
                    ELSE NULL 
                END as numero_serie'),
                
                DB::raw('CASE 
                    WHEN p.producto_requiere_serie = 1 THEN sp.serie_estado
                    ELSE "disponible"
                END as estado'),
                
                // CORREGIDO: Licencia específica para ESTA serie individual
                DB::raw('CASE 
                    WHEN lic.lipaimp_poliza IS NOT NULL THEN CONCAT("Póliza: ", lic.lipaimp_poliza)
                    ELSE "-"
                END as licencia_codigo'),
                
                'lot.lote_codigo',
                
                // Stock: 1 por serie individual o total calculado
                DB::raw('CASE 
                    WHEN p.producto_requiere_serie = 1 THEN 1
                    ELSE COALESCE(stock_calc.stock_actual, 0)
                END as stock'),
                
                DB::raw('COALESCE(pre.precio_costo, 0) as precio_costo'),
                DB::raw('COALESCE(pre.precio_venta, 0) as precio_venta'),
                DB::raw('COALESCE(pre.precio_venta_empresa, 0) as precio_venta_empresa'),
                'pre.precio_especial',
                DB::raw('COALESCE(pre.precio_moneda, "GTQ") as precio_moneda'),
                
                DB::raw('CASE 
                    WHEN p.producto_requiere_serie = 1 THEN sp.serie_fecha_ingreso
                    ELSE p.created_at
                END as fecha_ingreso')
            ])
            ->where('p.producto_situacion', 1)
            
            // Filtro: productos sin serie O productos con serie que tienen series registradas
            ->where(function($query) {
                $query->where('p.producto_requiere_serie', 0) // Productos sin serie
                      ->orWhere(function($q) {
                          $q->where('p.producto_requiere_serie', 1) // Productos con serie
                            ->whereNotNull('sp.serie_id');
                      });
            })
            
            ->orderBy('p.producto_nombre')
            ->orderBy('sp.serie_numero_serie')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
            'total' => $productos->count()
        ]);

    } catch (\Exception $e) {
        \Log::error('Error en getProductosExcel: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Error al cargar vista detallada: ' . $e->getMessage()
        ], 500);
    }
}
}