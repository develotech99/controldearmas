<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetodoPago;
use App\Models\Clientes;
use App\Models\ProCliente;

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
 const MONTO_TENENCIA = 60.00;

    public function index()
    {
        // Datos necesarios para los selects y filtros
        $categorias = DB::table('pro_categorias')->where('categoria_situacion', 1)->orderBy('categoria_nombre')->get();
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

        $nit = trim($request->query('nit', ''));
        $dpi = trim($request->query('dpi', ''));

        $clientes = Clientes::with(['empresas', 'saldo'])
            ->where('cliente_situacion', 1)
            ->when($nit, function ($q) use ($nit) {
                $q->where(function ($query) use ($nit) {
                    $query->where('cliente_nit', $nit)
                          ->orWhereHas('empresas', function ($q2) use ($nit) {
                              $q2->where('emp_nit', $nit);
                          });
                });
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
                'pro_codigo_sku',
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
                'stock_cantidad_reservada2',
                'producto_requiere_stock'
            )
            ->orderBy('producto_nombre')
            ->get()
            ->unique('producto_id')
            ->values();

        // Series + LOTES (igual que series, pero para pro_lotes)
        $productos = $productos->map(function ($producto) {
            $productoArray = (array) $producto;


            // 👇 Calcular stock real
            $stockTotal = $producto->stock_cantidad_total ?? 0;
            $stockReservado = $producto->stock_cantidad_reservada ?? 0;
            $stockReservado2 = $producto->stock_cantidad_reservada2 ?? 0;

            // 👇 IMPORTANTE: Sobrescribir stock_cantidad_total con el stock real disponible
            $productoArray['stock_cantidad_total'] = max(0, $stockTotal - $stockReservado-$stockReservado2);



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


    public function obtenerVentasPendientes()
    {
        try {
            $ventas = ProVenta::with(['cliente', 'vendedor', 'detalleVentas.producto'])
                ->whereIn('ven_situacion', ['PENDIENTE', 'AUTORIZADA', 'EDITABLE'])
                ->orderBy('ven_fecha', 'desc')
                ->get();

            $ventasProcesadas = $ventas->map(function ($venta) {
                $detalles = $venta->detalleVentas->map(function ($det) use ($venta) {
                    $series = [];
                    $lotes = [];

                    if ($det->producto) {
                        if ($det->producto->producto_requiere_serie) {
                            $series = DB::table('pro_movimientos')
                                ->join('pro_series_productos', 'pro_movimientos.mov_serie_id', '=', 'pro_series_productos.serie_id')
                                ->where('mov_documento_referencia', 'VENTA-' . $venta->ven_id)
                                ->where('mov_producto_id', $det->det_producto_id)
                                ->select('pro_series_productos.serie_id as id', 'pro_series_productos.serie_numero_serie as numero')
                                ->get()
                                ->map(function($s) { return ['id' => $s->id, 'numero' => $s->numero]; })
                                ->toArray();
                        }
                        
                        // Logic for lots
                        $lotes = DB::table('pro_movimientos')
                            ->join('pro_lotes', 'pro_movimientos.mov_lote_id', '=', 'pro_lotes.lote_id')
                            ->where('mov_documento_referencia', 'VENTA-' . $venta->ven_id)
                            ->where('mov_producto_id', $det->det_producto_id)
                            ->select('pro_lotes.lote_id as id', 'pro_lotes.lote_codigo as codigo')
                            ->get()
                            ->map(function($l) { return ['id' => $l->id, 'codigo' => $l->codigo]; })
                            ->toArray();
                    }

                    return [
                        'det_id' => $det->det_id,
                        'producto_id' => $det->det_producto_id,
                        'producto_nombre' => $det->producto->producto_nombre ?? 'Desconocido',
                        'cantidad' => $det->det_cantidad,
                        'precio_venta' => $det->det_precio,
                        'subtotal' => $det->det_cantidad * $det->det_precio,
                        'series' => $series,
                        'lotes' => $lotes
                    ];
                });

                return [
                    'ven_id' => $venta->ven_id,
                    'ven_fecha' => $venta->ven_fecha,
                    'ven_total_vendido' => $venta->ven_total_vendido,
                    'ven_situacion' => $venta->ven_situacion,
                    'ven_observaciones' => $venta->ven_observaciones,
                    'cliente' => $venta->cliente ? trim($venta->cliente->cliente_nombre1 . ' ' . $venta->cliente->cliente_apellido1) : 'Consumidor Final',
                    'empresa' => $venta->cliente ? $venta->cliente->cliente_nom_empresa : '',
                    'vendedor' => $venta->vendedor ? ($venta->vendedor->user_primer_nombre ?? $venta->vendedor->name) : 'Sistema',
                    'total_items' => $detalles->sum('cantidad'),
                    'productos_resumen' => $detalles->pluck('producto_nombre')->unique()->take(3)->join(', ') . ($detalles->unique('producto_nombre')->count() > 3 ? '...' : ''),
                    'detalles' => $detalles
                ];
            });

            return response()->json($ventasProcesadas);
        } catch (\Exception $e) {
            Log::error('Error en obtenerVentasPendientes: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function autorizarVenta(Request $request): JsonResponse
    {
        $venId = (int) $request->input('ven_id');
        $tipo = $request->input('tipo', 'facturar'); // 'facturar' or 'sin_facturar'

        try {
            DB::transaction(function () use ($venId, $tipo) {
                $venta = ProVenta::with('detalleVentas.producto')->findOrFail($venId);

                if (!in_array($venta->ven_situacion, ['PENDIENTE', 'EDITABLE'])) {
                    throw new \Exception('La venta no está en estado PENDIENTE ni EDITABLE.');
                }

                // Determine new status
                // 'sin_facturar' -> 'FINALIZADA' (o 'AUTORIZADA' si ese es el estado final que no pide factura)
                // 'facturar' -> 'POR_FACTURAR' (o 'AUTORIZADA' si el flujo de facturación busca 'AUTORIZADA')
                
                // REVISIÓN DE ESTADOS:
                // Si el usuario dice "Autorizar (igual a la lógica actual)", asumimos que el estado actual para facturar es 'AUTORIZADA' o 'ACTIVA'.
                // En el código anterior, 'ACTIVA' parecía ser el estado post-autorización.
                // Para 'sin_facturar', la venta debe quedar "cerrada" en cuanto a gestión, pero sin factura.
                // Usaremos 'COMPLETADA' o 'FINALIZADA' para sin facturar si existe, si no 'AUTORIZADA' con una bandera interna?
                // Vamos a usar 'AUTORIZADA' para el flujo normal (para que pase a facturación)
                // Y 'FINALIZADA' (o similar) para sin facturar.
                // PERO, el usuario dijo: "La venta queda autorizada...".
                // Si ambas quedan 'AUTORIZADA', ¿cómo sabe facturación cual tocar?
                // Asumiremos:
                // Normal -> 'AUTORIZADA' (Lista para facturar)
                // Sin Facturar -> 'FINALIZADA' (Ya no requiere nada más)
                
                // Ajuste según código previo: Antes se ponía 'ACTIVA'.
                // Vamos a mantener 'ACTIVA' para el flujo normal si eso es lo que espera facturación.
                // Y 'COMPLETADA' para sin facturar.
                
                $nuevoEstado = ($tipo === 'sin_facturar') ? 'COMPLETADA' : 'AUTORIZADA'; 

                // Update Sale Status
                $venta->ven_situacion = $nuevoEstado;
                // Si es sin facturar, tal vez queramos guardar una nota
                if ($tipo === 'sin_facturar') {
                    $venta->ven_observaciones .= " [Autorizada sin facturar por " . auth()->user()->name . "]";
                }
                $venta->save();

                // Update Details Status
                foreach ($venta->detalleVentas as $detalle) {
                    $detalle->det_situacion = ($nuevoEstado === 'COMPLETADA') ? 'COMPLETADO' : 'AUTORIZADO';
                    $detalle->save();

                    // Stock Deduction Logic
                    // Solo si el producto requiere stock
                    if ($detalle->producto && $detalle->producto->producto_requiere_stock) {
                        
                        // Find reserved movements for this product in this sale
                        $movimientos = DB::table('pro_movimientos')
                            ->where('mov_documento_referencia', "VENTA-{$venId}")
                            ->where('mov_producto_id', $detalle->det_producto_id)
                            ->where('mov_tipo', 'reserva')
                            ->get();

                        foreach ($movimientos as $mov) {
                            // Update movement to finalized sale
                            DB::table('pro_movimientos')
                                ->where('mov_id', $mov->mov_id)
                                ->update([
                                    'mov_tipo' => 'venta',
                                    'mov_situacion' => 1, // 1 = Active/Finalized
                                    'mov_destino' => 'cliente',
                                    'updated_at' => now()
                                ]);

                            // Update stock table
                            $stock = DB::table('pro_stock_actual')
                                ->where('stock_producto_id', $detalle->det_producto_id)
                                ->first();

                            if ($stock) {
                                // Decrement reserved (release reservation)
                                DB::table('pro_stock_actual')
                                    ->where('stock_producto_id', $detalle->det_producto_id)
                                    ->decrement('stock_cantidad_reservada2', $mov->mov_cantidad);
                                
                                // Decrement physical stock (actual deduction)
                                DB::table('pro_stock_actual')
                                    ->where('stock_producto_id', $detalle->det_producto_id)
                                    ->decrement('stock_cantidad', $mov->mov_cantidad);
                            }
                            
                            // Update Series/Lotes status
                            if ($mov->mov_serie_id) {
                                DB::table('pro_series_productos')
                                    ->where('serie_id', $mov->mov_serie_id)
                                    ->update([
                                        'serie_estado' => 'vendido',
                                        'serie_situacion' => 1,
                                        'updated_at' => now()
                                    ]);
                            }
                            
                            if ($mov->mov_lote_id) {
                                 DB::table('pro_lotes')
                                    ->where('lote_id', $mov->mov_lote_id)
                                    ->decrement('lote_cantidad_disponible', $mov->mov_cantidad);
                            }
                        }
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => ($tipo === 'sin_facturar') 
                    ? 'Venta autorizada sin facturación. Inventario actualizado.' 
                    : 'Venta autorizada y lista para facturar. Inventario actualizado.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error autorizando venta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al autorizar venta: ' . $e->getMessage()
            ], 500);
        }
    }
    public function updateEditableSale(Request $request)
    {
        $venId = $request->input('ven_id');
        $cambios = $request->input('cambios', []); // Array of { det_id, producto_id, old_serie_id, new_serie_id }

        try {
            DB::transaction(function () use ($venId, $cambios) {
                $venta = ProVenta::findOrFail($venId);
                
                if ($venta->ven_situacion !== 'EDITABLE') {
                    throw new \Exception('La venta no está en estado EDITABLE.');
                }

                foreach ($cambios as $cambio) {
                    // 1. Manejo de SERIES
                    if (isset($cambio['old_serie_id']) && isset($cambio['new_serie_id'])) {
                        // Validar que la nueva serie esté disponible
                        $nuevaSerie = DB::table('pro_series_productos')
                            ->where('serie_id', $cambio['new_serie_id'])
                            ->where('serie_estado', 'disponible')
                            ->first();
                            
                        if (!$nuevaSerie) {
                            throw new \Exception("La serie seleccionada no está disponible.");
                        }

                        // Buscar el movimiento asociado a la serie anterior
                        $movimiento = DB::table('pro_movimientos')
                            ->where('mov_documento_referencia', 'VENTA-' . $venId)
                            ->where('mov_producto_id', $cambio['producto_id'])
                            ->where('mov_serie_id', $cambio['old_serie_id'])
                            ->first();

                        if ($movimiento) {
                            // Liberar serie anterior
                            DB::table('pro_series_productos')
                                ->where('serie_id', $cambio['old_serie_id'])
                                ->update(['serie_estado' => 'disponible', 'serie_situacion' => 1]);

                            // Ocupar nueva serie
                            DB::table('pro_series_productos')
                                ->where('serie_id', $cambio['new_serie_id'])
                                ->update(['serie_estado' => 'vendido', 'serie_situacion' => 1]);

                            // Actualizar movimiento
                            DB::table('pro_movimientos')
                                ->where('mov_id', $movimiento->mov_id)
                                ->update([
                                    'mov_serie_id' => $cambio['new_serie_id'],
                                    'updated_at' => now()
                                ]);
                        }
                    }

                    // 2. Manejo de LOTES
                    if (isset($cambio['old_lote_id']) && isset($cambio['new_lote_id'])) {
                        // Validar nuevo lote
                        $nuevoLote = DB::table('pro_lotes')
                            ->where('lote_id', $cambio['new_lote_id'])
                            ->first();

                        if (!$nuevoLote || $nuevoLote->lote_cantidad_disponible < 1) {
                            throw new \Exception("El lote seleccionado no tiene stock disponible.");
                        }

                        // Buscar movimiento asociado al lote anterior
                        $movimiento = DB::table('pro_movimientos')
                            ->where('mov_documento_referencia', 'VENTA-' . $venId)
                            ->where('mov_producto_id', $cambio['producto_id'])
                            ->where('mov_lote_id', $cambio['old_lote_id'])
                            ->first();

                        if ($movimiento) {
                            // Revertir stock lote anterior (+1)
                            DB::table('pro_lotes')
                                ->where('lote_id', $cambio['old_lote_id'])
                                ->increment('lote_cantidad_disponible');

                            // Descontar stock nuevo lote (-1)
                            DB::table('pro_lotes')
                                ->where('lote_id', $cambio['new_lote_id'])
                                ->decrement('lote_cantidad_disponible');

                            // Actualizar movimiento
                            DB::table('pro_movimientos')
                                ->where('mov_id', $movimiento->mov_id)
                                ->update([
                                    'mov_lote_id' => $cambio['new_lote_id'],
                                    'updated_at' => now()
                                ]);
                        }
                    }
                }
                
                // Opcional: Si se completaron todos los cambios, ¿se cambia el estado?
                // No, el usuario debe dar click en "Autorizar" de nuevo para confirmar todo.
            });

            return response()->json(['success' => true, 'message' => 'Cambios aplicados correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error actualizando venta editable: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
            ) AS series_display,
            serie.serie_estado
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
          AND serie.serie_estado = 'reserva'
          AND v.ven_cliente = ?
        GROUP BY 
            v.ven_id, v.ven_fecha, v.ven_user, v.ven_total_vendido, v.ven_situacion,
            d.det_producto_id, d.det_ven_id, d.det_cantidad, d.det_precio,
            c.cliente_nombre1, c.cliente_nombre2, c.cliente_apellido1, c.cliente_apellido2, c.cliente_nom_empresa,
            u.user_primer_nombre, u.user_segundo_nombre, u.user_primer_apellido, u.user_segundo_apellido,
            p.producto_nombre, p.producto_requiere_serie, p.producto_requiere_stock,serie.serie_estado
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

    public function reservadas()
    {
        return view('ventas.reservadas');
    }

    public function getReservasActivas(Request $request): JsonResponse
    {
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $search = $request->input('search');

        $params = [];
        $whereClause = "d.det_situacion = 'PENDIENTE' AND v.ven_situacion = 'RESERVADA'";

        if ($fechaInicio) {
            $whereClause .= " AND DATE(v.ven_fecha) >= ?";
            $params[] = $fechaInicio;
        }

        if ($fechaFin) {
            $whereClause .= " AND DATE(v.ven_fecha) <= ?";
            $params[] = $fechaFin;
        }

        if ($search) {
            $term = "%{$search}%";
            $whereClause .= " AND (
                c.cliente_nombre1 LIKE ? OR 
                c.cliente_nombre2 LIKE ? OR 
                c.cliente_apellido1 LIKE ? OR 
                c.cliente_apellido2 LIKE ? OR 
                c.cliente_nom_empresa LIKE ? OR 
                c.cliente_nit LIKE ? OR
                CONCAT('RESERVA-', v.ven_id) LIKE ?
            )";
            // Add params for each LIKE
            for ($i = 0; $i < 7; $i++) {
                $params[] = $term;
            }
        }

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
            ) AS series_display,
            serie.serie_estado
        FROM pro_detalle_ventas d
        INNER JOIN pro_ventas v   ON v.ven_id = d.det_ven_id
        INNER JOIN users u        ON u.user_id = v.ven_user
        INNER JOIN pro_clientes c ON c.cliente_id = v.ven_cliente
        INNER JOIN pro_productos p ON d.det_producto_id = p.producto_id
        LEFT JOIN pro_movimientos mov ON mov.mov_producto_id = d.det_producto_id
            AND mov.mov_situacion = 2
            AND mov.mov_documento_referencia = CONCAT('RESERVA-', v.ven_id)
        LEFT JOIN pro_series_productos serie ON serie.serie_id = mov.mov_serie_id
        WHERE {$whereClause}
        GROUP BY 
            v.ven_id, v.ven_fecha, v.ven_user, v.ven_total_vendido, v.ven_situacion,
            d.det_producto_id, d.det_ven_id, d.det_cantidad, d.det_precio,
            c.cliente_nombre1, c.cliente_nombre2, c.cliente_apellido1, c.cliente_apellido2, c.cliente_nom_empresa,
            u.user_primer_nombre, u.user_segundo_nombre, u.user_primer_apellido, u.user_segundo_apellido,
            p.producto_nombre, p.producto_requiere_serie, p.producto_requiere_stock,serie.serie_estado
        ORDER BY v.ven_fecha DESC
    ", $params);

        if (empty($resultados)) {
            return response()->json([
                'success' => true,
                'reservas' => [],
                'message' => 'No hay reservas vigentes con los filtros aplicados.'
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

        $reservasFinal = $grupos->map(function ($items, $venId) use ($parseSeries, $parseLotes) {
            $first = $items->first();

            $productos = $items->map(function ($item) use ($parseSeries, $parseLotes) {
                return [
                    'producto_id' => $item->det_producto_id,
                    'nombre' => $item->producto_nombre,
                    'cantidad' => $item->det_cantidad,
                    'precio' => $item->det_precio,
                    'requiere_serie' => $item->producto_requiere_serie,
                    'requiere_stock' => $item->producto_requiere_stock,
                    'seriesSeleccionadas' => $parseSeries($item),
                    'lotesSeleccionados' => $parseLotes($item),
                ];
            })->values();

            return [
                'id' => $venId, // ID real
                'ven_id' => $venId, // ID real
                'numero' => "RESERVA-{$venId}",
                'fecha' => $first->ven_fecha,
                'total' => $first->ven_total_vendido,
                'situacion' => $first->ven_situacion,
                'cliente' => $first->cliente,
                'empresa' => $first->empresa,
                'vendedor' => $first->vendedor,
                'items' => $productos
            ];
        })->values();

        return response()->json([
            'success' => true,
            'count' => $grupos->count(),
            'reservas' => $reservasFinal
        ]);
    }

public function marcarSeriesDisponibles(Request $request)
{
    try {
        // 👀 Ver qué llega del JS
        Log::info('marcarSeriesDisponibles - payload', [
            'body' => $request->all()
        ]);

        $serieIds = $request->input('seriesSeleccionadas', []);

        if (empty($serieIds) || !is_array($serieIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron series seleccionadas válidas.',
                'data_recibida' => $serieIds,
            ], 400);
        }

        // 👀 Log antes del update
        Log::info('marcarSeriesDisponibles - IDs a actualizar', [
            'serie_numero_serie' => $serieIds
        ]);

        // OJO: estos nombres deben existir tal cual en la BD
        $afectadas = DB::table('pro_series_productos')
            ->whereIn('serie_numero_serie', $serieIds)
            ->update([
                'serie_estado' => 'disponible',
                'updated_at'   => now(), // quita si no tienes este campo
            ]);

        return response()->json([
            'success'   => true,
            'message'   => 'Series actualizadas a disponible correctamente.',
            'afectadas' => $serieIds,
        ]);
    } catch (\Throwable $e) {
        Log::error('Error en marcarSeriesDisponibles', [
            'error' => $e->getMessage(),
        ]);

        // 🔥 Mientras depuras, devuelve el mensaje real
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function procesarReserva(Request $request): JsonResponse
{
    try {
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'empresa_id' => 'required|exists:pro_clientes_empresas,emp_id',
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

        $ahora = now()->format('Y-m-d H:i:s');

        // 1. CREAR LA RESERVA
        $reservaId = DB::table('pro_ventas')->insertGetId([
            'ven_user' => auth()->id(),
            'ven_fecha' => $request->fecha_reserva,
            'ven_cliente' => $request->cliente_id,
            'ven_empresa_id' => $request->empresa_id,
            'ven_total_vendido' => $request->total,
            'ven_descuento' => isset($request->descuento_monto) ? $request->descuento_monto : 0,
            'ven_observaciones' => isset($request->observaciones) ? $request->observaciones : 'Reserva - Vigente por ' . (isset($request->dias_vigencia) ? $request->dias_vigencia : 30) . ' días',
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

            // Validar stock
            if ($productoData['producto_requiere_stock'] == 1) {
                $stockActual = DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $producto->producto_id)
                    ->first();

                if (!$stockActual) {
                    $stockActual = (object)[
                        'stock_cantidad_disponible' => 0,
                        'stock_cantidad_reservada'  => 0,
                        'stock_cantidad_reservada2' => 0,
                    ];
                }

                $disponible = isset($stockActual->stock_cantidad_disponible) ? $stockActual->stock_cantidad_disponible : 0;
                $reservada = isset($stockActual->stock_cantidad_reservada) ? $stockActual->stock_cantidad_reservada : 0;
                $reservada2 = isset($stockActual->stock_cantidad_reservada2) ? $stockActual->stock_cantidad_reservada2 : 0;

                $stockDisponibleReal = max(0, $disponible - $reservada - $reservada2);

                if ($stockDisponibleReal < $productoData['cantidad']) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para reservar: {$producto->producto_nombre}. Disponible: {$stockDisponibleReal}"
                    ], 422);
                }
            }

            // Insertar detalle
            DB::table('pro_detalle_ventas')->insertGetId([
                'det_ven_id' => $reservaId,
                'det_producto_id' => $producto->producto_id,
                'det_cantidad' => $productoData['cantidad'],
                'det_precio' => $productoData['precio_unitario'],
                'det_descuento' => 0,
                'det_situacion' => 'PENDIENTE',
            ]);

            if ($productoData['producto_requiere_stock'] == 1) {
                if ($productoData['requiere_serie'] == 1) {
                    // PRODUCTO CON SERIES
                    $seriesSeleccionadas = isset($productoData['series_seleccionadas']) ? $productoData['series_seleccionadas'] : array();

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
                            'message' => "Debe seleccionar exactamente {$productoData['cantidad']} serie(s)"
                        ], 422);
                    }

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
                            'message' => "Series no disponibles"
                        ], 422);
                    }

                    $seriesIds = $seriesInfo->pluck('serie_id');
                    $seriesConTenencia = isset($productoData['series_con_tenencia']) ? $productoData['series_con_tenencia'] : array();
                    $tieneTenenciaMap = array();

                    foreach ($seriesInfo as $serieInfo) {
                        $numeroSerie = $serieInfo->serie_numero_serie;
                        $tieneTenenciaMap[$serieInfo->serie_id] = isset($seriesConTenencia[$numeroSerie]) ? 1 : 0;
                    }

                    foreach ($seriesIds as $serieId) {
                        $tieneTenencia = (int)(isset($tieneTenenciaMap[$serieId]) ? $tieneTenenciaMap[$serieId] : 0);
                        $montoTenencia = $tieneTenencia ? (float)self::MONTO_TENENCIA : 0.00;
                        
                        DB::table('pro_series_productos')
                            ->where('serie_id', $serieId)
                            ->update([
                                'serie_estado' => 'reserva',
                                'serie_situacion' => 1,
                                'serie_tiene_tenencia' => $tieneTenencia,
                                'serie_monto_tenencia' => $montoTenencia,
                                'updated_at' => $ahora
                            ]);
                    }

                    foreach ($seriesInfo as $serieInfo) {
                        DB::table('pro_movimientos')->insert([
                            'mov_producto_id' => $producto->producto_id,
                            'mov_tipo' => 'reserva',
                            'mov_origen' => 'almacen',
                            'mov_destino' => 'reservado',
                            'mov_cantidad' => 1,
                            'mov_precio_unitario' => $productoData['precio_unitario'],
                            'mov_valor_total' => $productoData['precio_unitario'],
                            'mov_fecha' => $ahora,
                            'mov_usuario_id' => auth()->id(),
                            'mov_serie_id' => $serieInfo->serie_id,
                            'mov_documento_referencia' => "RESERVA-{$reservaId}",
                            'mov_observaciones' => "Reserva - Serie: {$serieInfo->serie_numero_serie}",
                            'mov_situacion' => 2,
                            'created_at' => $ahora,
                            'updated_at' => $ahora
                        ]);
                    }

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $producto->producto_id)
                        ->increment('stock_cantidad_reservada2', count($seriesSeleccionadas));

                } else {
                    // SIN SERIES
                    if ($productoData['tiene_lotes'] && !empty($productoData['lotes_seleccionados'])) {
                        // CON LOTES
                        $lotesSeleccionados = $productoData['lotes_seleccionados'];
                        $totalAsignado = array_sum(array_column($lotesSeleccionados, 'cantidad'));

                        if ($totalAsignado !== $productoData['cantidad']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "Cantidad en lotes no coincide"
                            ], 422);
                        }

                        foreach ($lotesSeleccionados as $loteData) {
                            $lote = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();

                            if (!$lote || $lote->lote_cantidad_disponible < $loteData['cantidad']) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => "Lote sin stock suficiente"
                                ], 422);
                            }

                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id' => $producto->producto_id,
                                'mov_tipo' => 'reserva',
                                'mov_origen' => 'almacen',
                                'mov_destino' => 'reservado',
                                'mov_cantidad' => $loteData['cantidad'],
                                'mov_precio_unitario' => $productoData['precio_unitario'],
                                'mov_valor_total' => $productoData['precio_unitario'] * $loteData['cantidad'],
                                'mov_fecha' => $ahora,
                                'mov_usuario_id' => auth()->id(),
                                'mov_lote_id' => $loteData['lote_id'],
                                'mov_documento_referencia' => "RESERVA-{$reservaId}",
                                'mov_observaciones' => "Reserva - Lote: {$lote->lote_codigo}",
                                'mov_situacion' => 2,
                                'created_at' => $ahora,
                                'updated_at' => $ahora
                            ]);
                        }
                    } else {
                        // SIN LOTES
                        DB::table('pro_movimientos')->insert([
                            'mov_producto_id' => $producto->producto_id,
                            'mov_tipo' => 'reserva',
                            'mov_origen' => 'almacen',
                            'mov_destino' => 'reservado',
                            'mov_cantidad' => $productoData['cantidad'],
                            'mov_precio_unitario' => $productoData['precio_unitario'],
                            'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                            'mov_fecha' => $ahora,
                            'mov_usuario_id' => auth()->id(),
                            'mov_lote_id' => null,
                            'mov_documento_referencia' => "RESERVA-{$reservaId}",
                            'mov_observaciones' => "Reserva - Stock general",
                            'mov_situacion' => 2,
                            'created_at' => $ahora,
                            'updated_at' => $ahora
                        ]);
                    }

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $producto->producto_id)
                        ->increment('stock_cantidad_reservada2', $productoData['cantidad']);
                }
            
            } else {
                // Sin control de stock
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id' => $producto->producto_id,
                    'mov_tipo' => 'reserva',
                    'mov_origen' => 'almacen',
                    'mov_destino' => 'reservado',
                    'mov_cantidad' => $productoData['cantidad'],
                    'mov_precio_unitario' => $productoData['precio_unitario'],
                    'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
                    'mov_fecha' => $ahora,
                    'mov_usuario_id' => auth()->id(),
                    'mov_lote_id' => null,
                    'mov_documento_referencia' => "RESERVA-{$reservaId}",
                    'mov_observaciones' => "Reserva - Producto sin control de stock",
                    'mov_situacion' => 2,
                    'created_at' => $ahora,
                    'updated_at' => $ahora
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Reserva procesada exitosamente',
            'reserva_id' => $reservaId,
            'numero_reserva' => "RESERVA-{$reservaId}",
            'vigencia_dias' => isset($request->dias_vigencia) ? $request->dias_vigencia : 30
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

        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}




    ///////// termino morales batz no estaba bien implementado cooregido bmar


  public function cancelarVenta(Request $request): JsonResponse
{
    $venId = (int) $request->input('ven_id');
    $motivoCancelacion = $request->input('motivo', 'Cancelación de venta');

    try {
        DB::transaction(function () use ($venId, $motivoCancelacion) {
            // Verificar que la venta existe
            $venta = DB::table('pro_ventas')->where('ven_id', $venId)->first();

            if (!$venta) {
                throw new \RuntimeException('Venta no encontrada.');
            }

            // Permitir cancelar PENDIENTE o ACTIVA (si fue autorizada pero aún no entregada)
            if (!in_array($venta->ven_situacion, ['PENDIENTE', 'ACTIVA','RESERVADA'])) {
                throw new \RuntimeException('Solo se pueden cancelar ventas en estado PENDIENTE o ACTIVA.');
            }

            $ref = 'VENTA-' . $venId;
            $yaAutorizada = ($venta->ven_situacion === 'ACTIVA');

            // ========================================
            // 1. REVERTIR SERIES
            // ========================================
            $movimientosSeries = DB::table('pro_movimientos')
                ->where('mov_documento_referencia', $ref)
                ->whereIn('mov_situacion', $yaAutorizada ? [1, 3] : [3])
                ->whereNotNull('mov_serie_id')
                ->get();

            if ($movimientosSeries->isNotEmpty()) {
                $seriesIds = $movimientosSeries->pluck('mov_serie_id')->unique();

                // Revertir estado y limpiar tenencia
                DB::table('pro_series_productos')
                    ->whereIn('serie_id', $seriesIds)
                    ->update([
                        'serie_estado' => 'disponible',
                        'serie_situacion' => 1,
                        'serie_tiene_tenencia' => 0,
                        'serie_monto_tenencia' => 0.00,
                        'updated_at' => now()
                    ]);

                // Anular movimientos
                DB::table('pro_movimientos')
                    ->whereIn('mov_serie_id', $seriesIds)
                    ->where('mov_documento_referencia', $ref)
                    ->update(['mov_situacion' => 0, 'updated_at' => now()]);

                // Revertir stock
                foreach ($movimientosSeries->groupBy('mov_producto_id') as $productoId => $movs) {
                    $cantidad = $movs->sum('mov_cantidad');
                    
                    // Siempre decrementar reservado
            

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->where('stock_cantidad_reservada', '>', 0)
                        ->decrement('stock_cantidad_reservada', $cantidad);

                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->where('stock_cantidad_reservada2', '>', 0)
                        ->decrement('stock_cantidad_reservada2', $cantidad);


                    // Si ya estaba autorizada, también revertir total y disponible
                    if ($yaAutorizada) {
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_total', $cantidad);
                        
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_disponible', $cantidad);
                    }
                }
            }

            // ========================================
            // 2. REVERTIR LOTES
            // ========================================
            $movimientosLotes = DB::table('pro_movimientos')
                ->where('mov_documento_referencia', $ref)
                ->whereIn('mov_situacion', $yaAutorizada ? [1, 3] : [3])
                ->whereNotNull('mov_lote_id')
                ->get();

            if ($movimientosLotes->isNotEmpty()) {
                foreach ($movimientosLotes as $mov) {
                    // Devolver cantidad al lote
                    DB::table('pro_lotes')
                        ->where('lote_id', $mov->mov_lote_id)
                        ->increment('lote_cantidad_total', $mov->mov_cantidad);

                    DB::table('pro_lotes')
                        ->where('lote_id', $mov->mov_lote_id)
                        ->increment('lote_cantidad_disponible', $mov->mov_cantidad);

                    // Reactivar lote si estaba agotado
                    DB::table('pro_lotes')
                        ->where('lote_id', $mov->mov_lote_id)
                        ->where('lote_situacion', 0)
                        ->update(['lote_situacion' => 1]);
                }

                // Anular movimientos
                DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $ref)
                    ->whereNotNull('mov_lote_id')
                    ->update(['mov_situacion' => 0, 'updated_at' => now()]);

                // Revertir stock
                foreach ($movimientosLotes->groupBy('mov_producto_id') as $productoId => $movs) {
                    $cantidad = $movs->sum('mov_cantidad');
                    
                    // Siempre decrementar reservado
                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_reservada', $cantidad);
                 DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_reservada2', $cantidad);

                    // Si ya estaba autorizada
                    if ($yaAutorizada) {
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_total', $cantidad);
                        
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_disponible', $cantidad);
                    }
                }
            }

            // ========================================
            // 3. STOCK GENERAL (sin series ni lotes)
            // ========================================
            $movimientosGenerales = DB::table('pro_movimientos')
                ->where('mov_documento_referencia', $ref)
                ->whereIn('mov_situacion', $yaAutorizada ? [1, 3] : [3])
                ->whereNull('mov_serie_id')
                ->whereNull('mov_lote_id')
                ->get();

            if ($movimientosGenerales->isNotEmpty()) {
                // Anular movimientos
                DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $ref)
                    ->whereNull('mov_serie_id')
                    ->whereNull('mov_lote_id')
                    ->update(['mov_situacion' => 0, 'updated_at' => now()]);

                // Revertir stock
                foreach ($movimientosGenerales->groupBy('mov_producto_id') as $productoId => $movs) {
                    $cantidad = $movs->sum('mov_cantidad');
                    
                    // Siempre decrementar reservado
                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_reservada', $cantidad);
                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $productoId)
                        ->decrement('stock_cantidad_reservada2', $cantidad);
                    
                    // Si ya estaba autorizada, revertir total y disponible
                    if ($yaAutorizada) {
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_total', $cantidad);
                        
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $productoId)
                            ->increment('stock_cantidad_disponible', $cantidad);
                    }
                }
            }

            // ========================================
            // 4. ELIMINAR PAGOS Y CUOTAS
            // ========================================
            $pago = DB::table('pro_pagos')
                ->where('pago_venta_id', $venId)
                ->first();

            if ($pago) {
                // 🔥 Eliminar en cascada
                // Esto eliminará automáticamente:
                // - pro_detalle_pagos (por FK con onDelete cascade)
                // - pro_cuotas (por FK con onDelete cascade)
                DB::table('pro_pagos')
                    ->where('pago_id', $pago->pago_id)
                    ->delete();
            }

            // ========================================
            // 5. CANCELAR DETALLES DE VENTA
            // ========================================
            DB::table('pro_detalle_ventas')
                ->where('det_ven_id', $venId)
                ->update(['det_situacion' => 'ANULADO']);

            // ========================================
            // 6. CANCELAR VENTA
            // ========================================
            DB::table('pro_ventas')
                ->where('ven_id', $venId)
                ->update([
                    'ven_situacion' => 'ANULADA',
                    'ven_observaciones' => $motivoCancelacion,
                    'updated_at' => now()
                ]);

            // ========================================
            // 7. CANCELAR COMISIÓN DEL VENDEDOR
            // ========================================
            DB::table('pro_porcentaje_vendedor')
                ->where('porc_vend_ven_id', $venId)
                ->update([
                    'porc_vend_estado' => 'CANCELADO',
                    'porc_vend_situacion' => 'INACTIVO',
                    'updated_at' => now()
                ]);

            // ========================================
            // 8. ACTUALIZAR HISTORIAL DE CAJA
            // ========================================
            DB::table('cja_historial')
                ->where('cja_id_venta', $venId)
                ->update([
                    'cja_situacion' => 'ANULADA',
                    'cja_observaciones' => $motivoCancelacion
                ]);

        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Venta cancelada y stock revertido exitosamente',
            'venta_id' => $venId
        ]);

    } catch (\Throwable $e) {
        report($e);
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


///venta de bolvito
// public function procesarVenta(Request $request): JsonResponse
// {
//     try {
//         $request->validate([
//             'cliente_id' => 'required|exists:pro_clientes,cliente_id',
//             'fecha_venta' => 'required|date',
//             'subtotal' => 'required|numeric|min:0',
//             'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
//             'descuento_monto' => 'nullable|numeric|min:0',
//             'total' => 'required|numeric|min:0',
//             'metodo_pago' => 'required|in:1,2,3,4,5,6',
//             'productos' => 'required|array|min:1',
//             'productos.*.producto_id' => 'required|exists:pro_productos,producto_id',
//             'productos.*.cantidad' => 'required|integer|min:1',
//             'productos.*.precio_unitario' => 'required|numeric|min:0',
//             'productos.*.subtotal_producto' => 'required|numeric|min:0',
//             'productos.*.requiere_serie' => 'required|in:0,1',
//             'productos.*.producto_requiere_stock' => 'required|in:0,1',
//             'productos.*.series_seleccionadas' => 'nullable|array',
//             'productos.*.tiene_lotes' => 'required|boolean',
//             'productos.*.lotes_seleccionados' => 'nullable|array',
//             'productos.*.lotes_seleccionados.*.lote_id' => 'nullable|exists:pro_lotes,lote_id',
//             'productos.*.lotes_seleccionados.*.cantidad' => 'nullable|integer|min:1',
//             'pago' => 'required|array',
//             'reserva_id' => 'nullable|exists:pro_ventas,venta_id', // ID de reserva si viene de una
//         ]);

//         DB::beginTransaction();

//         // ========================================
//         // 1. VERIFICAR SI VIENE DE UNA RESERVA
//         // ========================================
//         $esDeReserva = false;
//         $reservaExistente = null;
//         $isDisponible = false;

//         if ($request->has('reserva_id') && $request->reserva_id) {
//             // Buscar reserva existente
//             $reservaExistente = DB::table('pro_ventas')
//                 ->where('ven_id', $request->reserva_id) // Corregido: usar ven_id
//                 ->where('ven_cliente', $request->cliente_id)
//                 ->where('ven_situacion', 'RESERVADA')
//                 ->first();

//             if ($reservaExistente) {
//                 $esDeReserva = true;
//             }
//         } else {
//             // Buscar si hay una reserva pendiente para este cliente (opcional)
//             $reservaExistente = DB::table('pro_ventas')
//                 ->where('ven_cliente', $request->cliente_id)
//                 ->where('ven_situacion', 'RESERVADA')
//                 ->where('ven_user', auth()->id())
//                 //->whereDate('ven_fecha', '>=', now()->subDays(30)) // Reservas de últimos 30 días
//                 ->orderBy('ven_id', 'desc') // Corregido: usar ven_id en lugar de venta_id
//                 ->first();

//             if ($reservaExistente) {
//                 $esDeReserva = true;
//             }
//         }

//         // ========================================
//         // 2. CREAR O ACTUALIZAR VENTA
//         // ========================================
//         if ($esDeReserva && $reservaExistente) {
//             // ACTUALIZAR RESERVA A VENTA
//             $ventaId = $reservaExistente->ven_id; // Corregido: usar ven_id
            
//             DB::table('pro_ventas')
//                 ->where('ven_id', $ventaId) // Corregido: usar ven_id
//                 ->update([
//                     'ven_fecha' => $request->fecha_venta,
//                     'ven_total_vendido' => $request->total,
//                     'ven_descuento' => $request->descuento_monto ?? 0,
//                     'ven_observaciones' => 'Venta confirmada desde reserva - Pendiente de autorizar por digecam',
//                     'ven_situacion' => 'PENDIENTE',
//                     'updated_at' => now()
//                 ]);

//                 DB::table('pro_detalle_ventas')
//                 ->where('det_ven_id', $ventaId)
//                 ->delete();

//             // ACTUALIZAR MOVIMIENTOS PREVIOS DE RESERVA A VENTA
//             // DB::table('pro_movimientos')
//             //     ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
//             //     ->update([
//             //         'mov_tipo' => 'venta',
//             //         'mov_destino' => 'cliente',
//             //         'mov_documento_referencia' => "VENTA-{$ventaId}",
//             //         'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
//             //         'mov_situacion' => 3, // Pendiente de validar
//             //         'updated_at' => now()
//             //     ]);

//         } else {
//             // CREAR NUEVA VENTA
//             $ventaId = DB::table('pro_ventas')->insertGetId([
//                 'ven_user' => auth()->id(),
//                 'ven_fecha' => $request->fecha_venta,
//                 'ven_cliente' => $request->cliente_id,
//                 'ven_total_vendido' => $request->total,
//                 'ven_descuento' => $request->descuento_monto ?? 0,
//                 'ven_observaciones' => 'Venta Pendiente de autorizar por digecam',
//                 'ven_situacion' => 'PENDIENTE',
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ]);
//         }

