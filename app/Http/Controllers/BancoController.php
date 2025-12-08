<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use Illuminate\Http\Request;

class BancoController extends Controller
{
    public function index()
    {
        $bancos = Banco::where('banco_activo', true)->orderBy('banco_nombre')->get();
        return response()->json($bancos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'banco_nombre' => 'required|string|unique:pro_bancos,banco_nombre',
        ]);

        $banco = Banco::create([
            'banco_nombre' => $request->banco_nombre,
            'banco_activo' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Banco creado correctamente', 'data' => $banco]);
    }

    public function update(Request $request, $id)
    {
        $banco = Banco::findOrFail($id);
        
        $request->validate([
            'banco_nombre' => 'required|string|unique:pro_bancos,banco_nombre,' . $id . ',banco_id',
            'banco_activo' => 'boolean',
        ]);

        $banco->update($request->only(['banco_nombre', 'banco_activo']));

        return response()->json(['success' => true, 'message' => 'Banco actualizado correctamente', 'data' => $banco]);
    }

    public function destroy($id)
    {
        $banco = Banco::findOrFail($id);
        $banco->update(['banco_activo' => false]); // Soft delete logic for now
        return response()->json(['success' => true, 'message' => 'Banco desactivado correctamente']);
    }
}
