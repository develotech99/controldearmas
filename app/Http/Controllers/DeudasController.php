<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon; 
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
        ], [
            'cliente_id.required' => 'El cliente es obligatorio',
            'cliente_id.exists' => 'El cliente seleccionado no existe',
            'monto.required' => 'El monto es obligatorio',
            'monto.numeric' => 'El monto debe ser un número',
            'monto.min' => 'El monto debe ser mayor a 0',
            'fecha_deuda.required' => 'La fecha es obligatoria',
            'fecha_deuda.date' => 'La fecha no es válida',
            'descripcion.max' => 'La descripción no puede exceder 255 caracteres',
            'empresa_id.exists' => 'La empresa seleccionada no existe',
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

            $msg = 'Error al guardar la deuda.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function pagar(Request $request, $id)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'metodo_pago' => 'required|string',
            'referencia' => 'nullable|string|max:64',
            'nota' => 'nullable|string|max:255',
            'banco_id' => 'nullable|integer|exists:pro_bancos,banco_id',
            'fecha_pago' => 'nullable|date',
            'comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ], [
            'monto.required' => 'El monto es obligatorio',
            'monto.numeric' => 'El monto debe ser un número',
            'monto.min' => 'El monto debe ser mayor a 0',
            'referencia.max' => 'La referencia no puede exceder 64 caracteres',
            'nota.max' => 'La nota no puede exceder 255 caracteres',
            'banco_id.exists' => 'El banco seleccionado no existe',
            'comprobante.mimes' => 'El comprobante debe ser una imagen (jpg, png) o PDF',
            'comprobante.max' => 'El comprobante no debe pesar más de 5MB',
        ]);

        try {
            DB::beginTransaction();

            $deuda = DB::table('pro_deudas_clientes')->where('deuda_id', $id)->first();
            if (!$deuda) {
                return response()->json(['success' => false, 'message' => 'Deuda no encontrada.'], 404);
            }

        // Handle file upload with compression
        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $file = $request->file('comprobante');
            $filename = 'pagos_subidos/' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Simple compression using GD
            if (in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png'])) {
                $image = match(strtolower($file->getClientOriginalExtension())) {
                    'jpg', 'jpeg' => imagecreatefromjpeg($file->getRealPath()),
                    'png' => imagecreatefrompng($file->getRealPath()),
                    default => null,
                };

                if ($image) {
                    // Save with 75% quality
                    imagejpeg($image, storage_path('app/public/' . $filename), 75);
                    imagedestroy($image);
                    $comprobantePath = $filename;
                } else {
                    $comprobantePath = $file->store('pagos_subidos', 'public');
                }
            } else {
                $comprobantePath = $file->store('pagos_subidos', 'public');
            }
        }

        // Determine status based on proof presence
        $estado = $comprobantePath ? 'PENDIENTE_VALIDACION' : 'PENDIENTE_CARGA';

        // Registrar en pagos subidos
        DB::table('pro_pagos_subidos')->insert([
            'ps_deuda_id' => $id,
            'ps_cliente_user_id' => auth()->id(),
            'ps_estado' => $estado,
            'ps_canal' => 'WEB',
            'ps_fecha_comprobante' => $request->fecha_pago ? Carbon::parse($request->fecha_pago) : now(),
            'ps_monto_comprobante' => $request->monto,
            'ps_banco_id' => $request->banco_id,
            'ps_referencia' => $request->referencia,
            'ps_concepto' => $request->nota,
            'ps_imagen_path' => $comprobantePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::commit();

        $msg = $comprobantePath 
            ? 'Pago enviado a validación correctamente.' 
            : 'Pago registrado. Por favor sube el comprobante en "Mis Pagos" para validarlo.';

        return response()->json(['success' => true, 'message' => $msg]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error al registrar pago: ' . $e->getMessage());

        $msg = 'Error al procesar el pago.';
        if (str_contains($e->getMessage(), 'Data too long')) {
            $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
        } elseif (config('app.debug')) {
            $msg .= ' ' . $e->getMessage();
        }

        return response()->json(['success' => false, 'message' => $msg], 500);
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