//         $totalPagado = 0;
//         $cantidadPagos = 0;

//         // ========================================
//         // 3. PROCESAR CADA PRODUCTO
//         // ========================================
//         foreach ($request->productos as $productoData) {
//             $producto = DB::table('pro_productos')->where('producto_id', $productoData['producto_id'])->first();

//             if (!$producto) {
//                 DB::rollBack();
//                 return response()->json([
//                     'success' => false,
//                     'message' => "Producto con ID {$productoData['producto_id']} no encontrado"
//                 ], 422);
//             }

//             // Validar stock disponible SOLO si el producto lo necesita
//             if ($productoData['producto_requiere_stock'] == 1) {
//                 $stockActual = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();
                
//                 // Si viene de reserva, considerar stock reservado
//                 $stockDisponible = $esDeReserva 
//                     ? ($stockActual->stock_cantidad_disponible ?? 0)
//                     : (($stockActual->stock_cantidad_disponible ?? 0) - ($stockActual->stock_cantidad_reservada ?? 0));

//                 if ($stockDisponible < $productoData['cantidad']) {
//                     DB::rollBack();
//                     return response()->json([
//                         'success' => false,
//                         'message' => "Stock insuficiente para el producto: {$producto->producto_nombre}"
//                     ], 422);
//                 }
//             }

