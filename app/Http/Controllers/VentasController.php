<?php

namespace App\Http\Controllers;

use App\Models\Ventas;
use Illuminate\Http\Request;
use App\Models\MetodoPago;
use App\Models\Clientes;

use App\Models\Producto;
use App\Models\SerieProducto;
use App\Models\Lote;
use App\Models\Movimiento;
use App\Models\StockActual;
use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;



class VentasController extends Controller
{

    // Constante para el monto de tenencia
private const MONTO_TENENCIA = 60.00;

    public function index()
    {
        // // Datos necesarios para los selects y filtros
        $categorias = DB::table('pro_categorias')->where('categoria_situacion', 1)->get();
        $clientes = DB::table('users')->where('user_rol', 2)->get();
        $metodopago = MetodoPago::orderBy('metpago_descripcion')->paginate(15);

        return view('ventas.index', compact(
            'categorias',
            'clientes',
            'metodopago'
        ));
    }


    public function buscarClientes(Request $request)
    {
        // echo json_encode($_GET);
        // exit;
        $nit = trim($request->query('nit', ''));
        $dpi = trim($request->query('dpi', ''));

        $clientes = DB::table('pro_clientes')
            ->where('cliente_situacion', 1)
            ->when($nit, function ($q) use ($nit) {
                $q->where('cliente_nit', $nit);
            })
            ->when($dpi, function ($q) use ($dpi) {
                $q->where('cliente_dpi', $dpi);
            })
            ->select(
                'cliente_id',
                'cliente_nombre1',
                'cliente_nombre2',
                'cliente_apellido1',
                'cliente_apellido2',
                'cliente_nit',
                'cliente_dpi',
                'cliente_tipo',
                'cliente_nom_empresa'
            )
            ->orderBy('cliente_nombre1')
            ->get();

        return response()->json($clientes);
    }


    public function getSubcategorias($categoria_id)
    {
        $subcategorias = DB::table('pro_productos as p')
            ->join('pro_subcategorias as s', 'p.producto_subcategoria_id', '=', 's.subcategoria_id')
            ->where('p.producto_categoria_id', $categoria_id)
            ->where('p.producto_situacion', 1)
            ->select('s.subcategoria_id', 's.subcategoria_nombre')
            ->distinct()
            ->orderBy('s.subcategoria_nombre')
            ->get();

        return response()->json($subcategorias);
    }


    public function getMarcas($subcategoria_id)
    {
        $marcas = DB::table('pro_productos as p')
            ->join('pro_marcas as m', 'p.producto_marca_id', '=', 'm.marca_id')
            ->where('p.producto_subcategoria_id', $subcategoria_id)
            ->where('p.producto_situacion', 1)
            ->select('m.marca_id', 'm.marca_descripcion')
            ->distinct()
            ->get();

        return response()->json($marcas);
    }

    public function getModelos($marca_id)
    {
        $modelos = DB::table('pro_productos as p')
            ->join('pro_modelo as m', 'p.producto_modelo_id', '=', 'm.modelo_id')
            ->where('p.producto_marca_id', $marca_id)  // ← Corregido
            ->where('p.producto_situacion', 1)
            ->whereNotNull('p.producto_modelo_id')     // ← Solo productos con modelo
            ->select('m.modelo_id', 'm.modelo_descripcion') // ← Verifica este campo
            ->distinct()
            ->orderBy('m.modelo_descripcion')
            ->get();

        return response()->json($modelos);
    }

    public function getCalibres($modelo_id)
    {
        $calibres = DB::table('pro_productos as p')
            ->join('pro_calibres as c', 'p.producto_calibre_id', '=', 'c.calibre_id')
            ->where('p.producto_modelo_id', $modelo_id)
            ->where('p.producto_situacion', 1)
            ->whereNotNull('p.producto_calibre_id')  // Solo productos que tengan calibre
            ->select('c.calibre_id', 'c.calibre_nombre')
            ->distinct()
            ->orderBy('c.calibre_nombre')
            ->get();

        return response()->json($calibres);
    }

