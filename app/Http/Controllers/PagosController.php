<?php

namespace App\Http\Controllers;

use App\Mail\NotificarpagoMail;
use App\Models\ProVenta;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;
use Mail;
use PhpParser\Node\Expr;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metodopago = \App\Models\ProMetodoPago::where('metpago_situacion', 1)->get();
        return view('pagos.mispagos', compact('metodopago'));
    }

    public function index2()
    {
        $metodopago = \App\Models\ProMetodoPago::where('metpago_situacion', 1)->get();
        return view('pagos.mispagos', compact('metodopago'));
    }

    public function index3()
    {
        return view('pagos.administrar');
    }

    public function MisFacturasPendientes(Request $request)
    {
        try {
            $verTodas = (bool) $request->boolean('all', false);
            $userId = $request->input('user_id'); // Filter by user if provided
            $corte = $verTodas ? Carbon::create(1900, 1, 1) : Carbon::now()->subMonths(4)->startOfDay();

            // ===== Concepto por venta =====
            $labelsAgg = DB::table('pro_detalle_ventas as d')
                ->join('pro_productos as p', 'p.producto_id', '=', 'd.det_producto_id')
                ->leftJoin('pro_marcas as ma', 'ma.marca_id', '=', 'p.producto_marca_id')
                ->leftJoin('pro_modelo as mo', 'mo.modelo_id', '=', 'p.producto_modelo_id')
                ->leftJoin('pro_calibres as ca', 'ca.calibre_id', '=', 'p.producto_calibre_id')
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
                ->groupBy('x.det_ven_id');

            // ===== 🔥 NUEVO: Precios aplicados por venta =====
            $preciosSub = DB::table('pro_detalle_ventas as dv')
                ->join('pro_productos as p', 'p.producto_id', '=', 'dv.det_producto_id')
                ->leftJoin('pro_precios as pr', 'pr.precio_producto_id', '=', 'p.producto_id')
                ->select([
                    'dv.det_ven_id',
                    DB::raw('MAX(pr.precio_venta) as precio_individual'),
                    DB::raw('MAX(pr.precio_venta_empresa) as precio_empresa'),
                    DB::raw('MAX(dv.det_precio) as precio_aplicado') // el precio real de la venta
                ])
                ->groupBy('dv.det_ven_id');

            // ===== Ventas activas (TODAS) con información completa =====
            $ventas = DB::table('pro_ventas as v')
                ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
                ->leftJoin('pro_clientes as c', 'c.cliente_id', '=', 'v.ven_cliente')
                ->leftJoin('users as vendedor', 'vendedor.user_id', '=', 'v.ven_user') // 🔥 JOIN con vendedor
                ->leftJoinSub($conceptoSub, 'cx', fn($j) => $j->on('cx.det_ven_id', '=', 'v.ven_id'))
                ->leftJoinSub($preciosSub, 'px', fn($j) => $j->on('px.det_ven_id', '=', 'v.ven_id')) // 🔥 JOIN precios
                ->where('v.ven_situacion', '!=', 'ANULADA')
                ->when($userId, function ($query, $userId) {
                    return $query->where('v.ven_cliente', $userId);
                })
                ->when(auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor', function ($query) {
                    return $query->where('v.ven_user', auth()->id());
                })
                ->select([
                    'v.ven_id',
                    'v.ven_cliente',
                    'v.ven_fecha',
                    'v.ven_total_vendido',
                    'v.ven_descuento',
                    'v.ven_observaciones',
                    'v.ven_situacion', // 🔥 Estado de la venta
                    'v.ven_user', // 🔥 ID del vendedor

                    // Información del cliente
                    DB::raw("
                        CASE 
                            WHEN c.cliente_tipo = 3 THEN 
                                CONCAT(
                                    COALESCE(c.cliente_nom_empresa, ''), 
                                    ' | ', 
                                    TRIM(CONCAT_WS(' ', 
                                        COALESCE(c.cliente_nombre1, ''), 
                                        COALESCE(c.cliente_apellido1, '')
                                    ))
                                )
                            ELSE 
                                TRIM(CONCAT_WS(' ', 
                                    COALESCE(c.cliente_nombre1, ''),
                                    COALESCE(c.cliente_nombre2, ''),
                                    COALESCE(c.cliente_apellido1, ''),
                                    COALESCE(c.cliente_apellido2, '')
                                ))
                        END as cliente_nombre
                    "),
                    DB::raw("COALESCE(c.cliente_nom_empresa, 'Sin Empresa') as cliente_empresa"),
                    'c.cliente_tipo',
                    'c.cliente_nit',
                    'c.cliente_telefono',

                    // 🔥 Información del vendedor
                    DB::raw("
                        TRIM(CONCAT_WS(' ',
                            COALESCE(vendedor.user_primer_nombre, ''),
                            COALESCE(vendedor.user_segundo_nombre, ''),
                            COALESCE(vendedor.user_primer_apellido, ''),
                            COALESCE(vendedor.user_segundo_apellido, '')
                        )) as vendedor_nombre
                    "),

                    // 🔥 Precios
                    'px.precio_individual',
                    'px.precio_empresa',
                    'px.precio_aplicado',

                    'pg.pago_id',
                    'pg.pago_tipo_pago',
                    'pg.pago_monto_total',
                    'pg.pago_monto_pagado',
                    'pg.pago_monto_pendiente',
                    'pg.pago_estado',
                    'pg.pago_cantidad_cuotas',
                    'pg.pago_abono_inicial',
                    'pg.pago_fecha_inicio',
                    'pg.pago_fecha_completado',
                    DB::raw('(pg.pago_monto_total - pg.pago_monto_pagado) as calculo_pendiente'),
                    DB::raw('COALESCE(cx.concepto_resumen, "—") as concepto'),
                    DB::raw('COALESCE(cx.items_count, 0) as items_count'),
                ])
                ->orderBy('v.ven_fecha', 'desc')
                ->get();

            if ($ventas->isEmpty()) {
                return response()->json([
                    'codigo' => 1,
                    'mensaje' => 'Sin ventas activas',
                    'data' => [
                        'pendientes' => [],
                        'pagadas_ult4m' => [],
                        'facturas_pendientes_all' => [],
                        'all' => $verTodas
                    ]
                ]);
            }

            $pagoIds = $ventas->pluck('pago_id')->all();
            $ventaIds = $ventas->pluck('ven_id')->all();

            // ===== Cuotas pendientes o vencidas =====
            $cuotas = DB::table('pro_cuotas as ct')
                ->whereIn('ct.cuota_control_id', $pagoIds)
                ->whereIn('ct.cuota_estado', ['PENDIENTE', 'VENCIDA'])
                ->orderBy('ct.cuota_control_id')->orderBy('ct.cuota_numero')
                ->get()->groupBy('cuota_control_id');

            // ===== Pagos válidos (historial) =====
            $pagosValidos = DB::table('pro_detalle_pagos as dp')
                ->whereIn('dp.det_pago_pago_id', $pagoIds)
                ->where('dp.det_pago_estado', 'VALIDO')
                ->leftJoin('pro_metodos_pago as mp', 'mp.metpago_id', '=', 'dp.det_pago_metodo_pago')
                ->leftJoin('pro_bancos as b', 'b.banco_id', '=', 'dp.det_pago_banco_id') // Join banks
                ->select([
                    'dp.det_pago_id',
                    'dp.det_pago_pago_id',
                    'dp.det_pago_fecha',
                    'dp.det_pago_monto',
                    'dp.det_pago_tipo_pago',
                    'dp.det_pago_numero_autorizacion',
                    'dp.det_pago_imagen_boucher',
                    'mp.metpago_descripcion as metodo',
                    'b.banco_nombre' // Select bank name
                ])
                ->orderBy('dp.det_pago_fecha', 'asc')
                ->get()->groupBy('det_pago_pago_id');

            // ===== Cuotas EN REVISIÓN por venta =====
            $pendRows = DB::table('pro_pagos_subidos as ps')
                ->leftJoin('pro_bancos as b', 'b.banco_id', '=', 'ps.ps_banco_id')
                ->whereIn('ps.ps_venta_id', $ventaIds)
                ->where('ps.ps_estado', 'PENDIENTE_VALIDACION')
                ->select([
                    'ps.ps_id',
                    'ps.ps_venta_id',
                    'ps.ps_cuotas_json',
                    'ps.ps_imagen_path',
                    'ps.ps_monto_comprobante',
                    'ps.ps_referencia',
                    'ps.ps_concepto',
                    'ps.ps_banco_id',
                    'ps.ps_fecha_comprobante',
                    'b.banco_nombre'
                ])
                ->get();

            $cuotasEnRevisionPorVenta = [];
            $comprobantesEnRevisionPorVenta = [];
            $pagosEnRevisionPorVenta = []; // Nuevo array para detalles completos

            foreach ($pendRows as $row) {
                $lista = json_decode($row->ps_cuotas_json, true) ?: [];
                $vid = (int) $row->ps_venta_id;
                
                // IDs de cuotas
                $cuotasEnRevisionPorVenta[$vid] = array_values(array_unique(array_merge($cuotasEnRevisionPorVenta[$vid] ?? [], array_map('intval', $lista))));
                
                // Path del comprobante (último)
                if ($row->ps_imagen_path) {
                    $comprobantesEnRevisionPorVenta[$vid] = $row->ps_imagen_path;
                }

                // Detalle completo del pago en revisión
                if (!isset($pagosEnRevisionPorVenta[$vid])) {
                    $pagosEnRevisionPorVenta[$vid] = [];
                }
                $pagosEnRevisionPorVenta[$vid][] = [
                    'id' => $row->ps_id,
                    'monto' => (float) $row->ps_monto_comprobante,
                    'referencia' => $row->ps_referencia,
                    'concepto' => $row->ps_concepto,
                    'banco_id' => $row->ps_banco_id,
                    'banco_nombre' => $row->banco_nombre,
                    'fecha' => $row->ps_fecha_comprobante,
                    'comprobante' => $row->ps_imagen_path
                ];
            }

            // ===== Clasificación =====
            $pendientes = [];
            $pagadasUlt4m = [];

            foreach ($ventas as $v) {
                $pendiente = isset($v->pago_monto_pendiente) && $v->pago_monto_pendiente !== null
                    ? (float) $v->pago_monto_pendiente
                    : max((float) $v->calculo_pendiente, 0.0);

                $hist = ($pagosValidos[$v->pago_id] ?? collect())->map(fn($p) => [
                    'id' => $p->det_pago_id,
                    'fecha' => $p->det_pago_fecha,
                    'monto' => (float) $p->det_pago_monto,
                    'tipo' => $p->det_pago_tipo_pago,
                    'metodo' => $p->metodo ?? 'N/D',
                    'banco' => $p->banco_nombre ?? null, // Add bank name
                    'no_referencia' => $p->det_pago_numero_autorizacion,
                    'comprobante' => $p->det_pago_imagen_boucher,
                ])->values();

                $ultimaFechaPago = ($pagosValidos[$v->pago_id] ?? collect())->max('det_pago_fecha');
                $fechaCompletado = $v->pago_fecha_completado ?? $ultimaFechaPago;

                $enRevIds = collect($cuotasEnRevisionPorVenta[$v->ven_id] ?? []);

                $cuotasPend = ($cuotas[$v->pago_id] ?? collect())->map(function ($c) use ($enRevIds) {
                    $id = (int) $c->cuota_id;
                    return [
                        'cuota_id' => $id,
                        'numero' => (int) $c->cuota_numero,
                        'monto' => (float) $c->cuota_monto,
                        'vence' => $c->cuota_fecha_vencimiento,
                        'estado' => $c->cuota_estado,
                        'en_revision' => $enRevIds->contains($id),
                    ];
                })->values();

                // FIX: Si es pago ÚNICO y no hay cuotas (porque se borraron o nunca hubo), 
                // pero hay saldo pendiente, generar una "cuota virtual" para permitir el pago.
                if ($cuotasPend->isEmpty() && $pendiente > 0 && ($v->pago_tipo_pago === 'UNICO' || $v->pago_tipo_pago === 'PENDIENTE')) {
                    $cuotasPend->push([
                        'cuota_id' => -1 * $v->pago_id, // ID negativo para identificar virtual sin romper tipos
                        'numero' => 1,
                        'monto' => $pendiente,
                        'vence' => $v->ven_fecha, // Vence el mismo día de la venta
                        'estado' => 'PENDIENTE',
                        'en_revision' => false,
                        'is_virtual' => true // Flag para el frontend si se necesita
                    ]);
                }

                $disponibles = $cuotasPend->filter(fn($q) => !$q['en_revision'])->count();

                $base = [
                    'venta_id' => $v->ven_id,
                    'fecha' => $v->ven_fecha,
                    'concepto' => $v->concepto,
                    'items_count' => (int) $v->items_count,
                    'monto_total' => (float) $v->pago_monto_total ?: (float) $v->ven_total_vendido,
                    'pagado' => (float) $v->pago_monto_pagado,
                    'pendiente' => $pendiente,
                    'estado_pago' => $v->pago_estado ?? ($pendiente > 0 ? 'PENDIENTE' : 'COMPLETADO'),
                    'ven_situacion' => $v->ven_situacion, // 🔥 Estado de la venta para frontend
                    'observaciones' => $v->ven_observaciones,

                    // 🔥 Información del cliente
                    'cliente' => [
                        'id' => $v->ven_cliente,
                        'nombre' => $v->cliente_nombre ?? 'Sin Nombre',
                        'empresa' => $v->cliente_empresa ?? 'Sin Empresa',
                        'tipo' => $v->cliente_tipo ?? 1, // 🔥 Tipo de cliente
                        'nit' => $v->cliente_nit ?? '—',
                        'telefono' => $v->cliente_telefono ?? '—',
                    ],

                    // 🔥 Información del vendedor (NUEVO)
                    'vendedor' => [
                        'id' => $v->ven_user,
                        'nombre' => $v->vendedor_nombre ?? 'Sin Vendedor',
                    ],

                    // 🔥 Precios (NUEVO)
                    'precios' => [
                        'individual' => (float) ($v->precio_individual ?? 0),
                        'empresa' => (float) ($v->precio_empresa ?? 0),
                        'aplicado' => (float) ($v->precio_aplicado ?? 0),
                    ],

                    'cuotas_en_revision' => $enRevIds->values(),
                    'pagos_en_revision_detalles' => $pagosEnRevisionPorVenta[$v->ven_id] ?? [], // Nuevo campo
                    'comprobante_revision' => $comprobantesEnRevisionPorVenta[$v->ven_id] ?? null,
                    'cuotas_disponibles' => $disponibles,

                    'pago_master' => [
                        'pago_id' => (int) $v->pago_id,
                        'tipo' => $v->pago_tipo_pago,
                        'cuotas_totales' => (int) ($v->pago_cantidad_cuotas ?? 0),
                        'abono_inicial' => (float) ($v->pago_abono_inicial ?? 0),
                        'inicio' => $v->pago_fecha_inicio,
                        'fin' => $v->pago_fecha_completado,
                    ],
                    'pagos_realizados' => $hist,
                ];

                if ($pendiente > 0) {
                    $pendientes[] = $base + [
                        'cuotas_pendientes' => $cuotasPend,
                        'puede_pagar_en_linea' => $disponibles > 0,
                    ];
                } else {
                    $fechaReferencia = $fechaCompletado ?: $v->ven_fecha;
                    if ($fechaReferencia && Carbon::parse($fechaReferencia)->gte($corte)) {
                        $pagadasUlt4m[] = $base + [
                            'marcar_como' => 'PAGADO',
                            'fecha_ultimo_pago' => $ultimaFechaPago ?: $v->pago_fecha_completado ?: $v->ven_fecha,
                        ];
                    }
                }
            }

            $facturasPendientesAll = collect($pendientes)->map(fn($r) => [
                'venta_id' => $r['venta_id'],
                'fecha' => $r['fecha'],
                'concepto' => $r['concepto'],
                'total' => $r['monto_total'],
                'pagado' => $r['pagado'],
                'pendiente' => $r['pendiente'],
                'estado' => $r['estado_pago'],
                'cliente' => $r['cliente'],
                'vendedor' => $r['vendedor'], // 🔥 NUEVO
                'precios' => $r['precios'],  // 🔥 NUEVO
            ])->values();

            // ===== Deudas pendientes de carga de comprobante =====
            $deudasPendientesCarga = DB::table('pro_pagos_subidos as ps')
                ->join('pro_deudas_clientes as d', 'd.deuda_id', '=', 'ps.ps_deuda_id')
                ->leftJoin('pro_clientes as c', 'c.cliente_id', '=', 'd.cliente_id')
                ->where('ps.ps_estado', 'PENDIENTE_CARGA')
                ->when($userId, function ($query, $userId) {
                    return $query->where('ps.ps_cliente_user_id', $userId);
                })
                ->select([
                    'ps.ps_id',
                    'ps.ps_deuda_id',
                    'ps.ps_monto_comprobante as monto',
                    'ps.ps_referencia',
                    'ps.ps_concepto',
                    'ps.created_at',
                    'd.descripcion as deuda_descripcion',
                    'd.monto as deuda_monto_total',
                    'd.saldo_pendiente as deuda_saldo',
                    DB::raw("TRIM(CONCAT_WS(' ', c.cliente_nombre1, c.cliente_apellido1)) as cliente_nombre")
                ])
                ->get();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Datos devueltos correctamente',
                'data' => [
                    'pendientes' => array_values($pendientes),
                    'pagadas_ult4m' => array_values($pagadasUlt4m),
                    'facturas_pendientes_all' => $facturasPendientesAll,
                    'deudas_pendientes_carga' => $deudasPendientesCarga, // Nuevo campo
                    'all' => $verTodas,
                ]
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('Error en MisFacturasPendientes', [
                'mensaje' => $e->getMessage(),
                'linea' => $e->getLine(),
                'archivo' => $e->getFile()
            ]);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al obtener datos',
                'detalle' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }



    public function pagarCuotas(Request $request)
    {
        try {
            $user = $request->user();

            $data = $request->validate([
            'venta_id' => ['required', 'integer'],
            'cuotas' => ['nullable', 'string'], // JSON: [10,11] (nullable for proof upload)
            'monto_total' => ['required', 'numeric'], // suma de cuotas seleccionadas (front)
            'fecha' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'monto' => ['required', 'numeric'], // monto del comprobante
            'referencia' => ['required', 'string', 'min:6', 'max:64'],
            'concepto' => ['nullable', 'string', 'max:255'],
            'banco_id' => ['nullable', 'integer'],        // <- bigint en tu BD
            'banco_nombre' => ['nullable', 'string', 'max:64'],
            'comprobante' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,webp,heic,heif', 'max:5120'],
            'detalle_pago_id' => ['nullable', 'integer'], // ID del pago existente
        ], [
            'comprobante.mimes' => 'El comprobante debe ser una imagen (jpg, png, webp, heic) o un archivo PDF.',
            'comprobante.max' => 'El archivo no debe pesar más de 5MB.',
        ]);

        $ventaId = (int) $data['venta_id'];
        $cuotasArr = json_decode($data['cuotas'] ?? '[]', true) ?: [];
        $montoComprobante = (float) $data['monto'];

        // Validar que haya cuotas O detalle_pago_id O que sea un pago general (sin cuotas específicas)
        // Antes: if (empty($cuotasArr) && empty($data['detalle_pago_id'])) { ... }
        // AHORA: Permitimos pasar si hay monto > 0, asumiendo que es un pago a cuenta o liquidación total
        if (empty($cuotasArr) && empty($data['detalle_pago_id']) && $montoComprobante <= 0) {
             return response()->json(['codigo' => 0, 'mensaje' => 'Debe seleccionar cuotas o ingresar un monto válido'], 422);
        }

        // 1) Verificar que la venta exista (y opcionalmente que pertenezca al usuario)
        $venta = DB::table('pro_ventas as v')
            ->join('pro_pagos as pg', 'pg.pago_venta_id', '=', 'v.ven_id')
            ->where('v.ven_id', $ventaId)
            ->select('v.ven_id', 'pg.pago_id', 'v.ven_user')
            ->first();

        if (!$venta) {
            return response()->json(['codigo' => 0, 'mensaje' => 'Venta no encontrada'], 404);
        }

        // 2) BLOQUEO: ¿ya hay un envío en PENDIENTE_VALIDACION para esta venta?
        $yaPendiente = DB::table('pro_pagos_subidos')
            ->where('ps_venta_id', $ventaId)
            ->where('ps_estado', 'PENDIENTE_VALIDACION')
            ->exists();

        // Validar que el vendedor sea el dueño de la venta
        if (auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
            if ($venta->ven_user != auth()->id()) {
                return response()->json(['success' => false, 'message' => 'No tiene permiso para registrar pagos en esta venta.'], 403);
            }
        }

        if ($yaPendiente) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Ya existe un pago en revisión para esta venta. Espera la validación antes de enviar otro.',
            ], 200);
        }

        // 3) Subir archivo (opcional)
        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $request->file('comprobante')->store('pagos_subidos', 'public'); // requiere storage:link
        }

        // 4) Insert en TU ESQUEMA
        $montoTotalCuotas = (float) $data['monto_total'];
        $montoComprobante = (float) $data['monto'];
        $diferencia = $montoComprobante - $montoTotalCuotas;

        DB::beginTransaction();

        $insert = [
            'ps_venta_id' => $ventaId,
            'ps_cliente_user_id' => $user->id ?? $user->user_id ?? null,
            'ps_estado' => 'PENDIENTE_VALIDACION',
            'ps_canal' => 'WEB',

            'ps_fecha_comprobante' => $data['fecha'] ? Carbon::parse($data['fecha']) : null,
            'ps_monto_comprobante' => $montoComprobante,
            'ps_monto_total_cuotas_front' => $montoTotalCuotas,
            'ps_diferencia' => $diferencia,

            'ps_banco_id' => $data['banco_id'] ?? null,
            'ps_banco_nombre' => $data['banco_nombre'] ?? null,

            'ps_referencia' => $data['referencia'],
            'ps_concepto' => $data['concepto'] ?? null,
            'ps_cuotas_json' => json_encode(array_values($cuotasArr), JSON_UNESCAPED_UNICODE),
            'ps_detalle_pago_id' => $data['detalle_pago_id'] ?? null, // Guardamos ID

            'ps_imagen_path' => $path,
            // opcionales:
            'ps_checksum' => null, // si quieres, calcula hash del archivo/combinación
            'created_at' => now(),
            'updated_at' => now(),
        ];    
            $psId = DB::table('pro_pagos_subidos')->insertGetId($insert);

            DB::commit();

            // 5) Notificación (no bloqueante)
            try {
                $payload = [
                    'venta_id' => $ventaId,
                    'pago_id' => $venta->pago_id,
                    'cuotas' => $cuotasArr,
                    'monto_total' => $montoTotalCuotas,
                    'fecha' => $data['fecha'] ?? null,
                    'monto' => $montoComprobante,
                    'referencia' => $data['referencia'],
                    'concepto' => $data['concepto'] ?? null,
                    'banco_id' => $data['banco_id'] ?? null,
                    'banco_nombre' => $data['banco_nombre'] ?? null,
                    'cliente' => [
                        'id' => $user->id ?? $user->user_id ?? null,
                        'nombre' => $user->name ?? ($user->nombre ?? 'Cliente'),
                        'email' => $user->email ?? 'sin-correo',
                    ],
                    'ps_id' => $psId,
                    'comprobante_path' => $path,
                ];
                // Enviar correo a administradores
                $admins = \App\Models\User::whereHas('rol', function($q){
                    $q->where('nombre', 'administrador');
                })->get();

                foreach ($admins as $admin) {
                    if ($admin->email) {
                        Mail::to($admin->email)->send(new \App\Mail\NotificarpagoMail($payload, $path, 'VENTA'));
                    }
                }
            } catch (\Throwable $me) {
                Log::warning('Fallo al enviar correo de pago pendiente', ['error' => $me->getMessage()]);
            }

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Pago enviado. Quedó en revisión (PENDIENTE_VALIDACION).',
                'ps_id' => $psId,
                'path' => $path,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('pagarCuotas error', ['msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'No se pudo registrar el pago: ' . $e->getMessage(),
            ], 200);
        }
    }


    public function actualizarPagoSubido(Request $request)
    {
        try {
            $data = $request->validate([
                'ps_id' => 'required|integer',
                'banco_id' => 'nullable|integer',
                'referencia' => 'required|string|max:100',
                'concepto' => 'nullable|string|max:255',
            ]);

            $pago = DB::table('pro_pagos_subidos')->where('ps_id', $data['ps_id'])->first();

            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            // Validar que el vendedor sea el dueño de la venta
            $venta = DB::table('pro_ventas')->where('ven_id', $pago->ps_venta_id)->first();
            if ($venta && auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                if ($venta->ven_user != auth()->id()) {
                    return response()->json(['success' => false, 'message' => 'No tiene permiso para editar este pago.'], 403);
                }
            }

            if ($pago->ps_estado !== 'PENDIENTE_VALIDACION' && $pago->ps_estado !== 'PENDIENTE_CARGA') {
                return response()->json(['success' => false, 'message' => 'Solo se pueden editar pagos pendientes'], 400);
            }

            DB::table('pro_pagos_subidos')->where('ps_id', $data['ps_id'])->update([
                'ps_banco_id' => $data['banco_id'],
                'ps_referencia' => $data['referencia'],
                'ps_concepto' => $data['concepto'],
                'updated_at' => now()
            ]);

            return response()->json(['success' => true, 'message' => 'Pago actualizado correctamente']);
        } catch (\Exception $e) {
            Log::error('Error actualizando pago subido: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar el pago'], 500);
        }
    }

    public function anularPago(Request $request)
    {
        try {
            $request->validate([
                'venta_id' => 'required|integer',
                'motivo' => 'required|string|max:255'
            ]);

            $ventaId = $request->venta_id;
            $motivo = $request->motivo;

            DB::beginTransaction();

            // 1. Obtener el pago asociado a la venta
            $pago = DB::table('pro_pagos')->where('pago_venta_id', $ventaId)->first();
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'No se encontró registro de pago para esta venta.'], 404);
            }

            // Validar que el vendedor sea el dueño de la venta
            $venta = DB::table('pro_ventas')->where('ven_id', $ventaId)->first();
            if ($venta && auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                if ($venta->ven_user != auth()->id()) {
                    return response()->json(['success' => false, 'message' => 'No tiene permiso para anular este pago.'], 403);
                }
            }

            // 2. Verificar si existen pagos VALIDADOS
            $pagosValidados = DB::table('pro_detalle_pagos')
                ->where('det_pago_pago_id', $pago->pago_id)
                ->where('det_pago_estado', 'VALIDO')
                ->exists();

            // 3. Eliminar pagos subidos (pendientes de validación) siempre
            DB::table('pro_pagos_subidos')
                ->where('ps_venta_id', $ventaId)
                ->delete();

            // 4. Eliminar cuotas generadas (si las hay) siempre, se regenerarán
            DB::table('pro_cuotas')
                ->where('cuota_control_id', $pago->pago_id)
                ->delete();

            if ($pagosValidados) {
                // Calcular cuánto está validado
                $totalPagado = DB::table('pro_detalle_pagos')
                    ->where('det_pago_pago_id', $pago->pago_id)
                    ->where('det_pago_estado', 'VALIDO')
                    ->sum('det_pago_monto');

                // Si el pago validado cubre el total (o más), y se está anulando,
                // asumimos que se quiere corregir TODO el pago (ej. cambiar de Transferencia a Cuotas).
                // Por tanto, forzamos el RESET TOTAL (Escenario A) en lugar del parcial.
                if ($totalPagado >= $pago->pago_monto_total) {
                    // ESCENARIO A (Forzado): Reset Total
                    
                    // Eliminar pagos validados (se borrarán del historial de pagos)
                    DB::table('pro_detalle_pagos')
                        ->where('det_pago_pago_id', $pago->pago_id)
                        ->delete();

                    // Resetear maestro
                    DB::table('pro_pagos')
                        ->where('pago_id', $pago->pago_id)
                        ->update([
                            'pago_monto_pagado' => 0,
                            'pago_monto_pendiente' => $pago->pago_monto_total,
                            'pago_estado' => 'PENDIENTE',
                            'pago_tipo_pago' => 'UNICO',
                            'pago_cantidad_cuotas' => 0,
                            'pago_abono_inicial' => 0,
                            'updated_at' => now()
                        ]);

                    // Anular en caja
                    DB::table('cja_historial')
                        ->where('cja_id_venta', $ventaId)
                        ->where('cja_tipo', 'VENTA')
                        ->update([
                            'cja_situacion' => 'ANULADA',
                            'cja_observaciones' => DB::raw("CONCAT(cja_observaciones, ' - ANULADO (Corrección Total): $motivo')")
                        ]);

                    $message = 'Pago anulado completamente. Se ha liberado el saldo para registrar el nuevo método.';

                } else {
                    // ESCENARIO B: Existen pagos validados PARCIALES -> RESET PARCIAL (Refinanciamiento)
                    
                    $nuevoPendiente = $pago->pago_monto_total - $totalPagado;

                    // Actualizar maestro
                    DB::table('pro_pagos')
                        ->where('pago_id', $pago->pago_id)
                        ->update([
                            'pago_monto_pagado' => $totalPagado,
                            'pago_monto_pendiente' => $nuevoPendiente,
                            'pago_estado' => 'PENDIENTE', // Vuelve a pendiente para definir cómo pagar el resto
                            'pago_tipo_pago' => 'UNICO', // Reset a default temporalmente
                            'pago_cantidad_cuotas' => 0,
                            'pago_abono_inicial' => 0,
                            'updated_at' => now()
                        ]);

                    // No tocamos cja_historial porque los pagos validados se mantienen.
                    DB::table('pro_ventas')
                        ->where('ven_id', $ventaId)
                        ->update([
                            'ven_observaciones' => DB::raw("CONCAT(COALESCE(ven_observaciones,''), ' | Reajuste parcial solicitado: $motivo')")
                        ]);

                    $message = 'Se ha habilitado la edición del saldo pendiente. Los pagos parciales validados se mantuvieron.';
                }

            } else {
                // ESCENARIO A: No hay pagos validados -> RESET TOTAL (Lógica original)

                // Eliminar cualquier detalle de pago (que no sea válido, aunque el filtro arriba ya lo cubría, por seguridad borramos todo)
                DB::table('pro_detalle_pagos')
                    ->where('det_pago_pago_id', $pago->pago_id)
                    ->delete();

                // Resetear el registro maestro de pagos a cero
                DB::table('pro_pagos')
                    ->where('pago_id', $pago->pago_id)
                    ->update([
                        'pago_monto_pagado' => 0,
                        'pago_monto_pendiente' => $pago->pago_monto_total,
                        'pago_estado' => 'PENDIENTE',
                        'pago_tipo_pago' => 'UNICO',
                        'pago_cantidad_cuotas' => 0,
                        'pago_abono_inicial' => 0,
                        'updated_at' => now()
                    ]);

                // Anular registro en caja (si existe)
                DB::table('cja_historial')
                    ->where('cja_id_venta', $ventaId)
                    ->where('cja_tipo', 'VENTA')
                    ->update([
                        'cja_situacion' => 'ANULADA',
                        'cja_observaciones' => DB::raw("CONCAT(cja_observaciones, ' - ANULADO: $motivo')")
                    ]);
                
                $message = 'Pago anulado correctamente. Ahora puede registrar el pago nuevamente.';
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al anular el pago: ' . $e->getMessage()], 500);
        }
    }

    public function generarCuotas(Request $request)
    {
        try {
            $request->validate([
                'venta_id' => 'required|integer',
                'metodo_pago' => 'required',
                // Validación condicional para cuotas
                'cantidad_cuotas' => 'required_if:metodo_pago,6|integer|min:2|max:48',
                'abono_inicial' => 'nullable|numeric|min:0',
            ]);

            $ventaId = $request->venta_id;
            $metodoPago = $request->metodo_pago;

            DB::beginTransaction();

            $pago = DB::table('pro_pagos')->where('pago_venta_id', $ventaId)->first();
            if (!$pago) {
                return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
            }

            // Validar que el vendedor sea el dueño de la venta
            $venta = DB::table('pro_ventas')->where('ven_id', $ventaId)->first();
            if ($venta && auth()->user()->rol && strtolower(auth()->user()->rol->nombre) === 'vendedor') {
                if ($venta->ven_user != auth()->id()) {
                    return response()->json(['success' => false, 'message' => 'No tiene permiso para generar cuotas para esta venta.'], 403);
                }
            }

            // Validar que esté pendiente
            if ($pago->pago_estado !== 'PENDIENTE') {
                return response()->json(['success' => false, 'message' => 'El pago no está pendiente'], 422);
            }

            // Limpiar cuotas anteriores
            DB::table('pro_cuotas')->where('cuota_control_id', $pago->pago_id)->delete();

            // DETERMINAR MONTO BASE (Refinanciamiento vs Pago Total)
            // Si hay pagos validados previos, el monto a financiar es el pendiente.
            // Si no, es el total.
            $montoBase = ($pago->pago_monto_pagado > 0) ? $pago->pago_monto_pendiente : $pago->pago_monto_total;

            if ($metodoPago == '6') { // CUOTAS
                $cantidad = $request->cantidad_cuotas;
                $abono = $request->abono_inicial ?? 0;
                
                if ($abono >= $montoBase) {
                    return response()->json(['success' => false, 'message' => 'El abono no puede cubrir el total pendiente'], 422);
                }

                $saldoFinanciar = round($montoBase - $abono, 2);

                // Verificar si vienen cuotas personalizadas
                $cuotasCustom = $request->input('cuotas_custom'); // Array de montos
                if (is_array($cuotasCustom) && count($cuotasCustom) > 0) {
                    if (count($cuotasCustom) != $cantidad) {
                        return response()->json(['success' => false, 'message' => 'La cantidad de montos personalizados no coincide con el número de cuotas'], 422);
                    }

                    $sumCustom = array_sum($cuotasCustom);
                    // Permitir una pequeña diferencia por redondeo (0.05)
                    if (abs($sumCustom - $saldoFinanciar) > 0.05) {
                        return response()->json(['success' => false, 'message' => "La suma de las cuotas (Q$sumCustom) no coincide con el saldo a financiar (Q$saldoFinanciar)"], 422);
                    }
                } else {
                    $cuotasCustom = null;
                }

                DB::table('pro_pagos')->where('pago_id', $pago->pago_id)->update([
                    'pago_tipo_pago' => 'CUOTAS',
                    // 'pago_metodo_pago' => 6, // Column does not exist
                    'pago_cantidad_cuotas' => $cantidad,
                    'pago_abono_inicial' => $abono,
                    'updated_at' => now()
                ]);

                // Generar cuotas
                $fechaBase = now();
                
                if ($cuotasCustom) {
                    // Usar montos personalizados
                    foreach ($cuotasCustom as $index => $monto) {
                        DB::table('pro_cuotas')->insert([
                            'cuota_control_id' => $pago->pago_id,
                            'cuota_numero' => $index + 1,
                            'cuota_monto' => $monto,
                            'cuota_fecha_vencimiento' => $fechaBase->copy()->addMonths($index + 1),
                            'cuota_estado' => 'PENDIENTE',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                } else {
                    // Cálculo automático
                    $montoCuota = round($saldoFinanciar / $cantidad, 2);
                    $totalCalculado = $montoCuota * $cantidad;
                    $diferencia = round($saldoFinanciar - $totalCalculado, 2);

                    for ($i = 1; $i <= $cantidad; $i++) {
                        $monto = $montoCuota;
                        if ($i === $cantidad) {
                            $monto += $diferencia;
                        }

                        DB::table('pro_cuotas')->insert([
                            'cuota_control_id' => $pago->pago_id,
                            'cuota_numero' => $i,
                            'cuota_monto' => $monto,
                            'cuota_fecha_vencimiento' => $fechaBase->copy()->addMonths($i),
                            'cuota_estado' => 'PENDIENTE',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }

            } else { // PAGO UNICO (Transferencia, Cheque, Deposito)
                DB::table('pro_pagos')->where('pago_id', $pago->pago_id)->update([
                    'pago_tipo_pago' => 'UNICO',
                    // 'pago_metodo_pago' => $metodoPago, // Column does not exist
                    'pago_cantidad_cuotas' => 1,
                    'pago_abono_inicial' => 0,
                    'updated_at' => now()
                ]);

                // Si viene información de banco, crear registro en pagos_subidos
                if ($request->has('banco_id')) {
                    $estado = 'PENDIENTE_CARGA';
                    $path = null;

                    if ($request->hasFile('comprobante')) {
                        $file = $request->file('comprobante');
                        $filename = 'pago_' . $ventaId . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('comprobantes', $filename, 'public');
                        $estado = 'PENDIENTE_VALIDACION';
                    }

                    DB::table('pro_pagos_subidos')->insert([
                        'ps_venta_id' => $ventaId,
                        'ps_banco_id' => $request->banco_id,
                        'ps_monto' => $montoBase, // Usamos el monto base (pendiente)
                        'ps_fecha_pago' => $request->fecha_pago ?? now(),
                        'ps_no_comprobante' => $request->numero_autorizacion,
                        'ps_comprobante_path' => $path,
                        'ps_estado' => $estado,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Enviar correo a administradores
                    try {
                        $admins = \App\Models\User::whereHas('rol', function($q){
                            $q->whereIn('nombre', ['administrador', 'contador']);
                        })
                        ->where('user_situacion', 1)
                        ->get();

                        // Obtener datos del cliente para el correo
                        $venta = DB::table('pro_ventas')->where('ven_id', $ventaId)->first();
                        $cliente = null;
                        if ($venta) {
                             $cliente = DB::table('pro_clientes')->where('cliente_id', $venta->ven_cliente_id)->first();
                        }

                        $payload = [
                            'venta_id' => $ventaId,
                            'vendedor' => auth()->user()->name,
                            'cliente' => [
                                'nombre' => $cliente ? ($cliente->cliente_nombre1 . ' ' . $cliente->cliente_apellido1) : 'Cliente',
                                'email' => $cliente->cliente_email ?? 'No registrado'
                            ],
                            'fecha' => now()->format('d/m/Y H:i'),
                            'monto' => $montoBase,
                            'banco_nombre' => DB::table('pro_bancos')->where('banco_id', $request->banco_id)->value('banco_nombre'),
                            'banco_id' => $request->banco_id,
                            'referencia' => $request->numero_autorizacion,
                            'concepto' => 'Pago Único (Actualización) - Venta #' . $ventaId,
                            'cuotas' => 1,
                            'monto_total' => $montoBase
                        ];

                        foreach ($admins as $admin) {
                            if ($admin->email) {
                                \Illuminate\Support\Facades\Mail::to($admin->email)->send(new \App\Mail\NotificarpagoMail($payload, $path));
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error enviando correo de pago: ' . $e->getMessage());
                    }
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Método de pago actualizado correctamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando método de pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }
}