//             // Insertar detalle de venta
//             $detalleId = DB::table('pro_detalle_ventas')->insertGetId([
//                 'det_ven_id' => $ventaId,
//                 'det_producto_id' => $producto->producto_id,
//                 'det_cantidad' => $productoData['cantidad'],
//                 'det_precio' => $productoData['precio_unitario'],
//                 'det_descuento' => 0,
//                 'det_situacion' => 'PENDIENTE',
//             ]);

//             if ($productoData['producto_requiere_stock'] == 1) {
//                 // PROCESAR SEGÚN TIPO DE PRODUCTO
//                 if ($productoData['requiere_serie'] == 1) {
//                     // ===============================
//                     // PRODUCTO CON SERIES
//                     // ===============================
//                     $seriesSeleccionadas = $productoData['series_seleccionadas'] ?? [];

//                     if (empty($seriesSeleccionadas)) {
//                         DB::rollBack();
//                         return response()->json([
//                             'success' => false,
//                             'message' => "El producto {$producto->producto_nombre} requiere series"
//                         ], 422);
//                     }

//                     if (count($seriesSeleccionadas) !== $productoData['cantidad']) {
//                         DB::rollBack();
//                         return response()->json([
//                             'success' => false,
//                             'message' => "Debe seleccionar exactamente {$productoData['cantidad']} serie(s) para {$producto->producto_nombre}"
//                         ], 422);
//                     }

