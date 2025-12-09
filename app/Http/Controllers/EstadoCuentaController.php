<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Clientes;

class EstadoCuentaController extends Controller
{
    public function index()
    {
        return view('clientes.estado_cuenta');
    }

    public function listar(Request $request)
    {
        // Obtener todos los clientes activos
        $clientes = DB::table('pro_clientes as c')
            ->leftJoin('pro_clientes_empresas as e', 'c.cliente_id', '=', 'e.emp_cliente_id')
            ->leftJoin('pro_clientes_saldo as s', 'c.cliente_id', '=', 's.saldo_cliente_id')
            ->select(
                'c.cliente_id',
                'c.cliente_nombre1',
                'c.cliente_apellido1',
                'c.cliente_nom_empresa',
                'e.emp_nombre',
                's.saldo_monto as saldo_favor'
            )
            ->where('c.cliente_situacion', 1)
            ->get();

        // Calcular deudas y pagos pendientes para cada cliente
        // Esto podría optimizarse con subqueries, pero por claridad lo haremos iterando o con queries separados agrupados
        
        // 1. Deudas manuales (pro_deudas_clientes)
        $deudas = DB::table('pro_deudas_clientes')
            ->select('cliente_id', DB::raw('SUM(saldo_pendiente) as total_deuda'))
            ->where('estado', 'PENDIENTE')
            ->groupBy('cliente_id')
            ->pluck('total_deuda', 'cliente_id');

        // 2. Pagos pendientes de ventas (pro_pagos)
        // Necesitamos unir con ventas para saber el cliente
        $pagosPendientes = DB::table('pro_pagos as p')
            ->join('pro_ventas as v', 'p.pago_venta_id', '=', 'v.ven_id')
            ->select('v.ven_cliente', DB::raw('SUM(p.pago_monto_pendiente) as total_pendiente'))
            ->whereIn('p.pago_estado', ['PENDIENTE', 'PARCIAL', 'VENCIDO'])
            ->where('v.ven_situacion', 'ACTIVA')
            ->groupBy('v.ven_cliente')
            ->pluck('total_pendiente', 'ven_cliente');

        // Combinar datos
        $resultado = $clientes->map(function ($c) use ($deudas, $pagosPendientes) {
            $c->saldo_favor = floatval($c->saldo_favor ?? 0);
            $c->total_deuda = floatval($deudas[$c->cliente_id] ?? 0);
            $c->total_pendiente = floatval($pagosPendientes[$c->cliente_id] ?? 0);
            
            // Nombre completo concatenado
            $nombre = "{$c->cliente_nombre1} {$c->cliente_apellido1}";
            if ($c->emp_nombre) {
                $nombre .= " - {$c->emp_nombre}";
            } elseif ($c->cliente_nom_empresa) {
                $nombre .= " - {$c->cliente_nom_empresa}";
            }
            $c->nombre_completo = $nombre;

            return $c;
        });

        // Filtrar solo los que tienen algún movimiento (opcional, pero el usuario pidió ver historial)
        // Si el usuario quiere ver TODOS, quitamos el filtro.
        // Pero para "Estado de Cuenta", generalmente interesa quien debe o tiene saldo.
        // El usuario dijo "vista de los clientes que tengan saldo a favor, y deudas, y pagos pendientes".
        $resultado = $resultado->filter(function ($c) {
            return $c->saldo_favor > 0 || $c->total_deuda > 0 || $c->total_pendiente > 0;
        })->values();

        return response()->json(['data' => $resultado]);
    }

    public function detalle(Request $request, $id)
    {
        try {
            // 1. Historial de Saldo a Favor
            $historialSaldo = DB::table('pro_clientes_saldo_historial')
                ->where('hist_cliente_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            // 2. Deudas Manuales
            $deudas = DB::table('pro_deudas_clientes')
                ->where('cliente_id', $id)
                ->orderBy('fecha_deuda', 'desc')
                ->get();

            // 3. Ventas al Crédito (Pagos Pendientes)
            $ventasCredito = DB::table('pro_pagos as p')
                ->join('pro_ventas as v', 'p.pago_venta_id', '=', 'v.ven_id')
                ->where('v.ven_cliente', $id)
                ->whereIn('p.pago_estado', ['PENDIENTE', 'PARCIAL', 'VENCIDO'])
                ->select(
                    'v.ven_id',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'p.pago_monto_pendiente',
                    'p.pago_estado',
                    'p.pago_fecha_inicio',
                    'p.pago_fecha_completado'
                )
                ->orderBy('v.ven_fecha', 'desc')
                ->get();

            // Agregar detalles de productos a cada venta
            foreach ($ventasCredito as $venta) {
                $venta->productos = DB::table('pro_detalle_ventas as dv')
                    ->join('pro_productos as prod', 'dv.det_producto_id', '=', 'prod.producto_id')
                    ->where('dv.det_ven_id', $venta->ven_id)
                    ->select('prod.producto_nombre', 'dv.det_cantidad', 'dv.det_precio')
                    ->get();
            }

            return response()->json([
                'historial_saldo' => $historialSaldo,
                'deudas' => $deudas,
                'ventas_credito' => $ventasCredito
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en EstadoCuentaController@detalle: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado de cuenta: ' . $e->getMessage()
            ], 500);
        }
    }
}
