<?php

namespace App\Http\Controllers;

use App\Models\Preventa;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\ClienteSaldo;
use App\Models\ClienteSaldoHistorial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificarpagoMail;

class PreventaController extends Controller
{
    public function index()
    {
        $categorias = DB::table('pro_categorias')
            ->where('categoria_situacion', 1)
            ->orderBy('categoria_nombre')
            ->get();

        return view('preventas.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'fecha' => 'required|date',
            'monto_pagado' => 'required|numeric|min:0',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:pro_productos,producto_id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio' => 'required|numeric|min:0',
            // Payment fields validation
            'metodo_pago' => 'nullable|string',
            'banco_id' => 'nullable|integer|exists:pro_bancos,banco_id',
            'fecha_pago' => 'nullable|date',
            'referencia' => 'nullable|string',
        ]);

        // Custom validation for non-cash payments
        if ($request->monto_pagado > 0 && $request->metodo_pago && $request->metodo_pago !== 'EFECTIVO') {
            if (!$request->banco_id) {
                return response()->json(['success' => false, 'message' => 'El banco es requerido para este método de pago.'], 422);
            }
            if (!$request->fecha_pago) {
                return response()->json(['success' => false, 'message' => 'La fecha de pago es requerida.'], 422);
            }
            if (!$request->referencia) {
                return response()->json(['success' => false, 'message' => 'La referencia/autorización es requerida.'], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Calculate total
            $total = 0;
            foreach ($request->productos as $prod) {
                $total += $prod['cantidad'] * $prod['precio'];
            }

            $preventa = Preventa::create([
                'prev_cliente_id' => $request->cliente_id,
                'prev_empresa_id' => $request->empresa_id, // Save company ID
                'prev_fecha' => $request->fecha,
                'prev_total' => $total,
                'prev_monto_pagado' => $request->monto_pagado,
                'prev_observaciones' => $request->observaciones,
                'prev_estado' => 'PENDIENTE'
            ]);

            foreach ($request->productos as $prod) {
                \App\Models\PreventaDetalle::create([
                    'prev_id' => $preventa->prev_id,
                    'producto_id' => $prod['producto_id'],
                    'det_cantidad' => $prod['cantidad'],
                    'det_precio_unitario' => $prod['precio'],
                    'det_subtotal' => $prod['cantidad'] * $prod['precio']
                ]);
            }

            // 3. Registrar pago pendiente de validación si hay monto > 0
            if ($request->monto_pagado > 0) {
                $concepto = 'Abono Preventa #' . $preventa->prev_id;
                if ($request->metodo_pago) {
                    $concepto .= " ({$request->metodo_pago})";
                }

                DB::table('pro_pagos_subidos')->insert([
                    'ps_venta_id'               => null, // No hay venta aún
                    'ps_preventa_id'            => $preventa->prev_id,
                    'ps_cliente_user_id'        => null, // Ajustar si se tiene el user_id del cliente
                    'ps_monto_comprobante'      => $request->monto_pagado,
                    'ps_fecha_comprobante'      => $request->fecha_pago ? \Carbon\Carbon::parse($request->fecha_pago) : now(),
                    'ps_referencia'             => $request->referencia ?? ('PRE-' . $preventa->prev_id),
                    'ps_concepto'               => $concepto,
                    'ps_estado'                 => 'PENDIENTE_VALIDACION',
                    'ps_banco_id'               => $request->banco_id,
                    'ps_imagen_path'            => null,
                    'created_at'                => now(),
                    'updated_at'                => now(),
                ]);

                // Notificar al administrador si hay comprobante (aunque actualmente sea null, se deja preparado)
                if (isset($request->comprobante) || (isset($path) && $path)) { // Check if file exists (logic to be added if file upload is implemented)
                     // For now, since ps_imagen_path is null, this won't trigger unless logic changes. 
                     // But adhering to "whenever a proof is uploaded".
                     // If we want to support it now, we need to add file handling.
                     // Assuming the user wants the LOGIC in place.
                }
                
                // Let's implement the file handling if it's in the request, similar to DeudasController
                $comprobantePath = null;
                if ($request->hasFile('comprobante')) {
                     $file = $request->file('comprobante');
                     $filename = 'pagos_subidos/' . uniqid() . '.' . $file->getClientOriginalExtension();
                     $comprobantePath = $file->storeAs('pagos_subidos', basename($filename), 'public');
                     
                     // Update the inserted record with the path
                     DB::table('pro_pagos_subidos')
                        ->where('ps_preventa_id', $preventa->prev_id)
                        ->where('ps_monto_comprobante', $request->monto_pagado)
                        ->update(['ps_imagen_path' => $comprobantePath, 'ps_estado' => 'PENDIENTE_VALIDACION']);
                }

                if ($comprobantePath) {
                    try {
                        $admins = \App\Models\User::whereHas('rol', function($q){
                            $q->whereIn('nombre', ['administrador', 'contador']);
                        })
                        ->where('user_situacion', 1)
                        ->get();

                        $cliente = Cliente::find($request->cliente_id);

                        $payload = [
                            'venta_id' => 'PRE-' . $preventa->prev_id,
                            'vendedor' => auth()->user()->name,
                            'cliente' => [
                                'nombre' => $cliente ? ($cliente->cliente_nombre1 . ' ' . $cliente->cliente_apellido1) : 'Cliente',
                                'email' => $cliente->cliente_email ?? 'No registrado'
                            ],
                            'fecha' => now()->format('d/m/Y H:i'),
                            'monto' => $request->monto_pagado,
                            'banco_nombre' => $request->banco_id ? DB::table('pro_bancos')->where('banco_id', $request->banco_id)->value('banco_nombre') : 'No especificado',
                            'banco_id' => $request->banco_id,
                            'referencia' => $request->referencia ?? 'No especificada',
                            'concepto' => $concepto,
                            'cuotas' => 1,
                            'monto_total' => $total,
                            'empresa' => $request->empresa_id ? DB::table('pro_clientes_empresas')->where('emp_id', $request->empresa_id)->value('emp_nombre') : null,
                            'productos' => array_map(function($prod) {
                                $productoNombre = DB::table('pro_productos')->where('producto_id', $prod['producto_id'])->value('producto_nombre');
                                return [
                                    'nombre' => $productoNombre,
                                    'cantidad' => $prod['cantidad'],
                                    'precio' => $prod['precio']
                                ];
                            }, $request->productos)
                        ];

                        foreach ($admins as $admin) {
                            if ($admin->email) {
                                Mail::to($admin->email)->send(new \App\Mail\NotificarpagoMail($payload, $comprobantePath, 'PREVENTA'));
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error al enviar correo de preventa: ' . $e->getMessage());
                    }
                }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preventa registrada correctamente',
                'preventa_id' => $preventa->prev_id, // Return ID for printing
                'data' => $preventa
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar preventa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPendientes(Request $request)
    {
        $query = Preventa::with(['cliente', 'detalles.producto'])
            ->where('prev_estado', 'PENDIENTE');

        if ($request->cliente_id) {
            $query->where('prev_cliente_id', $request->cliente_id);
        }

        $preventas = $query->orderBy('prev_fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $preventas
        ]);
    }

    public function listado()
    {
        return view('preventas.listado');
    }

    public function apiListado(Request $request)
    {
        $preventas = Preventa::with(['cliente', 'empresa'])
            ->select('pro_preventas.*')
            ->orderBy('prev_fecha', 'desc')
            ->get();

        // Transform data for DataTable
        $data = $preventas->map(function ($p) {
            $nombreCliente = $p->cliente ? "{$p->cliente->cliente_nombre1} {$p->cliente->cliente_apellido1}" : 'N/A';
            if ($p->empresa) {
                $nombreCliente .= " - {$p->empresa->emp_nombre}";
            } elseif ($p->cliente && $p->cliente->cliente_nom_empresa) {
                $nombreCliente .= " - {$p->cliente->cliente_nom_empresa}";
            }

            return [
                'prev_id' => $p->prev_id,
                'fecha' => $p->prev_fecha->format('d/m/Y'),
                'cliente' => $nombreCliente,
                'total' => $p->prev_total,
                'monto_pagado' => $p->prev_monto_pagado, // Add this field
                'estado' => $p->prev_estado,
                'observaciones' => $p->prev_observaciones,
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function show($id)
    {
        $preventa = Preventa::with(['cliente', 'empresa', 'detalles.producto'])
            ->findOrFail($id);
            
        return response()->json($preventa);
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $preventa = Preventa::findOrFail($id);
            $motivo = request()->input('motivo', 'Cancelación de preventa');

            // 1. Buscar pagos asociados
            $pagos = DB::table('pro_pagos_subidos')->where('ps_preventa_id', $id)->get();

            foreach ($pagos as $pago) {
                // Si fue aprobado, revertir saldo y caja
                if ($pago->ps_estado === 'APROBADO') {
                    $monto = $pago->ps_monto_comprobante;
                    $clienteId = $preventa->prev_cliente_id;

                    // Revertir Saldo Cliente
                    DB::table('pro_clientes_saldo')
                        ->where('saldo_cliente_id', $clienteId)
                        ->decrement('saldo_monto', $monto);

                    $nuevoSaldo = DB::table('pro_clientes_saldo')->where('saldo_cliente_id', $clienteId)->value('saldo_monto');

                    // Historial de Reversión
                    DB::table('pro_clientes_saldo_historial')->insert([
                        'hist_cliente_id' => $clienteId,
                        'hist_tipo' => 'CARGO', // Cargo para reducir el saldo
                        'hist_monto' => $monto,
                        'hist_saldo_anterior' => $nuevoSaldo + $monto,
                        'hist_saldo_nuevo' => $nuevoSaldo,
                        'hist_referencia' => 'REV-PRE-' . $id,
                        'hist_observaciones' => 'Reversión por cancelación de Preventa #' . $id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Actualizar en Caja (NO ELIMINAR)
                    DB::table('cja_historial')
                        ->where('cja_no_referencia', $pago->ps_referencia)
                        ->orWhere('cja_observaciones', 'like', "%Preventa #{$id}%")
                        ->update([
                            'cja_situacion' => 'CANCELADA',
                            'cja_observaciones' => "Cancelación Preventa #{$id}"
                        ]);
                }
                
                // Actualizar el registro de pago subido (NO ELIMINAR)
                DB::table('pro_pagos_subidos')
                    ->where('ps_id', $pago->ps_id)
                    ->update(['ps_estado' => 'CANCELADO']);
            }
            
            // Actualizar estado de la preventa (NO ELIMINAR)
            $preventa->update([
                'prev_estado' => 'CANCELADA',
                'prev_observaciones' => $preventa->prev_observaciones . " | Cancelado: " . $motivo
            ]);
            
            DB::commit();
            
            return response()->json(['success' => true, 'message' => 'Preventa cancelada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al cancelar: ' . $e->getMessage()], 500);
        }
    }

    public function imprimir($id)
    {
        $preventa = Preventa::with(['cliente', 'empresa', 'detalles.producto'])
            ->findOrFail($id);

        return view('preventas.print', compact('preventa'));
    }
}