//                     // Buscar series: disponibles O reservadas (si viene de reserva)
//                     $estadosPermitidos = $esDeReserva ? ['disponible', 'reserva'] : ['disponible'];
                    
//                     $seriesInfo = DB::table('pro_series_productos')
//                         ->whereIn('serie_numero_serie', $seriesSeleccionadas)
//                         ->where('serie_producto_id', $producto->producto_id)
//                         ->whereIn('serie_estado', $estadosPermitidos)
//                         ->where('serie_situacion', 1)
//                         ->get();

//                     if ($seriesInfo->count() !== count($seriesSeleccionadas)) {
//                         DB::rollBack();
//                         return response()->json([
//                             'success' => false,
//                             'message' => "Una o más series no están disponibles para el producto {$producto->producto_nombre}"
//                         ], 422);
//                     }

//                     // Actualizar series a pendiente
//                                     // Actualizar series a pendiente
//                     $seriesIds = $seriesInfo->pluck('serie_id');

//                     //  NUEVO: Determinar qué series tienen tenencia
//                     $seriesConTenencia = $productoData['series_con_tenencia'] ?? [];
//                     $tieneTenenciaMap = []; // Mapa: numero_serie => tiene_tenencia

//                     foreach ($seriesInfo as $serieInfo) {
//                         $numeroSerie = $serieInfo->serie_numero_serie;
//                         $tieneTenenciaMap[$serieInfo->serie_id] = isset($seriesConTenencia[$numeroSerie]) ? 1 : 0;
//                     }

//                     // Actualizar cada serie individualmente con su tenencia
//                     foreach ($seriesIds as $serieId) {
//                         $tieneTenencia = $tieneTenenciaMap[$serieId] ?? 0;
//                         $montoTenencia = $tieneTenencia ? self::MONTO_TENENCIA : 0;
                        
//                         DB::table('pro_series_productos')
//                             ->where('serie_id', $serieId)
//                             ->where('serie_estado','disponible')
//                             ->update([
//                                 'serie_estado' => 'pendiente',
//                                 'serie_situacion' => 0,
//                                 'serie_tiene_tenencia' => $tieneTenencia,
//                                 'serie_monto_tenencia' => $montoTenencia,
//                                 'updated_at' => now()
//                             ]);
//                     }

//                     // Registrar/actualizar movimientos
//                     if ($esDeReserva) {
//                         // Actualizar movimientos existentes de reserva
//                         DB::table('pro_movimientos')
//                             ->whereIn('mov_serie_id', $seriesIds)
//                             ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
//                             ->update([
//                                 'mov_tipo' => 'venta',
//                                 'mov_destino' => 'cliente',
//                                 'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                 'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
//                                 'mov_situacion' => 3,
//                                 'updated_at' => now()
//                             ]);
//                     } else {
//                         // Crear nuevos movimientos
//                         foreach ($seriesInfo as $serieInfo) {
//                             DB::table('pro_movimientos')->insert([
//                                 'mov_producto_id' => $producto->producto_id,
//                                 'mov_tipo' => 'venta',
//                                 'mov_origen' => 'venta',
//                                 'mov_destino' => 'cliente',
//                                 'mov_cantidad' => 1,
//                                 'mov_precio_unitario' => $productoData['precio_unitario'],
//                                 'mov_valor_total' => $productoData['precio_unitario'],
//                                 'mov_fecha' => now(),
//                                 'mov_usuario_id' => auth()->id(),
//                                 'mov_serie_id' => $serieInfo->serie_id,
//                                 'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                 'mov_observaciones' => "Venta - Serie: {$serieInfo->serie_numero_serie}",
//                                 'mov_situacion' => 3,
//                                 'created_at' => now(),
//                                 'updated_at' => now()
//                             ]);
//                             $isDisponible = DB::table('pro_series_productos')
//                             ->where('serie_id', $serieInfo->serie_id)
//                             ->where('serie_estado', 'pendiente')
//                             ->exists();

    
//                         }
//                     }

                  
                                        
//                             $seriesCount = count($seriesSeleccionadas);

                                           
//                             $accionStock = null;

//                             if ($isDisponible === true ) {
//                                 DB::table('pro_stock_actual')
//                                     ->where('stock_producto_id', $producto->producto_id)
//                                     ->increment('stock_cantidad_reservada',$seriesCount);
                                
//                                 $accionStock = 'mover stock disponible';
                                
