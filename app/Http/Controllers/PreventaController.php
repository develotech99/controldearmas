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

class PreventaController extends Controller
{
    public function index()
    {
        return view('preventas.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'producto_id' => 'required|exists:pro_productos,producto_id',
            'cantidad' => 'required|integer|min:1',
            'monto_pagado' => 'required|numeric|min:0',
            'fecha' => 'required|date'
        ]);

        try {
            DB::beginTransaction();

            $preventa = Preventa::create([
                'prev_cliente_id' => $request->cliente_id,
                'prev_producto_id' => $request->producto_id,
                'prev_cantidad' => $request->cantidad,
                'prev_monto_pagado' => $request->monto_pagado,
                'prev_fecha' => $request->fecha,
                'prev_observaciones' => $request->observaciones,
                'prev_estado' => 'PENDIENTE'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Preventa registrada correctamente',
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
        $query = Preventa::with(['cliente', 'producto'])
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
}