    public function buscarProductos(Request $request)
    {
        $categoria_id = trim($request->query('categoria_id', ''));
        $subcategoria_id = trim($request->query('subcategoria_id', ''));
        $marca_id = trim($request->query('marca_id', ''));
        $modelo_id = trim($request->query('modelo_id', ''));
        $calibre_id = trim($request->query('calibre_id', ''));
        $busqueda = trim($request->query('busqueda', ''));

        $productos = DB::table('pro_productos')
            ->leftJoin('pro_precios', 'producto_id', '=', 'precio_producto_id')
            ->Join('pro_categorias', 'producto_categoria_id', '=', 'categoria_id')
            ->Join('pro_subcategorias', 'producto_subcategoria_id', '=', 'subcategoria_id')
            ->leftJoin('pro_marcas', 'producto_marca_id', '=', 'marca_id')
            ->leftJoin('pro_modelo', 'producto_modelo_id', '=', 'modelo_id')
            ->leftJoin('pro_calibres', 'producto_calibre_id', '=', 'calibre_id')
            ->leftJoin('pro_paises', 'producto_madein', '=', 'pais_id')
            ->leftJoin('pro_stock_actual', 'stock_producto_id', '=', 'producto_id')
            ->leftJoin('pro_productos_fotos', function ($join) {
                $join->on('producto_id', '=', 'foto_producto_id')
                    ->where('foto_principal', 1);
            })
            ->where('producto_situacion', 1)
            ->when($categoria_id, fn($q) => $q->where('categoria_id', $categoria_id))
            ->when($subcategoria_id, fn($q) => $q->where('subcategoria_id', $subcategoria_id))
            ->when($marca_id, fn($q) => $q->where('marca_id', $marca_id))
            ->when($modelo_id, fn($q) => $q->where('modelo_id', $modelo_id))
            ->when($calibre_id, fn($q) => $q->where('calibre_id', $calibre_id))
            ->when($busqueda, function ($q) use ($busqueda) {
                $q->where(function ($query) use ($busqueda) {
                    $query->where('producto_nombre', 'like', "%{$busqueda}%")
                        ->orWhere('marca_descripcion', 'like', "%{$busqueda}%")
                        ->orWhere('modelo_descripcion', 'like', "%{$busqueda}%")
                        ->orWhere('calibre_nombre', 'like', "%{$busqueda}%");
                });
            })
            ->select(
                'producto_id',
                'producto_nombre',
                'producto_descripcion',
                'producto_categoria_id',
                'categoria_nombre',
                'producto_subcategoria_id',
                'subcategoria_nombre',
                'producto_marca_id',
                'marca_descripcion',
                'producto_modelo_id',
                'modelo_descripcion',
                'producto_calibre_id',
                'calibre_nombre',
                'pais_descripcion',
                'producto_situacion',
                'producto_requiere_serie',
                'precio_venta',
                'precio_venta_empresa',
                'foto_url',
                'stock_cantidad_total',
                'stock_cantidad_reservada',
                'producto_requiere_stock'
            )
            ->orderBy('producto_nombre')
            ->get();

        // Series + LOTES (igual que series, pero para pro_lotes)
        $productos = $productos->map(function ($producto) {
            $productoArray = (array) $producto;


            // 👇 Calcular stock real
            $stockTotal = $producto->stock_cantidad_total ?? 0;
            $stockReservado = $producto->stock_cantidad_reservada ?? 0;

            // 👇 IMPORTANTE: Sobrescribir stock_cantidad_total con el stock real disponible
            $productoArray['stock_cantidad_total'] = max(0, $stockTotal - $stockReservado);



            // SERIES
            if ($producto->producto_requiere_serie == 1) {
                $seriesDisponibles = DB::table('pro_series_productos')
                    ->where('serie_producto_id', $producto->producto_id)
                    ->where('serie_estado', 'disponible')
                    ->select('serie_producto_id', 'serie_numero_serie', 'serie_situacion')
                    ->orderBy('serie_numero_serie')
                    ->get();

                $productoArray['series_disponibles'] = $seriesDisponibles;
                $productoArray['cantidad_series'] = $seriesDisponibles->count();
            } else {
                $productoArray['series_disponibles'] = [];
                $productoArray['cantidad_series'] = 0;
            }

            // LOTES (nuevo)
            $lotes = DB::table('pro_lotes')
                ->where('lote_producto_id', $producto->producto_id)
                ->select(
                    'lote_id',
                    'lote_producto_id',
                    'lote_codigo',
                    'lote_cantidad_total'
                    // agrega aquí más columnas si las tienes (lote_codigo, fecha_vencimiento, etc.)
                )
                ->orderBy('lote_id')
                ->get();

            $productoArray['lotes'] = $lotes;                           // listado de lotes
            $productoArray['cantidad_lotes'] = $lotes->count();                  // cuántos lotes
            $productoArray['lotes_cantidad_total'] = $lotes->sum('lote_cantidad_total'); // suma de cantidades

            return (object) $productoArray;
        });

        return response()->json($productos);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  /**
 * Store a newly created resource in storage.
 */
/**
 * Store a newly created resource in storage.
 */
public function guardarCliente(Request $request)
{
    try {
        $reglas = [
            'cliente_nombre1' => ['required', 'string', 'max:50'],
            'cliente_nombre2' => ['nullable', 'string', 'max:50'],
            'cliente_apellido1' => ['required', 'string', 'max:50'],
            'cliente_apellido2' => ['nullable', 'string', 'max:50'],
            'cliente_dpi' => ['nullable', 'string', 'max:20', 'unique:pro_clientes,cliente_dpi'],
            'cliente_nit' => ['nullable', 'string', 'max:20', 'unique:pro_clientes,cliente_nit'],
            'cliente_direccion' => ['nullable', 'string', 'max:255'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_correo' => ['nullable', 'email', 'max:150'],
            'cliente_tipo' => ['required', 'integer', 'in:1,2,3'],
            'cliente_user_id' => ['nullable', 'integer'],
            'cliente_nom_empresa' => ['nullable', 'string', 'max:255'],
            'cliente_nom_vendedor' => ['nullable', 'string', 'max:255'],
            'cliente_cel_vendedor' => ['nullable', 'string', 'max:30'],
            'cliente_ubicacion' => ['nullable', 'string', 'max:255'],
            'cliente_pdf_licencia' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];

        $mensajes = [
            'cliente_nombre1.required' => 'El primer nombre es obligatorio',
            'cliente_apellido1.required' => 'El primer apellido es obligatorio',
            'cliente_dpi.unique' => 'Ya existe un cliente registrado con este DPI',
            'cliente_nit.unique' => 'Ya existe un cliente registrado con este NIT',
            'cliente_correo.email' => 'El correo electrónico no tiene un formato válido',
            'cliente_tipo.required' => 'El tipo de cliente es obligatorio',
            'cliente_tipo.in' => 'El tipo de cliente no es válido',
            'cliente_pdf_licencia.mimes' => 'El archivo debe ser un PDF',
            'cliente_pdf_licencia.max' => 'El archivo PDF no debe superar los 10MB',
        ];

        $data = $request->validate($reglas, $mensajes);

        // Asegurar que cliente_situacion tenga valor por defecto
        if (!isset($data['cliente_situacion'])) {
            $data['cliente_situacion'] = 1;
        }

        // Manejar subida de PDF si existe
        if ($request->hasFile('cliente_pdf_licencia')) {
            $file = $request->file('cliente_pdf_licencia');
            $fileName = 'licencia_' . time() . '_' . uniqid() . '.pdf';
            $path = $file->storeAs('clientes/licencias', $fileName, 'public');
            $data['cliente_pdf_licencia'] = $path;
        }

        $cliente = Clientes::create($data);

        return response()->json([
            'codigo' => 1,
            'mensaje' => 'Cliente guardado correctamente',
            'data' => $cliente
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        \Log::error('Error de validación:', ['errors' => $e->errors()]);
        return response()->json([
            'codigo' => 0,
            'mensaje' => 'Error de validación',
            'errores' => $e->errors()
        ], 422);

    } catch (\Exception $e) {
        \Log::error('Error al guardar cliente:', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'data' => $request->except('cliente_pdf_licencia')
        ]);

        return response()->json([
            'codigo' => 0,
            'mensaje' => 'Error al guardar el cliente',
            'detalle' => $e->getMessage()
        ], 500);
    }
}





    ///hecho por morales batz 

public function obtenerVentasPendientes(Request $request): JsonResponse
{
    try {
        $clienteId = $request->input('cliente_id');
        $vendedorId = $request->input('vendedor_id');

        $query = "
        SELECT 
            v.ven_id,
            precio.precio_venta,
            precio.precio_venta_empresa,
            v.ven_user,
            d.det_producto_id,
            d.det_ven_id,
            v.ven_fecha,
            
            TRIM(
                CONCAT_WS(' ',
                    TRIM(c.cliente_nombre1),
                    TRIM(c.cliente_nombre2),
                    TRIM(c.cliente_apellido1),
                    TRIM(c.cliente_apellido2)
                )
            ) AS cliente,
            CASE 
                WHEN c.cliente_nom_empresa IS NULL OR c.cliente_nom_empresa = ''
                    THEN 'Cliente Individual'
                ELSE c.cliente_nom_empresa
            END AS empresa,
            TRIM(
                CONCAT_WS(' ',
                    TRIM(u.user_primer_nombre),
                    TRIM(u.user_segundo_nombre),
                    TRIM(u.user_primer_apellido),
                    TRIM(u.user_segundo_apellido)
                )
            ) AS vendedor,
            GROUP_CONCAT(DISTINCT p.producto_nombre SEPARATOR ', ') AS productos,
                        
            GROUP_CONCAT(DISTINCT mov.mov_serie_id ORDER BY mov.mov_serie_id SEPARATOR ',') AS series_ids,
            GROUP_CONCAT(DISTINCT serie.serie_numero_serie ORDER BY serie.serie_numero_serie SEPARATOR ',') AS series_numeros,
            GROUP_CONCAT(DISTINCT mov.mov_lote_id ORDER BY mov.mov_lote_id SEPARATOR ',') AS lotes_ids,
            
            GROUP_CONCAT(
                DISTINCT CONCAT(mov.mov_lote_id, ' (', mov.mov_cantidad, ')')
                ORDER BY mov.mov_lote_id SEPARATOR ', '
            ) AS lotes_display,
            
            GROUP_CONCAT(
                DISTINCT CONCAT(mov.mov_serie_id, ' (', mov.mov_cantidad, ')')
                ORDER BY mov.mov_serie_id SEPARATOR ', '
            ) AS series_display,
            
            v.ven_total_vendido,
            v.ven_situacion
        FROM pro_detalle_ventas d
        INNER JOIN pro_ventas v ON v.ven_id = d.det_ven_id
        INNER JOIN users u ON u.user_id = v.ven_user
        INNER JOIN pro_clientes c ON c.cliente_id = v.ven_cliente
        INNER JOIN pro_productos p ON d.det_producto_id = p.producto_id
        inner join pro_precios precio on precio.precio_producto_id = p.producto_id
        LEFT JOIN pro_movimientos mov ON mov.mov_producto_id = d.det_producto_id
            AND mov.mov_situacion = 3
            AND mov.mov_documento_referencia = CONCAT('VENTA-', v.ven_id)
        LEFT JOIN pro_series_productos serie ON serie.serie_id = mov.mov_serie_id
        WHERE d.det_situacion = 'PENDIENTE'
            AND v.ven_situacion = 'PENDIENTE'
        ";

        $bindings = [];

        if ($clienteId) {
            $query .= " AND v.ven_cliente = ?";
            $bindings[] = $clienteId;
        }

        if ($vendedorId) {
            $query .= " AND v.ven_user = ?";
            $bindings[] = $vendedorId;
        }

        $query .= "
        GROUP BY 
            v.ven_id,
            v.ven_fecha,
            v.ven_user,
            d.det_producto_id,
            d.det_ven_id,
            v.ven_total_vendido,
            v.ven_situacion,
            c.cliente_nombre1,
            c.cliente_nombre2,
            c.cliente_apellido1,
            c.cliente_apellido2,
            c.cliente_nom_empresa,
            u.user_primer_nombre,
            u.user_segundo_nombre,
            u.user_primer_apellido,
            u.user_segundo_apellido,
            precio.precio_venta,
            precio.precio_venta_empresa
        ORDER BY v.ven_fecha DESC
        ";

        $ventas = DB::select($query, $bindings);

        $ventasProcesadas = array_map(function ($venta) {
            return [
                'precio_venta' => $venta->precio_venta,
                'precio_venta_empresa' => $venta->precio_venta_empresa,
                'ven_id' => $venta->ven_id,
                'ven_user' => $venta->ven_user,
                'det_producto_id' => $venta->det_producto_id,
                'det_ven_id' => $venta->det_ven_id,
                'ven_fecha' => $venta->ven_fecha,
                'cliente' => $venta->cliente,
                'empresa' => $venta->empresa,
                'vendedor' => $venta->vendedor,
                'productos' => $venta->productos,
                'lotes_ids' => $venta->lotes_ids ?? '',
                'series_ids' => $venta->series_ids ?? '',
                'series_numeros' => $venta->series_numeros ?? '', // 👈 nuevo campo
                'lotes_display' => $venta->lotes_display ?? '',
                'series_display' => $venta->series_display ?? '',
                'ven_total_vendido' => $venta->ven_total_vendido,
                'ven_situacion' => $venta->ven_situacion
            ];
        }, $ventas);

        return response()->json([
            'success' => true,
            'total' => count($ventasProcesadas),
            'data' => $ventasProcesadas,
            'filtros_aplicados' => [
                'cliente_id' => $clienteId,
                'vendedor_id' => $vendedorId
            ]
        ]);

    } catch (QueryException $e) {
        Log::error('Pendientes SQL', [
            'sql' => $e->getSql(),
            'bindings' => $e->getBindings(),
            'info' => $e->errorInfo
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Error SQL en pendientes: ' . $e->getMessage(),
        ], 500);

    } catch (\Throwable $e) {
        Log::error('Pendientes error', ['err' => $e]);
        return response()->json([
            'success' => false,
            'message' => 'Error en pendientes: ' . $e->getMessage(),
        ], 500);
    }
}


    public function autorizarVenta(Request $request): JsonResponse
    {


        $venId = (int) $request->input('ven_id');
        $venUser = (int) $request->input('ven_user');
        $detProductoId = (int) $request->input('det_producto_id');
        $productoId = (int) $request->input('producto_id', $detProductoId);

        $seriesIds = collect($request->input('series_ids', []))
            ->map(fn($v) => (int) $v)->filter()->unique()->values();

        $lotesIds = collect($request->input('lotes_ids', []))
            ->map(fn($v) => (int) $v)->filter()->unique()->values();

        $qtyTotal = 0;
        $detallesProcesados = [];

        try {
            DB::transaction(function () use ($venId, $venUser, $productoId, $seriesIds, $lotesIds, &$qtyTotal, &$detallesProcesados) {
                // Paso 1: Activar venta
                $affected = DB::table('pro_ventas')
                    ->where('ven_id', $venId)
                    ->where('ven_user', $venUser)
                    ->update(['ven_situacion' => 'ACTIVA']);

                if ($affected === 0) {
                    throw new \RuntimeException('No se pudo activar la venta (pro_ventas).');
                }

                // Paso 2: Activar detalles
                DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venId)
                    ->update(['det_situacion' => 'ACTIVO']);

                $ref = 'VENTA-' . $venId;

                // Paso 3: SERIES
                if ($seriesIds->isNotEmpty()) {
                    $seriesCantidades = DB::table('pro_movimientos')
                        ->select('mov_serie_id', DB::raw('SUM(mov_cantidad) as qty'))
                        ->where('mov_documento_referencia', $ref)
                        ->where('mov_situacion', 3)
                        ->whereIn('mov_serie_id', $seriesIds)
                        ->groupBy('mov_serie_id')
                        ->pluck('qty', 'mov_serie_id');

                    if ($seriesCantidades->isNotEmpty()) {
                        DB::table('pro_series_productos')
                            ->whereIn('serie_id', $seriesCantidades->keys())
                            ->update(['serie_estado' => 'vendido', 'serie_situacion' => 1]);

                        DB::table('pro_movimientos')
                            ->whereIn('mov_serie_id', $seriesCantidades->keys())
                            ->where('mov_documento_referencia', $ref)
                            ->where('mov_situacion', 3)
                            ->update(['mov_situacion' => 1]);

                        $sumaSeries = $seriesCantidades->sum();
                        $qtyTotal += $sumaSeries;

                        $detallesProcesados[] = 'Series procesadas: ' . $seriesCantidades
                            ->map(fn($qty, $id) => "$id ($qty)")
                            ->implode(', ');
                    }
                }

                // Paso 4: LOTES
                if ($lotesIds->isNotEmpty()) {
                    $lotesCantidades = DB::table('pro_movimientos')
                        ->select('mov_lote_id', DB::raw('SUM(mov_cantidad) as qty'))
                        ->where('mov_documento_referencia', $ref)
                        ->where('mov_situacion', 3)
                        ->whereIn('mov_lote_id', $lotesIds)
                        ->groupBy('mov_lote_id')
                        ->pluck('qty', 'mov_lote_id');

                    if ($lotesCantidades->isNotEmpty()) {
                        DB::table('pro_movimientos')
                            ->whereIn('mov_lote_id', $lotesCantidades->keys())
                            ->where('mov_documento_referencia', $ref)
                            ->where('mov_situacion', 3)
                            ->update(['mov_situacion' => 1]);

                        $sumaLotes = $lotesCantidades->sum();
                        $qtyTotal += $sumaLotes;

                        $detallesProcesados[] = 'Lotes procesados: ' . $lotesCantidades
                            ->map(fn($qty, $id) => "$id ($qty)")
                            ->implode(', ');
                    }
                }

                // Paso 5: Actualizar stock
                if ($qtyTotal > 0) {
                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_reservada', $qtyTotal);

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_disponible', $qtyTotal);

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_total', $qtyTotal);
                }
            }, 3);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Venta autorizada exitosamente',
                'meta' => [
                        'qty_total' => $qtyTotal,
                        'detalles' => $detallesProcesados,
                        'ven_id' => $venId
                    ],
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'No se pudo autorizar la venta.',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }
public function actualizarLicencias(Request $request): JsonResponse
{
    try {
        // Log para ver qué está llegando
        Log::info('🟢 Payload recibido en actualizarLicencias:', $request->all());

        $venId = (int) $request->input('ven_id');
        $licencias = $request->input('licencias', []);

        foreach ($licencias as $lic) {
            Log::info('📄 Licencia recibida:', $lic);
        }

        DB::beginTransaction();

        foreach ($licencias as $licencia) {
            $serieId = $licencia['serie_id'] ?? null;
            $licAnterior = $licencia['licencia_anterior'] ?? null;
            $licNueva = $licencia['licencia_nueva'] ?? null;

            Log::info("🔧 Procesando serie_id=$serieId, anterior=$licAnterior, nueva=$licNueva");

            $ref = 'VENTA-' . $venId;

            // Intento de actualización
            $affected = DB::table('pro_movimientos')
                ->where('mov_serie_id', $serieId)
                ->where('mov_documento_referencia', $ref)
                ->update([
                    'mov_licencia_anterior' => $licAnterior,
                    'mov_licencia_nueva' => $licNueva,
                    'updated_at' => now(),
                ]);

            Log::info("✅ Movimientos afectados: $affected");

            // Actualiza también la serie
            // DB::table('pro_series_productos')
            //     ->where('serie_id', $serieId)
            //     ->update([
            //         'serie_licencia_actual' => $licNueva,
            //         'updated_at' => now(),
            //     ]);

            // Log::info("🟢 Serie actualizada correctamente: $serieId");
        }

        DB::commit();

        return response()->json([
            'codigo' => 1,
            'mensaje' => 'Licencias actualizadas correctamente (modo depuración)',
        ]);

    } catch (\Throwable $e) {
        DB::rollBack();

        // Log completo del error
        Log::error('❌ Error en actualizarLicencias: ' . $e->getMessage());
        Log::error($e->getTraceAsString());

        // Devolver mensaje exacto del error
        return response()->json([
            'codigo' => 0,
            'mensaje' => 'Error al actualizar licencias (depuración)',
            'detalle' => $e->getMessage(),
            'linea' => $e->getLine(),
            'archivo' => $e->getFile(),
        ], 500);
    }
}
    public function buscarReservaPorCliente(int $clienteId): JsonResponse
    {
        $resultados = DB::select("
        SELECT 
            v.ven_id,
            v.ven_user,
            d.det_producto_id,
            d.det_ven_id,
            d.det_cantidad,
            d.det_precio,
            v.ven_fecha,
            v.ven_total_vendido,
            v.ven_situacion,
            TRIM(
                CONCAT_WS(' ',
                    TRIM(c.cliente_nombre1),
                    TRIM(c.cliente_nombre2),
                    TRIM(c.cliente_apellido1),
                    TRIM(c.cliente_apellido2)
                )
            ) AS cliente,
            CASE 
                WHEN c.cliente_nom_empresa IS NULL OR c.cliente_nom_empresa = ''
                    THEN 'Cliente Individual'
                ELSE c.cliente_nom_empresa
            END AS empresa,
            TRIM(
                CONCAT_WS(' ',
                    TRIM(u.user_primer_nombre),
                    TRIM(u.user_segundo_nombre),
                    TRIM(u.user_primer_apellido),
                    TRIM(u.user_segundo_apellido)
                )
            ) AS vendedor,
            p.producto_nombre,
            IFNULL(p.producto_requiere_serie, 0) AS producto_requiere_serie,
            IFNULL(p.producto_requiere_stock, 1) AS producto_requiere_stock,
            GROUP_CONCAT(DISTINCT serie.serie_numero_serie ORDER BY serie.serie_numero_serie SEPARATOR ',') AS series_ids,
            GROUP_CONCAT(DISTINCT mov.mov_lote_id ORDER BY mov.mov_lote_id SEPARATOR ',') AS lotes_ids,
            GROUP_CONCAT(
                DISTINCT CONCAT(mov.mov_lote_id, ' (', mov.mov_cantidad, ')')
                ORDER BY mov.mov_lote_id SEPARATOR ', '
            ) AS lotes_display,
            GROUP_CONCAT(
                DISTINCT CONCAT(mov.mov_serie_id, ' (', mov.mov_cantidad, ')')
                ORDER BY mov.mov_serie_id SEPARATOR ', '
            ) AS series_display
        FROM pro_detalle_ventas d
        INNER JOIN pro_ventas v   ON v.ven_id = d.det_ven_id
        INNER JOIN users u        ON u.user_id = v.ven_user
        INNER JOIN pro_clientes c ON c.cliente_id = v.ven_cliente
        INNER JOIN pro_productos p ON d.det_producto_id = p.producto_id
        LEFT JOIN pro_movimientos mov ON mov.mov_producto_id = d.det_producto_id
            AND mov.mov_situacion = 2
            AND mov.mov_documento_referencia = CONCAT('RESERVA-', v.ven_id)
        LEFT JOIN pro_series_productos serie ON serie.serie_id = mov.mov_serie_id
        WHERE d.det_situacion = 'PENDIENTE'
          AND v.ven_situacion = 'RESERVADA'
          AND v.ven_cliente = ?
        GROUP BY 
            v.ven_id, v.ven_fecha, v.ven_user, v.ven_total_vendido, v.ven_situacion,
            d.det_producto_id, d.det_ven_id, d.det_cantidad, d.det_precio,
            c.cliente_nombre1, c.cliente_nombre2, c.cliente_apellido1, c.cliente_apellido2, c.cliente_nom_empresa,
            u.user_primer_nombre, u.user_segundo_nombre, u.user_primer_apellido, u.user_segundo_apellido,
            p.producto_nombre, p.producto_requiere_serie, p.producto_requiere_stock
        ORDER BY v.ven_fecha DESC
    ", [$clienteId]);

        if (empty($resultados)) {
            return response()->json([
                'success' => true,
                'reservas' => [],
                'message' => 'Cliente sin reservas vigentes.'
            ]);
        }

        // Helpers para parsear series y lotes
        $parseSeries = function ($r) {
            if (empty($r->series_ids))
                return [];
            return array_values(array_filter(array_map('trim', explode(',', $r->series_ids))));
        };

        $parseLotes = function ($r) {
            if (empty($r->lotes_ids))
                return [];
            $cantPorLote = [];
            if (!empty($r->lotes_display)) {
                foreach (explode(',', $r->lotes_display) as $par) {
                    if (preg_match('/(\d+)\s*\((\d+)\)/', $par, $m)) {
                        $cantPorLote[(int) $m[1]] = (int) $m[2];
                    }
                }
            }
            $out = [];
            foreach (array_unique(array_filter(array_map('trim', explode(',', $r->lotes_ids)))) as $lid) {
                $lidNum = (int) $lid;
                if ($lidNum > 0) {
                    $out[] = [
                        'lote_id' => $lidNum,
                        'cantidad' => $cantPorLote[$lidNum] ?? 0,
                    ];
                }
            }
            return $out;
        };

        // AGRUPAR por ven_id (cada grupo = una reserva)
        $grupos = collect($resultados)->groupBy('ven_id');

        $reservas = $grupos->map(function ($grupo, $venId) use ($clienteId, $parseSeries, $parseLotes) {
            $head = $grupo->first();

            $items = $grupo->map(function ($r) use ($parseSeries, $parseLotes) {
                return [
                    'producto_id' => (int) $r->det_producto_id,
                    'nombre' => $r->producto_nombre,
                    'cantidad' => (int) $r->det_cantidad,
                    'precio' => (float) $r->det_precio,
                    'precio_venta' => (float) $r->det_precio,
                    'precio_venta_empresa' => 0,
                    'precio_activo' => 'normal',
                    'precio_personalizado' => null,
                    'producto_requiere_serie' => (int) $r->producto_requiere_serie,
                    'producto_requiere_stock' => (int) $r->producto_requiere_stock,
                    'seriesSeleccionadas' => $parseSeries($r),
                    'series_disponibles' => [],
                    'lotes' => [],
                    'lotesSeleccionados' => $parseLotes($r),
                    'stock_cantidad_total' => null,
                    'marca' => '',
                    'imagen' => null
                ];
            })->values();

            return [
                'numero' => 'RESERVA-' . $venId,
                'ven_id' => (int) $venId,
                'fecha' => $head->ven_fecha,
                'cliente' => $head->cliente,
                'cliente_id' => $clienteId,
                'empresa' => $head->empresa,
                'vendedor' => $head->vendedor,
                'total' => (float) $head->ven_total_vendido,
                'situacion' => $head->ven_situacion,
                'items' => $items,
            ];
        })->values(); // array indexado

        return response()->json([
            'success' => true,
            'count' => $reservas->count(),
            'reservas' => $reservas,
        ]);
    }

    public function procesarReserva(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cliente_id' => 'required|exists:pro_clientes,cliente_id',
                'fecha_reserva' => 'required|date',
                'subtotal' => 'required|numeric|min:0',
                'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
                'descuento_monto' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'productos' => 'required|array|min:1',
                'productos.*.producto_id' => 'required|exists:pro_productos,producto_id',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio_unitario' => 'required|numeric|min:0',
                'productos.*.subtotal_producto' => 'required|numeric|min:0',
                'productos.*.requiere_serie' => 'required|in:0,1',
                'productos.*.producto_requiere_stock' => 'required|in:0,1',
                'productos.*.series_seleccionadas' => 'nullable|array',
                'productos.*.series_con_tenencia' => 'nullable|array',
                'productos.*.tiene_lotes' => 'required|boolean',
                'productos.*.lotes_seleccionados' => 'nullable|array',
                'productos.*.lotes_seleccionados.*.lote_id' => 'nullable|exists:pro_lotes,lote_id',
                'productos.*.lotes_seleccionados.*.cantidad' => 'nullable|integer|min:1',
                'observaciones' => 'nullable|string|max:500',
                'dias_vigencia' => 'nullable|integer|min:1|max:30',
            ]);

            DB::beginTransaction();

            // 1. CREAR LA RESERVA EN pro_ventas
            $reservaId = DB::table('pro_ventas')->insertGetId([
                'ven_user' => auth()->id(),
                'ven_fecha' => $request->fecha_reserva,
                'ven_cliente' => $request->cliente_id,
                'ven_total_vendido' => $request->total,
                'ven_descuento' => $request->descuento_monto ?? 0,
                'ven_observaciones' => $request->observaciones ?? 'Reserva - Vigente por ' . ($request->dias_vigencia ?? 7) . ' días',
                'ven_situacion' => 'RESERVADA'
            ]);

            // 2. PROCESAR CADA PRODUCTO
            foreach ($request->productos as $productoData) {
                $producto = DB::table('pro_productos')->where('producto_id', $productoData['producto_id'])->first();

                if (!$producto) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Producto con ID {$productoData['producto_id']} no encontrado"
                    ], 422);
                }

                // Validar stock disponible SOLO si el producto lo necesita
                if ($productoData['producto_requiere_stock'] == 1) {
                    $stockActual = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();

                    // Para reservas, considerar el stock ya reservado
                    $stockDisponibleReal = ($stockActual->stock_cantidad_disponible ?? 0) - ($stockActual->stock_cantidad_reservada ?? 0);

                    if ($stockDisponibleReal < $productoData['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Stock insuficiente para reservar: {$producto->producto_nombre}. Disponible: {$stockDisponibleReal}"
                        ], 422);
                    }
                }