//                             } elseif ($esDeReserva > 0) {
//                                 DB::table('pro_stock_actual')
//                                     ->where('stock_producto_id', $producto->producto_id)
//                                     ->decrement('stock_cantidad_reservada2', $seriesCount);
                                
//                                 $accionStock = 'mover stock en reserva';
                                
//                             } else {
//                                 $accionStock = 'sin_series_sin_cambios';
//                             }

 

                    

//                 } else {
//                     // ===============================
//                     // PRODUCTO SIN SERIES
//                     // ===============================
//                     if ($productoData['tiene_lotes'] && !empty($productoData['lotes_seleccionados'])) {
//                         // PRODUCTO CON LOTES
//                         $lotesSeleccionados = $productoData['lotes_seleccionados'];
//                         $totalAsignado = array_sum(array_column($lotesSeleccionados, 'cantidad'));

//                         if ($totalAsignado !== $productoData['cantidad']) {
//                             DB::rollBack();
//                             return response()->json([
//                                 'success' => false,
//                                 'message' => "La cantidad asignada en lotes ($totalAsignado) debe coincidir con la cantidad del producto ({$productoData['cantidad']}) para {$producto->producto_nombre}"
//                             ], 422);
//                         }

//                         foreach ($lotesSeleccionados as $loteData) {
//                             $lote = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();

//                             if (!$lote || $lote->lote_cantidad_disponible < $loteData['cantidad']) {
//                                 DB::rollBack();
//                                 return response()->json([
//                                     'success' => false,
//                                     'message' => "El lote {$lote->lote_codigo} no tiene suficiente stock disponible"
//                                 ], 422);
//                             }

//                             // Decrementar stock de lote
//                             DB::table('pro_lotes')
//                                 ->where('lote_id', $loteData['lote_id'])
//                                 ->decrement('lote_cantidad_disponible', $loteData['cantidad']);

//                             DB::table('pro_lotes')
//                                 ->where('lote_id', $loteData['lote_id'])
//                                 ->decrement('lote_cantidad_total', $loteData['cantidad']);

//                             // Registrar movimiento
//                             if ($esDeReserva) {
//                                 // Actualizar movimientos de reserva
//                                 DB::table('pro_movimientos')
//                                     ->where('mov_lote_id', $loteData['lote_id'])
//                                     ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
//                                     ->update([
//                                         'mov_tipo' => 'venta',
//                                         'mov_destino' => 'cliente',
//                                         'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                         'mov_observaciones' => DB::raw("REPLACE(mov_observaciones, 'Reserva', 'Venta')"),
//                                         'mov_situacion' => 3,
//                                         'updated_at' => now()
//                                     ]);
//                             } else {
//                                 DB::table('pro_movimientos')->insert([
//                                     'mov_producto_id' => $producto->producto_id,
//                                     'mov_tipo' => 'venta',
//                                     'mov_origen' => 'venta',
//                                     'mov_destino' => 'cliente',
//                                     'mov_cantidad' => $loteData['cantidad'],
//                                     'mov_precio_unitario' => $productoData['precio_unitario'],
//                                     'mov_valor_total' => $productoData['precio_unitario'] * $loteData['cantidad'],
//                                     'mov_fecha' => now(),
//                                     'mov_usuario_id' => auth()->id(),
//                                     'mov_lote_id' => $loteData['lote_id'],
//                                     'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                     'mov_observaciones' => "Venta - Lote: {$lote->lote_codigo}",
//                                     'mov_situacion' => 3,
//                                     'created_at' => now(),
//                                     'updated_at' => now()
//                                 ]);
//                             }

//                             // Cambiar situación si se agotó
//                             $loteActualizado = DB::table('pro_lotes')->where('lote_id', $loteData['lote_id'])->first();
//                             if ($loteActualizado->lote_cantidad_disponible <= 0) {
//                                 DB::table('pro_lotes')
//                                     ->where('lote_id', $loteData['lote_id'])
//                                     ->update(['lote_situacion' => 0]);
//                             }
//                         }
//                     } else {
//                         // PRODUCTO SIN LOTES
//                         if ($esDeReserva) {
//                             DB::table('pro_movimientos')
//                                 ->where('mov_producto_id', $producto->producto_id)
//                                 ->where('mov_documento_referencia', "RESERVA-{$ventaId}")
//                                 ->whereNull('mov_lote_id')
//                                 ->update([
//                                     'mov_tipo' => 'venta',
//                                     'mov_destino' => 'cliente',
//                                     'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                     'mov_observaciones' => 'Venta - Stock general',
//                                     'mov_situacion' => 1,
//                                     'updated_at' => now()
//                                 ]);
//                         } else {
//                             DB::table('pro_movimientos')->insert([
//                                 'mov_producto_id' => $producto->producto_id,
//                                 'mov_tipo' => 'venta',
//                                 'mov_origen' => 'venta',
//                                 'mov_destino' => 'cliente',
//                                 'mov_cantidad' => $productoData['cantidad'],
//                                 'mov_precio_unitario' => $productoData['precio_unitario'],
//                                 'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
//                                 'mov_fecha' => now(),
//                                 'mov_usuario_id' => auth()->id(),
//                                 'mov_lote_id' => null,
//                                 'mov_documento_referencia' => "VENTA-{$ventaId}",
//                                 'mov_observaciones' => "Venta - Stock general",
//                                 'mov_situacion' => 1,
//                                 'created_at' => now(),
//                                 'updated_at' => now()
//                             ]);
//                         }
//                     }

//                         $seriesCount = count($loteData);

//          $accionStock = null;

//                 if ($seriesCount > 0) {
//                     if ($esDeReserva) {
//                         DB::table('pro_stock_actual')
//                             ->where('stock_producto_id', $producto->producto_id)
//                             ->where('stock_cantidad_reservada2', '>=', $seriesCount)
//                             ->decrement('stock_cantidad_reservada2', $seriesCount);

//                         DB::table('pro_stock_actual')
//                             ->where('stock_producto_id', $producto->producto_id)
//                             ->increment('stock_cantidad_reservada', $seriesCount);

//                         $accionStock = 'mover_de_reserva2_a_reserva1 lotes o general'; 

//                     } else {
//                         DB::table('pro_stock_actual')
//                             ->where('stock_producto_id', $producto->producto_id)
//                             ->increment('stock_cantidad_reservada', $seriesCount);

//                         $accionStock = 'solo_increment_reserva1 lotes o general'; 
//                     }
//                 } else {
//                     $accionStock = 'sin_series_sin_cambios kljlj'; 
//                 }



//             }
//             } else {
//                 // Productos sin control de stock
//                 DB::table('pro_movimientos')->insert([
//                     'mov_producto_id' => $producto->producto_id,
//                     'mov_tipo' => 'venta',
//                     'mov_origen' => 'venta',
//                     'mov_destino' => 'cliente',
//                     'mov_cantidad' => $productoData['cantidad'],
//                     'mov_precio_unitario' => $productoData['precio_unitario'],
//                     'mov_valor_total' => $productoData['precio_unitario'] * $productoData['cantidad'],
//                     'mov_fecha' => now(),
//                     'mov_usuario_id' => auth()->id(),
//                     'mov_lote_id' => null,
//                     'mov_documento_referencia' => "VENTA-{$ventaId}",
//                     'mov_observaciones' => "Venta - Stock general",
//                     'mov_situacion' => 1,
//                     'created_at' => now(),
//                     'updated_at' => now()
//                 ]);
//             }
//         }

//         // ========================================
//         // 4. PROCESAR PAGOS
//         // ========================================
//         $metodoPago = $request->metodo_pago;
//         $totalVenta = $request->total;

//         if ($metodoPago == '6') {
//             // SISTEMA DE CUOTAS
//             $abonoInicial = $request->pago['abono_inicial'] ?? 0;
//             $cuotas = $request->pago['cuotas'] ?? [];

//             $pagoId = DB::table('pro_pagos')->insertGetId([
//                 'pago_venta_id' => $ventaId,
//                 'pago_monto_total' => $totalVenta,
//                 'pago_monto_pagado' => $abonoInicial,
//                 'pago_monto_pendiente' => $totalVenta - $abonoInicial,
//                 'pago_tipo_pago' => 'CUOTAS',
//                 'pago_cantidad_cuotas' => $request->pago['cantidad_cuotas'],
//                 'pago_abono_inicial' => $abonoInicial,
//                 'pago_estado' => 'PENDIENTE',
//                 'pago_fecha_inicio' => now(),
//                 'pago_fecha_completado' => $abonoInicial >= $totalVenta ? now() : null,
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ]);

//             if ($abonoInicial > 0) {
//                 $metodoAbonoId = $request->pago['metodo_abono'] === 'transferencia' ? 4 : 1;

//                 DB::table('pro_detalle_pagos')->insert([
//                     'det_pago_pago_id' => $pagoId,
//                     'det_pago_cuota_id' => null,
//                     'det_pago_fecha' => now(),
//                     'det_pago_monto' => $abonoInicial,
//                     'det_pago_metodo_pago' => $metodoAbonoId,
//                     'det_pago_banco_id' => 1,
//                     'det_pago_numero_autorizacion' => $request->pago['numero_autorizacion_abono'] ?? null,
//                     'det_pago_tipo_pago' => 'ABONO_INICIAL',
//                     'det_pago_estado' => 'VALIDO',
//                     'det_pago_observaciones' => 'Abono inicial de la venta',
//                     'det_pago_usuario_registro' => auth()->id(),
//                     'created_at' => now(),
//                     'updated_at' => now()
//                 ]);

//                 $totalPagado += $abonoInicial;
//                 $cantidadPagos++;
//             }

//             $fechaBase = now();
//             foreach ($cuotas as $index => $cuotaData) {
//                 if ($cuotaData['monto'] > 0) {
//                     $fechaVencimiento = $fechaBase->copy()->addMonths($index + 1);

//                     DB::table('pro_cuotas')->insert([
//                         'cuota_control_id' => $pagoId,
//                         'cuota_numero' => $index + 1,
//                         'cuota_monto' => $cuotaData['monto'],
//                         'cuota_fecha_vencimiento' => $fechaVencimiento,
//                         'cuota_estado' => 'PENDIENTE',
//                         'created_at' => now(),
//                         'updated_at' => now()
//                     ]);
//                 }
//             }
//         } else {
//             // PAGO ÚNICO
//             $pagoId = DB::table('pro_pagos')->insertGetId([
//                 'pago_venta_id' => $ventaId,
//                 'pago_monto_total' => $totalVenta,
//                 'pago_monto_pagado' => $totalVenta,
//                 'pago_monto_pendiente' => 0,
//                 'pago_tipo_pago' => 'UNICO',
//                 'pago_cantidad_cuotas' => 1,
//                 'pago_abono_inicial' => $totalVenta,
//                 'pago_estado' => 'PENDIENTE',
//                 'pago_fecha_inicio' => now(),
//                 'pago_fecha_completado' => now(),
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ]);

//             DB::table('pro_detalle_pagos')->insert([
//                 'det_pago_pago_id' => $pagoId,
//                 'det_pago_cuota_id' => null,
//                 'det_pago_fecha' => now(),
//                 'det_pago_monto' => $totalVenta,
//                 'det_pago_metodo_pago' => $metodoPago,
//                 'det_pago_banco_id' => 1,
//                 'det_pago_numero_autorizacion' => $request->numero_autorizacion ?? null,
//                 'det_pago_tipo_pago' => 'PAGO_UNICO',
//                 'det_pago_estado' => 'VALIDO',
//                 'det_pago_observaciones' => 'Pago completo de la venta',
//                 'det_pago_usuario_registro' => auth()->id(),
//                 'created_at' => now(),
//                 'updated_at' => now()
//             ]);

//             $totalPagado = $totalVenta;
//             $cantidadPagos = 1;
//         }

//         $porcentaje = 2.5;

//         //  NUEVO: Calcular total de tenencias cobradas
//         $totalTenencias = 0;
//         foreach ($request->productos as $productoData) {
//             if (isset($productoData['series_con_tenencia']) && is_array($productoData['series_con_tenencia'])) {
//                 $totalTenencias += count($productoData['series_con_tenencia']) * self::MONTO_TENENCIA;
//             }
//         }
        
