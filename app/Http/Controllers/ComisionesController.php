<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProPorcentajeVendedor;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ComisionesController extends Controller
{
    public function index(Request $request)
    {
        // Obtener el usuario logueado
        $usuarioLogueado = Auth::user();
        
        // Obtener solo vendedores que han realizado ventas (tienen comisiones)
        $vendedores = User::with('rol')
            ->whereHas('comisiones.venta', function($query) {
                $query->whereIn('ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA']);
            })
            ->whereHas('comisiones', function($query) {
                $query->where('porc_vend_situacion', 'ACTIVO');
            })
            ->where('user_situacion', 1)
            ->select('user_id', 'user_primer_nombre', 'user_primer_apellido')
            ->distinct()
            ->orderBy('user_primer_nombre');

        // Si es vendedor, solo mostrarse a sí mismo
        if ($usuarioLogueado->rol && strtolower($usuarioLogueado->rol->nombre) === 'vendedor') {
            $vendedores->where('user_id', $usuarioLogueado->id);
        }

        $vendedores = $vendedores->get();

        return view('comisiones.index', compact('vendedores', 'usuarioLogueado'));
    }

    public function search(Request $request)
    {
        try {
            $vendedor_id = $request->get('vendedor_id');
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin = $request->get('fecha_fin');
            $estado = $request->get('estado');

            $query = ProPorcentajeVendedor::with(['vendedor', 'venta'])
                ->where('porc_vend_situacion', 'ACTIVO');

            // Solo filtrar por vendedor si se especifica uno
            if ($vendedor_id) {
                $query->where('porc_vend_user_id', $vendedor_id);
            }

            // Enforce filter for sellers
            if (auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                $query->where('porc_vend_user_id', auth()->id());
            }

            if ($fecha_inicio) {
                $query->whereHas('venta', function($q) use ($fecha_inicio) {
                    $q->where('ven_fecha', '>=', $fecha_inicio);
                });
            }

            if ($fecha_fin) {
                $query->whereHas('venta', function($q) use ($fecha_fin) {
                    $q->where('ven_fecha', '<=', $fecha_fin);
                });
            }

            if ($estado) {
                $query->where('porc_vend_estado', $estado);
            }

            $comisiones = $query->orderBy('porc_vend_fecha_asignacion', 'desc')->get();

            $datos = $comisiones->map(function ($comision) {
                // Calcular días correctamente
                if ($comision->porc_vend_estado == 'PAGADO' && $comision->porc_vend_fecha_pago) {
                    $diasTranscurridos = $comision->porc_vend_fecha_asignacion->diffInDays($comision->porc_vend_fecha_pago);
                } else {
                    $diasTranscurridos = $comision->porc_vend_fecha_asignacion->diffInDays(now());
                }

                return [
                    'id' => $comision->porc_vend_id,
                    'vendedor_id' => $comision->porc_vend_user_id,
                    'vendedor_nombre' => $comision->vendedor 
                        ? trim($comision->vendedor->user_primer_nombre . ' ' . $comision->vendedor->user_primer_apellido)
                        : 'N/A',
                    'venta_id' => $comision->porc_vend_ven_id,
                    'fecha_venta' => $comision->venta ? $comision->venta->ven_fecha->format('Y-m-d') : null,
                    'monto_venta' => $comision->venta ? (float)$comision->venta->ven_total_vendido : 0,
                    'porcentaje_comision' => (float)$comision->porc_vend_porcentaje,
                    'ganancia_calculada' => (float)$comision->porc_vend_cantidad_ganancia,
                    'estado_pago' => $comision->porc_vend_estado,
                    'fecha_asignacion' => $comision->porc_vend_fecha_asignacion->format('Y-m-d'),
                    'fecha_pago' => $comision->porc_vend_fecha_pago ? $comision->porc_vend_fecha_pago->format('Y-m-d') : null,
                    'observaciones' => $comision->porc_vend_observaciones,
                    'dias_transcurridos' => $diasTranscurridos,
                    'ya_pagado' => $comision->porc_vend_estado == 'PAGADO' ? 'SI' : 'NO'
                ];
            });

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Comisiones encontradas',
                'datos' => $datos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error obteniendo comisiones',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function getResumen(Request $request)
    {
        try {
            $vendedor_id = $request->get('vendedor_id');
            $fecha_inicio = $request->get('fecha_inicio');
            $fecha_fin = $request->get('fecha_fin');

            $query = DB::table('pro_porcentaje_vendedor as pv')
                ->join('users as u', 'pv.porc_vend_user_id', '=', 'u.user_id')
                ->join('pro_ventas as v', 'pv.porc_vend_ven_id', '=', 'v.ven_id')
                ->where('pv.porc_vend_situacion', 'ACTIVO')
                ->whereIn('v.ven_situacion', ['ACTIVA', 'COMPLETADA', 'FACTURADA']);

            // Filtros
            if ($vendedor_id) {
                $query->where('pv.porc_vend_user_id', $vendedor_id);
            }

            // Enforce filter for sellers
            if (auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                $query->where('pv.porc_vend_user_id', auth()->id());
            }

            if ($fecha_inicio) {
                $query->where('v.ven_fecha', '>=', $fecha_inicio);
            }

            if ($fecha_fin) {
                $query->where('v.ven_fecha', '<=', $fecha_fin);
            }

            // Realizar la consulta con agregados
            $resumen = $query->select([
                'pv.porc_vend_user_id as vendedor_id',
                DB::raw("CONCAT(u.user_primer_nombre, ' ', u.user_primer_apellido) as nombre_vendedor"),
                DB::raw('COUNT(*) as total_ventas'),
                DB::raw('SUM(v.ven_total_vendido) as total_vendido'),
                DB::raw('SUM(pv.porc_vend_cantidad_ganancia) as total_comisiones'),
                DB::raw('AVG(pv.porc_vend_porcentaje) as porcentaje_promedio'),
                DB::raw("SUM(CASE WHEN pv.porc_vend_estado = 'PAGADO' THEN 1 ELSE 0 END) as ventas_pagadas"),
                DB::raw("SUM(CASE WHEN pv.porc_vend_estado = 'PENDIENTE' THEN 1 ELSE 0 END) as ventas_pendientes"),
                DB::raw("SUM(CASE WHEN pv.porc_vend_estado = 'PAGADO' THEN pv.porc_vend_cantidad_ganancia ELSE 0 END) as comisiones_pagadas"),
                DB::raw("SUM(CASE WHEN pv.porc_vend_estado = 'PENDIENTE' THEN pv.porc_vend_cantidad_ganancia ELSE 0 END) as comisiones_pendientes")
            ])
            ->groupBy('pv.porc_vend_user_id', 'u.user_primer_nombre', 'u.user_primer_apellido')
            ->orderBy('total_comisiones', 'desc')
            ->get();

            // Calcular totales generales
            $totalesGenerales = [
                'total_vendedores' => $resumen->count(),
                'total_ventas_general' => $resumen->sum('total_ventas'),
                'total_vendido_general' => $resumen->sum('total_vendido'),
                'total_comisiones_general' => $resumen->sum('total_comisiones'),
                'total_comisiones_pagadas_general' => $resumen->sum('comisiones_pagadas'),
                'total_comisiones_pendientes_general' => $resumen->sum('comisiones_pendientes'),
            ];

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Resumen obtenido',
                'datos' => $resumen,
                'totales' => $totalesGenerales
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error obteniendo resumen',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $id = $request->input('id');
            $estado = $request->input('estado', 'PAGADO');
            $comision = ProPorcentajeVendedor::findOrFail($id);
            
            if ($estado === 'PAGADO') {
                $comision->porc_vend_estado = 'PAGADO';
                $comision->porc_vend_fecha_pago = now()->toDateString();
                $comision->porc_vend_observaciones = $request->input('observaciones', $comision->porc_vend_observaciones);
                $mensaje = 'Comisión marcada como pagada exitosamente';
            } else if ($estado === 'CANCELADO') {
                $comision->porc_vend_estado = 'CANCELADO';
                $comision->porc_vend_observaciones = 'Comisión cancelada el ' . now()->format('Y-m-d H:i:s');
                $mensaje = 'Comisión cancelada exitosamente';
            }
            
            $comision->save();

            return response()->json([
                'codigo' => 1,
                'mensaje' => $mensaje
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error actualizando comisión',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelar(Request $request)
    {
        try {
            $id = $request->input('id');
            $comision = ProPorcentajeVendedor::findOrFail($id);
            
            $comision->porc_vend_estado = 'CANCELADO';
            $comision->porc_vend_observaciones = 'Comisión cancelada el ' . now()->format('Y-m-d H:i:s');
            $comision->save();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Comisión cancelada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error cancelando comisión',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
}