                // Insertar detalle de reserva en pro_detalle_ventas
                $detalleId = DB::table('pro_detalle_ventas')->insertGetId([
                    'det_ven_id' => $reservaId,
                    'det_producto_id' => $producto->producto_id,
                    'det_cantidad' => $productoData['cantidad'],
                    'det_precio' => $productoData['precio_unitario'],
                    'det_descuento' => 0,
                    'det_situacion' => 'PENDIENTE', // Usar PENDIENTE ya que está en tu ENUM
                ]);

                if ($productoData['producto_requiere_stock'] == 1) {
                    // PROCESAR SEGÚN TIPO DE PRODUCTO
                    if ($productoData['requiere_serie'] == 1) {
                        // ===============================
                        // PRODUCTO CON SERIES
                        // ===============================
                        $seriesSeleccionadas = $productoData['series_seleccionadas'] ?? [];

                        if (empty($seriesSeleccionadas)) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "El producto {$producto->producto_nombre} requiere series"
                            ], 422);
                        }

                        if (count($seriesSeleccionadas) !== $productoData['cantidad']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Debe seleccionar exactamente {$productoData['cantidad']} serie(s) para {$producto->producto_nombre}"
                            ], 422);
                        }

                        // Obtener IDs de series por número de serie
                        $seriesInfo = DB::table('pro_series_productos')
                            ->whereIn('serie_numero_serie', $seriesSeleccionadas)
                            ->where('serie_producto_id', $producto->producto_id)
                            ->where('serie_estado', 'disponible')
                            ->where('serie_situacion', 1)
                            ->get();

                        if ($seriesInfo->count() !== count($seriesSeleccionadas)) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Una o más series no están disponibles para el producto {$producto->producto_nombre}"
                            ], 422);
                        }

                        // Actualizar series: cambiar a pendiente (reservado)
                        $seriesIds = $seriesInfo->pluck('serie_id');
                        DB::table('pro_series_productos')
                            ->whereIn('serie_id', $seriesIds)
                            ->update([
                                'serie_estado' => 'reserva', // Usar pendiente como estado de reserva
                                'serie_situacion' => 1, // Mantener activo (no 0 porque es reserva, no venta)
                            ]);

                        // Registrar movimiento por cada serie
                        foreach ($seriesInfo as $serieInfo) {
                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id' => $producto->producto_id,
                                'mov_tipo' => 'reserva',
                                'mov_origen' => 'almacen',
                                'mov_destino' => 'reservado',
                                'mov_cantidad' => 1,
                                'mov_precio_unitario' => $productoData['precio_unitario'],
                                'mov_valor_total' => $productoData['precio_unitario'],
                                'mov_fecha' => now(),
                                'mov_usuario_id' => auth()->id(),
                                'mov_serie_id' => $serieInfo->serie_id,
                                'mov_documento_referencia' => "RESERVA-{$reservaId}",
                                'mov_observaciones' => "Reserva - Serie: {$serieInfo->serie_numero_serie}",
                                'mov_situacion' => 2, // situacion 2 = reservado
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }

                        // Actualizar stock reservado
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', count($seriesSeleccionadas));

                    } else {
                        // ===============================
                        // PRODUCTO SIN SERIES (CON O SIN LOTES)
                        // ===============================
                        if ($productoData['tiene_lotes'] && !empty($productoData['lotes_seleccionados'])) {
                            // PRODUCTO CON LOTES
                            $lotesSeleccionados = $productoData['lotes_seleccionados'];
                            $totalAsignado = array_sum(array_column($lotesSeleccionados, 'cantidad'));

                            if ($totalAsignado !== $productoData['cantidad']) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => "La cantidad asignada en lotes ($totalAsignado) debe coincidir con la cantidad del producto (" . $productoData['cantidad'] . ") para " . $producto->producto_nombre
                                ], 422);
                            }

                            // Procesar cada lote
                            foreach ($lotesSeleccionados as $loteData) {
                                $lote = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();

                                if (!$lote || $lote->lote_cantidad_disponible < $loteData['cantidad']) {
                                    DB::rollBack();
                                    return response()->json([
                                        'success' => false,
                                        'message' => 'El lote ' . ($lote->lote_codigo ?? 'desconocido') . ' no tiene suficiente stock disponible',
                                    ], 422);
                                }

                                // NO DECREMENTAR el stock del lote en reservas, solo marcar como reservado
                                // Si quieres llevar control, deberías tener un campo lote_cantidad_reservada

                                // Registrar movimiento de lote (solo registro, sin afectar inventario)
                                DB::table('pro_movimientos')->insert([
                                    'mov_producto_id' => $producto->producto_id,
                                    'mov_tipo' => 'reserva',
                                    'mov_origen' => 'almacen',
                                    'mov_destino' => 'reservado',
                                    'mov_cantidad' => $loteData['cantidad'],
                                    'mov_precio_unitario' => $productoData['precio_unitario'],
                                    'mov_valor_total' => $productoData['precio_unitario'] * $loteData['cantidad'],
                                    'mov_fecha' => now(),
                                    'mov_usuario_id' => auth()->id(),
                                    'mov_lote_id' => $loteData['lote_id'],
                                    'mov_documento_referencia' => "RESERVA-{$reservaId}",
                                    'mov_observaciones' => "Reserva - Lote: {$lote->lote_codigo}",
                                    'mov_situacion' => 2, // situacion 2 = reservado
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }
                        } else {
                            // PRODUCTO SIN LOTES (STOCK GENERAL)
                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id' => $producto->producto_id,
                                'mov_tipo' => 'reserva',
                                'mov_origen' => 'almacen',
                                'mov_destino' => 'reservado',
                                'mov_cantidad' => $productoData['cantidad'],
                                'mov_precio_unitario' => $productoData['precio_unitario'],
                                'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                                'mov_fecha' => now(),
                                'mov_usuario_id' => auth()->id(),
                                'mov_lote_id' => null,
                                'mov_documento_referencia' => "RESERVA-{$reservaId}",
                                'mov_observaciones' => "Reserva - Stock general",
                                'mov_situacion' => 2, // situacion 2 = reservado
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }

                        // Incrementar stock reservado
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $productoData['cantidad']);
                    }
                } else {
                    // Productos que NO requieren stock
                    DB::table('pro_movimientos')->insert([
                        'mov_producto_id' => $producto->producto_id,
                        'mov_tipo' => 'reserva',
                        'mov_origen' => 'almacen',
                        'mov_destino' => 'reservado',
                        'mov_cantidad' => $productoData['cantidad'],
                        'mov_precio_unitario' => $productoData['precio_unitario'],
                        'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                        'mov_fecha' => now(),
                        'mov_usuario_id' => auth()->id(),
                        'mov_lote_id' => null,
                        'mov_documento_referencia' => "RESERVA-{$reservaId}",
                        'mov_observaciones' => "Reserva - Producto sin control de stock",
                        'mov_situacion' => 2, // situacion 2 = reservado
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva procesada exitosamente',
                'reserva_id' => $reservaId,
                'numero_reserva' => "RESERVA-{$reservaId}",
                'vigencia_dias' => $request->dias_vigencia ?? 30
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error procesando reserva: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al procesar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    ///////// termino morales batz

    public function cancelarVenta(Request $request): JsonResponse
    {
        $venId = (int) $request->input('ven_id');
        $motivoCancelacion = $request->input('motivo', 'Cancelación de venta');

        try {
            DB::transaction(function () use ($venId, $motivoCancelacion) {
                // Verificar que la venta existe y está PENDIENTE
                $venta = DB::table('pro_ventas')
                    ->where('ven_id', $venId)
                    ->first();

                if (!$venta) {
                    throw new \RuntimeException('Venta no encontrada.');
                }

                if ($venta->ven_situacion !== 'PENDIENTE') {
                    throw new \RuntimeException('Solo se pueden cancelar ventas en estado PENDIENTE.');
                }

                $ref = 'VENTA-' . $venId;

                // 1. REVERTIR MOVIMIENTOS Y STOCK SEGÚN TIPO

                // 1.1 Revertir SERIES
                $movimientosSeries = DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $ref)
                    ->where('mov_situacion', 3)
                    ->whereNotNull('mov_serie_id')
                    ->get();

                if ($movimientosSeries->isNotEmpty()) {
                    $seriesIds = $movimientosSeries->pluck('mov_serie_id')->unique();

                    // Revertir estado de series a disponible
                    DB::table('pro_series_productos')
                        ->whereIn('serie_id', $seriesIds)
                        ->update([
                            'serie_estado' => 'disponible',
                            'serie_situacion' => 1
                        ]);

                    // Cancelar movimientos de series
                    DB::table('pro_movimientos')
                        ->whereIn('mov_serie_id', $seriesIds)
                        ->where('mov_documento_referencia', $ref)
                        ->where('mov_situacion', 3)
                        ->update(['mov_situacion' => 0]);

                    // Revertir stock reservado
                    foreach ($movimientosSeries->groupBy('mov_producto_id') as $productoId => $movs) {
                        $cantidad = $movs->sum('mov_cantidad');
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->decrement('stock_cantidad_reservada', $cantidad);
                    }
                }

                // 1.2 Revertir LOTES
                $movimientosLotes = DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $ref)
                    ->where('mov_situacion', 3)
                    ->whereNotNull('mov_lote_id')
                    ->get();

                if ($movimientosLotes->isNotEmpty()) {
                    foreach ($movimientosLotes as $mov) {
                        // Devolver cantidad al lote (PRIMERO TOTAL, LUEGO DISPONIBLE)
                        DB::table('pro_lotes')
                            ->where('lote_id', $mov->mov_lote_id)
                            ->increment('lote_cantidad_total', $mov->mov_cantidad);

                        DB::table('pro_lotes')
                            ->where('lote_id', $mov->mov_lote_id)
                            ->increment('lote_cantidad_disponible', $mov->mov_cantidad);

                        // Reactivar lote si estaba agotado
                        $lote = DB::table('pro_lotes')->where('lote_id', $mov->mov_lote_id)->first();
                        if ($lote && $lote->lote_cantidad_disponible > 0 && $lote->lote_situacion == 0) {
                            DB::table('pro_lotes')
                                ->where('lote_id', $mov->mov_lote_id)
                                ->update(['lote_situacion' => 1]);
                        }
                    }

                    // Cancelar movimientos de lotes
                    $lotesIds = $movimientosLotes->pluck('mov_lote_id')->unique();
                    DB::table('pro_movimientos')
                        ->whereIn('mov_lote_id', $lotesIds)
                        ->where('mov_documento_referencia', $ref)
                        ->where('mov_situacion', 3)
                        ->update(['mov_situacion' => 0]);

                    // Revertir stock reservado
                    foreach ($movimientosLotes->groupBy('mov_producto_id') as $productoId => $movs) {
                        $cantidad = $movs->sum('mov_cantidad');
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->decrement('stock_cantidad_reservada', $cantidad);
                    }
                }

                // 1.3 Cancelar movimientos sin serie ni lote (situación 1 - ya autorizados previamente o stock general)
                $movimientosGenerales = DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $ref)
                    ->where('mov_situacion', 1)
                    ->whereNull('mov_serie_id')
                    ->whereNull('mov_lote_id')
                    ->get();

                if ($movimientosGenerales->isNotEmpty()) {
                    foreach ($movimientosGenerales->groupBy('mov_producto_id') as $productoId => $movs) {
                        $cantidad = $movs->sum('mov_cantidad');

                        // Estos movimientos ya estaban confirmados, no tienen stock reservado que revertir
                        // Solo se marcan como ANULADAs
                    }

                    DB::table('pro_movimientos')
                        ->where('mov_documento_referencia', $ref)
                        ->where('mov_situacion', 1)
                        ->whereNull('mov_serie_id')
                        ->whereNull('mov_lote_id')
                        ->update(['mov_situacion' => 0]);
                }

                // 2. CANCELAR DETALLES DE VENTA
                DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venId)
                    ->update(['det_situacion' => 'ANULADA']);

                // 3. CANCELAR VENTA
                DB::table('pro_ventas')
                    ->where('ven_id', $venId)
                    ->update([
                        'ven_situacion' => 'ANULADA',
                        'ven_observaciones' => $motivoCancelacion
                    ]);

                // 4. CANCELAR PAGOS Y CUOTAS
                $pago = DB::table('pro_pagos')
                    ->where('pago_venta_id', $venId)
                    ->first();

                if ($pago) {
                    // Cancelar pago principal
                    DB::table('pro_pagos')
                        ->where('pago_id', $pago->pago_id)
                        ->update([
                            'pago_estado' => 'ANULADA',
                            'updated_at' => now()
                        ]);

                    // Cancelar detalles de pago
                    DB::table('pro_detalle_pagos')
                        ->where('det_pago_pago_id', $pago->pago_id)
                        ->update([
                            'det_pago_estado' => 'ANULADO',
                            'updated_at' => now()
                        ]);

                    // Cancelar cuotas si existen
                    DB::table('pro_cuotas')
                        ->where('cuota_control_id', $pago->pago_id)
                        ->update([
                            'cuota_estado' => 'ANULADA',
                            'updated_at' => now()
                        ]);
                }

                // 5. CANCELAR COMISIÓN DEL VENDEDOR
                DB::table('pro_porcentaje_vendedor')
                    ->where('porc_vend_ven_id', $venId)
                    ->update([
                        'porc_vend_estado' => 'CANCELADO',
                        'porc_vend_situacion' => 'INACTIVO'
                    ]);

                // 6. ACTUALIZAR HISTORIAL DE CAJA
                DB::table('cja_historial')
                    ->where('cja_id_venta', $venId)
                    ->update([
                        'cja_situacion' => 'ANULADA',
                        'cja_observaciones' => $motivoCancelacion
                    ]);

            }, 3);

            return response()->json([
                'success' => true,
                'message' => 'Venta cancelada exitosamente',
                'venta_id' => $venId
            ]);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cancelar la venta.',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


public function procesarVenta(Request $request): JsonResponse
{
    try {
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'fecha_venta' => 'required|date',
            'subtotal' => 'required|numeric|min:0',
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_monto' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'metodo_pago' => 'required|in:1,2,3,4,5,6',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:pro_productos,producto_id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
            'productos.*.subtotal_producto' => 'required|numeric|min:0',
            'productos.*.requiere_serie' => 'required|in:0,1',
            'productos.*.producto_requiere_stock' => 'required|in:0,1',
            'productos.*.series_seleccionadas' => 'nullable|array',
            'productos.*.tiene_lotes' => 'required|boolean',
            'productos.*.lotes_seleccionados' => 'nullable|array',
            'productos.*.lotes_seleccionados.*.lote_id' => 'nullable|exists:pro_lotes,lote_id',
            'productos.*.lotes_seleccionados.*.cantidad' => 'nullable|integer|min:1',
            'pago' => 'required|array',
            'reserva_id' => 'nullable|exists:pro_ventas,venta_id', // ID de reserva si viene de una
        ]);

        DB::beginTransaction();

        // ========================================
        // 1. VERIFICAR SI VIENE DE UNA RESERVA
        // ========================================
        $esDeReserva = false;
        $reservaExistente = null;

        if ($request->has('reserva_id') && $request->reserva_id) {
            // Buscar reserva existente
            $reservaExistente = DB::table('pro_ventas')
                ->where('ven_id', $request->reserva_id) // Corregido: usar ven_id
                ->where('ven_cliente', $request->cliente_id)
                ->where('ven_situacion', 'RESERVADA')
                ->first();

            if ($reservaExistente) {
                $esDeReserva = true;
            }
        } else {
            // Buscar si hay una reserva pendiente para este cliente (opcional)
            $reservaExistente = DB::table('pro_ventas')
                ->where('ven_cliente', $request->cliente_id)
                ->where('ven_situacion', 'RESERVADA')
                ->where('ven_user', auth()->id())
                ->whereDate('ven_fecha', '>=', now()->subDays(30)) // Reservas de últimos 30 días
                ->orderBy('ven_id', 'desc') // Corregido: usar ven_id en lugar de venta_id
                ->first();

            if ($reservaExistente) {
                $esDeReserva = true;
            }
        }

        // ========================================
        // 2. CREAR O ACTUALIZAR VENTA
        // ========================================
        if ($esDeReserva && $reservaExistente) {
            // ACTUALIZAR RESERVA A VENTA
            $ventaId = $reservaExistente->ven_id; // Corregido: usar ven_id
            
            DB::table('pro_ventas')
                ->where('ven_id', $ventaId) // Corregido: usar ven_id
                ->update([
                    'ven_fecha' => $request->fecha_venta,
                    'ven_total_vendido' => $request->total,
                    'ven_descuento' => $request->descuento_monto ?? 0,
                    'ven_observaciones' => 'Venta confirmada desde reserva - Pendiente de autorizar por digecam',
                    'ven_situacion' => 'PENDIENTE',
                    'updated_at' => now()
                ]);

            // LIMPIAR DETALLES ANTERIORES (opcional, o puedes hacer merge)
            DB::table('pro_detalle_ventas')
                ->where('det_ven_id', $ventaId)
                ->delete();

            // ACTUALIZAR MOVIMIENTOS PREVIOS DE RESERVA A VENTA
            DB::table('pro_movimientos')
                ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
                ->update([
                    'mov_tipo' => 'venta',
                    'mov_destino' => 'cliente',
                    'mov_documento_referencia' => "VENTA-{$ventaId}",
                    'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
                    'mov_situacion' => 3, // Pendiente de validar
                    'updated_at' => now()
                ]);

        } else {
            // CREAR NUEVA VENTA
            $ventaId = DB::table('pro_ventas')->insertGetId([
                'ven_user' => auth()->id(),
                'ven_fecha' => $request->fecha_venta,
                'ven_cliente' => $request->cliente_id,
                'ven_total_vendido' => $request->total,
                'ven_descuento' => $request->descuento_monto ?? 0,
                'ven_observaciones' => 'Venta Pendiente de autorizar por digecam',
                'ven_situacion' => 'PENDIENTE',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $totalPagado = 0;
        $cantidadPagos = 0;

        // ========================================
        // 3. PROCESAR CADA PRODUCTO
        // ========================================
        foreach ($request->productos as $productoData) {
            $producto = DB::table('pro_productos')->where('producto_id', $productoData['producto_id'])->first();

            if (!$producto) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Producto con ID {$productoData['producto_id']} no encontrado"
                ], 422);
            }

            // Validar stock disponible SOLO si el producto lo necesita
            if ($productoData['producto_requiere_stock'] == 1) {
                $stockActual = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();
                
                // Si viene de reserva, considerar stock reservado
                $stockDisponible = $esDeReserva 
                    ? ($stockActual->stock_cantidad_disponible ?? 0)
                    : (($stockActual->stock_cantidad_disponible ?? 0) - ($stockActual->stock_cantidad_reservada ?? 0));

                if ($stockDisponible < $productoData['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para el producto: {$producto->producto_nombre}"
                    ], 422);
                }
            }

            // Insertar detalle de venta
            $detalleId = DB::table('pro_detalle_ventas')->insertGetId([
                'det_ven_id' => $ventaId,
                'det_producto_id' => $producto->producto_id,
                'det_cantidad' => $productoData['cantidad'],
                'det_precio' => $productoData['precio_unitario'],
                'det_descuento' => 0,
                'det_situacion' => 'PENDIENTE',
            ]);

            if ($productoData['producto_requiere_stock'] == 1) {
                // PROCESAR SEGÚN TIPO DE PRODUCTO
                if ($productoData['requiere_serie'] == 1) {
                    // ===============================
                    // PRODUCTO CON SERIES
                    // ===============================
                    $seriesSeleccionadas = $productoData['series_seleccionadas'] ?? [];

                    if (empty($seriesSeleccionadas)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "El producto {$producto->producto_nombre} requiere series"
                        ], 422);
                    }

                    if (count($seriesSeleccionadas) !== $productoData['cantidad']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Debe seleccionar exactamente {$productoData['cantidad']} serie(s) para {$producto->producto_nombre}"
                        ], 422);
                    }

                    // Buscar series: disponibles O reservadas (si viene de reserva)
                    $estadosPermitidos = $esDeReserva ? ['disponible', 'reserva'] : ['disponible'];
                    
                    $seriesInfo = DB::table('pro_series_productos')
                        ->whereIn('serie_numero_serie', $seriesSeleccionadas)
                        ->where('serie_producto_id', $producto->producto_id)
                        ->whereIn('serie_estado', $estadosPermitidos)
                        ->where('serie_situacion', 1)
                        ->get();

                    if ($seriesInfo->count() !== count($seriesSeleccionadas)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => "Una o más series no están disponibles para el producto {$producto->producto_nombre}"
                        ], 422);
                    }

                    // Actualizar series a pendiente
                    $seriesIds = $seriesInfo->pluck('serie_id');
                    DB::table('pro_series_productos')
                        ->whereIn('serie_id', $seriesIds)
                        ->update([
                            'serie_estado' => 'pendiente',
                            'serie_situacion' => 0,
                        ]);

                    // Registrar/actualizar movimientos
                    if ($esDeReserva) {
                        // Actualizar movimientos existentes de reserva
                        DB::table('pro_movimientos')
                            ->whereIn('mov_serie_id', $seriesIds)
                            ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
                            ->update([
                                'mov_tipo' => 'venta',
                                'mov_destino' => 'cliente',
                                'mov_documento_referencia' => "VENTA-{$ventaId}",
                                'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
                                'mov_situacion' => 3,
                                'updated_at' => now()
                            ]);
                    } else {
                        // Crear nuevos movimientos
                        foreach ($seriesInfo as $serieInfo) {
                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id' => $producto->producto_id,
                                'mov_tipo' => 'venta',
                                'mov_origen' => 'venta',
                                'mov_destino' => 'cliente',
                                'mov_cantidad' => 1,
                                'mov_precio_unitario' => $productoData['precio_unitario'],
                                'mov_valor_total' => $productoData['precio_unitario'],
                                'mov_fecha' => now(),
                                'mov_usuario_id' => auth()->id(),
                                'mov_serie_id' => $serieInfo->serie_id,
                                'mov_documento_referencia' => "VENTA-{$ventaId}",
                                'mov_observaciones' => "Venta - Serie: {$serieInfo->serie_numero_serie}",
                                'mov_situacion' => 3,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }

                    // Actualizar stock: si viene de reserva, solo cambiar de reservado a vendido
                    if (!$esDeReserva) {
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', count($seriesSeleccionadas));
                    }

                } else {
                    // ===============================
                    // PRODUCTO SIN SERIES
                    // ===============================
                    if ($productoData['tiene_lotes'] && !empty($productoData['lotes_seleccionados'])) {
                        // PRODUCTO CON LOTES
                        $lotesSeleccionados = $productoData['lotes_seleccionados'];
                        $totalAsignado = array_sum(array_column($lotesSeleccionados, 'cantidad'));

                        if ($totalAsignado !== $productoData['cantidad']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "La cantidad asignada en lotes ($totalAsignado) debe coincidir con la cantidad del producto ({$productoData['cantidad']}) para {$producto->producto_nombre}"
                            ], 422);
                        }

                        foreach ($lotesSeleccionados as $loteData) {
                            $lote = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();

                            if (!$lote || $lote->lote_cantidad_disponible < $loteData['cantidad']) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => "El lote {$lote->lote_codigo} no tiene suficiente stock disponible"
                                ], 422);
                            }

                            // Decrementar stock de lote
                            DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->decrement('lote_cantidad_disponible', $loteData['cantidad']);

                            DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->decrement('lote_cantidad_total', $loteData['cantidad']);

                            // Registrar movimiento
                            if ($esDeReserva) {
                                // Actualizar movimientos de reserva
                                DB::table('pro_movimientos')
                                    ->where('mov_lote_id', $loteData['lote_id'])
                                    ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
                                    ->update([
                                        'mov_tipo' => 'venta',
                                        'mov_destino' => 'cliente',
                                        'mov_documento_referencia' => "VENTA-{$ventaId}",
                                        'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
                                        'mov_situacion' => 3,
                                        'updated_at' => now()
                                    ]);
                            } else {
                                DB::table('pro_movimientos')->insert([
                                    'mov_producto_id' => $producto->producto_id,
                                    'mov_tipo' => 'venta',
                                    'mov_origen' => 'venta',
                                    'mov_destino' => 'cliente',
                                    'mov_cantidad' => $loteData['cantidad'],
                                    'mov_precio_unitario' => $productoData['precio_unitario'],
                                    'mov_valor_total' => $productoData['precio_unitario'] * $loteData['cantidad'],
                                    'mov_fecha' => now(),
                                    'mov_usuario_id' => auth()->id(),
                                    'mov_lote_id' => $loteData['lote_id'],
                                    'mov_documento_referencia' => "VENTA-{$ventaId}",
                                    'mov_observaciones' => "Venta - Lote: {$lote->lote_codigo}",
                                    'mov_situacion' => 3,
                                    'created_at' => now(),
                                    'updated_at' => now()
                                ]);
                            }

                            // Cambiar situación si se agotó
                            $loteActualizado = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();
                            if ($loteActualizado->lote_cantidad_disponible <= 0) {
                                DB::table('pro_lotes')
                                    ->where('lote_id', $loteData['lote_id'])
                                    ->update(['lote_situacion' => 0]);
                            }
                        }
                    } else {
                        // PRODUCTO SIN LOTES
                        if ($esDeReserva) {
                            DB::table('pro_movimientos')
                                ->where('mov_producto_id', $producto->producto_id)
                                ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
                                ->whereNull('mov_lote_id')
                                ->update([
                                    'mov_tipo' => 'venta',
                                    'mov_destino' => 'cliente',
                                    'mov_documento_referencia' => "VENTA-{$ventaId}",
                                    'mov_observaciones' => 'Venta - Stock general',
                                    'mov_situacion' => 1,
                                    'updated_at' => now()
                                ]);
                        } else {
                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id' => $producto->producto_id,
                                'mov_tipo' => 'venta',
                                'mov_origen' => 'venta',
                                'mov_destino' => 'cliente',
                                'mov_cantidad' => $productoData['cantidad'],
                                'mov_precio_unitario' => $productoData['precio_unitario'],
                                'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                                'mov_fecha' => now(),
                                'mov_usuario_id' => auth()->id(),
                                'mov_lote_id' => null,
                                'mov_documento_referencia' => "VENTA-{$ventaId}",
                                'mov_observaciones' => "Venta - Stock general",
                                'mov_situacion' => 1,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }
                    }

                    // Actualizar stock
                    if (!$esDeReserva) {
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $productoData['cantidad']);
                    }
                }
            } else {
                // Productos sin control de stock
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => 'venta',
                    'mov_origen' => 'venta',
                    'mov_destino' => 'cliente',
                    'mov_cantidad' => $productoData['cantidad'],
                    'mov_precio_unitario' => $productoData['precio_unitario'],
                    'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                    'mov_fecha' => now(),
                    'mov_usuario_id' => auth()->id(),
                    'mov_lote_id' => null,
                    'mov_documento_referencia' => "VENTA-{$ventaId}",
                    'mov_observaciones' => "Venta - Stock general",
                    'mov_situacion' => 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // ========================================
        // 4. PROCESAR PAGOS
        // ========================================
        $metodoPago = $request->metodo_pago;
        $totalVenta = $request->total;

        if ($metodoPago == '6') {
            // SISTEMA DE CUOTAS
            $abonoInicial = $request->pago['abono_inicial'] ?? 0;
            $cuotas = $request->pago['cuotas'] ?? [];

            $pagoId = DB::table('pro_pagos')->insertGetId([
                'pago_venta_id' => $ventaId,
                'pago_monto_total' => $totalVenta,
                'pago_monto_pagado' => $abonoInicial,
                'pago_monto_pendiente' => $totalVenta - $abonoInicial,
                'pago_tipo_pago' => 'CUOTAS',
                'pago_cantidad_cuotas' => $request->pago['cantidad_cuotas'],
                'pago_abono_inicial' => $abonoInicial,
                'pago_estado' => 'PENDIENTE',
                'pago_fecha_inicio' => now(),
                'pago_fecha_completado' => $abonoInicial >= $totalVenta ? now() : null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if ($abonoInicial > 0) {
                $metodoAbonoId = $request->pago['metodo_abono'] === 'transferencia' ? 4 : 1;

                DB::table('pro_detalle_pagos')->insert([
                    'det_pago_pago_id' => $pagoId,
                    'det_pago_cuota_id' => null,
                    'det_pago_fecha' => now(),
                    'det_pago_monto' => $abonoInicial,
                    'det_pago_metodo_pago' => $metodoAbonoId,
                    'det_pago_banco_id' => 1,
                    'det_pago_numero_autorizacion' => $request->pago['numero_autorizacion_abono'] ?? null,
                    'det_pago_tipo_pago' => 'ABONO_INICIAL',
                    'det_pago_estado' => 'VALIDO',
                    'det_pago_observaciones' => 'Abono inicial de la venta',
                    'det_pago_usuario_registro' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $totalPagado += $abonoInicial;
                $cantidadPagos++;
            }

            $fechaBase = now();
            foreach ($cuotas as $index => $cuotaData) {
                if ($cuotaData['monto'] > 0) {
                    $fechaVencimiento = $fechaBase->copy()->addMonths($index + 1);

                    DB::table('pro_cuotas')->insert([
                        'cuota_control_id' => $pagoId,
                        'cuota_numero' => $index + 1,
                        'cuota_monto' => $cuotaData['monto'],
                        'cuota_fecha_vencimiento' => $fechaVencimiento,
                        'cuota_estado' => 'PENDIENTE',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        } else {
            // PAGO ÚNICO
            $pagoId = DB::table('pro_pagos')->insertGetId([
                'pago_venta_id' => $ventaId,
                'pago_monto_total' => $totalVenta,
                'pago_monto_pagado' => $totalVenta,
                'pago_monto_pendiente' => 0,
                'pago_tipo_pago' => 'UNICO',
                'pago_cantidad_cuotas' => 1,
                'pago_abono_inicial' => $totalVenta,
                'pago_estado' => 'PENDIENTE',
                'pago_fecha_inicio' => now(),
                'pago_fecha_completado' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::table('pro_detalle_pagos')->insert([
                'det_pago_pago_id' => $pagoId,
                'det_pago_cuota_id' => null,
                'det_pago_fecha' => now(),
                'det_pago_monto' => $totalVenta,
                'det_pago_metodo_pago' => $metodoPago,
                'det_pago_banco_id' => 1,
                'det_pago_numero_autorizacion' => $request->numero_autorizacion ?? null,
                'det_pago_tipo_pago' => 'PAGO_UNICO',
                'det_pago_estado' => 'VALIDO',
                'det_pago_observaciones' => 'Pago completo de la venta',
                'det_pago_usuario_registro' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $totalPagado = $totalVenta;
            $cantidadPagos = 1;
        }

        $porcentaje = 2.5;

        //  NUEVO: Calcular total de tenencias cobradas
        $totalTenencias = 0;
        foreach ($request->productos as $productoData) {
            if (isset($productoData['series_con_tenencia']) && is_array($productoData['series_con_tenencia'])) {
                $totalTenencias += count($productoData['series_con_tenencia']) * self::MONTO_TENENCIA;
            }
        }
        
        //  Base para comisión = Total venta - Tenencias
        $montoBaseComision = $totalVenta - $totalTenencias;
        $ganancia = $montoBaseComision * ($porcentaje / 100);
        
        DB::table('pro_porcentaje_vendedor')->insert([
            'porc_vend_user_id' => auth()->id(),
            'porc_vend_ven_id' => $ventaId,
            'porc_vend_porcentaje' => $porcentaje,
            'porc_vend_cantidad_ganancia' => $ganancia,
            'porc_vend_monto_base' => $montoBaseComision, //  Sin tenencia
            'porc_vend_fecha_asignacion' => now(),
            'porc_vend_estado' => 'PENDIENTE',
            'porc_vend_situacion' => 'ACTIVO',
            'porc_vend_observaciones' => "Comisión por venta (sin incluir Q{$totalTenencias} de tenencia)",
        ]);

        // 6. CAJA
        DB::table('cja_historial')->insert([
            'cja_tipo' => 'VENTA',
            'cja_id_venta' => $ventaId,
            'cja_usuario' => auth()->id(),
            'cja_monto' => $totalPagado,
            'cja_fecha' => now(),
            'cja_metodo_pago' => $request->metodo_pago,
            'cja_no_referencia' => "VENTA-{$ventaId}",
            'cja_situacion' => 'PENDIENTE',
            'cja_observaciones' => $esDeReserva ? 'Venta confirmada desde reserva' : 'Venta registrada',
            'created_at' => now()
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => $esDeReserva ? 'Reserva convertida a venta exitosamente' : 'Venta procesada exitosamente',
            'venta_id' => $ventaId,
            'folio' => "VENTA-{$ventaId}",
            'pago_id' => $pagoId,
            'fue_reserva' => $esDeReserva
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
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar la venta: ' . $e->getMessage()
        ], 500);
    }
}


    public function show(Ventas $ventas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ventas $ventas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ventas $ventas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ventas $ventas)
    {
        //
    }
}