//         //  Base para comisión = Total venta - Tenencias
//         $montoBaseComision = $totalVenta - $totalTenencias;
//         $ganancia = $montoBaseComision * ($porcentaje / 100);
        
//         DB::table('pro_porcentaje_vendedor')->insert([
//             'porc_vend_user_id' => auth()->id(),
//             'porc_vend_ven_id' => $ventaId,
//             'porc_vend_porcentaje' => $porcentaje,
//             'porc_vend_cantidad_ganancia' => $ganancia,
//             'porc_vend_monto_base' => $montoBaseComision, //  Sin tenencia
//             'porc_vend_fecha_asignacion' => now(),
//             'porc_vend_estado' => 'PENDIENTE',
//             'porc_vend_situacion' => 'ACTIVO',
//             'porc_vend_observaciones' => "Comisión por venta (sin incluir Q{$totalTenencias} de tenencia)",
//         ]);

//         // 6. CAJA
//         DB::table('cja_historial')->insert([
//             'cja_tipo' => 'VENTA',
//             'cja_id_venta' => $ventaId,
//             'cja_usuario' => auth()->id(),
//             'cja_monto' => $totalPagado,
//             'cja_fecha' => now(),
//             'cja_metodo_pago' => $request->metodo_pago,
//             'cja_no_referencia' => "VENTA-{$ventaId}",
//             'cja_situacion' => 'PENDIENTE',
//             'cja_observaciones' => $esDeReserva ? 'Venta confirmada desde reserva' : 'Venta registrada',
//             'created_at' => now()
//         ]);

//         DB::commit();

//         return response()->json([
//             'success' => true,
//             'message' => $esDeReserva ? 'Reserva convertida a venta exitosamente' : 'Venta procesada exitosamente',
//             'venta_id' => $ventaId,
//             'folio' => "VENTA-{$ventaId}",
//             'pago_id' => $pagoId,
//             'fue_reserva' => $esDeReserva,
//              'accion_stock' => $accionStock,
//              'accion disponible '=> $isDisponible,
//         ]);

//     } catch (\Illuminate\Validation\ValidationException $e) {
//         DB::rollBack();
//         return response()->json([
//             'success' => false,
//             'message' => 'Datos de validación incorrectos',
//             'errors' => $e->errors()
//         ], 422);
//     } catch (\Exception $e) {
//         DB::rollBack();
//         return response()->json([
//             'success' => false,
//             'message' => 'Error al procesar la venta: ' . $e->getMessage()
//         ], 500);
//     }
// }


