<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * DashboardController - VERSIÓN DEFINITIVA
 * Con estructura EXACTA de tus tablas de stock y ventas
 */
class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function getEstadisticas(Request $request)
    {
        try {
            $mesActual = date('m');
            $anioActual = date('Y');

            // Total de productos activos
            $totalArmas = DB::table('pro_productos')
                ->where('producto_situacion', 1)
                ->count();

            // Ventas del mes actual
            $ventasMes = DB::table('pro_ventas')
                ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA'])
                ->whereRaw('MONTH(ven_fecha) = ?', [$mesActual])
                ->whereRaw('YEAR(ven_fecha) = ?', [$anioActual])
                ->count();

            // Total clientes activos
            $totalClientes = DB::table('pro_clientes')
                ->where('cliente_situacion', 1)
                ->count();

            // Licencias activas y vigentes
            $licenciasActivas = DB::table('pro_licencias_para_importacion')
                ->where('lipaimp_situacion', 1)
                ->where(function($query) {
                    $query->whereNull('lipaimp_fecha_vencimiento')
                          ->orWhereDate('lipaimp_fecha_vencimiento', '>=', now());
                })
                ->count();

            // Ventas recientes
            $ventasRecientes = $this->obtenerVentasRecientes();

            // Productos con stock bajo
            $productosStockBajo = $this->obtenerProductosStockBajo();

            return response()->json([
                'success' => true,
                'data' => [
                    'estadisticas' => [
                        'total_armas' => (int)$totalArmas,
                        'ventas_mes' => (int)$ventasMes,
                        'total_clientes' => (int)$totalClientes,
                        'licencias_activas' => (int)$licenciasActivas
                    ],
                    'ventas_recientes' => $ventasRecientes,
                    'productos_stock_bajo' => $productosStockBajo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getEstadisticas: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar estadísticas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno'
            ], 500);
        }
    }

    /**
     * Obtener ventas recientes (últimas 5)
     */
    private function obtenerVentasRecientes()
    {
        try {
            // DEBUG: Contar ventas totales
            $totalVentas = DB::table('pro_ventas')->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA'])->count();
            Log::info("Dashboard - Total ventas activas: {$totalVentas}");

            $ventas = DB::table('pro_ventas as v')
                ->leftJoin('pro_clientes as c', 'v.ven_cliente', '=', 'c.cliente_id')
                ->leftJoin('users as u', 'v.ven_user', '=', 'u.user_id')
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA'])
                ->orderBy('v.ven_fecha', 'desc')
                ->limit(5)
                ->select([
                    'v.ven_id',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'v.ven_cliente',
                    'v.ven_user',
                    DB::raw('IFNULL(c.cliente_nombre1, "Cliente") as cliente_nombre'),
                    DB::raw('IFNULL(c.cliente_apellido1, "General") as cliente_apellido'),
                    DB::raw('IFNULL(u.user_primer_nombre, "Sin") as vendedor_nombre'),
                    DB::raw('IFNULL(u.user_primer_apellido, "Vendedor") as vendedor_apellido')
                ])
                ->get();

            // DEBUG: Log de ventas encontradas
            Log::info("Dashboard - Ventas encontradas: " . $ventas->count());
            if ($ventas->count() > 0) {
                Log::info("Dashboard - Primera venta: ", [
                    'id' => $ventas->first()->ven_id,
                    'fecha' => $ventas->first()->ven_fecha,
                    'cliente_id' => $ventas->first()->ven_cliente,
                    'user_id' => $ventas->first()->ven_user
                ]);
            }

            if ($ventas->isEmpty()) {
                Log::warning("Dashboard - No se encontraron ventas recientes");
                return [];
            }

            $resultado = [];
            foreach ($ventas as $venta) {
                // Contar items de la venta (COLUMNA CORRECTA: det_ven_id)
                $items = DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venta->ven_id)
                    ->where('det_situacion', 'ACTIVO')
                    ->sum('det_cantidad');
                
                $resultado[] = [
                    'id' => $venta->ven_id,
                    'fecha' => $venta->ven_fecha,
                    'cliente' => trim($venta->cliente_nombre . ' ' . $venta->cliente_apellido),
                    'vendedor' => trim($venta->vendedor_nombre . ' ' . $venta->vendedor_apellido),
                    'total' => number_format((float)$venta->ven_total_vendido, 2),
                    'items' => (int)$items,
                    'estado' => 'COMPLETADA' // Por defecto, ya que no hay columna ven_estado_venta
                ];
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Error en obtenerVentasRecientes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener productos con stock bajo
     * Basado en tu estructura: pro_stock_actual con stock_cantidad_disponible
     */
    private function obtenerProductosStockBajo()
    {
        try {
            // Productos con stock bajo según tu tabla pro_stock_actual
            $productos = DB::table('pro_stock_actual as s')
                ->join('pro_productos as p', 's.stock_producto_id', '=', 'p.producto_id')
                ->where('p.producto_situacion', 1)
                ->where('p.producto_requiere_stock', 1) // Solo productos que requieren control de stock
                ->whereRaw('s.stock_cantidad_disponible <= p.producto_stock_minimo')
                ->orderBy('s.stock_cantidad_disponible', 'asc')
                ->limit(10)
                ->select([
                    'p.producto_id',
                    'p.producto_nombre',
                    'p.pro_codigo_sku as codigo',
                    'p.producto_stock_minimo',
                    's.stock_cantidad_disponible',
                    's.stock_cantidad_total',
                    's.stock_cantidad_reservada'
                ])
                ->get();

            if ($productos->isEmpty()) {
                return [];
            }

            $resultado = [];
            foreach ($productos as $producto) {
                $stockDisponible = (int)$producto->stock_cantidad_disponible;
                $stockMinimo = (int)$producto->producto_stock_minimo;
                $diferencia = max(0, $stockMinimo - $stockDisponible);
                
                $resultado[] = [
                    'producto_id' => $producto->producto_id,
                    'nombre' => $producto->producto_nombre ?? 'Sin nombre',
                    'codigo' => $producto->codigo ?? 'N/A',
                    'stock_actual' => $stockDisponible,
                    'stock_minimo' => $stockMinimo,
                    'stock_total' => (int)$producto->stock_cantidad_total,
                    'stock_reservado' => (int)$producto->stock_cantidad_reservada,
                    'diferencia' => $diferencia,
                    'estado' => $stockDisponible == 0 ? 'AGOTADO' : 'BAJO'
                ];
            }

            return $resultado;

        } catch (\Exception $e) {
            Log::error('Error en obtenerProductosStockBajo: ' . $e->getMessage());
            Log::error('Stack: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Obtener resumen de ventas por período
     */
    public function getResumenVentas(Request $request)
    {
        try {
            $periodo = $request->get('periodo', 'mes');

            // Calcular fechas
            $fechaFin = date('Y-m-d 23:59:59');
            
            switch($periodo) {
                case 'semana':
                    $fechaInicio = date('Y-m-d 00:00:00', strtotime('-7 days'));
                    break;
                case 'anio':
                    $fechaInicio = date('Y-01-01 00:00:00');
                    break;
                case 'mes':
                default:
                    $fechaInicio = date('Y-m-01 00:00:00');
                    break;
            }

            $ventas = DB::table('pro_ventas')
                ->whereBetween('ven_fecha', [$fechaInicio, $fechaFin])
                ->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA'])
                ->selectRaw('DATE(ven_fecha) as fecha, COUNT(*) as total, SUM(ven_total_vendido) as monto')
                ->groupBy(DB::raw('DATE(ven_fecha)'))
                ->orderBy('fecha')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ventas,
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getResumenVentas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar resumen'
            ], 500);
        }
    }

    /**
     * Obtener productos más vendidos del mes
     */
    public function getProductosMasVendidos(Request $request)
    {
        try {
            $limite = (int)$request->get('limite', 10);
            $mesActual = date('m');
            $anioActual = date('Y');
            
            $productos = DB::table('pro_detalle_ventas as dv')
                ->join('pro_ventas as v', 'dv.det_ven_id', '=', 'v.ven_id')
                ->join('pro_productos as p', 'dv.det_producto_id', '=', 'p.producto_id')
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA'])
                ->where('dv.det_situacion', 'ACTIVO')
                ->whereRaw('MONTH(v.ven_fecha) = ?', [$mesActual])
                ->whereRaw('YEAR(v.ven_fecha) = ?', [$anioActual])
                ->select([
                    'p.producto_id',
                    'p.producto_nombre',
                    'p.pro_codigo_sku as codigo',
                    DB::raw('SUM(dv.det_cantidad) as total_vendido'),
                    DB::raw('SUM(dv.det_sub_total) as monto_total')
                ])
                ->groupBy('p.producto_id', 'p.producto_nombre', 'p.pro_codigo_sku')
                ->orderBy('total_vendido', 'desc')
                ->limit($limite)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $productos
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getProductosMasVendidos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos'
            ], 500);
        }
    }
}