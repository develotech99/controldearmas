<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Marcas;
use App\Models\ProPago;
use App\Models\Producto;
use App\Models\ProVenta;
use App\Models\Categoria;
use App\Models\ProCliente;
use Illuminate\Http\Request;
use App\Models\ProMetodoPago;
use App\Models\ProDetallePago;
use App\Models\ProDetalleVenta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProPorcentajeVendedor;



class ReportesController extends Controller
{
    /**
     * Vista principal de reportes
     */
    public function index()
    {
        return view('reportes.index');
    }


    /**
     * Dashboard principal de reportes con KPIs - CORREGIDO
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            // Convertir fechas correctamente
            $fechaInicio = $request->filled('fecha_inicio')
                ? Carbon::parse($request->fecha_inicio)->startOfDay()
                : Carbon::now()->startOfMonth();

            $fechaFin = $request->filled('fecha_fin')
                ? Carbon::parse($request->fecha_fin)->endOfDay()
                : Carbon::now()->endOfDay();

            \Log::info('Dashboard fechas:', [
                'inicio' => $fechaInicio->toDateTimeString(),
                'fin' => $fechaFin->toDateTimeString()
            ]);

            // KPIs principales
            $kpis = $this->getKPIs($fechaInicio, $fechaFin);

            // Gráficos
            $ventasPorDia = $this->getVentasPorDia($fechaInicio, $fechaFin);
            $productosMasVendidos = $this->getProductosMasVendidos($fechaInicio, $fechaFin);
            $ventasPorVendedor = $this->getVentasPorVendedor($fechaInicio, $fechaFin);
            $metodospagoStats = $this->getEstadisticasMetodosPago($fechaInicio, $fechaFin);

            \Log::info('Dashboard datos:', [
                'kpis' => $kpis,
                'ventas_por_dia_count' => count($ventasPorDia),
                'productos_count' => count($productosMasVendidos),
                'vendedores_count' => count($ventasPorVendedor),
                'metodos_pago_count' => count($metodospagoStats)
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'kpis' => $kpis,
                    'graficos' => [
                        'ventas_por_dia' => $ventasPorDia,
                        'productos_mas_vendidos' => $productosMasVendidos,
                        'ventas_por_vendedor' => $ventasPorVendedor,
                        'metodos_pago' => $metodospagoStats
                    ]
                ],
                'periodo' => [
                    'inicio' => $fechaInicio->format('Y-m-d'),
                    'fin' => $fechaFin->format('Y-m-d')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getDashboard:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar dashboard: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de ventas detallado
     */
public function getReporteVentas(Request $request): JsonResponse
{
    try {
        $query = ProVenta::withTrashed()->with([
            'vendedor:user_id,user_primer_nombre,user_primer_apellido',
            'detalleVentas.producto:producto_id,producto_nombre,pro_codigo_sku',
            'cliente:cliente_id,cliente_nombre1,cliente_apellido1,cliente_dpi',
            'pagos.detallesPago.metodoPago:metpago_id,metpago_descripcion'
        ]);

        // Filtro por fecha
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('pro_ventas.ven_fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('pro_ventas.ven_fecha', '<=', $request->fecha_fin);
        }

        // Filtro por vendedor
        if ($request->filled('vendedor_id')) {
            $query->where('pro_ventas.ven_user', $request->vendedor_id);
        }

        // Filtro por cliente ID (desde el select)
        if ($request->filled('cliente_id')) {
            $clienteId = $request->cliente_id;
            // Si viene compuesto "123_45", tomamos solo el 123
            if (strpos($clienteId, '_') !== false) {
                $parts = explode('_', $clienteId);
                $clienteId = $parts[0];
            }
            $query->where('pro_ventas.ven_cliente', $clienteId);
        }

        // Filtro por cliente - CORREGIDO: búsqueda por nombre o DPI
        if ($request->filled('cliente_buscar')) {
            $busqueda = $request->cliente_buscar;
            $query->whereHas('cliente', function ($q) use ($busqueda) {
                $q->where('cliente_nombre1', 'LIKE', "%{$busqueda}%")
                    ->orWhere('cliente_apellido1', 'LIKE', "%{$busqueda}%")
                    ->orWhere('cliente_dpi', 'LIKE', "%{$busqueda}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('pro_ventas.ven_situacion', $request->estado);
        }

        // Calcular estado de pago para cada venta
        $ventas = $query->orderBy('pro_ventas.ven_fecha', 'desc')
            ->paginate($request->get('per_page', 25));

        // Agregar información calculada a cada venta
        $ventas->getCollection()->transform(function ($venta) {
            // Calcular estado de pago
            $totalPagado = $venta->pagos->sum('pago_monto_pagado');
            $totalVenta = $venta->ven_total_vendido;

            if ($totalPagado == 0) {
                $venta->estado_pago = 'PENDIENTE';
            } elseif ($totalPagado >= $totalVenta - 0.01) { // Margen de error por decimales
                $venta->estado_pago = 'COMPLETADO';
            } else {
                $venta->estado_pago = 'PARCIAL';
            }

            $venta->total_pagado = $totalPagado;
            $venta->saldo_pendiente = $totalVenta - $totalPagado;

            // Obtener series de los movimientos asociados a esta venta
            $movimientosSeries = DB::table('pro_movimientos')
                ->join('pro_series_productos', 'pro_movimientos.mov_serie_id', '=', 'pro_series_productos.serie_id')
                ->where('mov_documento_referencia', "VENTA-{$venta->ven_id}")
                ->whereNotNull('mov_serie_id')
                ->select('mov_producto_id', 'serie_numero_serie')
                ->get()
                ->groupBy('mov_producto_id');

            // Formatear productos para el reporte
            $venta->productos_formateados = $venta->detalleVentas->map(function($detalle) use ($movimientosSeries) {
                $series = $movimientosSeries->get($detalle->det_producto_id, collect())->pluck('serie_numero_serie')->toArray();
                
                return [
                    'cantidad' => $detalle->det_cantidad,
                    'nombre' => $detalle->producto->producto_nombre ?? 'N/A',
                    'sku' => $detalle->producto->pro_codigo_sku ?? 'N/A',
                    'series' => $series
                ];
            });
            
            return $venta; 
        });

        return response()->json([
            'success' => true,
            'data' => $ventas
        ]); // ← AGREGA EL RETURN DE LA RESPUESTA

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener el reporte de ventas',
            'error' => $e->getMessage()
        ], 500);
    }
} 


    public function buscarClientes(Request $request): JsonResponse
    {
        try {
            $termino = $request->get('q', '');
            
            // Construir query con JOIN a empresas para mostrar variantes
            $query = ProCliente::select(
                    'pro_clientes.cliente_id', 
                    'pro_clientes.cliente_nombre1', 
                    'pro_clientes.cliente_nombre2',
                    'pro_clientes.cliente_apellido1', 
                    'pro_clientes.cliente_apellido2',
                    'pro_clientes.cliente_dpi',
                    'ce.emp_nombre as empresa_nombre',
                    'ce.emp_id as empresa_id'
                )
                ->leftJoin('pro_clientes_empresas as ce', 'pro_clientes.cliente_id', '=', 'ce.emp_cliente_id')
                ->where('pro_clientes.cliente_situacion', 1);
            
            // Si hay término de búsqueda, filtrar
            if (!empty($termino) && strlen($termino) >= 1) {
                $query->where(function($q) use ($termino) {
                    $q->where('pro_clientes.cliente_nombre1', 'LIKE', "%{$termino}%")
                      ->orWhere('pro_clientes.cliente_nombre2', 'LIKE', "%{$termino}%")
                      ->orWhere('pro_clientes.cliente_apellido1', 'LIKE', "%{$termino}%")
                      ->orWhere('pro_clientes.cliente_apellido2', 'LIKE', "%{$termino}%")
                      ->orWhere('pro_clientes.cliente_dpi', 'LIKE', "%{$termino}%")
                      ->orWhere('ce.emp_nombre', 'LIKE', "%{$termino}%");
                });
            }
            
            // Obtener resultados
            $clientes = $query->limit(20)
                ->orderBy('pro_clientes.cliente_nombre1', 'asc')
                ->get()
                ->map(function($cliente) {
                    $nombreCompleto = trim(
                        implode(' ', array_filter([
                            $cliente->cliente_nombre1,
                            $cliente->cliente_nombre2,
                            $cliente->cliente_apellido1,
                            $cliente->cliente_apellido2
                        ]))
                    );
                    
                    $texto = $nombreCompleto;
                    
                    // Agregar empresa si existe
                    if (!empty($cliente->empresa_nombre)) {
                        $texto .= " - " . $cliente->empresa_nombre;
                    } else {
                        $texto .= " (Personal)";
                    }

                    if ($cliente->cliente_dpi) {
                        $texto .= " [DPI: {$cliente->cliente_dpi}]";
                    }
                    
                    // ID compuesto para diferenciar opciones en el select
                    // Formato: clienteId_empresaId (0 si no tiene)
                    $empresaId = $cliente->empresa_id ?? 0;
                    $idCompuesto = "{$cliente->cliente_id}_{$empresaId}";
                    
                    return [
                        'id' => $idCompuesto,
                        'text' => $texto
                    ];
                });
                
            return response()->json([
                'success' => true,
                'results' => $clientes
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en buscarClientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'results' => [],
                'error' => $e->getMessage()
            ]);
        }
    }



    /**
     * Reporte de productos más vendidos
     */
    public function getReporteProductos(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->filled('fecha_inicio')
                ? Carbon::parse($request->fecha_inicio)->startOfDay()
                : Carbon::now()->startOfMonth();

            $fechaFin = $request->filled('fecha_fin')
                ? Carbon::parse($request->fecha_fin)->endOfDay()
                : Carbon::now()->endOfMonth();

            $query = DB::table('pro_detalle_ventas as dv')
                ->join('pro_ventas as v', 'dv.det_ven_id', '=', 'v.ven_id')
                ->join('pro_productos as p', 'dv.det_producto_id', '=', 'p.producto_id')
                ->leftJoin('pro_categorias as c', 'p.producto_categoria_id', '=', 'c.categoria_id')
                ->leftJoin('pro_marcas as m', 'p.producto_marca_id', '=', 'm.marca_id')
                ->whereBetween('v.ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->whereIn('dv.det_situacion', ['ACTIVO', 'ACTIVA'])
                ->select([
                    'p.producto_id',
                    'p.pro_codigo_sku',
                    'p.producto_nombre',
                    'c.categoria_nombre',
                    'm.marca_descripcion as marca_nombre',
                    DB::raw('SUM(dv.det_cantidad) as total_vendido'),
                    DB::raw('AVG(dv.det_precio) as precio_promedio'),
                    DB::raw('SUM(dv.det_cantidad * dv.det_precio) as total_ingresos'),
                    DB::raw('COUNT(DISTINCT v.ven_id) as total_transacciones')
                ])
                ->groupBy('p.producto_id', 'p.pro_codigo_sku', 'p.producto_nombre', 'c.categoria_nombre', 'm.marca_descripcion');

            // ✅ FILTROS CORREGIDOS
            if ($request->filled('categoria_id') && $request->categoria_id != '') {
                $query->where('p.producto_categoria_id', $request->categoria_id);
            }

            if ($request->filled('marca_id') && $request->marca_id != '') {
                $query->where('p.producto_marca_id', $request->marca_id);
            }

            $limit = $request->filled('limit') ? (int)$request->limit : 25;

            $productos = $query->orderBy('total_vendido', 'desc')
                ->limit($limit)
                ->get();

            \Log::info('Productos filtrados:', [
                'categoria' => $request->categoria_id,
                'marca' => $request->marca_id,
                'total' => $productos->count()
            ]);

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getReporteProductos:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reporte de productos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de comisiones de vendedores
     */
    public function getReporteComisiones(Request $request): JsonResponse
    {
        try {
            $query = ProPorcentajeVendedor::with([
                'vendedor:user_id,user_primer_nombre,user_primer_apellido',
                'venta:ven_id,ven_fecha,ven_total_vendido'
            ]);

            // Aplicar filtros por fecha
            if ($request->filled('fecha_inicio') || $request->filled('fecha_fin')) {
                $query->whereHas('venta', function ($q) use ($request) {
                    if ($request->filled('fecha_inicio')) {
                        $q->whereDate('ven_fecha', '>=', $request->fecha_inicio);
                    }
                    if ($request->filled('fecha_fin')) {
                        $q->whereDate('ven_fecha', '<=', $request->fecha_fin);
                    }
                });
            }

            if ($request->filled('vendedor_id')) {
                $query->where('porc_vend_user_id', $request->vendedor_id);
            }

            // Enforce filter for sellers
            if (auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                $query->where('porc_vend_user_id', auth()->id());
            }

            if ($request->filled('estado')) {
                $query->where('porc_vend_estado', $request->estado);
            }

            $comisiones = $query->orderBy('porc_vend_fecha_asignacion', 'desc')
                ->paginate($request->get('per_page', 25));

            // Resumen de comisiones
            $resumen = $this->getResumenComisiones($request);

            return response()->json([
                'success' => true,
                'data' => [
                    'comisiones' => $comisiones,
                    'resumen' => $resumen
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reporte de comisiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de pagos y cuotas
     */
    public function getReportePagos(Request $request): JsonResponse
    {
        try {
            $query = ProPago::with([
                'venta:ven_id,ven_fecha,ven_total_vendido,ven_user,ven_cliente',
                'venta.vendedor:user_id,user_primer_nombre,user_primer_apellido',
                'venta.cliente:cliente_id,cliente_nombre1,cliente_apellido1',
                'detallesPago.metodoPago:metpago_id,metpago_descripcion'
            ]);

            // ✅ FILTROS CORREGIDOS
            if ($request->filled('fecha_inicio')) {
                $query->whereDate('pago_fecha_inicio', '>=', $request->fecha_inicio);
            }

            if ($request->filled('fecha_fin')) {
                $query->whereDate('pago_fecha_inicio', '<=', $request->fecha_fin);
            }

            if ($request->filled('estado') && $request->estado != '') {
                $query->where('pago_estado', $request->estado);
            }

            if ($request->filled('tipo_pago') && $request->tipo_pago != '') {
                $query->where('pago_tipo_pago', $request->tipo_pago);
            }

            $pagos = $query->orderBy('pago_fecha_inicio', 'desc')
                ->paginate($request->get('per_page', 25));

            // Agregar información calculada
            $pagos->getCollection()->transform(function ($pago) {
                $pago->monto_total_pagado = $pago->detallesPago
                    ->where('det_pago_estado', 'VALIDO')
                    ->sum('det_pago_monto');

                $pago->saldo_pendiente = $pago->pago_monto_total - $pago->monto_total_pagado;

                // ✅ FIX: ESTADO DINÁMICO
                if ($pago->monto_total_pagado >= $pago->pago_monto_total) {
                    $pago->estado_pago = 'COMPLETADO';
                    $pago->pago_estado = 'COMPLETADO'; 
                } elseif ($pago->monto_total_pagado > 0) {
                    $pago->estado_pago = 'PARCIAL';
                    $pago->pago_estado = 'PARCIAL';
                } else {
                    $pago->estado_pago = 'PENDIENTE';
                    // $pago->pago_estado = 'PENDIENTE'; // Mantener original si es pendiente
                }

                return $pago;
            });

            \Log::info('Pagos filtrados:', [
                'estado' => $request->estado,
                'tipo' => $request->tipo_pago,
                'total' => $pagos->total()
            ]);

            return response()->json([
                'success' => true,
                'data' => $pagos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getReportePagos:', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reporte de pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte a PDF
     */
    public function exportarPDF(Request $request)
    {
        try {
            $tipoReporte = $request->get('tipo_reporte');

            if (!$tipoReporte) {
                throw new \Exception('Tipo de reporte no especificado');
            }

            // Validar tipo de reporte
            $tiposValidos = ['ventas', 'productos', 'comisiones', 'pagos'];
            if (!in_array($tipoReporte, $tiposValidos)) {
                throw new \Exception('Tipo de reporte no válido: ' . $tipoReporte);
            }

            $data = $this->getDataForExport($request);

            // Verificar que tenemos datos
            if (empty($data['data']) && !isset($data['error'])) {
                throw new \Exception('No se encontraron datos para el reporte');
            }

            // Verificar que existe la vista
            $vistaPath = "reportes.pdf.{$tipoReporte}";
            if (!view()->exists($vistaPath)) {
                throw new \Exception("Vista no encontrada: {$vistaPath}");
            }

            $pdf = Pdf::loadView($vistaPath, $data);

            // Configurar el PDF
            $pdf->setPaper('letter', 'portrait');
            $pdf->setOption('defaultFont', 'sans-serif');

            $filename = "reporte_{$tipoReporte}_" . date('Y-m-d_H-i-s') . ".pdf";

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error generando PDF: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reporte a Excel
     */
    public function exportarExcel(Request $request)
    {
        try {
            $tipoReporte = $request->get('tipo_reporte');

            return Excel::download(
                new \App\Exports\ReportesExport($request->all()),
                "reporte_{$tipoReporte}_" . date('Y-m-d') . ".xlsx"
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener datos para filtros
     */
    public function getFiltros(): JsonResponse
    {
        try {
            $vendedores = User::select('user_id', 'user_primer_nombre', 'user_primer_apellido')
                ->orderBy('user_primer_nombre')
                ->get();

            $categorias = Categoria::select('categoria_id', 'categoria_nombre')
                ->where('categoria_situacion', 1)
                ->orderBy('categoria_nombre')
                ->get();

            $marcas = Marcas::select('marca_id', 'marca_descripcion')
                ->where('marca_situacion', 1)
                ->orderBy('marca_descripcion')
                ->get();

            $metodosPago = ProMetodoPago::select('metpago_id', 'metpago_descripcion')
                ->where('metpago_situacion', 1)
                ->orderBy('metpago_descripcion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'vendedores' => $vendedores,
                    'categorias' => $categorias,
                    'marcas' => $marcas,
                    'metodos_pago' => $metodosPago
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar filtros: ' . $e->getMessage()
            ], 500);
        }
    }

    // ================================
    // MÉTODOS PRIVADOS CORREGIDOS
    // ================================

    private function getKPIs($fechaInicio, $fechaFin)
    {
        try {
            $ventasQuery = ProVenta::whereBetween('ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA']);

            $totalVentas = $ventasQuery->count();

            // Re-instanciar o clonar para la siguiente consulta, ya que count() modifica el builder o ejecuta
            $montoTotal = ProVenta::whereBetween('ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->sum('ven_total_vendido') ?? 0;
            $promedioVenta = $totalVentas > 0 ? round($montoTotal / $totalVentas, 2) : 0;

            $productosVendidos = ProDetalleVenta::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('ven_fecha', [$fechaInicio, $fechaFin])
                    ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA']);
            })->whereIn('det_situacion', ['ACTIVO', 'ACTIVA'])
                ->sum('det_cantidad') ?? 0;

            $comisionesPendientes = ProPorcentajeVendedor::whereHas('venta', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('ven_fecha', [$fechaInicio, $fechaFin]);
            })->where('porc_vend_estado', 'PENDIENTE')
                ->sum('porc_vend_cantidad_ganancia') ?? 0;

            return [
                'total_ventas' => (int) $totalVentas,
                'monto_total' => (float) $montoTotal,
                'promedio_venta' => (float) $promedioVenta,
                'productos_vendidos' => (int) $productosVendidos,
                'comisiones_pendientes' => (float) $comisionesPendientes
            ];
        } catch (\Exception $e) {
            \Log::error('Error en getKPIs: ' . $e->getMessage());
            return [
                'total_ventas' => 0,
                'monto_total' => 0,
                'promedio_venta' => 0,
                'productos_vendidos' => 0,
                'comisiones_pendientes' => 0
            ];
        }
    }

    private function getVentasPorDia($fechaInicio, $fechaFin)
    {
        try {
            $ventas = DB::table('pro_ventas')
                ->selectRaw('DATE(ven_fecha) as fecha, COUNT(*) as total_ventas, COALESCE(SUM(ven_total_vendido), 0) as monto_total')
                ->whereBetween('ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->groupBy(DB::raw('DATE(ven_fecha)'))
                ->orderBy('fecha')
                ->get();

            \Log::info('Ventas por día query:', [
                'count' => $ventas->count(),
                'first' => $ventas->first()
            ]);

            return $ventas->map(function ($item) {
                return [
                    'fecha' => $item->fecha,
                    'total_ventas' => (int) $item->total_ventas,
                    'monto_total' => (float) $item->monto_total
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error en getVentasPorDia: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getProductosMasVendidos($fechaInicio, $fechaFin, $limit = 10)
    {
        try {
            $productos = DB::table('pro_detalle_ventas as dv')
                ->join('pro_ventas as v', 'dv.det_ven_id', '=', 'v.ven_id')
                ->join('pro_productos as p', 'dv.det_producto_id', '=', 'p.producto_id')
                ->selectRaw('
                p.producto_id,
                p.producto_nombre, 
                SUM(dv.det_cantidad) as total_vendido,
                COALESCE(SUM(dv.det_cantidad * dv.det_precio), 0) as total_ingresos
            ')
                ->whereBetween('v.ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->whereIn('dv.det_situacion', ['ACTIVO', 'ACTIVA'])
                ->groupBy('p.producto_id', 'p.producto_nombre')
                ->orderBy('total_vendido', 'desc')
                ->limit($limit)
                ->get();

            \Log::info('Productos más vendidos:', [
                'count' => $productos->count(),
                'first' => $productos->first()
            ]);

            return $productos->map(function ($item) {
                return [
                    'producto_id' => $item->producto_id,
                    'producto_nombre' => $item->producto_nombre,
                    'total_vendido' => (int) $item->total_vendido,
                    'total_ingresos' => (float) $item->total_ingresos
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error en getProductosMasVendidos: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getVentasPorVendedor($fechaInicio, $fechaFin)
    {
        try {
            $ventas = DB::table('pro_ventas as v')
                ->join('users as u', 'v.ven_user', '=', 'u.user_id')
                ->selectRaw('
                u.user_id,
                CONCAT(u.user_primer_nombre, " ", u.user_primer_apellido) as vendedor, 
                COUNT(*) as total_ventas, 
                COALESCE(SUM(v.ven_total_vendido), 0) as monto_total
            ')
                ->whereBetween('v.ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->groupBy('u.user_id', 'u.user_primer_nombre', 'u.user_primer_apellido')
                ->orderBy('monto_total', 'desc')
                ->get();

            \Log::info('Ventas por vendedor:', [
                'count' => $ventas->count(),
                'first' => $ventas->first()
            ]);

            return $ventas->map(function ($item) {
                return [
                    'vendedor_id' => $item->user_id,
                    'vendedor' => $item->vendedor,
                    'total_ventas' => (int) $item->total_ventas,
                    'monto_total' => (float) $item->monto_total
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error en getVentasPorVendedor: ' . $e->getMessage());
            return collect([]);
        }
    }

    private function getEstadisticasMetodosPago($fechaInicio, $fechaFin)
    {
        try {
            $stats = DB::table('pro_detalle_pagos as dp')
                ->join('pro_pagos as p', 'dp.det_pago_pago_id', '=', 'p.pago_id')
                ->join('pro_ventas as v', 'p.pago_venta_id', '=', 'v.ven_id')
                ->join('pro_metodos_pago as mp', 'dp.det_pago_metodo_pago', '=', 'mp.metpago_id')
                ->selectRaw('
                mp.metpago_id,
                mp.metpago_descripcion as metodo, 
                COUNT(*) as total_transacciones, 
                COALESCE(SUM(dp.det_pago_monto), 0) as monto_total
            ')
                ->whereBetween('v.ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'AUTORIZADA'])
                ->where('dp.det_pago_estado', 'VALIDO')
                ->groupBy('mp.metpago_id', 'mp.metpago_descripcion')
                ->orderBy('monto_total', 'desc')
                ->get();

            \Log::info('Métodos de pago stats:', [
                'count' => $stats->count(),
                'first' => $stats->first()
            ]);

            return $stats->map(function ($item) {
                return [
                    'metodo_id' => $item->metpago_id,
                    'metodo' => $item->metodo,
                    'total_transacciones' => (int) $item->total_transacciones,
                    'monto_total' => (float) $item->monto_total
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error en getEstadisticasMetodosPago: ' . $e->getMessage());
            return collect([]);
        }
    }
    private function getResumenComisiones($request)
    {
        $query = ProPorcentajeVendedor::query();

        if ($request->filled('fecha_inicio') || $request->filled('fecha_fin')) {
            $query->whereHas('venta', function ($q) use ($request) {
                if ($request->filled('fecha_inicio')) {
                    $q->whereDate('ven_fecha', '>=', $request->fecha_inicio);
                }
                if ($request->filled('fecha_fin')) {
                    $q->whereDate('ven_fecha', '<=', $request->fecha_fin);
                }
            });
        }

        if ($request->filled('vendedor_id')) {
            $query->where('porc_vend_user_id', $request->vendedor_id);
        }

        // Enforce filter for sellers
        if (auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
            $query->where('porc_vend_user_id', auth()->id());
        }

        return [
            'total_comisiones' => $query->sum('porc_vend_cantidad_ganancia'),
            'pendientes' => $query->where('porc_vend_estado', 'PENDIENTE')->sum('porc_vend_cantidad_ganancia'),
            'pagadas' => $query->where('porc_vend_estado', 'PAGADO')->sum('porc_vend_cantidad_ganancia'),
            'canceladas' => $query->where('porc_vend_estado', 'CANCELADO')->sum('porc_vend_cantidad_ganancia')
        ];
    }

    private function getDataForExport($request)
    {
        $tipo = $request->get('tipo_reporte');

        try {
            switch ($tipo) {
                case 'ventas':
                    $response = $this->getReporteVentas($request);
                    $responseData = $response->getData(true);

                    // Verificar que existe la estructura esperada
                    if (!isset($responseData['success']) || !$responseData['success']) {
                        throw new \Exception('Error obteniendo datos de ventas');
                    }

                    return [
                        'data' => $responseData['data'] ?? [],
                        'tipo' => 'ventas',
                        'titulo' => 'Reporte de Ventas',
                        'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                        'filtros' => $request->all()
                    ];

                case 'productos':
                    $response = $this->getReporteProductos($request);
                    $responseData = $response->getData(true);

                    if (!isset($responseData['success']) || !$responseData['success']) {
                        throw new \Exception('Error obteniendo datos de productos');
                    }

                    return [
                        'data' => $responseData['data'] ?? [],
                        'tipo' => 'productos',
                        'titulo' => 'Productos Más Vendidos',
                        'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                        'filtros' => $request->all()
                    ];

                case 'comisiones':
                    $response = $this->getReporteComisiones($request);
                    $responseData = $response->getData(true);

                    if (!isset($responseData['success']) || !$responseData['success']) {
                        throw new \Exception('Error obteniendo datos de comisiones');
                    }

                    // Para comisiones, los datos están en una estructura diferente
                    return [
                        'data' => $responseData['data']['comisiones'] ?? [],
                        'resumen' => $responseData['data']['resumen'] ?? [],
                        'tipo' => 'comisiones',
                        'titulo' => 'Reporte de Comisiones',
                        'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                        'filtros' => $request->all()
                    ];

                case 'pagos':
                    $response = $this->getReportePagos($request);
                    $responseData = $response->getData(true);

                    if (!isset($responseData['success']) || !$responseData['success']) {
                        throw new \Exception('Error obteniendo datos de pagos');
                    }

                    return [
                        'data' => $responseData['data'] ?? [],
                        'tipo' => 'pagos',
                        'titulo' => 'Estado de Pagos',
                        'fecha_generacion' => now()->format('d/m/Y H:i:s'),
                        'filtros' => $request->all()
                    ];

                default:
                    throw new \Exception('Tipo de reporte no válido: ' . $tipo);
            }
        } catch (\Exception $e) {
            \Log::error('Error en getDataForExport: ' . $e->getMessage());
            return [
                'data' => [],
                'error' => $e->getMessage(),
                'tipo' => $tipo ?? 'desconocido',
                'titulo' => 'Error en Reporte',
                'fecha_generacion' => now()->format('d/m/Y H:i:s')
            ];
        }
    }


    /**
     * Verificar si un producto es arma según categorías
     */
    private function esProductoArma($productoId): bool
    {
        $producto = Producto::with('categoria')->find($productoId);

        if (!$producto || !$producto->categoria) {
            return false;
        }

        $categoriaArma = strtoupper($producto->categoria->categoria_nombre);
        $palabrasClaveArmas = ['ARMA', 'PISTOLA', 'REVOLVER', 'CARABINA', 'RIFLE', 'ESCOPETA'];

        foreach ($palabrasClaveArmas as $palabra) {
            if (str_contains($categoriaArma, $palabra)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reporte DIGECAM - Ventas de Armas de Fuego
     */
    public function getReporteDigecamArmas(Request $request): JsonResponse
    {
        try {
            $mes = $request->get('mes', now()->month);
            $anio = $request->get('anio', now()->year);

            \Log::info('📊 Generando reporte DIGECAM Armas:', [
                'mes' => $mes,
                'anio' => $anio
            ]);

            // ✅ FIX FINAL: Consulta directa Venta -> Movimientos -> Series
            // Elimina dependencia de pro_detalle_ventas para evitar producto cartesiano
            $ventas = DB::table('pro_ventas as v')
                ->join('pro_movimientos as mov', function($join) {
                    $join->on(DB::raw("CONCAT('VENTA-', v.ven_id)"), '=', 'mov.mov_documento_referencia')
                         ->where('mov.mov_tipo', '=', 'venta')
                         ->where('mov.mov_situacion', '=', 1);
                })
                ->join('pro_series_productos as sp', 'mov.mov_serie_id', '=', 'sp.serie_id')
                ->join('pro_productos as p', 'mov.mov_producto_id', '=', 'p.producto_id')
                ->join('pro_categorias as c', 'p.producto_categoria_id', '=', 'c.categoria_id')
                ->join('pro_marcas as m', 'p.producto_marca_id', '=', 'm.marca_id')
                ->join('pro_modelo as mo', 'p.producto_modelo_id', '=', 'mo.modelo_id')
                ->join('pro_calibres as cal', 'p.producto_calibre_id', '=', 'cal.calibre_id')
                ->join('pro_clientes as cl', 'v.ven_cliente', '=', 'cl.cliente_id')
                ->leftJoin('facturacion as f', 'v.ven_id', '=', 'f.fac_venta_id') 
                ->select([
                    'mov.mov_licencia_anterior AS pro_tenencia_anterior',
                    'mov.mov_licencia_nueva    AS pro_tenencia_nueva',
                    'p.producto_nombre as tipo',
                    DB::raw('COALESCE(sp.serie_numero_serie, "SIN SERIE") as serie'),
                    DB::raw('COALESCE(m.marca_descripcion, "N/A") as marca'),
                    DB::raw('COALESCE(mo.modelo_descripcion, "N/A") as modelo'),
                    DB::raw('COALESCE(cal.calibre_nombre, "N/A") as calibre'),
                    DB::raw('CONCAT(
                        COALESCE(cl.cliente_nombre1, ""), " ", 
                        COALESCE(cl.cliente_nombre2, ""), " ",
                        COALESCE(cl.cliente_apellido1, ""), " ",
                        COALESCE(cl.cliente_apellido2, "")
                    ) as comprador'),
                    'v.ven_id as autorizacion',
                    'v.ven_fecha as fecha',
                    DB::raw('COALESCE(f.fac_numero, "PENDIENTE") as factura')
                ])
                ->whereYear('v.ven_fecha', $anio)
                ->whereMonth('v.ven_fecha', $mes)
                ->whereIn('v.ven_situacion', ['COMPLETADA', 'FACTURADA'])
                ->where(function ($query) {
                    $query->where('c.categoria_nombre', 'LIKE', '%ARMA%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%PISTOLA%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%REVOLVER%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%CARABINA%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%RIFLE%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%ESCOPETA%');
                })
                ->orderBy('v.ven_fecha')
                ->get();

            // ✅ FIX FINAL: No expandir por cantidad, la consulta ya trae una fila por serie
            $ventasExpandidas = $ventas->map(function($venta) {
                return (object)[
                    'pro_tenencia_anterior' => $venta->pro_tenencia_anterior,
                    'pro_tenencia_nueva' => $venta->pro_tenencia_nueva,
                    'tipo' => $venta->tipo,
                    'serie' => $venta->serie,
                    'marca' => $venta->marca,
                    'modelo' => $venta->modelo,
                    'calibre' => $venta->calibre,
                    'comprador' => trim($venta->comprador),
                    'autorizacion' => $venta->autorizacion,
                    'fecha' => $venta->fecha,
                    'factura' => $venta->factura
                ];
            });

            \Log::info('✅ Reporte DIGECAM Armas generado:', [
                'total_registros' => $ventasExpandidas->count(),
                'muestra' => $ventasExpandidas->take(3)
            ]);

            return response()->json([
                'success' => true,
                'data' => $ventasExpandidas->values()->all(),
                'mes' => (int)$mes,
                'anio' => (int)$anio,
                'mes_nombre' => $this->getNombreMes($mes)
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error en getReporteDigecamArmas:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reporte de armas: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Reporte DIGECAM - Ventas de Municiones
     */
    public function getReporteDigecamMuniciones(Request $request): JsonResponse
    {
        try {
            $fechaInicio = $request->filled('fecha_inicio')
                ? Carbon::parse($request->fecha_inicio)->startOfDay()
                : now()->startOfMonth();

            $fechaFin = $request->filled('fecha_fin')
                ? Carbon::parse($request->fecha_fin)->endOfDay()
                : now()->endOfMonth();

            \Log::info('📊 Generando reporte DIGECAM Municiones:', [
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString()
            ]);

            $ventas = DB::table('pro_detalle_ventas as dv')
                ->join('pro_ventas as v', 'dv.det_ven_id', '=', 'v.ven_id')
                ->join('pro_productos as p', 'dv.det_producto_id', '=', 'p.producto_id')
                ->join('pro_categorias as c', 'p.producto_categoria_id', '=', 'c.categoria_id')
                ->leftJoin('pro_clientes as cl', 'v.ven_cliente', '=', 'cl.cliente_id')
                ->leftJoin('pro_calibres as cal', 'p.producto_calibre_id', '=', 'cal.calibre_id')
                ->leftJoin('facturacion as f', 'v.ven_id', '=', 'f.fac_venta_id') // ✅ JOIN FACTURACION
                ->select([
                    'v.ven_id as autorizacion',
                    DB::raw('CASE 
                    WHEN cl.cliente_dpi IS NOT NULL THEN "DPI"
                    ELSE "DOCUMENTO"
                END as documento'),
                    DB::raw('CONCAT(
                    COALESCE(cl.cliente_nombre1, ""), " ",
                    COALESCE(cl.cliente_nombre2, ""), " ",
                    COALESCE(cl.cliente_apellido1, ""), " ",
                    COALESCE(cl.cliente_apellido2, "")
                ) as nombre'),
                    DB::raw('COALESCE(f.fac_numero, "PENDIENTE") as factura'), // ✅ MOSTRAR FACTURA REAL
                    'v.ven_fecha as fecha',
                    DB::raw('"N/A" as serie_arma'),
                    DB::raw('"PISTOLA" as clase_arma'),
                    DB::raw('COALESCE(cal.calibre_nombre, "N/A") as calibre_arma'),
                    DB::raw('COALESCE(cal.calibre_nombre, "N/A") as calibre_vendido'),
                    'dv.det_cantidad as cantidad'
                ])
                ->whereBetween('v.ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('v.ven_situacion', ['ACTIVA', 'AUTORIZADA', 1, '1', 'COMPLETADA', 'FACTURADA']) // ✅ FIX: Incluir todos los estados válidos
                ->whereIn('dv.det_situacion', ['ACTIVO', 'AUTORIZADA', 1, '1'])
                ->where(function ($query) {
                    $query->where('c.categoria_nombre', 'LIKE', '%MUNICION%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%MUNICIÓN%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%CARTUCHO%')
                        ->orWhere('c.categoria_nombre', 'LIKE', '%BALA%')
                        ->orWhere('p.producto_nombre', 'LIKE', '%MUNICION%')
                        ->orWhere('p.producto_nombre', 'LIKE', '%CARTUCHO%');
                })
                ->orderBy('v.ven_fecha')
                ->get();

            \Log::info('✅ Reporte DIGECAM Municiones generado:', [
                'total_registros' => $ventas->count(),
                'total_municiones' => $ventas->sum('cantidad')
            ]);

            return response()->json([
                'success' => true,
                'data' => $ventas,
                'fecha_inicio' => $fechaInicio->toDateString(),
                'fecha_fin' => $fechaFin->toDateString()
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error en getReporteDigecamMuniciones:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reporte de municiones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reportes DIGECAM a PDF
     */
    public function exportarDigecamPDF(Request $request)
    {
        try {
            $tipo = $request->get('tipo');

            \Log::info('📄 Exportando PDF DIGECAM:', [
                'tipo' => $tipo,
                'params' => $request->all()
            ]);

            if ($tipo === 'armas') {
                // Crear un nuevo request con los parámetros
                $dataRequest = new Request([
                    'mes' => $request->get('mes'),
                    'anio' => $request->get('anio')
                ]);

                $response = $this->getReporteDigecamArmas($dataRequest);
                $data = $response->getData(true);

                if (!$data['success']) {
                    throw new \Exception($data['message'] ?? 'Error obteniendo datos');
                }

                // ✅ CONVERTIR ARRAYS A OBJETOS
                $dataParaPdf = collect($data['data'])->map(function ($item) {
                    return is_array($item) ? (object)$item : $item;
                })->all();

                $pdf = Pdf::loadView('reportes.pdf.digecam-armas', [
                    'data' => $dataParaPdf, // ✅ Usar data convertida
                    'mes' => $data['mes_nombre'],
                    'anio' => $data['anio'],
                    'operador' => auth()->user()->user_primer_nombre . ' ' . auth()->user()->user_primer_apellido,
                    'empresa' => 'PRO ARMAS',
                    'fecha_generacion' => now()->format('d/m/Y H:i:s')
                ]);

                $filename = "digecam_armas_{$data['mes']}_{$data['anio']}.pdf";
            } else {
                // Crear un nuevo request con los parámetros
                $dataRequest = new Request([
                    'fecha_inicio' => $request->get('fecha_inicio'),
                    'fecha_fin' => $request->get('fecha_fin')
                ]);

                $response = $this->getReporteDigecamMuniciones($dataRequest);
                $data = $response->getData(true);

                if (!$data['success']) {
                    throw new \Exception($data['message'] ?? 'Error obteniendo datos');
                }

                // ✅ CONVERTIR ARRAYS A OBJETOS
                $dataParaPdf = collect($data['data'])->map(function ($item) {
                    return is_array($item) ? (object)$item : $item;
                })->all();

                $pdf = Pdf::loadView('reportes.pdf.digecam-municiones', [
                    'data' => $dataParaPdf, // ✅ Usar data convertida
                    'fecha_inicio' => $data['fecha_inicio'],
                    'fecha_fin' => $data['fecha_fin'],
                    'empresa' => 'PRO ARMAS',
                    'fecha_generacion' => now()->format('d/m/Y H:i:s')
                ]);

                $filename = "digecam_municiones_" . date('Y-m-d') . ".pdf";
            }

            $pdf->setPaper('letter', 'landscape'); // ✅ CAMBIAR A HORIZONTAL
            $pdf->setOption('defaultFont', 'sans-serif');

            \Log::info('✅ PDF generado exitosamente:', ['filename' => $filename]);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('❌ Error generando PDF DIGECAM:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Helper: Obtener nombre del mes
     */
    private function getNombreMes($mes)
    {
        $meses = [
            1 => 'ENERO',
            2 => 'FEBRERO',
            3 => 'MARZO',
            4 => 'ABRIL',
            5 => 'MAYO',
            6 => 'JUNIO',
            7 => 'JULIO',
            8 => 'AGOSTO',
            9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE'
        ];

        return $meses[$mes] ?? 'DESCONOCIDO';
    }


    /**
     * Imprimir comprobante de venta individual
     */
    public function imprimirVenta($id)
    {
        try {
            $venta = ProVenta::with([
                'cliente',
                'vendedor',
                'detalleVentas.producto',
                'pagos'
            ])->findOrFail($id);

            // Calcular estado de pago
            $totalPagado = $venta->pagos->sum('pago_monto_abonado');
            $totalVenta = $venta->ven_total_vendido;

            if ($totalPagado == 0) {
                $venta->estado_pago = 'PENDIENTE';
            } elseif ($totalPagado >= $totalVenta) {
                $venta->estado_pago = 'COMPLETADO';
            } else {
                $venta->estado_pago = 'PARCIAL';
            }

            // Obtener series de los movimientos asociados a esta venta
            $movimientosSeries = DB::table('pro_movimientos')
                ->join('pro_series_productos', 'pro_movimientos.mov_serie_id', '=', 'pro_series_productos.serie_id')
                ->where('mov_documento_referencia', "VENTA-{$venta->ven_id}")
                ->whereNotNull('mov_serie_id')
                ->select('mov_producto_id', 'serie_numero_serie')
                ->get()
                ->groupBy('mov_producto_id');

            // Adjuntar series a los detalles
            foreach ($venta->detalleVentas as $detalle) {
                $detalle->series = $movimientosSeries->get($detalle->det_producto_id, collect())->pluck('serie_numero_serie')->toArray();
            }

            $pdf = Pdf::loadView('reportes.pdf.comprobante_venta', compact('venta'));
            
            return $pdf->stream("comprobante_venta_{$venta->ven_id}.pdf");

        } catch (\Exception $e) {
            \Log::error('Error al imprimir venta: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el comprobante: ' . $e->getMessage());
        }
    }

    /**
     * Obtener detalle de venta para modal (JSON)
     */
    public function getDetalleVenta($id)
    {
        try {
            $venta = ProVenta::withTrashed()->with([
                'cliente',
                'vendedor',
                'detalleVentas.producto',
                'pagos'
            ])->findOrFail($id);

            // Calcular estado de pago
            $totalPagado = $venta->pagos->sum('pago_monto_pagado');
            $totalVenta = $venta->ven_total_vendido;

            if ($totalPagado == 0) {
                $venta->estado_pago = 'PENDIENTE';
            } elseif ($totalPagado >= $totalVenta - 0.01) {
                $venta->estado_pago = 'COMPLETADO';
            } else {
                $venta->estado_pago = 'PARCIAL';
            }

            // Formatear pagos para mostrar estado
            foreach ($venta->pagos as $pago) {
                $pago->pago_estado = 'COMPLETADO'; 
            }
            
            // Obtener series
            $movimientosSeries = DB::table('pro_movimientos')
                ->join('pro_series_productos', 'pro_movimientos.mov_serie_id', '=', 'pro_series_productos.serie_id')
                ->where('mov_documento_referencia', "VENTA-{$venta->ven_id}")
                ->whereNotNull('mov_serie_id')
                ->select('mov_producto_id', 'serie_numero_serie')
                ->get()
                ->groupBy('mov_producto_id');

            // Adjuntar series a los detalles
            foreach ($venta->detalleVentas as $detalle) {
                $detalle->series = $movimientosSeries->get($detalle->det_producto_id, collect())->pluck('serie_numero_serie')->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => $venta
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener detalle de venta: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el detalle de la venta: ' . $e->getMessage()
            ], 500);
        }
    }
}
