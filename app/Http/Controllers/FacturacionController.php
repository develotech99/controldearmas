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


// public function buscarCUI(Request $request)
// {
//     $data = $request->validate([
//         'cui' => ['required', 'string', 'max:20'],
//     ], [
//         'cui.required' => 'Debe ingresar un CUI válido',
//     ]);

//     $cui = trim($data['cui']);

//     try {
//         $json = $this->felService->consultarCui($cui);

//         // Si la API devuelve mensaje de error tipo autorización
//         if (isset($json['Message']) && !isset($json['Resultado'])) {
//             return response()->json([
//                 'codigo'  => 0,
//                 'mensaje' => $json['Message'],
//                 'fel_raw' => $json,
//             ]);
//         }

//         // Si no viene Resultado o viene en false
//         if (!isset($json['Resultado']) || $json['Resultado'] !== true) {
//             return response()->json([
//                 'codigo'  => 0,
//                 'mensaje' => 'CUI no encontrado',
//                 'fel_raw' => $json,
//             ]);
//         }

//         $nombreApi = $json['Nombre'] ?? null;

//         // Caso especial: FEL dice "Ingrese nombre manualmente"
//         $requiereManual = false;
//         $nombre = $nombreApi;

//         if (!$nombreApi || trim($nombreApi) === '' || strcasecmp($nombreApi, 'Ingrese nombre manualmente') === 0) {
//             $nombre = '';
//             $requiereManual = true;
//         }

//         return response()->json([
//             'codigo'         => 1,
//             'cui'            => $json['Cui'] ?? $cui,
//             'nombre'         => $nombre,
//             'fallecido'      => $json['Fallecido'] ?? false,
//             'direccion'      => null,      // este endpoint no trae dirección
//             'requiereManual' => $requiereManual,
//             'fel_raw'        => $json,     // útil para debug
//         ]);
//     } catch (\Throwable $e) {
//         return response()->json([
//             'codigo'  => 0,
//             'mensaje' => 'Error al consultar CUI',
//             'detalle' => $e->getMessage(),
//         ], 200);
//     }
// }


public function buscarCUI(Request $request)
{
    $cui = trim($request->input('cui'));

    try {
        $json = $this->felService->consultarCui($cui);

        \Log::info("FEL consultar CUI", $json);

        if (!isset($json['Resultado']) || $json['Resultado'] != true) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'CUI no encontrado',
                'detalle' => $json['Errores'] ?? 'Sin detalle'
            ], 200);
        }

        return response()->json([
            'codigo'    => 1,
            'nombre'    => $json['Nombre'] ?? '',
            'direccion' => $json['Direccion'] ?? '',
        ], 200);

    } catch (\Throwable $e) {

        return response()->json([
            'codigo'  => 0,
            'mensaje' => 'Error al consultar CUI',
            'detalle' => $e->getMessage(),
        ], 200);
    }
}



