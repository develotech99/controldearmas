<?php

namespace App\Http\Controllers;

use App\Models\ProModelo;
use App\Models\Marcas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProModeloController extends Controller
{
    public function index()
    {
        $modelos = ProModelo::with('marca')
                           ->orderBy('modelo_descripcion')
                           ->paginate(20);
        
        // Para uso en JavaScript
        $modelosData = $modelos->getCollection()->map(function($modelo) {
            return [
                'modelo_id' => $modelo->modelo_id,
                'modelo_descripcion' => $modelo->modelo_descripcion,
                'modelo_situacion' => $modelo->modelo_situacion,
                'modelo_marca_id' => $modelo->modelo_marca_id,
                'marca_nombre' => $modelo->marca ? $modelo->marca->marca_descripcion : null,
                'created_at' => $modelo->created_at,
            ];
        });

        return view('modelos.index', compact('modelos', 'modelosData'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'modelo_descripcion' => 'required|string|max:50|unique:pro_modelo,modelo_descripcion',
            'modelo_marca_id' => 'required|exists:pro_marcas,marca_id'
        ], [
            'modelo_descripcion.required' => 'La descripción es obligatoria',
            'modelo_descripcion.max' => 'La descripción no puede tener más de 50 caracteres',
            'modelo_descripcion.unique' => 'Ya existe un modelo con el mismo nombre',
            'modelo_marca_id.required' => 'La marca es obligatoria',
            'modelo_marca_id.exists' => 'La marca seleccionada no existe'
        ]);

        try {
            ProModelo::create([
                'modelo_descripcion' => trim($request->modelo_descripcion),
                'modelo_marca_id' => $request->modelo_marca_id,
                'modelo_situacion' => 1
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'mensaje' => 'Modelo creado exitosamente'
                ]);
            }

            return redirect()->route('modelos.index')
                ->with('success', 'Modelo creado exitosamente');
                
        } catch (\Exception $e) {
            $msg = 'Error al crear el modelo.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un modelo con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => $msg
                ], 500);
            }

            return redirect()->back()
                ->with('error', $msg)
                ->withInput();
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function edit(Request $request)
    {
        $id = (int)$request->modelo_id;
        $modelo = ProModelo::findOrFail($id);

        $request->validate([
            'modelo_descripcion' => [
                'required',
                'string',
                'max:50',
                Rule::unique('pro_modelo', 'modelo_descripcion')
                    ->ignore($modelo->modelo_id, 'modelo_id')
                    ->where(fn($q) => $q->where('modelo_situacion', 1))
            ],
            'modelo_marca_id' => 'required|exists:pro_marcas,marca_id'
        ], [
            'modelo_descripcion.required' => 'La descripción es obligatoria',
            'modelo_descripcion.max' => 'La descripción no puede tener más de 50 caracteres',
            'modelo_descripcion.unique' => 'Ya existe un modelo con el mismo nombre',
            'modelo_marca_id.required' => 'La marca es obligatoria',
            'modelo_marca_id.exists' => 'La marca seleccionada no existe'
        ]);

        try {
            $modelo->update([
                'modelo_descripcion' => trim($request->modelo_descripcion),
                'modelo_marca_id' => $request->modelo_marca_id,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'mensaje' => 'Modelo actualizado correctamente'
                ]);
            }

            return redirect()->route('modelos.index')
                ->with('success', 'Modelo actualizado correctamente');
                
        } catch (\Exception $e) {
            $msg = 'Error al actualizar.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un modelo con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'mensaje' => $msg
                ], 500);
            }

            return redirect()->back()
                ->with('error', $msg)
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        try {
            $id = (int) $request->modelo_id;
            $modelo = ProModelo::findOrFail($id);

            $modelo->delete();

            return redirect()->route('modelos.index')
                ->with('success', 'Modelo eliminado correctamente');
                
        } catch (\Exception $e) {
            return redirect()->route('modelos.index')
                ->with('error', 'Error al eliminar el modelo, puede tener registros relacionados');
        }
    }

    /**
     * Get active brands for select options
     */
    public function getMarcasActivas()
    {
        $marcas = Marcas::activos() // Cambiar scopeActivos() por activos()
                       ->orderBy('marca_descripcion')
                       ->get(['marca_id', 'marca_descripcion']);
        
        return response()->json([
            'success' => true,
            'data' => $marcas
        ]);
    }
}