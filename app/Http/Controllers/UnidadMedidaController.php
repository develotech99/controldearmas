<?php

namespace App\Http\Controllers;

use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnidadMedidaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $unidadesMedida = UnidadMedida::orderBy('unidad_nombre')->paginate(15);
        $tipos = UnidadMedida::getTipos();
        
        return view('unidades-medida.index', compact('unidadesMedida', 'tipos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unidad_nombre' => 'required|string|max:50|unique:pro_unidades_medida,unidad_nombre',
            'unidad_abreviacion' => 'required|string|max:10|unique:pro_unidades_medida,unidad_abreviacion',
            'unidad_tipo' => 'required|string|max:20|in:longitud,peso,volumen,otro',
            'unidad_situacion' => 'required|integer|in:0,1',
        ], [
            'unidad_nombre.required' => 'El nombre de la unidad es obligatorio.',
            'unidad_nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'unidad_nombre.unique' => 'Esta unidad de medida ya existe.',
            'unidad_abreviacion.required' => 'La abreviación es obligatoria.',
            'unidad_abreviacion.max' => 'La abreviación no puede tener más de 10 caracteres.',
            'unidad_abreviacion.unique' => 'Esta abreviación ya está en uso.',
            'unidad_tipo.required' => 'El tipo de unidad es obligatorio.',
            'unidad_tipo.in' => 'El tipo de unidad debe ser: longitud, peso, volumen u otro.',
            'unidad_situacion.required' => 'El estado es obligatorio.',
            'unidad_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            UnidadMedida::create([
                'unidad_nombre' => ucfirst(strtolower(trim($request->unidad_nombre))),
                'unidad_abreviacion' => strtolower(trim($request->unidad_abreviacion)),
                'unidad_tipo' => $request->unidad_tipo,
                'unidad_situacion' => $request->unidad_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unidad de medida creada exitosamente.'
                ]);
            }

            return redirect()->route('unidades-medida.index')
                           ->with('success', 'Unidad de medida creada exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al crear la unidad de medida.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una unidad de medida con ese nombre o abreviación.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
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
    public function update(Request $request, $id)
    {
        $unidadMedida = UnidadMedida::findOrFail($id);

        $request->validate([
            'unidad_nombre' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('pro_unidades_medida', 'unidad_nombre')->ignore($unidadMedida->unidad_id, 'unidad_id')
            ],
            'unidad_abreviacion' => [
                'required', 
                'string', 
                'max:10',
                Rule::unique('pro_unidades_medida', 'unidad_abreviacion')->ignore($unidadMedida->unidad_id, 'unidad_id')
            ],
            'unidad_tipo' => 'required|string|max:20|in:longitud,peso,volumen,otro',
            'unidad_situacion' => 'required|integer|in:0,1',
        ], [
            'unidad_nombre.required' => 'El nombre de la unidad es obligatorio.',
            'unidad_nombre.max' => 'El nombre no puede tener más de 50 caracteres.',
            'unidad_nombre.unique' => 'Esta unidad de medida ya existe.',
            'unidad_abreviacion.required' => 'La abreviación es obligatoria.',
            'unidad_abreviacion.max' => 'La abreviación no puede tener más de 10 caracteres.',
            'unidad_abreviacion.unique' => 'Esta abreviación ya está en uso.',
            'unidad_tipo.required' => 'El tipo de unidad es obligatorio.',
            'unidad_tipo.in' => 'El tipo de unidad debe ser: longitud, peso, volumen u otro.',
            'unidad_situacion.required' => 'El estado es obligatorio.',
            'unidad_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            $unidadMedida->update([
                'unidad_nombre' => ucfirst(strtolower(trim($request->unidad_nombre))),
                'unidad_abreviacion' => strtolower(trim($request->unidad_abreviacion)),
                'unidad_tipo' => $request->unidad_tipo,
                'unidad_situacion' => $request->unidad_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unidad de medida actualizada exitosamente.'
                ]);
            }

            return redirect()->route('unidades-medida.index')
                           ->with('success', 'Unidad de medida actualizada exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al actualizar la unidad de medida.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una unidad de medida con ese nombre o abreviación.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $msg
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
    public function destroy($id)
    {
        try {
            $unidadMedida = UnidadMedida::findOrFail($id);
            
            // Verificar si tiene calibres relacionados
            if ($unidadMedida->calibres()->count() > 0) {
                return redirect()->route('unidades-medida.index')
                               ->with('error', 'No se puede eliminar la unidad porque tiene calibres asociados.');
            }

            $unidadMedida->delete();

            return redirect()->route('unidades-medida.index')
                           ->with('success', 'Unidad de medida eliminada exitosamente.');

        } catch (\Exception $e) {
            return redirect()->route('unidades-medida.index')
                           ->with('error', 'Error al eliminar la unidad de medida.');
        }
    }

    /**
     * Search units for AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $unidades = UnidadMedida::when($search, function ($query) use ($search) {
                return $query->where('unidad_nombre', 'LIKE', "%{$search}%")
                           ->orWhere('unidad_abreviacion', 'LIKE', "%{$search}%");
            })
            ->orderBy('unidad_nombre')
            ->limit(20)
            ->get();

        return response()->json($unidades);
    }

    /**
     * Get active units
     */
    public function getActivos()
    {
        $unidades = UnidadMedida::activos()
                               ->orderBy('unidad_nombre')
                               ->get();

        return response()->json($unidades);
    }

    /**
     * Get units by type
     */
    public function getByTipo(Request $request)
    {
        $tipo = $request->get('tipo');
        
        $unidades = UnidadMedida::activos()
                               ->when($tipo, function ($query) use ($tipo) {
                                   return $query->where('unidad_tipo', $tipo);
                               })
                               ->orderBy('unidad_nombre')
                               ->get();

        return response()->json($unidades);
    }
}