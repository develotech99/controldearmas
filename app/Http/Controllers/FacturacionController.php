<?php

namespace App\Http\Controllers;

use App\Models\Facturacion;
use App\Models\FacturacionDetalle;
use App\Services\FelService;
use App\Services\FelXmlBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FacturacionController extends Controller
{
    protected $felService;
    protected $xmlBuilder;

    public function __construct(FelService $felService, FelXmlBuilder $xmlBuilder)
    {
        $this->felService = $felService;
        $this->xmlBuilder = $xmlBuilder;
    }

    public function index()
    {
        return view('facturacion.index');
    }

    public function buscarNIT(Request $request)
    {
        $data = $request->validate([
            'nit' => ['required', 'string'],
        ], [
            'nit.required' => 'Necesita un NIT válido',
        ]);

        $nit = trim($data['nit']);

        if (strcasecmp($nit, 'CF') === 0) {
            return response()->json([
                'codigo' => 1,
                'nit' => 'CF',
                'nombre' => 'CONSUMIDOR FINAL',
            ]);
        }

        try {
            $json = $this->felService->consultarNit($nit);

            $nombre = $json['NombreEmisor']
                ?? $json['nombreEmisor']
                ?? $json['NombreReceptor']
                ?? $json['nombreReceptor']
                ?? $json['Nombre']
                ?? $json['nombre']
                ?? 'No encontrado';

            $nitDevuelto = $json['NitEmisor']
                ?? $json['nitEmisor']
                ?? $json['NitReceptor']
                ?? $json['nitReceptor']
                ?? $nit;

            return response()->json([
                'codigo' => 1,
                'nit' => (string) $nitDevuelto,
                'nombre' => $nombre,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Ocurrió un error al consultar el NIT',
                'detalle' => $e->getMessage(),
            ], 200);
        }
    }

    public function certificar(Request $request)
    {
        try {
            // Validar datos
            $validated = $request->validate([
                'fac_nit_receptor' => 'required|string',
                'fac_receptor_nombre' => 'required|string',
                'fac_receptor_direccion' => 'nullable|string',
                'fac_receptor_email' => 'nullable|email',
                'det_fac_producto_desc' => 'required|array|min:1',
                'det_fac_producto_desc.*' => 'required|string',
                'det_fac_cantidad' => 'required|array',
                'det_fac_cantidad.*' => 'required|numeric|min:0.01',
                'det_fac_precio_unitario' => 'required|array',
                'det_fac_precio_unitario.*' => 'required|numeric|min:0',
                'det_fac_descuento' => 'nullable|array',
                'det_fac_descuento.*' => 'nullable|numeric|min:0',
                'fac_venta_id' => 'nullable|integer|exists:pro_ventas,ven_id',
                'det_fac_producto_id' => 'nullable|array',
                'det_fac_producto_id.*' => 'nullable|integer|exists:pro_productos,producto_id',
            ]);

            DB::beginTransaction();

            // Preparar items
            $items = [];
            $subtotalNeto = 0;
            $ivaTotal = 0;
            $descuentoTotal = 0;

            for ($i = 0; $i < count($validated['det_fac_producto_desc']); $i++) {
                $cantidad = (float) $validated['det_fac_cantidad'][$i];
                $precio = (float) $validated['det_fac_precio_unitario'][$i];
                $descuento = (float) ($validated['det_fac_descuento'][$i] ?? 0);

                $totalItem = ($cantidad * $precio) - $descuento;
                $montoGravable = $totalItem / 1.12;
                $ivaItem = $totalItem - $montoGravable;

                $items[] = [
                    'descripcion' => $validated['det_fac_producto_desc'][$i],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'monto_gravable' => $montoGravable,
                    'iva' => $ivaItem,
                    'total' => $totalItem,
                ];

                $subtotalNeto += $montoGravable;
                $ivaTotal += $ivaItem;
                $descuentoTotal += $descuento;
            }

            $totalFactura = $subtotalNeto + $ivaTotal;

            // Generar referencia única
            $referencia = 'FACT-' . now()->format('YmdHis') . '-' . Str::random(4);

            // Preparar datos para XML
            $datosXml = [
                'receptor' => [
                    'nit' => $validated['fac_nit_receptor'],
                    'nombre' => $validated['fac_receptor_nombre'],
                    'direccion' => $validated['fac_receptor_direccion'] ?? '',
                ],
                'items' => $items,
                'totales' => [
                    'subtotal' => $subtotalNeto,
                    'iva' => $ivaTotal,
                    'total' => $totalFactura,
                ],
            ];

            // Generar XML
            $xml = $this->xmlBuilder->generarXmlFactura($datosXml);
            $xmlBase64 = base64_encode($xml);

            Log::info('FEL: XML generado', ['referencia' => $referencia, 'total' => $totalFactura]);

            // Certificar con FEL
            $respuesta = $this->felService->certificarDte($xmlBase64, $referencia);

            // 1) Validación de resultado
            if (!($respuesta['Resultado'] ?? false)) {
                throw new Exception('Error en certificación: ' . json_encode($respuesta['Errores'] ?? $respuesta));
            }

            // 2) Tomar el XML certificado (Base64) soportando diferentes 'casing'
            $xmlCertKey = $respuesta['XmlDteCertificado']
                ?? $respuesta['XMLDTECertificado']
                ?? $respuesta['xmlDteCertificado']
                ?? null;

            if (!$xmlCertKey) {
                throw new Exception('Certificación exitosa pero sin XML certificado en respuesta: ' . json_encode($respuesta));
            }

            $uuidResp   = $respuesta['UUID']   ?? $respuesta['uuid']   ?? null;
            $serieResp  = $respuesta['Serie']  ?? $respuesta['serie']  ?? null;
            $numeroResp = $respuesta['Numero'] ?? $respuesta['numero'] ?? null;
            $fechaCert  = $respuesta['FechaHoraCertificacion'] ?? $respuesta['fechaHoraCertificacion'] ?? now()->toDateTimeString();


            $storagePath = 'fel/xmls';
            $fecha = now()->format('Y/m');
            $dir = "{$storagePath}/{$fecha}";

            $xmlEnviadoPath     = "{$dir}/enviado_{$referencia}.xml";
            $xmlCertificadoPath = "{$dir}/certificado_{$referencia}.xml";

            $disk = Storage::disk('public');
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            $disk->put($xmlEnviadoPath, $xml);
            $disk->put($xmlCertificadoPath, base64_decode($xmlCertKey));

            // Guardar en BD
            $factura = Facturacion::create([
                'fac_uuid' => $uuidResp,
                'fac_referencia' => $referencia,
                'fac_serie' => $serieResp,
                'fac_numero' => $numeroResp,
                'fac_estado' => 'CERTIFICADO',
                'fac_tipo_documento' => 'FACT',

                'fac_nit_receptor' => $validated['fac_nit_receptor'],
                'fac_receptor_nombre' => $validated['fac_receptor_nombre'],
                'fac_receptor_direccion' => $validated['fac_receptor_direccion'] ?? null,
                'fac_receptor_email' => $validated['fac_receptor_email'] ?? null,

                'fac_fecha_emision' => now()->toDateString(),
                'fac_fecha_certificacion' => $fechaCert,

                'fac_subtotal' => $subtotalNeto,
                'fac_descuento' => $descuentoTotal,
                'fac_impuestos' => $ivaTotal,
                'fac_total' => $totalFactura,
                'fac_moneda' => 'GTQ',

                'fac_xml_enviado_path' => $xmlEnviadoPath,
                'fac_xml_certificado_path' => $xmlCertificadoPath,

                'fac_alertas' => $respuesta['Alertas'] ?? $respuesta['alertas'] ?? [],
                'fac_operacion' => 'WEB',
                'fac_vendedor' => auth()->user()->user_primer_nombre ?? 'Sistema',
                'fac_usuario_id' => auth()->id(),
                'fac_fecha_operacion' => now(),
                'fac_venta_id' => $validated['fac_venta_id'] ?? null,
            ]);

            // LOGICA DE INVENTARIO: Si hay venta asociada y es PENDIENTE
            if (!empty($validated['fac_venta_id'])) {
                $venta = DB::table('pro_ventas')->where('ven_id', $validated['fac_venta_id'])->first();
                
                if ($venta && $venta->ven_situacion === 'PENDIENTE') {
                    // 1. Marcar venta como ACTIVA
                    DB::table('pro_ventas')
                        ->where('ven_id', $venta->ven_id)
                        ->update(['ven_situacion' => 'ACTIVA']);

                    // 2. Marcar detalles como ACTIVOS
                    DB::table('pro_detalle_ventas')
                        ->where('det_ven_id', $venta->ven_id)
                        ->update(['det_situacion' => 'ACTIVO']);

                    // 3. Procesar SERIES y LOTES (Descontar stock)
                    $refVenta = 'VENTA-' . $venta->ven_id;
                    
                    // a) Series reservadas (mov_situacion = 3) -> Vendidas (mov_situacion = 1)
                    $seriesMovs = DB::table('pro_movimientos')
                        ->where('mov_documento_referencia', $refVenta)
                        ->where('mov_situacion', 3)
                        ->whereNotNull('mov_serie_id')
                        ->get();

                    foreach ($seriesMovs as $mov) {
                        // Actualizar serie a vendida
                        DB::table('pro_series_productos')
                            ->where('serie_id', $mov->mov_serie_id)
                            ->update(['serie_estado' => 'vendido', 'serie_situacion' => 1]);
                        
                        // Actualizar movimiento a confirmado
                        DB::table('pro_movimientos')
                            ->where('mov_id', $mov->mov_id)
                            ->update(['mov_situacion' => 1]);
                        
                        // Descontar de stock (reservado y total)
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_reservada', $mov->mov_cantidad);
                            
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_disponible', $mov->mov_cantidad);
                            
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_total', $mov->mov_cantidad);
                    }

                    // b) Lotes reservados -> Confirmados
                    $lotesMovs = DB::table('pro_movimientos')
                        ->where('mov_documento_referencia', $refVenta)
                        ->where('mov_situacion', 3)
                        ->whereNotNull('mov_lote_id')
                        ->get();

                    foreach ($lotesMovs as $mov) {
                        // Actualizar movimiento
                        DB::table('pro_movimientos')
                            ->where('mov_id', $mov->mov_id)
                            ->update(['mov_situacion' => 1]);

                        // Descontar de stock
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_reservada', $mov->mov_cantidad);
                            
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_disponible', $mov->mov_cantidad);
                            
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->decrement('stock_cantidad_total', $mov->mov_cantidad);
                    }
                }
            }


            // Guardar detalle
            foreach ($items as $index => $item) {
                FacturacionDetalle::create([
                    'det_fac_factura_id' => $factura->fac_id,
                    'det_fac_tipo' => 'B',
                    'det_fac_producto_id' => $validated['det_fac_producto_id'][$index] ?? null,
                    'det_fac_producto_desc' => $item['descripcion'],
                    'det_fac_cantidad' => $item['cantidad'],
                    'det_fac_unidad_medida' => 'UNI',
                    'det_fac_precio_unitario' => $item['precio_unitario'],
                    'det_fac_descuento' => $item['descuento'],
                    'det_fac_monto_gravable' => $item['monto_gravable'],
                    'det_fac_tipo_impuesto' => 'IVA',
                    'det_fac_impuesto' => $item['iva'],
                    'det_fac_total' => $item['total'],
                ]);
            }

            DB::commit();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Factura certificada exitosamente',
                'data' => [
                    'fac_id' => $factura->fac_id,
                    'uuid' => $respuesta['UUID'],
                    'serie' => $respuesta['Serie'],
                    'numero' => $respuesta['Numero'],
                    'fecha' => $respuesta['FechaHoraCertificacion'],
                    'total' => $totalFactura,
                ],
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error certificando factura', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al certificar factura',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerFacturas(Request $request)
    {
        $query = Facturacion::with('detalle');

        if ($request->filled('fecha_inicio')) {
            $query->where('fac_fecha_emision', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->where('fac_fecha_emision', '<=', $request->fecha_fin);
        }
        $facturas = $query->orderBy('fac_fecha_emision', 'desc')
            ->orderBy('fac_id', 'desc')
            ->get()
            ->map(function ($f) {
                $f->url_xml_enviado = $f->fac_xml_enviado_path ? asset('storage/' . $f->fac_xml_enviado_path) : null;
                $f->url_xml_certificado = $f->fac_xml_certificado_path ? asset('storage/' . $f->fac_xml_certificado_path) : null;
                return $f;
            });

        return response()->json([
            'codigo' => 1,
            'mensaje' => 'Facturas obtenidas',
            'data' => $facturas,
        ]);
    }

    public function vista($id)
    {
        $factura = Facturacion::with('detalle')->findOrFail($id);

        $emisor = [
            'nombre' => config('fel.emisor.nombre'),
            'comercial' => config('fel.emisor.nombre_comercial'),
            'nit' => config('fel.emisor.nit'),
            'direccion' => config('fel.emisor.direccion'),
            'municipio' => config('fel.emisor.municipio'),
            'departamento' => config('fel.emisor.departamento'),
            'pais' => config('fel.emisor.pais', 'GT'),
            'telefono' => config('fel.emisor.telefono', ''),
            'website' => config('fel.emisor.website', ''),
            'talonario' => config('fel.emisor.talonario', ''),
        ];

        return view('facturacion.factura', compact('factura', 'emisor'));
    }
    public function consultarDte($uuid)
    {
        try {
            Log::info('Consultando DTE', ['uuid' => $uuid]);

            $facturaLocal = Facturacion::where('fac_uuid', $uuid)->first();

            $respuesta = $this->felService->consultarDte($uuid);

            if ($respuesta['Resultado'] ?? false) {
                $respuesta['estado_local'] = $facturaLocal ? $facturaLocal->fac_estado : 'NO_REGISTRADO';

                if ($facturaLocal && $facturaLocal->fac_estado === 'ANULADO') {
                    $respuesta['fecha_anulacion'] = $facturaLocal->fac_fecha_anulacion
                        ? $facturaLocal->fac_fecha_anulacion->format('d/m/Y H:i:s')
                        : null;
                    $respuesta['motivo_anulacion'] = $facturaLocal->fac_motivo_anulacion;
                    $respuesta['anulado_por'] = $facturaLocal->anulador
                        ? $facturaLocal->anulador->name
                        : null;
                }

                return response()->json([
                    'codigo' => 1,
                    'mensaje' => 'DTE encontrado exitosamente',
                    'data' => $respuesta
                ]);
            } else {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'DTE no encontrado en el FEL',
                    'errores' => $respuesta['Errores'] ?? ['El documento no existe'],
                    'data' => $respuesta
                ], 404);
            }
        } catch (Exception $e) {
            Log::error('Error consultando DTE', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al consultar el DTE',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }

    public function anular($id)
    {
        try {
            DB::beginTransaction();

            $factura = Facturacion::with('detalle')->findOrFail($id);

            // Verificar que la factura no esté ya anulada
            if ($factura->fac_estado === 'ANULADO') {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'La factura ya está anulada'
                ], 400);
            }

            // Verificar que la factura esté certificada
            if ($factura->fac_estado !== 'CERTIFICADO') {
                return response()->json([
                    'codigo' => 0,
                    'mensaje' => 'Solo se pueden anular facturas certificadas'
                ], 400);
            }

            // Generar XML de anulación
            $xmlAnulacion = $this->xmlBuilder->generarXmlAnulacion($factura);
            $xmlAnulacionBase64 = base64_encode($xmlAnulacion);

            Log::info('FEL: Anulando factura', [
                'uuid' => $factura->fac_uuid,
                'factura_id' => $factura->fac_id
            ]);

            // Anular en FEL
            $respuesta = $this->felService->anularDte($xmlAnulacionBase64);

            // Validar respuesta de anulación
            if (!($respuesta['Resultado'] ?? false)) {
                throw new Exception('Error en anulación FEL: ' . json_encode($respuesta['Errores'] ?? $respuesta));
            }

            // Actualizar estado de la factura
            $factura->update([
                'fac_estado' => 'ANULADO',
                'fac_fecha_anulacion' => now(),
                'fac_motivo_anulacion' => 'Anulación solicitada por el usuario'
            ]);

            // Guardar XML de anulación
            $storagePath = 'fel/anulaciones';
            $fecha = now()->format('Y/m');
            $dir = "{$storagePath}/{$fecha}";

            $disk = Storage::disk('public');
            if (!$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            $xmlAnulacionPath = "{$dir}/anulacion_{$factura->fac_uuid}.xml";
            $disk->put($xmlAnulacionPath, $xmlAnulacion);

            // LOGICA DE REVERSION DE INVENTARIO
            if ($factura->fac_venta_id) {
                $venta = DB::table('pro_ventas')->where('ven_id', $factura->fac_venta_id)->first();
                
                if ($venta && $venta->ven_situacion === 'ACTIVA') {
                    // 1. Anular Venta
                    DB::table('pro_ventas')
                        ->where('ven_id', $venta->ven_id)
                        ->update(['ven_situacion' => 'ANULADA']);

                    // 2. Anular Detalles
                    DB::table('pro_detalle_ventas')
                        ->where('det_ven_id', $venta->ven_id)
                        ->update(['det_situacion' => 'ANULADA']);

                    $refVenta = 'VENTA-' . $venta->ven_id;

                    // 3. Revertir Series (Movimientos tipo 1 -> Anulados/Revertidos)
                    // Buscar movimientos confirmados de esta venta
                    $movimientos = DB::table('pro_movimientos')
                        ->where('mov_documento_referencia', $refVenta)
                        ->where('mov_situacion', 1)
                        ->get();

                    foreach ($movimientos as $mov) {
                        // Si es serie
                        if ($mov->mov_serie_id) {
                            // Devolver serie a disponible
                            DB::table('pro_series_productos')
                                ->where('serie_id', $mov->mov_serie_id)
                                ->update(['serie_estado' => 'disponible', 'serie_situacion' => 1]);
                        }
                        
                        // Si es lote (incrementar disponible en lote)
                        if ($mov->mov_lote_id) {
                             DB::table('pro_lotes')
                                ->where('lote_id', $mov->mov_lote_id)
                                ->increment('lote_cantidad_disponible', $mov->mov_cantidad);
                                
                             // Si estaba cerrado, abrirlo
                             DB::table('pro_lotes')
                                ->where('lote_id', $mov->mov_lote_id)
                                ->update(['lote_situacion' => 1]);
                        }

                        // Revertir Stock Actual (Incrementar)
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->increment('stock_cantidad_disponible', $mov->mov_cantidad);
                            
                        DB::table('pro_stock_actual')
                            ->where('stock_producto_id', $mov->mov_producto_id)
                            ->increment('stock_cantidad_total', $mov->mov_cantidad);

                        // Marcar movimiento como anulado (o crear contra-movimiento)
                        // Aquí optamos por marcar el movimiento original como anulado (situacion 0)
                        // Ojo: Si se prefiere historial, crear un nuevo movimiento de ingreso por anulación.
                        // Por simplicidad y consistencia con "ANULADA", lo marcamos como 0 o creamos reingreso.
                        // Vamos a crear un movimiento de anulación para trazabilidad.
                        
                        DB::table('pro_movimientos')->insert([
                            'mov_producto_id' => $mov->mov_producto_id,
                            'mov_tipo' => 'anulacion_venta',
                            'mov_origen' => 'Factura Anulada ' . $factura->fac_referencia,
                            'mov_destino' => 'Bodega',
                            'mov_cantidad' => $mov->mov_cantidad, // Positivo para ingreso
                            'mov_fecha' => now(),
                            'mov_usuario_id' => auth()->id(),
                            'mov_serie_id' => $mov->mov_serie_id,
                            'mov_lote_id' => $mov->mov_lote_id,
                            'mov_documento_referencia' => 'ANUL-' . $factura->fac_referencia,
                            'mov_observaciones' => 'Reingreso por anulación de factura',
                            'mov_situacion' => 1
                        ]);
                        
                        // Actualizar el movimiento original a anulado para que no cuente doble si se recalcula
                         DB::table('pro_movimientos')
                            ->where('mov_id', $mov->mov_id)
                            ->update(['mov_situacion' => 0]); // 0 = Anulado/Inactivo
                    }
                }
            }

            DB::commit();

            Log::info('FEL: Factura anulada exitosamente', [
                'uuid' => $factura->fac_uuid,
                'factura_id' => $factura->fac_id
            ]);

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'Factura anulada exitosamente',
                'data' => [
                    'uuid' => $factura->fac_uuid,
                    'estado' => 'ANULADO'
                ]
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Error anulando factura', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al anular la factura',
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
    
    public function buscarVenta(Request $request)
    {
        $busqueda = trim($request->query('q', ''));
        
        if (strlen($busqueda) < 2) {
            return response()->json([
                'codigo' => 1,
                'data' => []
            ]);
        }

        $ventas = DB::table('pro_ventas as v')
            ->join('pro_clientes as c', 'v.ven_cliente', '=', 'c.cliente_id')
            ->join('users as u', 'v.ven_user', '=', 'u.user_id')
            ->leftJoin('pro_detalle_ventas as d', 'v.ven_id', '=', 'd.det_ven_id')
            ->leftJoin('pro_productos as p', 'd.det_producto_id', '=', 'p.producto_id')
            ->leftJoin('pro_movimientos as m', function($join) {
                $join->on('m.mov_producto_id', '=', 'p.producto_id')
                     ->whereRaw("m.mov_documento_referencia = CONCAT('VENTA-', v.ven_id)");
            })
            ->leftJoin('pro_series_productos as s', 'm.mov_serie_id', '=', 's.serie_id')
            ->where('v.ven_situacion', 'PENDIENTE') // Solo ventas pendientes de facturar
            ->where(function($q) use ($busqueda) {
                $q->where('v.ven_id', $busqueda)
                  ->orWhere('c.cliente_nombre1', 'LIKE', "%{$busqueda}%")
                  ->orWhere('c.cliente_apellido1', 'LIKE', "%{$busqueda}%")
                  ->orWhere('c.cliente_nit', 'LIKE', "%{$busqueda}%")
                  ->orWhere('c.cliente_nom_empresa', 'LIKE', "%{$busqueda}%")
                  ->orWhere('p.pro_codigo_sku', 'LIKE', "%{$busqueda}%")
                  ->orWhere('s.serie_numero_serie', 'LIKE', "%{$busqueda}%");
            })
            ->select(
                'v.ven_id',
                'v.ven_fecha',
                'v.ven_total_vendido',
                'c.cliente_nombre1',
                'c.cliente_apellido1',
                'c.cliente_nom_empresa',
                'c.cliente_nit',
                'c.cliente_direccion',
                'c.cliente_correo'
            )
            ->distinct()
            ->limit(10)
            ->get();

        // Cargar detalles para cada venta encontrada
        $resultados = $ventas->map(function($venta) {
            $detalles = DB::table('pro_detalle_ventas as d')
                ->join('pro_productos as p', 'd.det_producto_id', '=', 'p.producto_id')
                ->where('d.det_ven_id', $venta->ven_id)
                ->select(
                    'd.det_producto_id',
                    'p.producto_nombre',
                    'd.det_cantidad',
                    'd.det_precio',
                    'd.det_descuento'
                )
                ->get();

            $venta->detalles = $detalles;
            return $venta;
        });

        return response()->json([
            'codigo' => 1,
            'data' => $resultados
        ]);
    }
}
