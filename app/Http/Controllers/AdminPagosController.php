<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CajaSaldo;
use Carbon\Carbon;
use Dotenv\Exception\ValidationException;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminPagosController extends Controller
{
    public function stats(Request $request)
    {
        try {
            $saldos = DB::table('caja_saldos as s')
                ->join('pro_metodos_pago as m', 'm.metpago_id', '=', 's.caja_saldo_metodo_pago')
                ->select(
                    's.caja_saldo_metodo_pago as metodo_id',
                    'm.metpago_descripcion as metodo',
                    's.caja_saldo_moneda',
                    's.caja_saldo_monto_actual',
                    's.caja_saldo_actualizado'
                )
                ->orderBy('m.metpago_descripcion')
                ->get();

            $totalGTQ   = (float) $saldos->where('caja_saldo_moneda', 'GTQ')->sum('caja_saldo_monto_actual');

            // Contar pagos pendientes
            $pagosPendientes = DB::table('pro_pagos_subidos')
                ->whereIn('ps_estado', ['PENDIENTE', 'PENDIENTE_VALIDACION'])
                ->count();

            // Contar movimientos de caja pendientes  
            $movimientosPendientes = DB::table('cja_historial')
                ->where('cja_situacion', 'PENDIENTE')
                ->count();

            $pendientes = $pagosPendientes + $movimientosPendientes;

            $ultimaCarga = DB::table('pro_estados_cuenta')->max('created_at');

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Estadísticas obtenidas exitosamente',
                'data' => [
                    'saldo_total_gtq' => $totalGTQ,
                    'saldos'          => $saldos,
                    'pendientes'      => $pendientes,
                    'ultima_carga'    => $ultimaCarga,
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al obtener las estadísticas',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Bandeja de validación
     * GET /admin/pagos/pendientes
     * =========================== */
    public function pendientes(Request $request)
    {
        try {
            $q      = trim((string) $request->query('q', ''));
            $estado = (string) $request->query('estado', '');

            $rows = DB::table('pro_pagos_subidos as ps')
                ->join('pro_ventas as v', 'v.ven_id', '=', 'ps.ps_venta_id')
                ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
                // tu esquema usa users.user_id en varios FKs
                ->leftJoin('users as u', 'u.user_id', '=', 'ps.ps_cliente_user_id')
                ->leftJoin('pro_clientes as c', 'c.cliente_user_id', '=', 'ps.ps_cliente_user_id')
                ->select([
                    'ps.ps_id',
                    'ps.ps_venta_id',
                    'ps.ps_estado',
                    'ps.ps_referencia',
                    'ps.ps_concepto',
                    'ps.ps_imagen_path',
                    'ps.ps_monto_comprobante',
                    'ps.ps_monto_total_cuotas_front',
                    'ps.ps_cuotas_json',
                    'ps.created_at',

                    'v.ven_id',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'v.ven_observaciones',

                    'pg.pago_id',
                    'pg.pago_monto_total',
                    'pg.pago_monto_pagado',
                    'pg.pago_monto_pendiente',
                    'pg.pago_estado',

                    DB::raw("
                    COALESCE(
                        NULLIF(
                            TRIM(CONCAT_WS(' ',
                                c.cliente_nombre1,
                                c.cliente_nombre2,
                                c.cliente_apellido1,
                                c.cliente_apellido2
                            )),
                            ''
                        ),
                        u.email,
                        CONCAT('Usuario ', ps.ps_cliente_user_id),
                        'Cliente'
                    ) as cliente
                "),
                ])
                ->when($estado !== '', function ($qq) use ($estado) {
                    if ($estado === 'PENDIENTE') {
                        $qq->whereIn('ps.ps_estado', ['PENDIENTE', 'PENDIENTE_VALIDACION']);
                    } else {
                        $qq->where('ps.ps_estado', $estado);
                    }
                })
                ->when($estado === '', fn($qq) => $qq->whereIn('ps.ps_estado', ['PENDIENTE', 'PENDIENTE_VALIDACION']))
                ->when($q !== '', function ($qq) use ($q) {
                    $qq->where(function ($w) use ($q) {
                        $w->where('ps.ps_referencia', 'like', "%{$q}%")
                            ->orWhere('ps.ps_concepto', 'like', "%{$q}%")
                            ->orWhere('v.ven_observaciones', 'like', "%{$q}%")
                            ->orWhere('v.ven_id', 'like', "%{$q}%");
                    });
                })
                ->orderByDesc('ps.created_at')
                ->limit(300)
                ->get();

            // Resumen de items por venta (marca/modelo/producto ...)
            $labelsAgg = DB::table('pro_detalle_ventas as d')
                ->join('pro_productos as p', 'p.producto_id', '=', 'd.det_producto_id')
                ->leftJoin('pro_marcas as ma', 'ma.marca_id', '=', 'p.producto_marca_id')
                ->leftJoin('pro_modelo as mo', 'mo.modelo_id', '=', 'p.producto_modelo_id')
                ->leftJoin('pro_calibres as ca', 'ca.calibre_id', '=', 'p.producto_calibre_id')
                ->whereIn('d.det_ven_id', $rows->pluck('ven_id')->all())
                ->select([
                    'd.det_ven_id',
                    DB::raw("TRIM(CONCAT_WS(' ', ma.marca_descripcion, mo.modelo_descripcion, p.producto_nombre, IFNULL(CONCAT('(', ca.calibre_nombre, ')'), ''))) as label"),
                    DB::raw('SUM(d.det_cantidad) as qty'),
                    DB::raw('MAX(d.det_id) as ord'),
                ])
                ->groupBy('d.det_ven_id', 'label');

            $conceptoSub = DB::query()->fromSub($labelsAgg, 'x')
                ->select([
                    'x.det_ven_id',
                    DB::raw("GROUP_CONCAT(CONCAT(x.qty,' ',x.label) ORDER BY x.ord SEPARATOR ', ') as concepto_resumen"),
                    DB::raw('COUNT(*) as items_count'),
                ])
                ->groupBy('x.det_ven_id')
                ->get()
                ->keyBy('det_ven_id');

            // (Opcional) Agregados de cuotas por venta para “pago n de X”
            $cuotasAgg = DB::table('pro_cuotas')
                ->whereIn('cuota_control_id', $rows->pluck('pago_id')->all())
                ->select([
                    'cuota_control_id',
                    DB::raw('COUNT(*) as cuotas_total'),
                    DB::raw("SUM(CASE WHEN cuota_estado='PENDIENTE' THEN 1 ELSE 0 END) as cuotas_pendientes"),
                    DB::raw('SUM(cuota_monto) as monto_cuotas_total'),
                    DB::raw("SUM(CASE WHEN cuota_estado='PENDIENTE' THEN cuota_monto ELSE 0 END) as monto_cuotas_pendiente"),
                ])
                ->groupBy('cuota_control_id')
                ->get()
                ->keyBy('cuota_control_id');

            $data = $rows->map(function ($r) use ($conceptoSub, $cuotasAgg) {
                $c = $conceptoSub[$r->ven_id] ?? null;

                // Debía para ESTE envío (lo que el cliente seleccionó)
                $debiaEnvio = (float) ($r->ps_monto_total_cuotas_front ?? 0);

                // Pendiente global de la venta (contexto)
                $pendienteVenta = (float) ($r->pago_monto_pendiente
                    ?? max(($r->pago_monto_total ?? 0) - ($r->pago_monto_pagado ?? 0), 0));

                // Qué mostrar en la columna "Debía" de la bandeja:
                $debiaMostrado = $debiaEnvio > 0 ? $debiaEnvio : $pendienteVenta;

                $depositado = (float) ($r->ps_monto_comprobante ?? 0);
                $dif        = $depositado - $debiaMostrado;

                $imagenUrl  = $r->ps_imagen_path
                    ? Storage::disk('public')->url($r->ps_imagen_path)
                    : null;

                // Cuotas seleccionadas en este envío (desde JSON guardado)
                $cuotasSel = 0;
                if (!empty($r->ps_cuotas_json)) {
                    $arr = json_decode($r->ps_cuotas_json, true);
                    $cuotasSel = is_array($arr) ? count($arr) : 0;
                }

                // Agregados de cuotas de la venta (si tienes tabla de cuotas)
                $cuAgg = $cuotasAgg[$r->pago_id] ?? null;

                return [
                    'ps_id'           => (int) $r->ps_id,
                    'venta_id'        => (int) $r->ven_id,
                    'fecha'           => $r->ven_fecha,
                    'cliente'         => $r->cliente,

                    'concepto'        => $c->concepto_resumen ?? '—',
                    'items_count'     => (int) ($c->items_count ?? 0),

                    // Lo que verás en la tabla:
                    'debia'           => round($debiaMostrado, 2),
                    'depositado'      => round($depositado, 2),
                    'diferencia'      => round($dif, 2),

                    // Contexto adicional (por si quieres mostrarlo en tooltip o columnas nuevas)
                    'debia_envio'         => round($debiaEnvio, 2),
                    'pendiente_venta'     => round($pendienteVenta, 2),
                    'venta_total'         => round((float) ($r->ven_total_vendido ?? 0), 2),

                    'estado'          => $r->ps_estado,
                    'referencia'      => $r->ps_referencia,
                    'imagen'          => $imagenUrl,

                    // Cuotas
                    'cuotas_seleccionadas'   => $cuotasSel,
                    'cuotas_total_venta'     => $cuAgg->cuotas_total ?? null,
                    'cuotas_pendientes'      => $cuAgg->cuotas_pendientes ?? null,
                    'monto_cuotas_pendiente' => isset($cuAgg) ? round((float) $cuAgg->monto_cuotas_pendiente, 2) : null,

                    'observaciones_venta' => $r->ven_observaciones,
                    'created_at'       => $r->created_at,
                ];
            })->values();

            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'Pendientes obtenidos exitosamente',
                'data'    => $data
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'Error al obtener los pendientes',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Aprobar pago
     * POST /admin/pagos/aprobar
     * =========================== */
    public function aprobar(Request $request)
    {
        try {
            $data = $request->validate([
                'ps_id'         => ['required', 'integer', 'min:1'],
                'observaciones' => ['nullable', 'string', 'max:255'],
                'metodo_id'     => ['nullable', 'integer', 'min:1'],
            ]);

            $metodoEfectivoId = (int) ($data['metodo_id'] ?? 1);

            $ps = DB::table('pro_pagos_subidos')->where('ps_id', $data['ps_id'])->first();
            if (!$ps) return response()->json(['codigo' => 0, 'mensaje' => 'Registro no encontrado'], 404);

            // Aceptamos PENDIENTE o PENDIENTE_VALIDACION
            if (!in_array($ps->ps_estado, ['PENDIENTE', 'PENDIENTE_VALIDACION'])) {
                return response()->json(['codigo' => 0, 'mensaje' => 'El registro no está pendiente'], 422);
            }

            $venta = DB::table('pro_ventas as v')
                ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
                ->select(
                    'v.ven_id',
                    'pg.pago_id',
                    'pg.pago_monto_total',
                    'pg.pago_monto_pagado'
                )
                ->where('v.ven_id', $ps->ps_venta_id)
                ->first();

            if (!$venta) return response()->json(['codigo' => 0, 'mensaje' => 'Venta asociada no encontrada'], 404);

            // 1) IDs de cuotas desde el JSON del PS (sanitizar + validar que pertenezcan al pago)
            $cuotasIds = json_decode($ps->ps_cuotas_json ?? '[]', true) ?: [];
            $cuotasIds = array_values(array_unique(array_map('intval', $cuotasIds)));

            $validCuotas = [];
            if ($cuotasIds) {
                $validCuotas = DB::table('pro_cuotas')
                    ->where('cuota_control_id', $venta->pago_id)
                    ->whereIn('cuota_id', $cuotasIds)
                    ->pluck('cuota_id')
                    ->all();
            }

            $monto = (float) ($ps->ps_monto_comprobante ?? 0);
            $fecha = $ps->ps_fecha_comprobante ?: now();

            DB::beginTransaction();

            // 2) Detalle de pago (1 registro por comprobante)
            $detId = DB::table('pro_detalle_pagos')->insertGetId([
                'det_pago_pago_id'             => $venta->pago_id,
                'det_pago_cuota_id'            => null,
                'det_pago_fecha'               => $fecha,
                'det_pago_monto'               => $monto,
                'det_pago_metodo_pago'         => $metodoEfectivoId,
                'det_pago_banco_id'            => $ps->ps_banco_id ?? null,
                'det_pago_numero_autorizacion' => $ps->ps_referencia ?? null,
                'det_pago_imagen_boucher'      => $ps->ps_imagen_path ?? null,
                'det_pago_tipo_pago'           => 'PAGO_UNICO',
                'det_pago_estado'              => 'VALIDO',
                'det_pago_observaciones'       => $data['observaciones'] ?? $ps->ps_concepto,
                'det_pago_usuario_registro'    => auth()->id(),
                'created_at'                   => now(),
                'updated_at'                   => now(),
            ]);

            // 3) Master de pagos
            $nuevoPagado    = (float)$venta->pago_monto_pagado + $monto;
            $nuevoPendiente = max((float)$venta->pago_monto_total - $nuevoPagado, 0);
            $nuevoEstado    = $nuevoPendiente <= 0 ? 'COMPLETADO' : 'PARCIAL';

            DB::table('pro_pagos')->where('pago_id', $venta->pago_id)->update([
                'pago_monto_pagado'     => $nuevoPagado,
                'pago_monto_pendiente'  => $nuevoPendiente,
                'pago_estado'           => $nuevoEstado,
                'pago_fecha_completado' => $nuevoPendiente <= 0 ? now() : null,
                'updated_at'            => now(),
            ]);

            // 4) Caja
            DB::table('cja_historial')->insert([
                'cja_tipo'          => 'VENTA',
                'cja_id_venta'      => $venta->ven_id,
                'cja_usuario'       => auth()->id(),
                'cja_monto'         => $monto,
                'cja_fecha'         => now(),
                'cja_metodo_pago'   => $metodoEfectivoId,
                'cja_no_referencia' => $ps->ps_referencia ?? null,
                'cja_situacion'     => 'ACTIVO',
                'cja_observaciones' => 'Aprobación ps#' . $ps->ps_id,
                'created_at'        => now(),
            ]);

            // 5) Saldos
            CajaSaldo::ensureRow($metodoEfectivoId, 'GTQ')->addAmount($monto);

            // 6) Marcar cuotas (si corresponde)
            if (!empty($validCuotas)) {
                DB::table('pro_cuotas')
                    ->whereIn('cuota_id', $validCuotas)
                    ->update([
                        'cuota_estado'     => 'PAGADA',
                        'cuota_fecha_pago' => now(),
                        'updated_at'       => now(),
                    ]);
            }

            // 7) PS -> APROBADO (usando tus columnas reales)
            DB::table('pro_pagos_subidos')->where('ps_id', $ps->ps_id)->update([
                'ps_estado'         => 'APROBADO',
                'ps_notas_revision' => $data['observaciones'] ?? null,
                'ps_revisado_por'   => auth()->id(),
                'ps_revisado_en'    => now(),
                'updated_at'        => now(),
            ]);

            DB::commit();

            // $this->safeDeletePublicPath($ps->ps_imagen_path ?? null);

            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'Pago aprobado exitosamente',
                'data'    => [
                    'det_pago_id'         => $detId,
                    'cuotas_pagadas_ids'  => $validCuotas,
                ]
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al aprobar el pago',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }


    /* ===========================
     * Rechazar pago
     * POST /admin/pagos/rechazar
     * =========================== */
    public function rechazar(Request $request)
    {
        try {
            $data = $request->validate([
                'ps_id'  => ['required', 'integer', 'min:1'],
                'motivo' => ['required', 'string', 'min:5', 'max:255'],
            ]);

            $ps = DB::table('pro_pagos_subidos')->where('ps_id', $data['ps_id'])->first();
            if (!$ps) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Registro no encontrado'
                ], 404);
            }

            if (!in_array($ps->ps_estado, ['PENDIENTE', 'PENDIENTE_VALIDACION'])) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'El registro no está pendiente'
                ], 422);
            }

            DB::table('pro_pagos_subidos')
                ->where('ps_id', $data['ps_id'])
                ->update([
                    'ps_estado'    => 'RECHAZADO',
                    'ps_notas_revision' => $data['motivo'],
                    'ps_revisado_por' => auth()->id(),
                    'ps_revisado_en' => now(),
                    'updated_at'   => now(),
                ]);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Pago rechazado exitosamente'
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al rechazar el pago',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Movimientos de caja
     * GET /admin/pagos/movimientos
     * =========================== */

     public function movimientos(Request $request)
     {
         try {
             $from = $request->query('from') ?: Carbon::now()->startOfMonth()->toDateString();
             $to   = $request->query('to')   ?: Carbon::now()->endOfMonth()->toDateString();
             $metodoId = $request->query('metodo_id');
     
             $q = DB::table('cja_historial as h')
                 ->leftJoin('pro_metodos_pago as m', 'm.metpago_id', '=', 'h.cja_metodo_pago')
                 ->leftJoin('pro_ventas as v', 'v.ven_id', '=', 'h.cja_id_venta')
                 ->leftJoin('pro_clientes as c', 'c.cliente_id', '=', 'v.ven_cliente')
                 ->leftJoin('users as vendedor', 'vendedor.user_id', '=', 'v.ven_user')
                 ->leftJoin('users as usuario_registro', 'usuario_registro.user_id', '=', 'h.cja_usuario')
                 ->select(
                     'h.cja_id',
                     'h.cja_fecha',
                     'h.cja_tipo',
                     'h.cja_no_referencia',
                     'h.cja_observaciones',
                     'h.cja_monto',
                     'h.cja_situacion',
                     'h.cja_id_venta',
                     'm.metpago_descripcion as metodo',
                     
                     // ⭐ Cliente
                     DB::raw("
                         CASE 
                             WHEN c.cliente_tipo = 3 AND c.cliente_nom_empresa IS NOT NULL THEN 
                                 CONCAT(
                                     c.cliente_nom_empresa, 
                                     ' | ', 
                                     TRIM(CONCAT_WS(' ', 
                                         COALESCE(c.cliente_nombre1, ''), 
                                         COALESCE(c.cliente_apellido1, '')
                                     ))
                                 )
                             WHEN c.cliente_id IS NOT NULL THEN 
                                 TRIM(CONCAT_WS(' ', 
                                     COALESCE(c.cliente_nombre1, ''),
                                     COALESCE(c.cliente_nombre2, ''),
                                     COALESCE(c.cliente_apellido1, ''),
                                     COALESCE(c.cliente_apellido2, '')
                                 ))
                             ELSE NULL
                         END as cliente_nombre
                     "),
                     DB::raw("COALESCE(c.cliente_nom_empresa, NULL) as cliente_empresa"),
                     'c.cliente_tipo',
                     'c.cliente_nit',
                     
                     // ⭐ Vendedor
                     DB::raw("
                         TRIM(CONCAT_WS(' ',
                             COALESCE(vendedor.user_primer_nombre, ''),
                             COALESCE(vendedor.user_segundo_nombre, ''),
                             COALESCE(vendedor.user_primer_apellido, ''),
                             COALESCE(vendedor.user_segundo_apellido, '')
                         )) as vendedor_nombre
                     "),
                     'v.ven_user as vendedor_id',
                     
                     // ⭐ Usuario que registró el movimiento
                     DB::raw("
                         TRIM(CONCAT_WS(' ',
                             COALESCE(usuario_registro.user_primer_nombre, ''),
                             COALESCE(usuario_registro.user_segundo_nombre, ''),
                             COALESCE(usuario_registro.user_primer_apellido, ''),
                             COALESCE(usuario_registro.user_segundo_apellido, '')
                         )) as usuario_registro_nombre
                     "),
                     'h.cja_usuario as usuario_registro_id',
                     
                     // ⭐ Total de la venta (si aplica)
                     'v.ven_total_vendido as venta_total'
                 )
                 ->whereDate('h.cja_fecha', '>=', $from)
                 ->whereDate('h.cja_fecha', '<=', $to)
                 ->when($metodoId, fn($qq) => $qq->where('h.cja_metodo_pago', $metodoId))
                 ->orderBy('h.cja_fecha', 'desc');
     
             $rows = $q->get();
     
             // ⭐ Obtener productos vendidos por venta
             $ventaIds = $rows->pluck('cja_id_venta')->filter()->unique()->values()->all();
             
             $productos = [];
             if (!empty($ventaIds)) {
                 $labelsAgg = DB::table('pro_detalle_ventas as d')
                     ->join('pro_productos as p', 'p.producto_id', '=', 'd.det_producto_id')
                     ->leftJoin('pro_marcas as ma', 'ma.marca_id', '=', 'p.producto_marca_id')
                     ->leftJoin('pro_modelo as mo', 'mo.modelo_id', '=', 'p.producto_modelo_id')
                     ->leftJoin('pro_calibres as ca', 'ca.calibre_id', '=', 'p.producto_calibre_id')
                     ->whereIn('d.det_ven_id', $ventaIds)
                     ->select([
                         'd.det_ven_id',
                         DB::raw("
                             TRIM(CONCAT_WS(' ',
                                 ma.marca_descripcion,
                                 mo.modelo_descripcion,
                                 p.producto_nombre,
                                 IF(ca.calibre_nombre IS NULL OR ca.calibre_nombre = '', '', CONCAT('(', ca.calibre_nombre, ')'))
                             )) as label
                         "),
                         DB::raw('SUM(d.det_cantidad) as qty'),
                         DB::raw('MAX(d.det_id) as ord')
                     ])
                     ->groupBy('d.det_ven_id', 'label');
     
                 $conceptoSub = DB::query()->fromSub($labelsAgg, 'x')
                     ->select([
                         'x.det_ven_id',
                         DB::raw("GROUP_CONCAT(CONCAT(x.qty, ' ', x.label) ORDER BY x.ord SEPARATOR ', ') as concepto_resumen"),
                         DB::raw('COUNT(*) as items_count')
                     ])
                     ->groupBy('x.det_ven_id')
                     ->get()
                     ->keyBy('det_ven_id');
     
                 $productos = $conceptoSub;
             }
     
             // ⭐ Enriquecer datos
             $movimientosEnriquecidos = $rows->map(function ($r) use ($productos) {
                 $productoInfo = null;
                 if ($r->cja_id_venta && isset($productos[$r->cja_id_venta])) {
                     $prod = $productos[$r->cja_id_venta];
                     $productoInfo = [
                         'concepto' => $prod->concepto_resumen ?? '—',
                         'items_count' => (int)($prod->items_count ?? 0)
                     ];
                 }
     
                 return [
                     'cja_id'                => $r->cja_id,
                     'cja_fecha'             => $r->cja_fecha,
                     'cja_tipo'              => $r->cja_tipo,
                     'cja_no_referencia'     => $r->cja_no_referencia,
                     'cja_observaciones'     => $r->cja_observaciones,
                     'cja_monto'             => (float)$r->cja_monto,
                     'cja_situacion'         => $r->cja_situacion,
                     'cja_id_venta'          => $r->cja_id_venta,
                     'metodo'                => $r->metodo,
                     
                     // ⭐ NUEVO: Información del cliente
                     'cliente' => $r->cliente_nombre ? [
                         'nombre'   => $r->cliente_nombre,
                         'empresa'  => $r->cliente_empresa,
                         'tipo'     => $r->cliente_tipo,
                         'nit'      => $r->cliente_nit ?? '—',
                     ] : null,
                     
                     // ⭐ NUEVO: Información del vendedor
                     'vendedor' => $r->vendedor_nombre && trim($r->vendedor_nombre) ? [
                         'id'     => $r->vendedor_id,
                         'nombre' => $r->vendedor_nombre,
                     ] : null,
                     
                     // ⭐ NUEVO: Usuario que registró
                     'usuario_registro' => $r->usuario_registro_nombre && trim($r->usuario_registro_nombre) ? [
                         'id'     => $r->usuario_registro_id,
                         'nombre' => $r->usuario_registro_nombre,
                     ] : null,
                     
                     // ⭐ NUEVO: Productos (si es venta)
                     'productos' => $productoInfo,
                     
                     // ⭐ NUEVO: Total venta (si aplica)
                     'venta_total' => $r->venta_total ? (float)$r->venta_total : null,
                 ];
             })->values();
     
             $total = 0.0;
             foreach ($movimientosEnriquecidos as $r) {
                 // Solo sumar movimientos ACTIVOS para el total
                 if ($r['cja_situacion'] === 'ACTIVO') {
                     $total += in_array($r['cja_tipo'], ['VENTA', 'DEPOSITO', 'AJUSTE_POS'])
                         ? (float)$r['cja_monto']
                         : -(float)$r['cja_monto'];
                 }
             }
     
             return response()->json([
                 'codigo' => 1,
                 'mensaje' => 'Movimientos obtenidos exitosamente',
                 'data' => [
                     'movimientos' => $movimientosEnriquecidos,
                     'total' => round($total, 2),
                 ]
             ], 200);
         } catch (Exception $e) {
             \Log::error('Error en movimientos:', [
                 'mensaje' => $e->getMessage(),
                 'linea' => $e->getLine()
             ]);
             
             return response()->json([
                 'codigo' => 0,
                 'mensaje' => 'Error al obtener los movimientos',
                 'detalle' => $e->getMessage()
             ], 500);
         }
     }
    /* ===========================
    * Registrar egreso de caja
    * POST /admin/pagos/egresos
    * =========================== */
    public function registrarEgreso(Request $request)
    {
        try {
            $data = $request->validate([
                'fecha'      => ['nullable', 'date'],
                'monto'      => ['required', 'numeric', 'gt:0'],
                'motivo'     => ['required', 'string', 'max:200'],
                'referencia' => ['nullable', 'string', 'max:100'],
                'archivo'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            ]);

            $path = null;

            if ($request->hasFile('archivo')) {
                $path = $request->file('archivo')->store('egresos', 'public');
            }

            DB::beginTransaction();

            // ✅ AGREGAR VALIDACIÓN DE SALDO
            $metodoId = 1; // Efectivo como valor por defecto
            $cajaSaldo = CajaSaldo::ensureRow($metodoId, 'GTQ');
            $saldoActual = (float) $cajaSaldo->caja_saldo_monto_actual;
            $montoEgreso = (float) $data['monto'];

            // Validar que el egreso no sea mayor al saldo disponible
            if ($montoEgreso > $saldoActual) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Saldo insuficiente',
                    'detalle' => "No hay suficiente saldo en caja. Saldo disponible: Q " . number_format($saldoActual, 2) . ", Egreso solicitado: Q " . number_format($montoEgreso, 2)
                ], 422);
            }

            DB::table('cja_historial')->insert([
                'cja_tipo'          => 'EGRESO', // ✅ Este SÍ existe en el ENUM
                'cja_id_venta'      => null,
                'cja_id_import'     => null,
                'cja_usuario'       => auth()->id(),
                'cja_monto'         => $data['monto'],
                'cja_fecha'         => $data['fecha'] ? Carbon::parse($data['fecha']) : now(),
                'cja_metodo_pago'   => 1, // ← Usar valor por defecto
                'cja_no_referencia' => $data['referencia'] ?? null,
                'cja_situacion'     => 'ACTIVO',
                'cja_observaciones' => $data['motivo'],
                'created_at'        => now(),
            ]);

            // CORREGIDO: Usar valor por defecto para metodo_id
            CajaSaldo::ensureRow(1, 'GTQ')->subtractAmount($data['monto']);

            DB::commit();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Egreso registrado exitosamente',
                'data' => [
                    'archivo' => $path
                ]
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            if ($path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (Exception $__) {
                }
            }
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al registrar el egreso',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Upload/preview estado de cuenta
     * POST /admin/pagos/movs/upload
     * =========================== */
    public function estadoCuentaPreview(Request $request)
    {
        try {
            $request->validate([
                'archivo'  => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
                'banco_id' => ['nullable', 'integer'],
            ]);

            $file = $request->file('archivo');
            $path = $file->store('estados_cuenta/tmp', 'public');

            [$headers, $rows] = $this->parseSheet(storage_path('app/public/' . $path));

            \Log::info('Headers detectados:', $headers);
            \Log::info('Primera fila procesada:', $rows[0] ?? []);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Vista previa generada exitosamente',
                'data' => [
                    'path'    => $path,
                    'headers' => $headers,
                    'rows'    => array_slice($rows, 0, 50),
                ]
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al generar la vista previa',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Procesar estado de cuenta (guardar control)
     * POST /admin/pagos/movs/procesar
     * =========================== */
    public function estadoCuentaProcesar(Request $request)
    {
        try {
            $data = $request->validate([
                'archivo_path' => ['required', 'string'],
                'banco_id'     => ['nullable', 'integer'],
                'fecha_inicio' => ['nullable', 'date'],
                'fecha_fin'    => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            ]);

            $full = storage_path('app/public/' . $data['archivo_path']);
            if (!file_exists($full)) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Archivo no encontrado'
                ], 404);
            }

            [$headers, $rows] = $this->parseSheet($full);

            $ecId = DB::table('pro_estados_cuenta')->insertGetId([
                'ec_banco_id'  => $data['banco_id'] ?? null,
                'ec_archivo'   => $data['archivo_path'],
                'ec_headers'   => json_encode($headers, JSON_UNESCAPED_UNICODE),
                'ec_rows'      => json_encode($rows, JSON_UNESCAPED_UNICODE),
                'ec_fecha_ini' => $data['fecha_inicio'] ?? null,
                'ec_fecha_fin' => $data['fecha_fin'] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Estado de cuenta procesado exitosamente',
                'data' => [
                    'ec_id' => $ecId,
                    'rows_count' => count($rows)
                ]
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al procesar el estado de cuenta',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
     * Utilidades privadas
     * =========================== */

    /**
     * Lee CSV/XLSX y devuelve [headers, rows normalizados].
     * Estandariza: fecha, descripcion, referencia, monto
     */
    private function parseSheet(string $fullPath): array
    {
        setlocale(LC_ALL, 'es_ES.UTF-8', 'es_GT.UTF-8', 'Spanish_Guatemala.1252');
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $normalizeKey = function ($s) {
            $s = mb_strtolower(trim((string)$s), 'UTF-8');
            $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
            $s = preg_replace('/\s+/', ' ', $s);
            return trim($s);
        };
        $rmSpaces = fn($s) => str_replace(' ', '', $s);

        // ==== CSV/TXT ====
        if (in_array($ext, ['csv', 'txt'])) {
            $raw = file_get_contents($fullPath);
            if ($raw === false) throw new \RuntimeException('No se pudo leer el archivo CSV/TXT.');

            // BOM/encoding
            $bom = substr($raw, 0, 3);
            $encoding = null;
            if ($bom === "\xEF\xBB\xBF") {
                $encoding = 'UTF-8';
                $raw = substr($raw, 3);
            } elseif (substr($raw, 0, 2) === "\xFF\xFE") {
                $encoding = 'UTF-16LE';
            } elseif (substr($raw, 0, 2) === "\xFE\xFF") {
                $encoding = 'UTF-16BE';
            }
            if (!$encoding) {
                $enc = mb_detect_encoding($raw, ['UTF-8', 'Windows-1252', 'ISO-8859-1', 'ASCII'], true);
                $encoding = $enc ?: 'UTF-8';
            }
            if ($encoding !== 'UTF-8') $raw = mb_convert_encoding($raw, 'UTF-8', $encoding);

            // Limpiar caracteres corruptos
            $raw = str_replace(['ï¿½', '�'], 'ñ', $raw);

            // delimitador
            $firstLine = strtok($raw, "\n");
            $delims = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
            arsort($delims);
            $delimiter = array_key_first($delims) ?? ',';

            // stream memoria
            $fh = fopen('php://temp', 'r+');
            fwrite($fh, $raw);
            rewind($fh);

            // BUSCAR HEADERS - enfoque específico para este formato bancario
            $headers = [];
            $dataStartLine = 0;
            $lineNumber = 0;

            // Patrones bancarios comunes para detección
            $commonBankPatterns = [
                // Formatos con headers en español
                ['fecha', 'descripción', 'monto', 'referencia'],
                ['fecha', 'concepto', 'importe', 'numero'],
                ['fecha operación', 'descripción', 'débito', 'crédito'],
                ['fecha', 'detalle', 'cargo', 'abono'],

                // Formatos con headers en inglés  
                ['date', 'description', 'amount', 'reference'],
                ['date', 'details', 'debit', 'credit'],
                ['transaction date', 'description', 'withdrawal', 'deposit'],
            ];

            while (($line = fgets($fh)) !== false) {
                $lineNumber++;
                $cleanLine = str_replace(['ï¿½', '�'], 'ñ', $line);
                $row = str_getcsv($cleanLine, $delimiter);
                $cleanRow = array_map(fn($v) => trim((string)$v), $row);

                // Saltar líneas vacías
                if (!array_filter($cleanRow)) continue;

                // DEBUG: Log cada línea para ver qué está procesando
                \Log::info("Línea $lineNumber:", $cleanRow);

                // ESTRATEGIA 1: Buscar específicamente el header bancario con "Fecha" en primera columna
                $firstCell = $cleanRow[0] ?? '';

                // Si esta línea tiene "Fecha" en la primera columna, es el header
                if (strtolower($firstCell) === 'fecha') {
                    $headers = $cleanRow;
                    $dataStartLine = $lineNumber + 1; // Los datos empiezan en la siguiente línea
                    \Log::info("HEADERS ENCONTRADOS en línea $lineNumber", $headers);
                    break;
                }

                // ESTRATEGIA 2: Buscar por combinación de columnas típicas
                $hasFecha = stripos(implode(' ', $cleanRow), 'fecha') !== false;
                $hasDebito = stripos(implode(' ', $cleanRow), 'débito') !== false || stripos(implode(' ', $cleanRow), 'debito') !== false || stripos(implode(' ', $cleanRow), 'dñbito') !== false;
                $hasCredito = stripos(implode(' ', $cleanRow), 'crédito') !== false || stripos(implode(' ', $cleanRow), 'credito') !== false || stripos(implode(' ', $cleanRow), 'crñdito') !== false;
                $hasReferencia = stripos(implode(' ', $cleanRow), 'referencia') !== false;

                if ($hasFecha && ($hasDebito || $hasCredito)) {
                    $headers = $cleanRow;
                    $dataStartLine = $lineNumber + 1;
                    \Log::info("HEADERS ENCONTRADOS por patrones en línea $lineNumber", $headers);
                    break;
                }

                // ESTRATEGIA 3: Verificar si coincide con patrones bancarios comunes
                $cleanRowLower = array_map(fn($v) => mb_strtolower(trim((string)$v), 'UTF-8'), $row);
                foreach ($commonBankPatterns as $pattern) {
                    $matchScore = 0;
                    foreach ($pattern as $expectedHeader) {
                        foreach ($cleanRowLower as $cell) {
                            if (str_contains($cell, $expectedHeader)) {
                                $matchScore++;
                                break;
                            }
                        }
                    }

                    // Si coincide con al menos el 75% de los patrones
                    if ($matchScore >= count($pattern) * 0.75) {
                        $headers = $cleanRow; // Usar la versión original (no en minúsculas)
                        $dataStartLine = $lineNumber + 1;
                        \Log::info("Headers detectados por patrón bancario:", $headers);
                        break 2;
                    }
                }

                // ESTRATEGIA 4: Si encontramos una línea que parece datos (fecha en formato DD/MM/YYYY), retroceder para buscar headers
                if (preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $firstCell)) {
                    \Log::info("DATOS ENCONTRADOS en línea $lineNumber, buscando headers...");

                    // Buscar headers en las 5 líneas anteriores
                    $possibleHeaders = [];
                    $tempPos = ftell($fh); // Guardar posición actual

                    fseek($fh, 0); // Ir al inicio
                    for ($i = 1; $i < $lineNumber; $i++) {
                        $prevLine = fgets($fh);
                        $prevClean = str_replace(['ï¿½', '�'], 'ñ', $prevLine);
                        $prevRow = str_getcsv($prevClean, $delimiter);
                        $prevCleanRow = array_map(fn($v) => trim((string)$v), $prevRow);

                        if (!array_filter($prevCleanRow)) continue;

                        // Verificar si esta línea anterior tiene headers
                        $prevFirst = $prevCleanRow[0] ?? '';
                        if (
                            strtolower($prevFirst) === 'fecha' ||
                            stripos(implode(' ', $prevCleanRow), 'fecha') !== false
                        ) {
                            $possibleHeaders = $prevCleanRow;
                            break;
                        }
                    }

                    if (!empty($possibleHeaders)) {
                        $headers = $possibleHeaders;
                        $dataStartLine = $lineNumber;
                        fseek($fh, $tempPos); // Restaurar posición
                        \Log::info("HEADERS ENCONTRADOS retrocediendo", $headers);
                        break;
                    }

                    fseek($fh, $tempPos); // Restaurar posición si no encontró headers
                }
            }

            // ESTRATEGIA 5: Si no encontró headers específicos, usar la primera línea que tenga "Fecha"
            if (empty($headers)) {
                fseek($fh, 0);
                $lineNumber = 0;

                while (($line = fgets($fh)) !== false) {
                    $lineNumber++;
                    $cleanLine = str_replace(['ï¿½', '�'], 'ñ', $line);
                    $row = str_getcsv($cleanLine, $delimiter);
                    $cleanRow = array_map(fn($v) => trim((string)$v), $row);

                    if (!array_filter($cleanRow)) continue;

                    // Buscar cualquier línea que contenga "Fecha"
                    foreach ($cleanRow as $cell) {
                        if (stripos($cell, 'fecha') !== false) {
                            $headers = $cleanRow;
                            $dataStartLine = $lineNumber + 1;
                            \Log::info("HEADERS ENCONTRADOS por 'fecha' en línea $lineNumber", $headers);
                            break 2;
                        }
                    }
                }
            }

            // ESTRATEGIA 6: Si todavía no hay headers, usar la primera línea no vacía
            if (empty($headers)) {
                fseek($fh, 0);
                while (($line = fgets($fh)) !== false) {
                    $row = str_getcsv($line, $delimiter);
                    $cleanRow = array_map(fn($v) => trim((string)$v), $row);
                    if (array_filter($cleanRow)) {
                        $headers = $cleanRow;
                        $dataStartLine = 2; // Asumir que la siguiente línea son datos
                        \Log::info("HEADERS USANDO primera línea no vacía", $headers);
                        break;
                    }
                }
            }

            // Posicionarse en el inicio de los datos
            fseek($fh, 0);
            for ($i = 1; $i < $dataStartLine; $i++) {
                fgets($fh);
            }

            \Log::info("Headers finales:", $headers);
            \Log::info("Inicio de datos en línea: $dataStartLine");

            // normalizaciones
            $headersNorm  = array_map($normalizeKey, $headers);
            $headersNoSp  = array_map($rmSpaces, $headersNorm);
            $headersCount = count($headersNorm);

            // index de Descripción (para recompactar)
            $ALIAS_DESC = ['descripcion', 'descripci on', 'descripci n', 'detalle', 'concepto', 'narrativa', 'glosa', 'motivo'];
            $descIdx = null;
            foreach ($ALIAS_DESC as $alias) {
                if (($i = array_search($alias, $headersNorm, true)) !== false) {
                    $descIdx = $i;
                    break;
                }
                $aliasNo = $rmSpaces($alias);
                foreach ($headersNorm as $i => $h) if (str_contains($h, $alias)) {
                    $descIdx = $i;
                    break 2;
                }
                if ($descIdx === null) foreach ($headersNoSp as $i => $h) if (str_contains($h, $aliasNo)) {
                    $descIdx = $i;
                    break 2;
                }
            }
            if ($descIdx === null && $headersCount >= 3) $descIdx = 2;

            $fixWidth = function (array $vals) use ($headersCount, $delimiter, $descIdx) {
                $n = count($vals);
                if ($n === $headersCount) return $vals;
                if ($n <  $headersCount)  return array_pad($vals, $headersCount, null);
                if ($descIdx === null)    return array_slice($vals, 0, $headersCount);
                $left  = array_slice($vals, 0, $descIdx + 1);
                $extra = $n - $headersCount;
                $mid   = array_slice($vals, $descIdx + 1, $extra);
                $right = array_slice($vals, $descIdx + 1 + $extra);
                $left[$descIdx] = trim((string)$left[$descIdx] . ($mid ? ($delimiter . implode($delimiter, $mid)) : ''));
                return array_merge($left, $right);
            };

            $rows = [];
            while (($line = fgets($fh)) !== false) {
                $cleanLine = str_replace(['ï¿½', '�'], 'ñ', $line);
                $r = str_getcsv($cleanLine, $delimiter);
                if (!array_filter($r, fn($v) => trim((string)$v) !== '')) continue;

                // Saltar línea "Confidencial" al final
                $firstCell = $r[0] ?? '';
                if (stripos($firstCell, 'confidencial') !== false) continue;

                $vals = $fixWidth(array_values($r));

                $normalized = $this->normalizeRowFlexible($headers, $headersNorm, $headersNoSp, $vals, $normalizeKey);

                if ($normalized['fecha'] || $normalized['monto'] != 0.0 || !empty($normalized['referencia'])) {
                    $rows[] = $normalized;
                }
            }
            fclose($fh);

            \Log::info("Total de filas procesadas:", ['count' => count($rows)]);
            return [$headers, $rows];
        }

        // ==== XLS/XLSX ====
        $reader = IOFactory::createReaderForFile($fullPath);
        $spread = $reader->load($fullPath);
        $sheet  = $spread->getSheet(0);
        $rowsRaw = $sheet->toArray(null, true, true, true);

        $headers = [];
        $dataStartIdx = 0;
        foreach ($rowsRaw as $idx => $row) {
            $first = array_values($row)[0] ?? '';
            if (stripos((string)$first, 'fecha') !== false) {
                $headers = array_values($row);
                $dataStartIdx = $idx + 1;
                break;
            }
        }
        if (empty($headers)) {
            $headers = array_values(array_shift($rowsRaw) ?: []);
            $dataStartIdx = 0;
        }

        $headers = array_map(fn($v) => is_null($v) ? '' : trim((string)$v), $headers);
        $headersNorm = array_map($normalizeKey, $headers);
        $headersNoSp = array_map($rmSpaces, $headersNorm);
        $headersCount = count($headersNorm);

        $rows = [];
        for ($i = $dataStartIdx; $i < count($rowsRaw); $i++) {
            $vals = array_values($rowsRaw[$i]);
            if (!array_filter($vals)) continue;
            if (count($vals) < $headersCount) $vals = array_pad($vals, $headersCount, null);
            elseif (count($vals) > $headersCount) $vals = array_slice($vals, 0, $headersCount);

            $normalized = $this->normalizeRowFlexible($headers, $headersNorm, $headersNoSp, $vals, $normalizeKey);

            if ($normalized['fecha'] || $normalized['monto'] != 0.0 || !empty($normalized['referencia'])) {
                $rows[] = $normalized;
            }
        }

        return [$headers, $rows];
    }

    /**
     * Normaliza una fila usando headers normalizados con múltiples estrategias
     */
    private function normalizeRowFlexible(array $headersRaw, array $headersNorm, array $headersNoSp, array $values, callable $normalizeKey): array
    {
        $rmSpaces = fn($s) => str_replace(' ', '', $s);

        // === 1) BUSCAR ÍNDICES DIRECTAMENTE EN HEADERS ORIGINALES ===
        $idxFecha = $idxDescripcion = $idxReferencia = $idxDebito = $idxCredito = null;

        foreach ($headersRaw as $i => $header) {
            $headerLower = mb_strtolower(trim($header), 'UTF-8');

            if (str_contains($headerLower, 'fecha')) $idxFecha = $i;
            if (str_contains($headerLower, 'descrip')) $idxDescripcion = $i;
            if (str_contains($headerLower, 'referencia')) $idxReferencia = $i;
            if (str_contains($headerLower, 'débito') || str_contains($headerLower, 'debito') || str_contains($headerLower, 'dñbito')) $idxDebito = $i;
            if (str_contains($headerLower, 'crédito') || str_contains($headerLower, 'credito') || str_contains($headerLower, 'crñdito')) $idxCredito = $i;
        }

        // DEBUG: Log de índices encontrados
        \Log::info("Índices encontrados:", [
            'fecha' => $idxFecha,
            'descripcion' => $idxDescripcion,
            'referencia' => $idxReferencia,
            'debito' => $idxDebito,
            'credito' => $idxCredito
        ]);

        // === 2) EXTRACCIÓN DIRECTA POR ÍNDICE ===
        $getByIndex = fn($idx) => ($idx !== null && isset($values[$idx])) ? trim((string)$values[$idx]) : '';

        // FECHA
        $rawFecha = $getByIndex($idxFecha);
        $fecha = null;
        if ($rawFecha) {
            try {
                // Probar formato DD/MM/YYYY primero
                if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $rawFecha, $matches)) {
                    $fecha = sprintf('%04d-%02d-%02d', $matches[3], $matches[2], $matches[1]);
                } else {
                    $fecha = \Carbon\Carbon::parse($rawFecha)->format('Y-m-d');
                }
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        // DESCRIPCIÓN Y REFERENCIA
        $desc = $getByIndex($idxDescripcion);
        $ref = $getByIndex($idxReferencia);

        // === 3) EXTRACCIÓN DE MONTOS - MÁS ROBUSTA ===
        $toFloat = function ($val) {
            if (empty($val) || $val === '0' || $val === '0.0') return 0.0;

            $val = trim((string)$val);
            $val = str_ireplace(['Q', 'GTQ', '$', ' '], '', $val);

            // Manejar formato europeo 1.234,56
            if (strpos($val, ',') !== false && strpos($val, '.') === false) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else {
                $val = str_replace(',', '', $val);
            }

            // Manejar paréntesis para negativos
            if (preg_match('/^\((.*)\)$/', $val, $m)) {
                $val = '-' . $m[1];
            }

            return (float) $val;
        };

        // Extraer montos de débito y crédito
        $rawDebito = $getByIndex($idxDebito);
        $rawCredito = $getByIndex($idxCredito);

        $debito = $toFloat($rawDebito);
        $credito = $toFloat($rawCredito);

        // DEBUG: Log de valores extraídos
        \Log::info("Valores extraídos para fila:", [
            'rawDebito' => $rawDebito,
            'rawCredito' => $rawCredito,
            'debito' => $debito,
            'credito' => $credito,
            'values' => $values
        ]);

        // Determinar monto final
        $monto = 0.0;
        if ($credito > 0) {
            $monto = $credito;
        } elseif ($debito > 0) {
            $monto = -$debito;
        }

        // === 4) FALLBACK: Si no encontró por índices, buscar por posición conocida ===
        if ($monto == 0.0) {
            // En tu CSV, Débito está en posición 6 y Crédito en posición 7 (índice 6 y 7)
            if (isset($values[6]) && isset($values[7])) {
                $debitoFallback = $toFloat($values[6]);
                $creditoFallback = $toFloat($values[7]);

                if ($creditoFallback > 0) {
                    $monto = $creditoFallback;
                } elseif ($debitoFallback > 0) {
                    $monto = -$debitoFallback;
                }

                \Log::info("Fallback por posición:", [
                    'debito_pos6' => $values[6],
                    'credito_pos7' => $values[7],
                    'monto_final' => $monto
                ]);
            }
        }

        // === 5) DETECCIÓN PARA OTROS FORMATOS BANCARIOS COMUNES ===
        if ($monto == 0.0) {
            // Estrategia: buscar columnas que contengan patrones bancarios comunes
            $possibleAmountColumns = [];

            foreach ($headersRaw as $i => $header) {
                $headerLower = mb_strtolower(trim($header), 'UTF-8');

                // Patrones comunes en diferentes bancos
                $amountPatterns = [
                    'monto',
                    'importe',
                    'valor',
                    'amount',
                    'cargo',
                    'abono',
                    'retiro',
                    'deposito',
                    'egreso',
                    'ingreso',
                    'haber',
                    'debe',
                    'valor movimiento',
                    'monto transaccion'
                ];

                foreach ($amountPatterns as $pattern) {
                    if (str_contains($headerLower, $pattern)) {
                        $possibleAmountColumns[] = $i;
                        break;
                    }
                }
            }

            // Probar estas columnas
            foreach ($possibleAmountColumns as $colIndex) {
                if (isset($values[$colIndex])) {
                    $testAmount = $toFloat($values[$colIndex]);
                    if ($testAmount != 0.0) {
                        $monto = $testAmount;

                        // Intentar determinar si es débito o crédito por el nombre
                        $headerName = mb_strtolower($headersRaw[$colIndex] ?? '');
                        if (
                            str_contains($headerName, 'debito') || str_contains($headerName, 'debe') ||
                            str_contains($headerName, 'egreso') || str_contains($headerName, 'cargo') ||
                            str_contains($headerName, 'retiro')
                        ) {
                            $monto = -abs($monto);
                        }

                        \Log::info("Monto detectado por patrón bancario:", [
                            'columna' => $headersRaw[$colIndex],
                            'valor' => $values[$colIndex],
                            'monto' => $monto
                        ]);
                        break;
                    }
                }
            }
        }

        // === 6) DETECCIÓN POR ANÁLISIS DE VALORES EN LA FILA ===
        if ($monto == 0.0) {
            // Buscar el valor numérico más significativo en la fila
            $numericValues = [];

            foreach ($values as $i => $value) {
                $floatVal = $toFloat($value);
                if ($floatVal != 0.0 && abs($floatVal) > 0.01) {
                    $numericValues[] = [
                        'index' => $i,
                        'value' => $floatVal,
                        'header' => $headersRaw[$i] ?? ''
                    ];
                }
            }

            // Si hay exactamente un valor numérico significativo, usarlo
            if (count($numericValues) === 1) {
                $monto = $numericValues[0]['value'];
                \Log::info("Monto único detectado:", $numericValues[0]);
            }
            // Si hay dos valores, asumir débito/crédito
            elseif (count($numericValues) === 2) {
                $val1 = $numericValues[0]['value'];
                $val2 = $numericValues[1]['value'];

                // Asumir que el positivo es crédito y negativo débito
                if ($val1 > 0 && $val2 == 0) $monto = $val1;
                elseif ($val2 > 0 && $val1 == 0) $monto = $val2;
                elseif ($val1 < 0 && $val2 == 0) $monto = $val1;
                elseif ($val2 < 0 && $val1 == 0) $monto = $val2;

                \Log::info("Dos valores numéricos detectados:", $numericValues);
            }
        }

        \Log::info("Resultado final:", [
            'fecha' => $fecha,
            'descripcion' => $desc,
            'referencia' => $ref,
            'monto' => $monto
        ]);

        return [
            'fecha'       => $fecha,
            'descripcion' => $desc,
            'referencia'  => $ref,
            'monto'       => round($monto, 2),
        ];
    }


    public function conciliarAutomatico(Request $request)
    {
        try {
            $data = $request->validate([
                'ec_id'         => ['required', 'integer'],
                'auto_aprobar'  => ['sometimes', 'boolean'],
                'tolerancia'    => ['sometimes', 'numeric'],
            ]);

            $autoAprobar = (bool)($data['auto_aprobar'] ?? false);
            $tol         = (float)($data['tolerancia'] ?? 1.00);

            $ec = DB::table('pro_estados_cuenta')->where('ec_id', $data['ec_id'])->first();
            if (!$ec) return response()->json(['codigo' => 0, 'mensaje' => 'Estado de cuenta no encontrado'], 404);

            $rowsBanco = json_decode($ec->ec_rows, true) ?: [];

            $pendientes = DB::table('pro_pagos_subidos as ps')
                ->join('pro_ventas as v', 'v.ven_id', '=', 'ps.ps_venta_id')
                ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
                ->select(
                    'ps.*',
                    'v.ven_id',
                    'pg.pago_id',
                    'pg.pago_monto_total',
                    'pg.pago_monto_pagado',
                    'pg.pago_monto_pendiente'
                )
                ->whereIn('ps.ps_estado', ['PENDIENTE', 'PENDIENTE_VALIDACION'])
                ->get();

            $matchesAlta = [];
            $paraRevision = [];
            $sinMatch = [];

            $normRef = function ($s) {
                $s = (string)$s;
                $soloDig = preg_replace('/\D+/', '', $s);
                return strlen($soloDig) >= 6 ? $soloDig : trim($s);
            };

            foreach ($pendientes as $ps) {
                $psRef = $normRef($ps->ps_referencia);
                $psMonto = (float)$ps->ps_monto_comprobante;

                $mejor = null;

                foreach ($rowsBanco as $row) {
                    $bRef   = $normRef($row['referencia'] ?? '');
                    $bMonto = (float)($row['monto'] ?? 0);

                    $refOK = $psRef && $bRef && (stripos($bRef, $psRef) !== false || stripos($psRef, $bRef) !== false);
                    $montoOK = abs($bMonto - $psMonto) <= $tol;

                    if ($refOK && $montoOK) {
                        $mejor = [
                            'ps_id'       => $ps->ps_id,
                            'venta_id'    => $ps->ps_venta_id,
                            'banco_monto' => $bMonto,
                            'banco_fecha' => $row['fecha'] ?? null,
                            'banco_ref'   => $row['referencia'] ?? null,
                            'confianza'   => 'ALTA',
                            '_pago_id'    => $ps->pago_id ?? null,
                            '_ps_row'     => $ps,
                        ];
                        break;
                    }
                    if (!$mejor && ($refOK || $montoOK)) {
                        $mejor = [
                            'ps_id'       => $ps->ps_id,
                            'venta_id'    => $ps->ps_venta_id,
                            'banco_monto' => $bMonto,
                            'banco_fecha' => $row['fecha'] ?? null,
                            'banco_ref'   => $row['referencia'] ?? null,
                            'confianza'   => 'MEDIA',
                            '_pago_id'    => $ps->pago_id ?? null,
                            '_ps_row'     => $ps,
                        ];
                    }
                }

                if ($mejor) {
                    if ($mejor['confianza'] === 'ALTA') {
                        $matchesAlta[] = $mejor;
                    } else {
                        $paraRevision[] = $mejor;
                    }
                } else {
                    $sinMatch[] = [
                        'ps_id'         => $ps->ps_id,
                        'venta_id'      => $ps->ps_venta_id,
                        'ps_referencia' => $ps->ps_referencia,
                        'ps_monto'      => (float)$ps->ps_monto_comprobante,
                    ];
                }
            }

            $autoAprobados = [];
            if ($autoAprobar && count($matchesAlta)) {
                foreach ($matchesAlta as $m) {
                    $ps = DB::table('pro_pagos_subidos')
                        ->where('ps_id', $m['ps_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$ps || !in_array($ps->ps_estado, ['PENDIENTE', 'PENDIENTE_VALIDACION'])) continue;

                    $venta = DB::table('pro_ventas as v')
                        ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
                        ->select('v.ven_id', 'pg.pago_id', 'pg.pago_monto_total', 'pg.pago_monto_pagado')
                        ->where('v.ven_id', $ps->ps_venta_id)
                        ->first();
                    if (!$venta) continue;

                    // IDs de cuotas desde JSON + validación por pago
                    $cuotasIds = json_decode($ps->ps_cuotas_json ?? '[]', true) ?: [];
                    $cuotasIds = array_values(array_unique(array_map('intval', $cuotasIds)));

                    $validCuotas = [];
                    if ($cuotasIds) {
                        $validCuotas = DB::table('pro_cuotas')
                            ->where('cuota_control_id', $venta->pago_id)
                            ->whereIn('cuota_id', $cuotasIds)
                            ->pluck('cuota_id')
                            ->all();
                    }

                    DB::beginTransaction();
                    try {
                        $monto = (float)$ps->ps_monto_comprobante;
                        $metodoEfectivoId = 1; // o derivarlo
                        $fecha = $ps->ps_fecha_comprobante ?: now();

                        // Detalle
                        $detId = DB::table('pro_detalle_pagos')->insertGetId([
                            'det_pago_pago_id'             => $venta->pago_id,
                            'det_pago_cuota_id'            => null,
                            'det_pago_fecha'               => $fecha,
                            'det_pago_monto'               => $monto,
                            'det_pago_metodo_pago'         => $metodoEfectivoId,
                            'det_pago_banco_id'            => $ps->ps_banco_id ?? null,
                            'det_pago_numero_autorizacion' => $ps->ps_referencia ?? null,
                            'det_pago_imagen_boucher'      => $ps->ps_imagen_path ?? null,
                            'det_pago_tipo_pago'           => 'PAGO_UNICO',
                            'det_pago_estado'              => 'VALIDO',
                            'det_pago_observaciones'       => 'Auto-aprobado por conciliación',
                            'det_pago_usuario_registro'    => auth()->id(),
                            'created_at'                   => now(),
                            'updated_at'                   => now(),
                        ]);

                        // Master
                        $nuevoPagado    = (float)$venta->pago_monto_pagado + $monto;
                        $nuevoPendiente = max((float)$venta->pago_monto_total - $nuevoPagado, 0);
                        $nuevoEstado    = $nuevoPendiente <= 0 ? 'COMPLETADO' : 'PARCIAL';

                        DB::table('pro_pagos')->where('pago_id', $venta->pago_id)->update([
                            'pago_monto_pagado'     => $nuevoPagado,
                            'pago_monto_pendiente'  => $nuevoPendiente,
                            'pago_estado'           => $nuevoEstado,
                            'pago_fecha_completado' => $nuevoPendiente <= 0 ? now() : null,
                            'updated_at'            => now(),
                        ]);

                        // Caja
                        DB::table('cja_historial')->insert([
                            'cja_tipo'          => 'VENTA',
                            'cja_id_venta'      => $venta->ven_id,
                            'cja_usuario'       => auth()->id(),
                            'cja_monto'         => $monto,
                            'cja_fecha'         => now(),
                            'cja_metodo_pago'   => $metodoEfectivoId,
                            'cja_no_referencia' => $ps->ps_referencia ?? null,
                            'cja_situacion'     => 'ACTIVO',
                            'cja_observaciones' => 'Auto-aprobación ps#' . $ps->ps_id,
                            'created_at'        => now(),
                        ]);

                        // Saldos
                        CajaSaldo::ensureRow($metodoEfectivoId, 'GTQ')->addAmount($monto);

                        // Cuotas (si hay)
                        if (!empty($validCuotas)) {
                            DB::table('pro_cuotas')
                                ->whereIn('cuota_id', $validCuotas)
                                ->update([
                                    'cuota_estado'     => 'PAGADA',
                                    'cuota_fecha_pago' => now(),
                                    'updated_at'       => now(),
                                ]);
                        }

                        // PS -> APROBADO
                        DB::table('pro_pagos_subidos')->where('ps_id', $ps->ps_id)->update([
                            'ps_estado'         => 'APROBADO',
                            'ps_notas_revision' => 'Auto-aprobado (conciliación alta)',
                            'ps_revisado_por'   => auth()->id(),
                            'ps_revisado_en'    => now(),
                            'updated_at'        => now(),
                        ]);

                        DB::commit();

                        $this->safeDeletePublicPath($ps->ps_imagen_path ?? null);

                        $autoAprobados[] = [
                            'ps_id'               => $ps->ps_id,
                            'venta_id'            => $ps->ps_venta_id,
                            'banco_ref'           => $m['banco_ref'],
                            'banco_monto'         => $m['banco_monto'],
                            'banco_fecha'         => $m['banco_fecha'],
                            'det_pago_id'         => $detId,
                            'cuotas_pagadas_ids'  => $validCuotas,
                        ];
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        \Log::error('Auto-aprobación fallida', ['ps_id' => $ps->ps_id, 'e' => $e->getMessage()]);
                    }
                }
            }


            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'Conciliación realizada',
                'data'    => [
                    'auto_aprobados' => $autoAprobados,        // aprobados de una vez
                    'coincidencias'  => $matchesAlta,          // alta confianza (si no auto-aprobaste)
                    'revision'       => $paraRevision,         // media confianza -> revisar
                    'sin_match'      => $sinMatch,
                    'total_ps'       => count($pendientes),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error en conciliación',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
 * Helper privado: borra archivo de /public de forma segura
 * =========================== */
    private function safeDeletePublicPath(?string $path): void
    {
        if (!$path) return;
        try {

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        } catch (\Throwable $e) {
            \Log::warning('No se pudo eliminar comprobante', ['path' => $path, 'e' => $e->getMessage()]);
        }
    }

    /* ===========================
 * Validar movimiento de caja
 * POST /admin/pagos/movimientos/{id}/validar
 * =========================== */
    public function validarMovimiento($id)
    {
        try {
            DB::beginTransaction();

            $movimiento = DB::table('cja_historial')->where('cja_id', $id)->first();

            if (!$movimiento) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Movimiento no encontrado'
                ], 404);
            }

            if ($movimiento->cja_situacion !== 'PENDIENTE') {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'El movimiento no está pendiente de validación'
                ], 422);
            }

            // Actualizar estado a ACTIVO (solo cambiar cja_situacion)
            DB::table('cja_historial')
                ->where('cja_id', $id)
                ->update([
                    'cja_situacion' => 'ACTIVO'
                ]);

            // Si es una VENTA, actualizar saldo de caja
            if (in_array($movimiento->cja_tipo, ['VENTA', 'DEPOSITO', 'AJUSTE_POS'])) {
                CajaSaldo::ensureRow($movimiento->cja_metodo_pago, 'GTQ')
                    ->addAmount($movimiento->cja_monto);
            }

            DB::commit();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Movimiento validado correctamente'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al validar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
 * Rechazar movimiento de caja
 * POST /admin/pagos/movimientos/{id}/rechazar
 * =========================== */
    public function rechazarMovimiento($id)
    {
        try {
            $movimiento = DB::table('cja_historial')->where('cja_id', $id)->first();

            if (!$movimiento) {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Movimiento no encontrado'
                ], 404);
            }

            if ($movimiento->cja_situacion !== 'PENDIENTE') {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'El movimiento no está pendiente de validación'
                ], 422);
            }

            DB::table('cja_historial')
                ->where('cja_id', $id)
                ->update([
                    'cja_situacion' => 'ANULADA'
                ]);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Movimiento rechazado correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al rechazar movimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /* ===========================
 * Registrar ingreso de caja
 * POST /admin/pagos/movimientos
 * =========================== */
    /* ===========================
 * Registrar ingreso de caja
 * POST /admin/pagos/movimientos
 * =========================== */
    public function registrarIngreso(Request $request)
    {
        try {
            $data = $request->validate([
                'fecha'      => ['nullable', 'date'],
                'monto'      => ['required', 'numeric', 'gt:0'],
                'concepto'   => ['required', 'string', 'max:200'],
                'referencia' => ['nullable', 'string', 'max:100'],
                'archivo'    => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            ]);

            $path = null;

            if ($request->hasFile('archivo')) {
                $path = $request->file('archivo')->store('ingresos', 'public');
            }

            DB::beginTransaction();

            // CORREGIDO: Cambiar 'INGRESO' por 'DEPOSITO' que SÍ existe en el ENUM
            DB::table('cja_historial')->insert([
                'cja_tipo'          => 'DEPOSITO', // ← CAMBIADO DE 'INGRESO' A 'DEPOSITO'
                'cja_id_venta'      => null,
                'cja_id_import'     => null,
                'cja_usuario'       => auth()->id(),
                'cja_monto'         => $data['monto'],
                'cja_fecha'         => $data['fecha'] ? Carbon::parse($data['fecha']) : now(),
                'cja_metodo_pago'   => 1,
                'cja_no_referencia' => $data['referencia'] ?? null,
                'cja_situacion'     => 'ACTIVO',
                'cja_observaciones' => $data['concepto'],
                'created_at'        => now(),
            ]);

            // Actualizar saldos para ingreso
            CajaSaldo::ensureRow(1, 'GTQ')->addAmount($data['monto']);

            DB::commit();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Ingreso registrado exitosamente',
                'data' => [
                    'archivo' => $path
                ]
            ], 200);
        } catch (ValidationException $ve) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Datos de validación inválidos',
                'detalle' => $ve->getMessage()
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            if ($path) {
                try {
                    Storage::disk('public')->delete($path);
                } catch (Exception $__) {
                }
            }
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al registrar el ingreso',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
}
