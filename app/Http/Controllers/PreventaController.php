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
        ]);

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
}