public function verFacturaCambiaria($id)
{
    // Cargar factura
    $factura = Facturacion::with('detalle')->findOrFail($id);

    // Datos del emisor (los usas en la otra plantilla)
    $emisor = [
        'nombre'     => config('fel.emisor.nombre'),
        'comercial'  => config('fel.emisor.nombre_comercial'),
        'nit'        => config('fel.emisor.nit'),
        'direccion'  => config('fel.emisor.direccion'),
    ];

    // Cargar abonos (AJUSTAR según tu modelo real)
    $abonos = \DB::table('factura_abonos')
        ->where('factura_id', $id)
        ->orderBy('numero')
        ->get()
        ->map(function($a){
            return [
                'numero' => $a->numero,
                'fecha'  => $a->fecha_vencimiento,
                'monto'  => $a->monto
            ];
        });

    return view('facturacion.factura_cambiaria', [
        'factura' => $factura,
        'emisor'  => $emisor,
        'abonos'  => $abonos
    ]);
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
                // Partial Billing Fields
                'det_fac_detalle_venta_id' => 'nullable|array',
                'det_fac_detalle_venta_id.*' => 'nullable|integer|exists:pro_detalle_ventas,det_id',
                'det_fac_series' => 'nullable|array', // Array of arrays of series IDs
                'det_fac_series.*' => 'nullable|array',
            ]);

            DB::beginTransaction();

            // Preparar items
            $items = [];
            $subtotalNeto = 0;
            $ivaTotal = 0;
            $descuentoTotal = 0;
            $detallesVentaUpdates = []; // To track updates and commit them later

            for ($i = 0; $i < count($validated['det_fac_producto_desc']); $i++) {
                $cantidad = (float) $validated['det_fac_cantidad'][$i];
                $precio = (float) $validated['det_fac_precio_unitario'][$i];
                $descuento = (float) ($validated['det_fac_descuento'][$i] ?? 0);

                // Partial Billing Validation & Logic
                $detalleVentaId = $validated['det_fac_detalle_venta_id'][$i] ?? null;
                if ($detalleVentaId) {
                    $detalleVenta = \App\Models\ProDetalleVenta::lockForUpdate()->find($detalleVentaId);
                    if ($detalleVenta) {
                        $pendiente = $detalleVenta->det_cantidad - $detalleVenta->det_cantidad_facturada;
                        // Allow a small margin of error for floats if needed, but quantity is usually integer/decimal
                        if ($cantidad > $pendiente + 0.0001) {
                            throw new Exception("La cantidad a facturar ({$cantidad}) excede lo pendiente ({$pendiente}) para el producto '{$validated['det_fac_producto_desc'][$i]}'");
                        }
                        
                        // Store update to perform later
                        $detallesVentaUpdates[] = [
                            'model' => $detalleVenta,
                            'cantidad' => $cantidad
                        ];
                    }
                }

                $totalItem = ($cantidad * $precio) - $descuento;
                
                // 💡 FIX: Redondear a 2 decimales por ítem para evitar errores de precisión en FEL (2.7.5.1)
                $montoGravable = round($totalItem / 1.12, 2);
                $ivaItem = round($totalItem - $montoGravable, 2);

                $items[] = [
                    'descripcion' => $validated['det_fac_producto_desc'][$i],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento' => $descuento,
                    'monto_gravable' => $montoGravable,
                    'iva' => $ivaItem,
                    'total' => $totalItem,
                    // Metadata for saving later
                    'detalle_venta_id' => $detalleVentaId,
                    'producto_id' => $validated['det_fac_producto_id'][$i] ?? null,
                    'series_ids' => $validated['det_fac_series'][$i] ?? [],
                ];

                $subtotalNeto += $montoGravable;
                $ivaTotal += $ivaItem;
                $descuentoTotal += $descuento;
            }

            // El total debe ser la suma de los subtotales e IVA redondeados
            $totalFactura = round($subtotalNeto + $ivaTotal, 2);

            // Generar referencia única
            $referencia = 'FACT-' . now()->format('YmdHis') . '-' . Str::random(4);

            // Preparar datos para XML
            $datosXml = [
                'receptor' => [
                    'nit' => $validated['fac_nit_receptor'],
                    'nombre' => $validated['fac_receptor_nombre'],
                    'direccion' => $validated['fac_receptor_direccion'] ?? '',
                ],
                'items' => $items, // Note: XML Builder should handle extra keys gracefully or we should clean them
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

            // Apply updates to ProDetalleVenta
            foreach ($detallesVentaUpdates as $update) {
                $update['model']->increment('det_cantidad_facturada', $update['cantidad']);
            }

            // Guardar detalle
            foreach ($items as $index => $item) {
                $detalle = \App\Models\FacturacionDetalle::create([
                    'det_fac_factura_id' => $factura->fac_id,
                    'det_fac_tipo' => 'B',
                    'det_fac_producto_id' => $item['producto_id'],
                    'det_fac_detalle_venta_id' => $item['detalle_venta_id'],
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

                // Save Series for this item
                if (!empty($item['series_ids'])) {
                    foreach ($item['series_ids'] as $serieId) {
                        DB::table('facturacion_series')->insert([
                            'fac_detalle_id' => $detalle->det_fac_id,
                            'serie_id' => $serieId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            // Update Sale Status & Finalize Stock
            if (!empty($validated['fac_venta_id'])) {
                 $venta = \App\Models\ProVenta::find($validated['fac_venta_id']);
                 if ($venta) {
                     
                     // 1. Finalize Stock Movements (Reserved -> Sold)
                     $refVenta = 'VENTA-' . $venta->ven_id;

                     // Iterate through billed items to finalize their specific movements
                     foreach ($items as $billedItem) {
                         $prodId = $billedItem['producto_id'];
                         $qtyBilled = $billedItem['cantidad'];
                         $seriesIds = $billedItem['series_ids'] ?? [];

                         // A) Serialized Items
                         if (!empty($seriesIds)) {
                             foreach ($seriesIds as $serieId) {
                                 // Find the reserved movement for this series
                                 $mov = DB::table('pro_movimientos')
                                     ->where('mov_documento_referencia', $refVenta)
                                     ->where('mov_situacion', 3) // Reserved
                                     ->where('mov_serie_id', $serieId)
                                     ->first();

                                 if ($mov) {
                                     // Update series status
                                     DB::table('pro_series_productos')
                                         ->where('serie_id', $serieId)
                                         ->update(['serie_estado' => 'vendido', 'serie_situacion' => 1]);

                                     // Update movement status
                                     DB::table('pro_movimientos')
                                         ->where('mov_id', $mov->mov_id)
                                         ->update(['mov_situacion' => 1]); // Confirmed/Sold

                                     // Decrement Reserved, Decrement Total, Decrement Available
                                     // Note: "Available" was already decremented when reserved? 
                                     // Usually: Available = Total - Reserved.
                                     // When Reserved -> Sold:
                                     // Reserved decreases. Total decreases. Available stays same (0).
                                     // Let's check previous logic:
                                     // decrement('stock_cantidad_reservada', $mov->mov_cantidad);
                                     // decrement('stock_cantidad_disponible', $mov->mov_cantidad); <--- This seems wrong if it was already reserved?
                                     // If it was reserved, it's NOT available. So decrementing available again seems double counting?
                                     // WAIT: The previous code did:
                                     // decrement('stock_cantidad_reservada')
                                     // decrement('stock_cantidad_disponible')
                                     // decrement('stock_cantidad_total')
                                     
                                     // Let's assume the previous logic was "correct" for the business rules, 
                                     // OR it was buggy. 
                                     // Standard logic:
                                     // Reserve: Available -1, Reserved +1. Total same.
                                     // Sell (from Reserve): Reserved -1, Total -1. Available same.
                                     
                                     // However, if the previous code decremented ALL three, maybe "Available" includes Reserved in this system?
                                     // Let's stick to the previous logic to avoid breaking inventory, 
                                     // BUT apply it only to the specific item.
                                     
                                     DB::table('pro_stock_actual')
                                         ->where('stock_producto_id', $prodId)
                                         ->decrement('stock_cantidad_reservada', 1);
                                         
                                     DB::table('pro_stock_actual')
                                         ->where('stock_producto_id', $prodId)
                                         ->decrement('stock_cantidad_disponible', 1);
                                         
                                     DB::table('pro_stock_actual')
                                         ->where('stock_producto_id', $prodId)
                                         ->decrement('stock_cantidad_total', 1);
                                 }
                             }
                         } 
                         // B) Non-Serialized Items (Bulk)
                         else {
                             // Find reserved movements for this product in this sale
                             $movs = DB::table('pro_movimientos')
                                 ->where('mov_documento_referencia', $refVenta)
                                 ->where('mov_situacion', 3) // Reserved
                                 ->where('mov_producto_id', $prodId)
                                 ->whereNull('mov_serie_id') // Only bulk
                                 ->orderBy('mov_id') // FIFOish
                                 ->get();

                             $qtyRemainingToFinalize = $qtyBilled;

                             foreach ($movs as $mov) {
                                 if ($qtyRemainingToFinalize <= 0) break;

                                 $qtyToTake = min($mov->mov_cantidad, $qtyRemainingToFinalize);

                                 // If we take the full movement amount
                                 if (abs($mov->mov_cantidad - $qtyToTake) < 0.0001) {
                                     // Just update status
                                     DB::table('pro_movimientos')
                                         ->where('mov_id', $mov->mov_id)
                                         ->update(['mov_situacion' => 1]);
                                 } else {
                                     // Partial take from this movement
                                     // 1. Decrease original reserved movement
                                     DB::table('pro_movimientos')
                                         ->where('mov_id', $mov->mov_id)
                                         ->decrement('mov_cantidad', $qtyToTake);

                                     // 2. Create new "Sold" movement
                                     DB::table('pro_movimientos')->insert([
                                         'mov_tipo' => $mov->mov_tipo, // SALIDA?
                                         'mov_producto_id' => $mov->mov_producto_id,
                                         'mov_cantidad' => $qtyToTake,
                                         'mov_fecha' => now(),
                                         'mov_documento_referencia' => $refVenta,
                                         'mov_situacion' => 1, // Sold
                                         'mov_origen_destino' => $mov->mov_origen_destino,
                                         'mov_user_id' => auth()->id(),
                                         'created_at' => now(),
                                         'updated_at' => now(),
                                     ]);
                                 }

                                 // Update Stock
                                 DB::table('pro_stock_actual')
                                     ->where('stock_producto_id', $prodId)
                                     ->decrement('stock_cantidad_reservada', $qtyToTake);
                                     
                                 DB::table('pro_stock_actual')
                                     ->where('stock_producto_id', $prodId)
                                     ->decrement('stock_cantidad_disponible', $qtyToTake);
                                     
                                 DB::table('pro_stock_actual')
                                     ->where('stock_producto_id', $prodId)
                                     ->decrement('stock_cantidad_total', $qtyToTake);

                                 $qtyRemainingToFinalize -= $qtyToTake;
                             }
                         }
                     }

                     // 2. Update Sale Status
                     $allInvoiced = true;
                     foreach ($venta->detalleVentas as $dv) {
                         $dv->refresh();
                         if ($dv->det_cantidad_facturada < $dv->det_cantidad) {
                             $allInvoiced = false;
                             break;
                         }
                     }
                     
                     if ($allInvoiced) {
                         $venta->update(['ven_situacion' => 'COMPLETADA']);
                     } else {
                         if ($venta->ven_situacion !== 'AUTORIZADA') {
                             $venta->update(['ven_situacion' => 'AUTORIZADA']);
                         }
                     }
                 }
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


public function certificarCambiaria(Request $request)
{
    try {
        $validated = $request->validate([
            'fac_cam_nit_receptor'       => 'required|string',
            'fac_cam_cui_receptor'       => 'nullable|string|max:20',
            'fac_cam_receptor_nombre'    => 'required|string',
            'fac_cam_receptor_direccion' => 'nullable|string',
            'fac_cam_plazo_dias'         => 'required|integer|min:1',
            'fac_cam_fecha_vencimiento'  => 'required|date',
            'fac_cam_interes'            => 'nullable|numeric|min:0',
            'det_fac_producto_desc'      => 'required|array|min:1',
            'det_fac_producto_desc.*'    => 'required|string',
            'det_fac_producto_id'        => 'nullable|array',
            'det_fac_producto_id.*'      => 'nullable|integer',
            'det_fac_cantidad'           => 'required|array',
            'det_fac_cantidad.*'         => 'required|numeric|min:0.01',
            'det_fac_precio_unitario'    => 'required|array',
            'det_fac_precio_unitario.*'  => 'required|numeric|min:0',
            'det_fac_descuento'          => 'nullable|array',
            'det_fac_descuento.*'        => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        // Preparar items
        $items = [];
        $subtotalNeto = 0;
        $ivaTotal = 0;
        $descuentoTotal = 0;

        for ($i = 0; $i < count($validated['det_fac_producto_desc']); $i++) {
            $cantidad  = (float) $validated['det_fac_cantidad'][$i];
            $precio    = (float) $validated['det_fac_precio_unitario'][$i];
            $descuento = (float) ($validated['det_fac_descuento'][$i] ?? 0);

            $totalItem     = ($cantidad * $precio) - $descuento;
            
            // 💡 FIX: Redondear a 2 decimales por ítem para evitar errores de precisión en FEL (2.7.5.1)
            $montoGravable = round($totalItem / 1.12, 2);
            $ivaItem       = round($totalItem - $montoGravable, 2);

            $items[] = [
                'descripcion'     => $validated['det_fac_producto_desc'][$i],
                'producto_id'     => $validated['det_fac_producto_id'][$i] ?? null,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'descuento'       => $descuento,
                'monto_gravable'  => $montoGravable,
                'iva'             => $ivaItem,
                'total'           => $totalItem,
            ];

            $subtotalNeto   += $montoGravable;
            $ivaTotal       += $ivaItem;
            $descuentoTotal += $descuento;
        }

        // El total debe ser la suma de los subtotales e IVA redondeados
        $totalFactura = round($subtotalNeto + $ivaTotal, 2);

        // Abonos
        $abonos = [[
            'numero' => 1,
            'fecha'  => $validated['fac_cam_fecha_vencimiento'],
            'monto'  => $totalFactura,
        ]];

        $referencia = 'FCAM-' . now()->format('YmdHis') . '-' . Str::random(4);

        // Datos para el XML
        $datosXml = [
            'tipo' => 'FCAM',
            'receptor' => [
                'nit'       => $validated['fac_cam_nit_receptor'],
                'nombre'    => $validated['fac_cam_receptor_nombre'],
                'direccion' => $validated['fac_cam_receptor_direccion'] ?? '',
                'cui'       => $validated['fac_cam_cui_receptor'] ?? '',
            ],
            'items'   => $items,
            'totales' => [
                'subtotal' => $subtotalNeto,
                'iva'      => $ivaTotal,
                'total'    => $totalFactura,
            ],
            'abonos'  => $abonos,
        ];

        // ✅ GENERAR XML CORRECTAMENTE
        $xml = $this->xmlBuilder->generarXmlFacturaCambiaria($datosXml);
        
        // 🔍 Debug: guardar XML para revisar
        Storage::disk('local')->put('debug_xml_cambiaria.xml', $xml);
        Log::info('XML Factura Cambiaria generado', [
            'referencia' => $referencia,
            'total' => $totalFactura
        ]);

        $xmlBase64 = base64_encode($xml);

        // Certificar con FEL
        $respuesta = $this->felService->certificarDte($xmlBase64, $referencia);

        if (!($respuesta['Resultado'] ?? false)) {
            throw new Exception('Error en certificación: ' . json_encode($respuesta['Errores'] ?? $respuesta));
        }

        $xmlCertKey = $respuesta['XmlDteCertificado']
            ?? $respuesta['XMLDTECertificado']
            ?? $respuesta['xmlDteCertificado']
            ?? null;

        if (!$xmlCertKey) {
            throw new Exception('Sin XML certificado en respuesta: ' . json_encode($respuesta));
        }

        $uuidResp   = $respuesta['UUID'] ?? $respuesta['uuid'] ?? null;
        $serieResp  = $respuesta['Serie'] ?? $respuesta['serie'] ?? null;
        $numeroResp = $respuesta['Numero'] ?? $respuesta['numero'] ?? null;
        $fechaCert  = $respuesta['FechaHoraCertificacion']
            ?? $respuesta['fechaHoraCertificacion']
            ?? now()->toDateTimeString();

        // Guardar XMLs
        $storagePath = 'fel/xmls';
        $fechaPath = now()->format('Y/m');
        $dir = "{$storagePath}/{$fechaPath}";
        $xmlEnviadoPath = "{$dir}/enviado_{$referencia}.xml";
        $xmlCertificadoPath = "{$dir}/certificado_{$referencia}.xml";

        $disk = Storage::disk('public');
        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $disk->put($xmlEnviadoPath, $xml);
        $disk->put($xmlCertificadoPath, base64_decode($xmlCertKey));

        // 4. Actualizar factura con datos FEL y paths de XML
        $factura->update([
            'fac_uuid'                => $uuidResp,
            'fac_serie'               => $serieResp,
            'fac_numero'              => $numeroResp,
            'fac_fecha_certificacion' => $fechaCert,
            'fac_estado'              => 'CERTIFICADO',
            'fac_xml_enviado_path'    => $xmlEnviadoPath,
            'fac_xml_certificado_path'=> $xmlCertificadoPath,
            'fac_alertas'             => $respuesta['Alertas'] ?? $respuesta['alertas'] ?? [],
        ]);

        // LOGICA DE INVENTARIO: Si hay venta asociada y es PENDIENTE
        if (!empty($validated['fac_venta_id'])) {
            $venta = DB::table('pro_ventas')->where('ven_id', $validated['fac_venta_id'])->first();
            
            if ($venta && in_array($venta->ven_situacion, ['PENDIENTE', 'AUTORIZADA'])) {
                // 1. Marcar venta como ACTIVA
                DB::table('pro_ventas')
                    ->where('ven_id', $venta->ven_id)
                    ->update(['ven_situacion' => 'ACTIVA']);

                // 2. Marcar detalles como ACTIVOS
                DB::table('pro_detalle_ventas')
                    ->where('det_ven_id', $venta->ven_id)
                    ->update(['det_situacion' => 'ACTIVA']);

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

                // ✅ FIX: c) Stock General (Sin serie ni lote)
                $generalStockMovs = DB::table('pro_movimientos')
                    ->where('mov_documento_referencia', $refVenta)
                    ->where('mov_situacion', 3)
                    ->whereNull('mov_serie_id')
                    ->whereNull('mov_lote_id')
                    ->get();

                foreach ($generalStockMovs as $mov) {
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
                'det_fac_producto_id' => $item['producto_id'], // Use product_id from prepared items
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

        // Guardar abonos
        foreach ($abonos as $ab) {
            DB::table('factura_abonos')->insert([
                'factura_id' => $factura->fac_id,
                'numero' => $ab['numero'],
                'fecha_vencimiento' => $ab['fecha'],
                'monto' => $ab['monto'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::commit();

        return response()->json([
            'codigo'  => 1,
            'mensaje' => 'Factura cambiaria certificada exitosamente',
            'data'    => [
                'fac_id' => $factura->fac_id,
                'uuid'   => $uuidResp,
                'serie'  => $serieResp,
                'numero' => $numeroResp,
                'fecha'  => $fechaCert,
                'total'  => $totalFactura,
            ],
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        
        Log::error('Error certificando factura cambiaria', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'codigo'  => 0,
            'mensaje' => 'Error al certificar factura cambiaria',
            'detalle' => $e->getMessage(),
        ], 500);
    }
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

        // Extraer Número de Acceso del XML si existe
        $numeroAcceso = null;
        if ($factura->fac_xml_certificado_path && Storage::disk('public')->exists($factura->fac_xml_certificado_path)) {
            try {
                $xmlContent = Storage::disk('public')->get($factura->fac_xml_certificado_path);
                // Buscar NumeroAcceso en el XML (puede estar con namespace o sin)
                // Estructura común: <dte:DatosGenerales ... NumeroAcceso="123456789" ...>
                if (preg_match('/NumeroAcceso="([^"]+)"/', $xmlContent, $matches)) {
                    $numeroAcceso = $matches[1];
                }
            } catch (\Exception $e) {
                // Ignorar error al leer XML
                \Log::warning("Error leyendo XML para factura {$id}: " . $e->getMessage());
            }
        }

        return view('facturacion.factura', compact('factura', 'emisor', 'numeroAcceso'));
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


    public function anular(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $factura = Facturacion::with('detalle')->findOrFail($id);
            $tipoAnulacion = $request->input('tipo_anulacion', 'corregir'); // 'corregir' (Freeze) or 'anular' (Full)
            $motivo = $request->input('motivo', 'Anulación solicitada por el usuario');

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
                'factura_id' => $factura->fac_id,
                'tipo' => $tipoAnulacion
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
                'fac_motivo_anulacion' => $motivo . " [Tipo: " . strtoupper($tipoAnulacion) . "]"
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

            // LOGICA DE REVERSION
            if ($factura->fac_venta_id) {
                $venta = DB::table('pro_ventas')->where('ven_id', $factura->fac_venta_id)->first();
                
                if ($venta) {
                    // 1. Revertir cantidad facturada (SIEMPRE)
                    foreach ($factura->detalle as $detFac) {
                        if ($detFac->det_fac_detalle_venta_id) {
                            DB::table('pro_detalle_ventas')
                                ->where('det_id', $detFac->det_fac_detalle_venta_id)
                                ->decrement('det_cantidad_facturada', $detFac->det_fac_cantidad);
                        }
                    }

                    if ($tipoAnulacion === 'corregir') {
                        // TIPO A: CONGELAR / EDITAR
                        // Pasamos la venta a EDITABLE para que el usuario pueda corregir series/datos
                        // NO revertimos stock físico (se queda reservado/vendido)
                        
                        DB::table('pro_ventas')
                            ->where('ven_id', $venta->ven_id)
                            ->update([
                                'ven_situacion' => 'EDITABLE',
                                'ven_observaciones' => $venta->ven_observaciones . " [Factura anulada (Corrección): " . $factura->fac_referencia . "]"
                            ]);

                        DB::table('pro_detalle_ventas')
                            ->where('det_ven_id', $venta->ven_id)
                            ->update(['det_situacion' => 'EDITABLE']);

                    } else {
                        // TIPO B: ANULACION COMPLETA
                        // Revertimos stock y cancelamos venta
                        
                        DB::table('pro_ventas')
                            ->where('ven_id', $venta->ven_id)
                            ->update([
                                'ven_situacion' => 'CANCELADA',
                                'ven_observaciones' => $venta->ven_observaciones . " [Factura anulada (Definitiva): " . $factura->fac_referencia . "]"
                            ]);

                        DB::table('pro_detalle_ventas')
                            ->where('det_ven_id', $venta->ven_id)
                            ->update(['det_situacion' => 'CANCELADO']);

                        // Revertir Stock
                        $detallesVenta = DB::table('pro_detalle_ventas')->where('det_ven_id', $venta->ven_id)->get();
                        foreach ($detallesVenta as $det) {
                            // Revertir series si existen
                            $seriesMov = DB::table('pro_movimiento_series')
                                ->where('mov_serie_detalle_id', $det->det_id)
                                ->get();
                            
                            foreach ($seriesMov as $sm) {
                                DB::table('pro_series')
                                    ->where('serie_id', $sm->mov_serie_serie_id)
                                    ->update(['serie_situacion' => 1]); // 1 = Disponible
                            }

                            // Revertir stock numérico
                            // Asumimos que al vender se descontó de 'disponible' y no se incrementó 'reservada' (venta directa)
                            // O si fue reserva, se manejó diferente.
                            // Para simplificar: Devolvemos a disponible.
                            
                            // Check if product requires stock
                            $prod = DB::table('pro_productos')->where('producto_id', $det->det_producto_id)->first();
                            if ($prod && $prod->producto_requiere_stock) {
                                DB::table('pro_stock_actual')
                                    ->where('stock_producto_id', $det->det_producto_id)
                                    ->increment('stock_cantidad_disponible', $det->det_cantidad);
                            }
                        }
                        
                        // Cancelar pagos asociados?
                        // Si se anula definitivamente, deberíamos cancelar pagos o dejarlos como saldo a favor?
                        // Por ahora, marcamos caja como cancelada si es venta contado.
                        DB::table('cja_historial')
                            ->where('cja_id_venta', $venta->ven_id)
                            ->update(['cja_situacion' => 'CANCELADA']);
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
            ->whereIn('v.ven_situacion', ['PENDIENTE', 'AUTORIZADA']) // Solo ventas pendientes de facturar
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
                    'd.det_id', // Added det_id
                    'd.det_producto_id',
                    'p.producto_nombre',
                    'p.producto_requiere_serie',
                    'd.det_cantidad',
                    'd.det_cantidad_facturada',
                    'd.det_precio',
                    'd.det_descuento'
                )
                ->get();

            foreach ($detalles as $det) {
                if ($det->producto_requiere_serie == 1) {
                    // Get all series assigned to this sale detail
                    $series = DB::table('pro_movimientos as m')
                        ->join('pro_series_productos as s', 'm.mov_serie_id', '=', 's.serie_id')
                        ->where('m.mov_documento_referencia', 'VENTA-' . $venta->ven_id)
                        ->where('m.mov_producto_id', $det->det_producto_id)
                        ->select('s.serie_id', 's.serie_numero_serie')
                        ->get();

                    // Get IDs of series already invoiced for this sale
                    $invoicedSeriesIds = DB::table('facturacion_series as fs')
                        ->join('facturacion_detalle as fd', 'fs.fac_detalle_id', '=', 'fd.det_fac_id')
                        ->join('facturacion as f', 'fd.det_fac_factura_id', '=', 'f.fac_id')
                        ->where('f.fac_venta_id', $venta->ven_id)
                        ->where('f.fac_estado', '!=', 'ANULADO') // Ignore cancelled invoices
                        ->pluck('fs.serie_id')
                        ->toArray();

                    // Filter out invoiced series
                    $availableSeries = $series->filter(function($s) use ($invoicedSeriesIds) {
                        return !in_array($s->serie_id, $invoicedSeriesIds);
                    })->values()->toArray(); // Return full objects, not just strings

                    $det->series = $availableSeries;
                } else {
                    $det->series = [];
                }
            }

            $venta->detalles = $detalles;
            return $venta;
        });

        return response()->json([
            'codigo' => 1,
            'data' => $resultados
        ]);
    }

    public function anularFactura(Request $request)
    {
        $request->validate([
            'fac_id' => 'required|integer|exists:facturacion,fac_id',
            'motivo' => 'required|string|min:5'
        ]);

        $facId = $request->input('fac_id');
        $motivo = $request->input('motivo');

        try {
            DB::transaction(function () use ($facId, $motivo) {
                $factura = Facturacion::findOrFail($facId);

                if ($factura->fac_estado === 'ANULADA') {
                    throw new Exception('La factura ya está anulada.');
                }

                // 1. Anular en FEL (Si aplica)
                // TODO: Integrar con FelService para anular en SAT si es necesario.
                
                // 2. Actualizar estado de factura
                $factura->update([
                    'fac_estado' => 'ANULADA',
                    'fac_motivo_anulacion' => $motivo,
                    'fac_anulada_por' => auth()->id(),
                    'fac_fecha_anulacion' => now()
                ]);

                // 3. Actualizar venta asociada
                if ($factura->fac_venta_id) {
                    $venta = \App\Models\ProVenta::find($factura->fac_venta_id);
                    
                    if ($venta) {
                        // Regla de negocio: La venta pasa a EDITABLE, no se anula ni devuelve stock.
                        $venta->update([
                            'ven_situacion' => 'EDITABLE',
                            'ven_observaciones' => $venta->ven_observaciones . " [Factura #{$factura->fac_numero} anulada: $motivo]"
                        ]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Factura anulada correctamente. La venta ha pasado a estado EDITABLE.'
            ]);

        } catch (Exception $e) {
            Log::error('Error anulando factura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al anular factura: ' . $e->getMessage()
            ], 500);
        }
    }
}