public function procesarVenta(Request $request): JsonResponse
{
    try {
        // 0. VALIDACIÓN
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'empresa_id' => 'required|exists:pro_clientes_empresas,emp_id',
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
            'reserva_id' => 'nullable|exists:pro_ventas,ven_id',
        ]);

        DB::beginTransaction();

      
        
        $productosValidados = [];
        $esDesdeReserva = null; // null, true o false
        
        foreach ($request->productos as $productoData) {
            
            $producto = DB::table('pro_productos')
                ->where('producto_id', $productoData['producto_id'])
                ->first();

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => "Producto con ID {$productoData['producto_id']} no encontrado",
                ], 422);
            }

            // Validar stock
            if ((int) $productoData['producto_requiere_stock'] === 1) {
                $stockActual = DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $producto->producto_id)
                    ->first();

                $stockDisponible = ($stockActual->stock_cantidad_disponible ?? 0)
                    - ($stockActual->stock_cantidad_reservada ?? 0);

                if ($stockDisponible < $productoData['cantidad']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para el producto: {$producto->producto_nombre}",
                    ], 422);
                }
            }

            if ((int) $productoData['requiere_serie'] === 1 && (int) $productoData['producto_requiere_stock'] === 1) {
                
                $seriesSeleccionadas = $productoData['series_seleccionadas'] ?? [];

                if (empty($seriesSeleccionadas)) {
                    return response()->json([
                        'success' => false,
                        'message' => "El producto {$producto->producto_nombre} requiere series",
                    ], 422);
                }

                if (count($seriesSeleccionadas) !== (int) $productoData['cantidad']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Debe seleccionar exactamente {$productoData['cantidad']} serie(s) para {$producto->producto_nombre}",
                    ], 422);
                }

             
                $seriesInfo = DB::table('pro_series_productos')
                    ->whereIn('serie_numero_serie', $seriesSeleccionadas)
                    ->where('serie_producto_id', $producto->producto_id)
                    ->where('serie_situacion', 1)
                    ->get();

                if ($seriesInfo->count() !== count($seriesSeleccionadas)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Una o más series no existen o no están activas para el producto {$producto->producto_nombre}",
                    ], 422);
                }

                $estadosEncontrados = $seriesInfo->pluck('serie_estado')->unique()->values();

                if ($estadosEncontrados->count() !== 1) {
                    return response()->json([
                        'success' => false,
                        'message' => "No puedes mezclar series con estados diferentes para el producto {$producto->producto_nombre}.",
                    ], 422);
                }

                $estadoSerie = $estadosEncontrados[0];

               
                if (!in_array($estadoSerie, ['disponible', 'reserva'])) {
                    return response()->json([
                        'success' => false,
                        'message' => "Las series seleccionadas para {$producto->producto_nombre} están en estado '{$estadoSerie}' y no pueden venderse. Solo se permiten series 'disponible' o 'reserva'.",
                    ], 422);
                }

                $esReservaProducto = ($estadoSerie === 'reserva');
                
           
                if ($esDesdeReserva === null) {
                    $esDesdeReserva = $esReservaProducto;
                } elseif ($esDesdeReserva !== $esReservaProducto) {
                    return response()->json([
                        'success' => false,
                        'message' => "No puedes mezclar productos desde reserva con productos disponibles en la misma venta.",
                    ], 422);
                }

                $productosValidados[$productoData['producto_id']] = [
                    'producto' => $producto,
                    'productoData' => $productoData,
                    'seriesInfo' => $seriesInfo,
                    'esDesdeReserva' => $esReservaProducto,
                ];

            } else {
               
                $productosValidados[$productoData['producto_id']] = [
                    'producto' => $producto,
                    'productoData' => $productoData,
                    'seriesInfo' => null,
                    'esDesdeReserva' => false,
                ];
            }
        }
        
        $ventaId = null;
        
        if ($esDesdeReserva === true && $request->filled('reserva_id')) {
      
            $ventaId = $request->reserva_id;
            
            DB::table('pro_ventas')
                ->where('ven_id', $ventaId)
                ->update([
                    'ven_total_vendido' => $request->total,
                    'ven_descuento'     => $request->descuento_monto ?? 0,
                    'ven_observaciones' => 'Venta Pendiente de autorizar por digecam (desde reserva)',
                    'ven_situacion'     => 'PENDIENTE',
                    'ven_empresa_id'    => $request->empresa_id,
                    'updated_at'        => now(),
                ]);
                
        } else {
      
            $ventaId = DB::table('pro_ventas')->insertGetId([
                'ven_user'          => auth()->id(),
                'ven_fecha'         => $request->fecha_venta,
                'ven_cliente'       => $request->cliente_id,
                'ven_empresa_id'    => $request->empresa_id,
                'ven_total_vendido' => $request->total,
                'ven_descuento'     => $request->descuento_monto ?? 0,
                'ven_observaciones' => 'Venta Pendiente de autorizar por digecam',
                'ven_situacion'     => 'PENDIENTE',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

 
        
        $accionStock = null;
        $isDisponible = false;

        foreach ($productosValidados as $productoId => $data) {
            
            $producto = $data['producto'];
            $productoData = $data['productoData'];
            $seriesInfo = $data['seriesInfo'];
            $esReservaProducto = $data['esDesdeReserva'];

           
            if ($esDesdeReserva === true) {
                DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $ventaId)
                    ->where('det_producto_id', $producto->producto_id)
                    ->update([
                        'det_cantidad'    => $productoData['cantidad'],
                        'det_precio'      => $productoData['precio_unitario'],
                        'det_situacion'   => 'PENDIENTE',
                    ]);
            } else {
                // Crear nuevo detalle
                DB::table('pro_detalle_ventas')->insertGetId([
                    'det_ven_id'      => $ventaId,
                    'det_producto_id' => $producto->producto_id,
                    'det_cantidad'    => $productoData['cantidad'],
                    'det_precio'      => $productoData['precio_unitario'],
                    'det_descuento'   => 0,
                    'det_situacion'   => 'PENDIENTE',
                ]);
            }

        
            if ((int) $productoData['producto_requiere_stock'] === 1) {

        
                if ((int) $productoData['requiere_serie'] === 1 && $seriesInfo) {

                    $seriesIds = $seriesInfo->pluck('serie_id')->all();
                    $seriesCount = count($seriesIds);

                    // Tenencia por serie
                    $seriesConTenencia = $productoData['series_con_tenencia'] ?? [];
                    $tenenciaMap = [];

                    foreach ($seriesInfo as $serieInfo) {
                        $numSerie = $serieInfo->serie_numero_serie;
                        $tenenciaMap[$serieInfo->serie_id] = isset($seriesConTenencia[$numSerie]) ? 1 : 0;
                    }

                  
                    foreach ($seriesIds as $serieId) {
                        $tieneTenencia = $tenenciaMap[$serieId] ?? 0;
                        $montoTenencia = $tieneTenencia ? self::MONTO_TENENCIA : 0;

                        $filasActualizadas = DB::table('pro_series_productos')
                            ->where('serie_id', $serieId)
                            ->whereIn('serie_estado', ['disponible', 'reserva'])
                            ->update([
                                'serie_estado'         => 'pendiente',
                                'serie_situacion'      => 0,
                                'serie_tiene_tenencia' => $tieneTenencia,
                                'serie_monto_tenencia' => $montoTenencia,
                                'updated_at'           => now(),
                            ]);

                        // Verificar que se actualizó
                        if ($filasActualizadas === 0) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "No se pudo actualizar la serie ID {$serieId}. Posiblemente cambió de estado.",
                            ], 422);
                        }
                    }

                   
                    if ($esReservaProducto) {
                     
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->decrement('stock_cantidad_reservada2', $seriesCount);

                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $seriesCount);

                        $accionStock = 'Series desde reserva → pendiente';
                    } else {
                       
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $seriesCount);

                        $accionStock = 'Series desde disponible → pendiente';
                    }

               
                    foreach ($seriesInfo as $serieInfo) {
                        DB::table('pro_movimientos')->insert([
                            'mov_producto_id'          => $producto->producto_id,
                            'mov_tipo'                 => 'venta',
                            'mov_origen'               => 'venta',
                            'mov_destino'              => 'cliente',
                            'mov_cantidad'             => 1,
                            'mov_precio_unitario'      => $productoData['precio_unitario'],
                            'mov_valor_total'          => $productoData['precio_unitario'],
                            'mov_fecha'                => now(),
                            'mov_usuario_id'           => auth()->id(),
                            'mov_serie_id'             => $serieInfo->serie_id,
                            'mov_documento_referencia' => "VENTA-{$ventaId}",
                            'mov_observaciones'        => "Venta - Serie: {$serieInfo->serie_numero_serie}",
                            'mov_situacion'            => 3,
                            'created_at'               => now(),
                            'updated_at'               => now(),
                        ]);

                        $isDisponible = DB::table('pro_series_productos')
                            ->where('serie_id', $serieInfo->serie_id)
                            ->where('serie_estado', 'pendiente')
                            ->exists();
                    }

           
                } else {

               
                    if ($productoData['tiene_lotes'] && !empty($productoData['lotes_seleccionados'])) {
                        $lotesSeleccionados = $productoData['lotes_seleccionados'];
                        $totalAsignado = array_sum(array_column($lotesSeleccionados, 'cantidad'));

                        if ($totalAsignado !== (int) $productoData['cantidad']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => "La cantidad asignada en lotes ($totalAsignado) debe coincidir con la cantidad del producto ({$productoData['cantidad']}) para {$producto->producto_nombre}",
                            ], 422);
                        }

                        foreach ($lotesSeleccionados as $loteData) {
                            $lote = DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->first();

                            if (!$lote || $lote->lote_cantidad_disponible < $loteData['cantidad']) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => "El lote {$lote->lote_codigo} no tiene suficiente stock disponible",
                                ], 422);
                            }

                            DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->decrement('lote_cantidad_disponible', $loteData['cantidad']);

                            DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->decrement('lote_cantidad_total', $loteData['cantidad']);

                            $accionStock = 'Lotes desde disponible';

                            DB::table('pro_movimientos')->insert([
                                'mov_producto_id'          => $producto->producto_id,
                                'mov_tipo'                 => 'venta',
                                'mov_origen'               => 'venta',
                                'mov_destino'              => 'cliente',
                                'mov_cantidad'             => $loteData['cantidad'],
                                'mov_precio_unitario'      => $productoData['precio_unitario'],
                                'mov_valor_total'          => $productoData['precio_unitario'] * $loteData['cantidad'],
                                'mov_fecha'                => now(),
                                'mov_usuario_id'           => auth()->id(),
                                'mov_lote_id'              => $loteData['lote_id'],
                                'mov_documento_referencia' => "VENTA-{$ventaId}",
                                'mov_observaciones'        => "Venta - Lote: {$lote->lote_codigo}",
                                'mov_situacion'            => 3,
                                'created_at'               => now(),
                                'updated_at'               => now(),
                            ]);

                            $loteActualizado = DB::table('pro_lotes')
                                ->where('lote_id', $loteData['lote_id'])
                                ->first();

                            if ($loteActualizado->lote_cantidad_disponible <= 0) {
                                DB::table('pro_lotes')
                                    ->where('lote_id', $loteData['lote_id'])
                                    ->update(['lote_situacion' => 0]);
                            }
                        }

                        // ✅ FIX: Incrementar stock reservado para lotes
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $totalAsignado);

                    } else {
                 
                        DB::table('pro_movimientos')->insert([
                            'mov_producto_id'          => $producto->producto_id,
                            'mov_tipo'                 => 'venta',
                            'mov_origen'               => 'venta',
                            'mov_destino'              => 'cliente',
                            'mov_cantidad'             => $productoData['cantidad'],
                            'mov_precio_unitario'      => $productoData['precio_unitario'],
                            'mov_valor_total'          => $productoData['precio_unitario'] * $productoData['cantidad'],
                            'mov_fecha'                => now(),
                            'mov_usuario_id'           => auth()->id(),
                            'mov_lote_id'              => null,
                            'mov_documento_referencia' => "VENTA-{$ventaId}",
                            'mov_observaciones'        => "Venta - Stock general",
                            'mov_situacion'            => 3, // ✅ FIX: 3 = Reservado (antes 1)
                            'created_at'               => now(),
                            'updated_at'               => now(),
                        ]);

                        // ✅ FIX: Incrementar stock reservado para stock general
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $producto->producto_id)
                            ->increment('stock_cantidad_reservada', $productoData['cantidad']);

                        $accionStock = 'Stock general desde disponible';
                    }
                }

   
            } else {
                DB::table('pro_movimientos')->insert([
                    'mov_producto_id'          => $producto->producto_id,
                    'mov_tipo'                 => 'venta',
                    'mov_origen'               => 'venta',
                    'mov_destino'              => 'cliente',
                    'mov_cantidad'             => $productoData['cantidad'],
                    'mov_precio_unitario'      => $productoData['precio_unitario'],
                    'mov_valor_total'          => $productoData['precio_unitario'] * $productoData['cantidad'],
                    'mov_fecha'                => now(),
                    'mov_usuario_id'           => auth()->id(),
                    'mov_lote_id'              => null,
                    'mov_documento_referencia' => "VENTA-{$ventaId}",
                    'mov_observaciones'        => "Venta - Producto sin control de stock",
                    'mov_situacion'            => 1,
                    'created_at'               => now(),
                    'updated_at'               => now(),
                ]);
            }
        }

    
        $totalVenta = $request->total;
        $totalPagado = 0;
        $cantidadPagos = 0;
        $metodoPago = $request->metodo_pago;
        // 3. DATOS ESPECÍFICOS SEGÚN MÉTODO DE PAGO
        $saldoFavorUsado = floatval($request->saldo_favor_usado ?? 0);
        $metodoPagoPrincipal = $request->metodo_pago;
        $montoPrincipal = $totalVenta - $saldoFavorUsado;

        // Validar saldo a favor si se usa
        if ($saldoFavorUsado > 0) {
            $clienteSaldo = DB::table('pro_clientes_saldo')
                ->where('saldo_cliente_id', $request->cliente_id)
                ->first();

            if (!$clienteSaldo || $clienteSaldo->saldo_monto < $saldoFavorUsado) {
                throw new \Exception("El cliente no tiene suficiente saldo a favor. Disponible: Q" . ($clienteSaldo->saldo_monto ?? 0));
            }

            // Descontar saldo
            $saldoAnterior = $clienteSaldo->saldo_monto;
            $saldoNuevo = $saldoAnterior - $saldoFavorUsado;

            DB::table('pro_clientes_saldo')
                ->where('saldo_cliente_id', $request->cliente_id)
                ->decrement('saldo_monto', $saldoFavorUsado);

            // Registrar historial
            DB::table('pro_clientes_saldo_historial')->insert([
                'hist_cliente_id' => $request->cliente_id,
                'hist_monto' => -$saldoFavorUsado,
                'hist_tipo' => 'CARGO',
                'hist_referencia' => "VENTA-{$ventaId}",
                'hist_saldo_anterior' => $saldoAnterior,
                'hist_saldo_nuevo' => $saldoNuevo,
                'hist_observaciones' => 'Pago con Saldo a Favor',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($metodoPagoPrincipal == "6") { // Pagos/Cuotas
            $pagoData = $request->pago;
            $abonoInicial = floatval($pagoData['abono_inicial'] ?? 0);
            $cantidadCuotas = intval($pagoData['cantidad_cuotas'] ?? 0);
            $cuotas = $pagoData['cuotas'] ?? [];

            // El monto total del pago es el total de la venta
            // El abono inicial REAL es lo que paga en efectivo/transferencia + lo que cubre con saldo a favor
            // PERO para efectos de cuotas, el saldo a favor reduce el monto a financiar.
            
            // Ajuste: Si usa saldo a favor, el "abono inicial" que viene del front es solo la parte pagada en el momento.
            // El saldo a favor se considera un pago inicial adicional.
            
            $pagoId = DB::table('pro_pagos')->insertGetId([
                'pago_venta_id'        => $ventaId,
                'pago_monto_total'     => $totalVenta,
                'pago_monto_pagado'    => $abonoInicial + $saldoFavorUsado, // Sumamos saldo a favor al pagado
                'pago_monto_pendiente' => $totalVenta - ($abonoInicial + $saldoFavorUsado),
                'pago_tipo_pago'       => 'CUOTAS',
                'pago_cantidad_cuotas' => $cantidadCuotas,
                'pago_abono_inicial'   => $abonoInicial, // Guardamos lo que pagó "en el acto"
                'pago_estado'          => 'PENDIENTE',
                'pago_fecha_inicio'    => now(),
                'pago_fecha_completado'=> null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // Detalle del abono inicial (si hubo)
            if ($abonoInicial > 0) {
                DB::table('pro_detalle_pagos')->insert([
                    'det_pago_pago_id'          => $pagoId,
                    'det_pago_cuota_id'         => null,
                    'det_pago_fecha'            => now(),
                    'det_pago_monto'            => $abonoInicial,
                    'det_pago_metodo_pago'      => $pagoData['metodo_abono'] ?? 'efectivo', // ID o texto? Ajustar según front
                    'det_pago_banco_id'         => $pagoData['banco_id_abono'] ?? null,
                    'det_pago_numero_autorizacion' => $pagoData['numero_autorizacion_abono'] ?? null,
                    'det_pago_tipo_pago'        => 'ABONO_INICIAL',
                    'det_pago_estado'           => 'VALIDO',
                    'det_pago_observaciones'    => 'Abono inicial de venta a cuotas',
                    'det_pago_usuario_registro' => auth()->id(),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }

            // Detalle del Saldo a Favor (si hubo)
            if ($saldoFavorUsado > 0) {
                DB::table('pro_detalle_pagos')->insert([
                    'det_pago_pago_id'          => $pagoId,
                    'det_pago_cuota_id'         => null,
                    'det_pago_fecha'            => now(),
                    'det_pago_monto'            => $saldoFavorUsado,
                    'det_pago_metodo_pago'      => 7, // ID 7 = Saldo a Favor
                    'det_pago_banco_id'         => null,
                    'det_pago_numero_autorizacion' => null,
                    'det_pago_tipo_pago'        => 'ABONO_INICIAL',
                    'det_pago_estado'           => 'VALIDO',
                    'det_pago_observaciones'    => 'Pago con saldo a favor',
                    'det_pago_usuario_registro' => auth()->id(),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }

            $totalPagado = $abonoInicial + $saldoFavorUsado;
            $cantidadPagos = ($abonoInicial > 0 ? 1 : 0) + ($saldoFavorUsado > 0 ? 1 : 0);

            $fechaBase = now();
            foreach ($cuotas as $index => $cuotaData) {
                if (($cuotaData['monto'] ?? 0) > 0) {
                    $fechaVencimiento = $fechaBase->copy()->addMonths($index + 1);

                    DB::table('pro_cuotas')->insert([
                        'cuota_control_id'        => $pagoId,
                        'cuota_numero'            => $index + 1,
                        'cuota_monto'             => $cuotaData['monto'],
                        'cuota_fecha_vencimiento' => $fechaVencimiento,
                        'cuota_estado'            => 'PENDIENTE',
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                }
            }

        } else {
            // Pago único (o split con saldo a favor)
            $pagoId = DB::table('pro_pagos')->insertGetId([
                'pago_venta_id'        => $ventaId,
                'pago_monto_total'     => $totalVenta,
                'pago_monto_pagado'    => $totalVenta, // Se asume pagado completo
                'pago_monto_pendiente' => 0,
                'pago_tipo_pago'       => 'UNICO',
                'pago_cantidad_cuotas' => 1,
                'pago_abono_inicial'   => $totalVenta,
                'pago_estado'          => 'COMPLETADO', // Completado directo
                'pago_fecha_inicio'    => now(),
                'pago_fecha_completado'=> now(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 1. Registrar pago principal (si queda monto por pagar)
            if ($montoPrincipal > 0) {
                DB::table('pro_detalle_pagos')->insert([
                    'det_pago_pago_id'          => $pagoId,
                    'det_pago_cuota_id'         => null,
                    'det_pago_fecha'            => now(),
                    'det_pago_monto'            => $montoPrincipal,
                    'det_pago_metodo_pago'      => $metodoPagoPrincipal,
                    'det_pago_banco_id'         => $request->pago['banco_id'] ?? null,
                    'det_pago_numero_autorizacion' => $request->pago['numero_autorizacion'] ?? null,
                    'det_pago_tipo_pago'        => 'PAGO_UNICO',
                    'det_pago_estado'           => 'VALIDO',
                    'det_pago_observaciones'    => 'Pago principal de la venta',
                    'det_pago_usuario_registro' => auth()->id(),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }

            // 2. Registrar pago con saldo a favor (si hubo)
            if ($saldoFavorUsado > 0) {
                DB::table('pro_detalle_pagos')->insert([
                    'det_pago_pago_id'          => $pagoId,
                    'det_pago_cuota_id'         => null,
                    'det_pago_fecha'            => now(),
                    'det_pago_monto'            => $saldoFavorUsado,
                    'det_pago_metodo_pago'      => 7, // ID 7 = Saldo a Favor
                    'det_pago_banco_id'         => null,
                    'det_pago_numero_autorizacion' => null,
                    'det_pago_tipo_pago'        => 'ABONO_INICIAL',
                    'det_pago_estado'           => 'VALIDO',
                    'det_pago_observaciones'    => 'Pago con saldo a favor',
                    'det_pago_usuario_registro' => auth()->id(),
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);
            }

            $totalPagado = $totalVenta;
            $cantidadPagos = ($montoPrincipal > 0 ? 1 : 0) + ($saldoFavorUsado > 0 ? 1 : 0);
        }


        
        $porcentaje = 2.5;
        $totalTenencias = 0;

        foreach ($request->productos as $productoData) {
            if (isset($productoData['series_con_tenencia']) && is_array($productoData['series_con_tenencia'])) {
                $totalTenencias += count($productoData['series_con_tenencia']) * self::MONTO_TENENCIA;
            }
        }

        $montoBaseComision = $totalVenta - $totalTenencias;
        $ganancia = $montoBaseComision * ($porcentaje / 100);

        DB::table('pro_porcentaje_vendedor')->insert([
            'porc_vend_user_id'           => auth()->id(),
            'porc_vend_ven_id'            => $ventaId,
            'porc_vend_porcentaje'        => $porcentaje,
            'porc_vend_cantidad_ganancia' => $ganancia,
            'porc_vend_monto_base'        => $montoBaseComision,
            'porc_vend_fecha_asignacion'  => now(),
            'porc_vend_estado'            => 'PENDIENTE',
            'porc_vend_situacion'         => 'ACTIVO',
            'porc_vend_observaciones'     => "Comisión por venta (sin incluir Q{$totalTenencias} de tenencia)",
        ]);

      
        
        DB::table('cja_historial')->insert([
            'cja_tipo'          => 'VENTA',
            'cja_id_venta'      => $ventaId,
            'cja_usuario'       => auth()->id(),
            'cja_monto'         => $totalPagado,
            'cja_fecha'         => now(),
            'cja_metodo_pago'   => $request->metodo_pago,
            'cja_no_referencia' => "VENTA-{$ventaId}",
            'cja_situacion'     => 'PENDIENTE',
            'cja_observaciones' => 'Venta registrada',
            'created_at'        => now(),
        ]);

        DB::commit();

        return response()->json([
            'success'           => true,
            'message'           => 'Venta procesada exitosamente',
            'venta_id'          => $ventaId,
            'folio'             => "VENTA-{$ventaId}",
            'pago_id'           => $pagoId,
            'fue_reserva'       => $esDesdeReserva === true,
            'accion_stock'      => $accionStock,
            'accion_disponible' => $isDisponible,
            'saldo_favor_usado' => $saldoFavorUsado,
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Datos de validación incorrectos',
            'errors'  => $e->errors(),
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar la venta: ' . $e->getMessage(),
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
    public function obtenerVentasPendientes(Request $request)
    {
        try {
            $fechaDesde = $request->query('fecha_desde');
            $fechaHasta = $request->query('fecha_hasta');
            $vendedorId = $request->query('vendedor_id');
            $clienteId = $request->query('cliente_id');

            $query = DB::table('pro_ventas as v')
                ->join('pro_clientes as c', 'v.ven_cliente', '=', 'c.cliente_id')
                ->leftJoin('users as u', 'v.ven_user', '=', 'u.user_id')
                ->whereIn('v.ven_situacion', ['PENDIENTE', 'EDITABLE', 'AUTORIZADA']) // Adjust statuses as needed
                ->select(
                    'v.ven_id',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'v.ven_situacion',
                    'c.cliente_nombre1',
                    'c.cliente_apellido1',
                    'c.cliente_nom_empresa',
                    'u.user_primer_nombre',
                    'u.user_primer_apellido'
                );

            if ($fechaDesde) {
                $query->whereDate('v.ven_fecha', '>=', $fechaDesde);
            }

            if ($fechaHasta) {
                $query->whereDate('v.ven_fecha', '<=', $fechaHasta);
            }

            if ($vendedorId) {
                $query->where('v.ven_user', $vendedorId);
            }

            if ($clienteId) {
                $query->where('v.ven_cliente', $clienteId);
            }

            $ventas = $query->orderBy('v.ven_fecha', 'desc')->get();

            // Transform data for frontend
            $data = $ventas->map(function ($venta) {
                $detalles = DB::table('pro_detalle_ventas as d')
                    ->join('pro_productos as p', 'd.det_producto_id', '=', 'p.producto_id')
                    ->where('d.det_ven_id', $venta->ven_id)
                    ->select('d.det_id', 'd.det_producto_id', 'p.producto_nombre', 'd.det_cantidad')
                    ->get();

                // Fetch series/lotes if needed (simplified for summary)
                $detalles->transform(function ($det) use ($venta) {
                    $det->series = DB::table('pro_movimientos as m')
                        ->join('pro_series_productos as s', 'm.mov_serie_id', '=', 's.serie_id')
                        ->where('m.mov_documento_referencia', 'VENTA-' . $venta->ven_id) // Or RESERVA depending on flow
                        ->where('m.mov_producto_id', $det->det_producto_id)
                        ->pluck('s.serie_numero_serie')
                        ->toArray();
                    
                    // Lotes logic if applicable
                    $det->lotes = []; 

                    return $det;
                });

                $clienteNombre = trim("{$venta->cliente_nombre1} {$venta->cliente_apellido1}");
                $vendedorNombre = trim("{$venta->user_primer_nombre} {$venta->user_primer_apellido}");

                return [
                    'ven_id' => $venta->ven_id,
                    'ven_fecha' => $venta->ven_fecha,
                    'cliente' => $clienteNombre,
                    'empresa' => $venta->cliente_nom_empresa,
                    'vendedor' => $vendedorNombre ?: 'Sin asignar',
                    'ven_total_vendido' => $venta->ven_total_vendido,
                    'ven_situacion' => $venta->ven_situacion,
                    'total_items' => $detalles->sum('det_cantidad'),
                    'productos_resumen' => $detalles->pluck('producto_nombre')->join(', '),
                    'detalles' => $detalles
                ];
            });

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Error fetching pending sales: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function listarReservas(Request $request)
    {
        try {
            $fechaInicio = $request->query('fecha_inicio');
            $fechaFin = $request->query('fecha_fin');
            $busqueda = $request->query('busqueda');
    
            $query = DB::table('pro_ventas as v')
                ->join('pro_clientes as c', 'v.ven_cliente', '=', 'c.cliente_id')
                ->leftJoin('users as u', 'v.ven_user', '=', 'u.user_id')  // ← CAMBIAR A leftJoin
                ->where('v.ven_situacion', 'RESERVADA')
                ->select(
                    'v.ven_id',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'v.ven_descuento',
                    'v.ven_situacion',
                    'c.cliente_nombre1',
                    'c.cliente_nombre2',
                    'c.cliente_apellido1',
                    'c.cliente_apellido2',
                    'c.cliente_nit',
                    'c.cliente_nom_empresa',
                    DB::raw("COALESCE(TRIM(CONCAT_WS(' ', u.user_primer_nombre, u.user_segundo_nombre, u.user_primer_apellido, u.user_segundo_apellido)), 'Sin asignar') as vendedor")  // ← AGREGAR COALESCE
                );
    
            if ($fechaInicio) {
                $query->whereDate('v.ven_fecha', '>=', $fechaInicio);
            }
    
            if ($fechaFin) {
                $query->whereDate('v.ven_fecha', '<=', $fechaFin);
            }
    
            if ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('c.cliente_nombre1', 'like', "%{$busqueda}%")
                        ->orWhere('c.cliente_apellido1', 'like', "%{$busqueda}%")
                        ->orWhere('c.cliente_nit', 'like', "%{$busqueda}%")
                        ->orWhere('c.cliente_nom_empresa', 'like', "%{$busqueda}%");
                });
            }
    
            $reservas = $query->orderBy('v.ven_fecha', 'desc')->get();
    
            // Cargar detalles para cada reserva
            foreach ($reservas as $reserva) {
                $detalles = DB::table('pro_detalle_ventas as d')
                    ->join('pro_productos as p', 'd.det_producto_id', '=', 'p.producto_id')
                    ->where('d.det_ven_id', $reserva->ven_id)
                    ->select(
                        'd.det_id',
                        'd.det_producto_id',
                        'p.producto_nombre',
                        'd.det_cantidad',
                        'd.det_precio'
                    )
                    ->get();
    
                // Cargar series reservadas para cada detalle
                foreach ($detalles as $detalle) {
                    $series = DB::table('pro_movimientos as m')
                        ->join('pro_series_productos as s', 'm.mov_serie_id', '=', 's.serie_id')
                        ->where('m.mov_documento_referencia', 'RESERVA-' . $reserva->ven_id)
                        ->where('m.mov_producto_id', $detalle->det_producto_id)
                        ->where('m.mov_situacion', 2) // Reservado
                        ->select('s.serie_numero_serie')
                        ->get()
                        ->pluck('serie_numero_serie');
                    
                    $detalle->series = $series;
                }
    
                $reserva->detalles = $detalles;
                $reserva->cantidad_productos = $detalles->sum('det_cantidad');
            }
    
            return response()->json($reservas);
    
        } catch (\Exception $e) {
            Log::error('Error al listar reservas: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar reservas: ' . $e->getMessage()], 500);
        }
    }
    public function cancelarReserva(Request $request)
    {
        $venId = $request->input('id');

        try {
            DB::transaction(function () use ($venId) {
                // 1. Verificar estado actual
                $venta = DB::table('pro_ventas')->where('ven_id', $venId)->first();
                
                if (!$venta) {
                    throw new \Exception('Venta no encontrada');
                }

                if ($venta->ven_situacion !== 'RESERVADA') {
                    throw new \Exception('La venta no está en estado RESERVADA');
                }

                // 2. Cambiar estado de venta a CANCELADA
                DB::table('pro_ventas')
                    ->where('ven_id', $venId)
                    ->update(['ven_situacion' => 'ANULADA']);

                // 3. Liberar series y lotes (movimientos)
                // Buscar movimientos reservados (situacion 3) asociados a esta venta
                $movimientos = DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', 'RESERVA-' . $venId)
                    ->where('mov_situacion', 2)
                    ->get();

                foreach ($movimientos as $mov) {
                    // Si tiene serie, liberar la serie en pro_series_productos
                    if ($mov->mov_serie_id) {
                        DB::table('pro_series_productos')
                            ->where('serie_id', $mov->mov_serie_id)
                            ->update([
                                'serie_estado' => 'disponible',
                                'serie_situacion' => 1
                            ]);
                    }

                    // Eliminar o anular el movimiento de reserva?
                    // Generalmente se cambia a anulado (0) o se elimina. 
                    // Si usamos lógica de "liberar", podríamos borrarlos o marcar mov_situacion = 0 (Anulado)
                    // Para mantener historial, mejor marcar como anulado.
                    DB::table('pro_movimientos')
                        ->where('mov_id', $mov->mov_id)
                        ->update(['mov_situacion' => 0]); // 0 = Anulado/Cancelado
                }

                // 4. Devolver stock reservado a disponible
                // Necesitamos saber qué productos y qué cantidades se reservaron
                // Podemos usar los detalles de la venta o los movimientos.
                // Usaremos los detalles para ser más precisos con lo que se pidió.
                
                $detalles = DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venId)
                    ->get();

                foreach ($detalles as $det) {
                    // Decrementar stock reservado 2 (usado para reservas)
                    DB::table('pro_stock_actual')
                        ->where('stock_producto_id', $det->det_producto_id)
                        ->decrement('stock_cantidad_reservada2', $det->det_cantidad);
                    
                    // No es necesario incrementar stock disponible ya que procesarReserva no lo decrementa
                    // Solo afecta el cálculo de disponible real (disponible - reservada - reservada2)
                        
                    // El stock total no debería cambiar, ya que la mercadería nunca salió físicamente,
                    // solo cambió de estado.
                }
                
                // Actualizar estado de detalles
                DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venId)
                    ->update(['det_situacion' => 'ANULADA']);

            });

            return response()->json(['success' => true, 'message' => 'Reserva cancelada correctamente']);

        } catch (\Exception $e) {
            Log::error('Error al cancelar reserva: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
