<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Clientes;
use Illuminate\Support\Facades\Log;

class DeudasController extends Controller
{
    public function index()
    {
        return view('clientes.deudas');
    }

    public function buscarDeudas(Request $request)
    {
        $query = DB::table('pro_deudas_clientes as d')
            ->join('pro_clientes as c', 'd.cliente_id', '=', 'c.cliente_id')
            ->leftJoin('pro_clientes_empresas as e', 'd.empresa_id', '=', 'e.emp_id')
            ->select(
                'd.*',
                'c.cliente_nombre1',
                'c.cliente_apellido1',
                'c.cliente_nit',
                'e.emp_nombre'
            )
            ->whereNull('d.deleted_at');

        if ($request->filled('cliente_id')) {
            $query->where('d.cliente_id', $request->cliente_id);
        }

        if ($request->filled('estado')) {
            $query->where('d.estado', $request->estado);
        }

        $deudas = $query->orderBy('d.created_at', 'desc')->paginate(10);

        return response()->json($deudas);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:pro_clientes,cliente_id',
            'empresa_id' => 'nullable|exists:pro_clientes_empresas,emp_id',
            'monto' => 'required|numeric|min:0.01',
            'descripcion' => 'nullable|string|max:255',
            'fecha_deuda' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $id = DB::table('pro_deudas_clientes')->insertGetId([
                'cliente_id' => $request->cliente_id,
                'empresa_id' => $request->empresa_id, // Puede ser null
                'monto' => $request->monto,
                'descripcion' => $request->descripcion,
                'fecha_deuda' => $request->fecha_deuda,
                'estado' => 'PENDIENTE',
                'user_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Deuda registrada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar deuda: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al registrar la deuda.'], 500);
        }
    }

    public function pagar(Request $request, $id)
    {
        $request->validate([
            'metodo_pago' => 'required|string', // Ajustar validación según métodos disponibles
        ]);

        try {
            DB::beginTransaction();

            $deuda = DB::table('pro_deudas_clientes')->where('deuda_id', $id)->first();

            if (!$deuda || $deuda->estado === 'PAGADO') {
                return response()->json(['success' => false, 'message' => 'Deuda no válida o ya pagada.'], 422);
            }

            // 1. Actualizar estado de la deuda
            DB::table('pro_deudas_clientes')
                ->where('deuda_id', $id)
                ->update([
                    'estado' => 'PAGADO',
                    'fecha_pago' => now(),
                    'updated_at' => now(),
                ]);

            // 2. Registrar en Caja (cja_historial)
            // Asumiendo estructura de caja basada en ventas anteriores
            DB::table('cja_historial')->insert([
                'cja_tipo' => 'INGRESO', // O 'PAGO_DEUDA' si existe ese tipo
                'cja_id_venta' => null, // No es una venta directa de productos
                'cja_usuario' => auth()->id(),
                'cja_monto' => $deuda->monto,
                'cja_fecha' => now(),
                'cja_metodo_pago' => $request->metodo_pago,
                'cja_no_referencia' => "PAGO-DEUDA-{$id}",
                'cja_situacion' => 'PAGADO', // O 'CONFIRMADO'
                'cja_observaciones' => "Pago de deuda ID {$id} - Cliente ID {$deuda->cliente_id}",
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Deuda pagada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al pagar deuda: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar el pago.'], 500);
        }
    }
}
