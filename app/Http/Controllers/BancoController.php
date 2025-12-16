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
            'banco_nombre' => 'required|string|max:50|unique:pro_bancos,banco_nombre',
        ], [
            'banco_nombre.required' => 'El nombre del banco es obligatorio',
            'banco_nombre.max' => 'El nombre no puede exceder 50 caracteres',
            'banco_nombre.unique' => 'Ya existe un banco con ese nombre',
        ]);

        try {
            $banco = Banco::create([
                'banco_nombre' => $request->banco_nombre,
                'banco_activo' => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Banco creado correctamente', 'data' => $banco]);
        } catch (\Exception $e) {
            $msg = 'Error al crear el banco.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un banco con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $banco = Banco::findOrFail($id);
        
        $request->validate([
            'banco_nombre' => 'required|string|max:50|unique:pro_bancos,banco_nombre,' . $id . ',banco_id',
            'banco_activo' => 'boolean',
        ], [
            'banco_nombre.required' => 'El nombre del banco es obligatorio',
            'banco_nombre.max' => 'El nombre no puede exceder 50 caracteres',
            'banco_nombre.unique' => 'Ya existe un banco con ese nombre',
        ]);

        try {
            $banco->update($request->only(['banco_nombre', 'banco_activo']));

            return response()->json(['success' => true, 'message' => 'Banco actualizado correctamente', 'data' => $banco]);
        } catch (\Exception $e) {
            $msg = 'Error al actualizar el banco.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un banco con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function destroy($id)
    {
        $banco = Banco::findOrFail($id);
        $banco->update(['banco_activo' => false]); // Soft delete logic for now
        return response()->json(['success' => true, 'message' => 'Banco desactivado correctamente']);
    }
}
