<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class DeudasController extends Controller
{
    public function index()
    {
        return view('clientes.deudas');
    }

    public function buscarDeudas(Request $request)
    {
        $clienteId = $request->get('cliente_id');
        $estado = $request->get('estado');

        $query = DB::table('pro_deudas_clientes as d')
            ->join('pro_clientes as c', 'd.cliente_id', '=', 'c.cliente_id')
            ->leftJoin('pro_clientes_empresas as e', 'd.empresa_id', '=', 'e.emp_id')
            ->select(
                'd.deuda_id',
                'd.fecha_deuda',
                'd.monto',
                'd.monto_pagado',
                'd.saldo_pendiente',
                'd.descripcion',
                'd.estado',
                'c.cliente_nombre1 as cliente_nombre',
                'c.cliente_apellido1 as cliente_apellido',
                'c.cliente_nit',
                'e.emp_nombre'
            );

        if ($clienteId) {
            $query->where('d.cliente_id', $clienteId);
        }

        if ($estado && $estado !== 'TODOS') {
            $query->where('d.estado', $estado);
        }

        $deudas = $query->orderBy('d.fecha_deuda', 'desc')->get();

        return response()->json(['data' => $deudas]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_deuda' => 'required|date',
            'descripcion' => 'nullable|string|max:255',
            'empresa_id' => 'nullable|exists:pro_clientes_empresas,emp_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Error de validación', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $id = DB::table('pro_deudas_clientes')->insertGetId([
                'cliente_id' => $request->cliente_id,
                'empresa_id' => $request->empresa_id,
                'user_id' => auth()->id(), // Usuario que registra
                'monto' => $request->monto,
                'monto_pagado' => 0,
                'saldo_pendiente' => $request->monto,
                'fecha_deuda' => $request->fecha_deuda,
                'descripcion' => $request->descripcion,
                'estado' => 'PENDIENTE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Deuda registrada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar deuda: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al guardar la deuda.'], 500);
        }
    }

    public function pagar(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string',
            'referencia' => 'nullable|string',
            'nota' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 422);
        }

        try {
            DB::beginTransaction();

            $deuda = DB::table('pro_deudas_clientes')->where('deuda_id', $id)->first();

            if (!$deuda) {
                return response()->json(['success' => false, 'message' => 'Deuda no encontrada'], 404);
            }

            if ($request->monto > $deuda->saldo_pendiente) {
                return response()->json(['success' => false, 'message' => 'El monto excede el saldo pendiente'], 422);
            }

            // 1. Registrar Abono
            DB::table('pro_deudas_abonos')->insert([
                'deuda_id' => $id,
                'monto' => $request->monto,
                'metodo_pago' => $request->metodo_pago,
                'referencia' => $request->referencia,
                'nota' => $request->nota,
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2. Actualizar Deuda
            $nuevoPagado = $deuda->monto_pagado + $request->monto;
            $nuevoSaldo = $deuda->monto - $nuevoPagado;
            $nuevoEstado = $nuevoSaldo <= 0 ? 'PAGADO' : 'PENDIENTE';

            DB::table('pro_deudas_clientes')
                ->where('deuda_id', $id)
                ->update([
                    'monto_pagado' => $nuevoPagado,
                    'saldo_pendiente' => $nuevoSaldo,
                    'estado' => $nuevoEstado,
                    'updated_at' => now(),
                ]);

            // 3. Registrar en Caja (cja_historial)
            // Buscar ID del método de pago
            $metodoId = DB::table('pro_metodos_pago')
                ->where('metpago_descripcion', $request->metodo_pago)
                ->value('metpago_id');

            // Si no encuentra el método, usar uno por defecto (ID 1)
            if (!$metodoId) {
                $metodoId = 1; 
            }

            DB::table('cja_historial')->insert([
                'cja_tipo' => 'PAGO_DEUDA',
                'cja_id_venta' => null,
                'cja_id_import' => $id, // Store deuda_id here for linking
                'cja_usuario' => auth()->id(),
                'cja_monto' => $request->monto,
                'cja_fecha' => now(),
                'cja_metodo_pago' => $metodoId,
                'cja_no_referencia' => $request->referencia ?? "PAGO-DEUDA-{$id}",
                'cja_situacion' => 'ACTIVO',
                'cja_observaciones' => "Abono a deuda ID {$id}. Nota: " . ($request->nota ?? ''),
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pago registrado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar pago: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar el pago.'], 500);
        }
    }

    public function historial($id)
    {
        $abonos = DB::table('pro_deudas_abonos as a')
            ->join('users as u', 'a.user_id', '=', 'u.user_id')
            ->where('a.deuda_id', $id)
            ->select(
                'a.created_at',
                'a.metodo_pago',
                'a.referencia',
                'a.monto',
                DB::raw("CONCAT(u.user_primer_nombre, ' ', u.user_primer_apellido) as usuario")
            )
            ->orderBy('a.created_at', 'desc')
            ->get();

        return response()->json($abonos);
    }
}
