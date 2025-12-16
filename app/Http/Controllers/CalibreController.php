<?php

namespace App\Http\Controllers;

use App\Models\Calibre;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CalibreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $calibres = Calibre::with('unidadMedida')
                          ->orderBy('calibre_nombre')
                          ->paginate(15);
        
        $unidadesMedida = UnidadMedida::activos()
                                    ->orderBy('unidad_nombre')
                                    ->get();
        
        return view('calibres.index', compact('calibres', 'unidadesMedida'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'calibre_nombre' => 'required|string|max:20|unique:pro_calibres,calibre_nombre',
            'calibre_unidad_id' => 'required|integer|exists:pro_unidades_medida,unidad_id',
            'calibre_equivalente_mm' => 'nullable|numeric|between:0,9999.99',
            'calibre_situacion' => 'required|integer|in:0,1',
        ], [
            'calibre_nombre.required' => 'El nombre del calibre es obligatorio.',
            'calibre_nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
            'calibre_nombre.unique' => 'Este calibre ya existe.',
            'calibre_unidad_id.required' => 'La unidad de medida es obligatoria.',
            'calibre_unidad_id.exists' => 'La unidad de medida seleccionada no es válida.',
            'calibre_equivalente_mm.numeric' => 'El equivalente en mm debe ser un número.',
            'calibre_equivalente_mm.between' => 'El equivalente en mm debe estar entre 0 y 9999.99.',
            'calibre_situacion.required' => 'El estado es obligatorio.',
            'calibre_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            Calibre::create([
                'calibre_nombre' => trim($request->calibre_nombre),
                'calibre_unidad_id' => $request->calibre_unidad_id,
                'calibre_equivalente_mm' => $request->calibre_equivalente_mm,
                'calibre_situacion' => $request->calibre_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Calibre creado exitosamente.'
                ]);
            }

            return redirect()->route('calibres.index')
                           ->with('success', 'Calibre creado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al crear el calibre.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un calibre con ese nombre.';
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
        $calibre = Calibre::findOrFail($id);

        $request->validate([
            'calibre_nombre' => [
                'required', 
                'string', 
                'max:20',
                Rule::unique('pro_calibres', 'calibre_nombre')->ignore($calibre->calibre_id, 'calibre_id')
            ],
            'calibre_unidad_id' => 'required|integer|exists:pro_unidades_medida,unidad_id',
            'calibre_equivalente_mm' => 'nullable|numeric|between:0,9999.99',
            'calibre_situacion' => 'required|integer|in:0,1',
        ], [
            'calibre_nombre.required' => 'El nombre del calibre es obligatorio.',
            'calibre_nombre.max' => 'El nombre no puede tener más de 20 caracteres.',
            'calibre_nombre.unique' => 'Este calibre ya existe.',
            'calibre_unidad_id.required' => 'La unidad de medida es obligatoria.',
            'calibre_unidad_id.exists' => 'La unidad de medida seleccionada no es válida.',
            'calibre_equivalente_mm.numeric' => 'El equivalente en mm debe ser un número.',
            'calibre_equivalente_mm.between' => 'El equivalente en mm debe estar entre 0 y 9999.99.',
            'calibre_situacion.required' => 'El estado es obligatorio.',
            'calibre_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            $calibre->update([
                'calibre_nombre' => trim($request->calibre_nombre),
                'calibre_unidad_id' => $request->calibre_unidad_id,
                'calibre_equivalente_mm' => $request->calibre_equivalente_mm,
                'calibre_situacion' => $request->calibre_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Calibre actualizado exitosamente.'
                ]);
            }

            return redirect()->route('calibres.index')
                           ->with('success', 'Calibre actualizado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al actualizar el calibre.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un calibre con ese nombre.';
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
            $calibre = Calibre::findOrFail($id);
            
            // Verificar si tiene registros relacionados (agregar según tu sistema)
            // Por ejemplo: if ($calibre->armas()->count() > 0) { ... }

            $calibre->delete();

            return redirect()->route('calibres.index')
                           ->with('success', 'Calibre eliminado exitosamente.');

        } catch (\Exception $e) {
            return redirect()->route('calibres.index')
                           ->with('error', 'Error al eliminar el calibre. Puede tener registros relacionados.');
        }
    }

    /**
     * Search calibers for AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $calibres = Calibre::with('unidadMedida')
                          ->when($search, function ($query) use ($search) {
                              return $query->where('calibre_nombre', 'LIKE', "%{$search}%");
                          })
                          ->orderBy('calibre_nombre')
                          ->limit(20)
                          ->get();

        return response()->json($calibres);
    }

    /**
     * Get active calibers
     */
    public function getActivos()
    {
        $calibres = Calibre::activos()
                          ->with('unidadMedida')
                          ->orderBy('calibre_nombre')
                          ->get();

        return response()->json($calibres);
    }

    /**
     * Get calibers by unit
     */
    public function getByUnidad(Request $request)
    {
        $unidadId = $request->get('unidad_id');
        
        $calibres = Calibre::activos()
                          ->with('unidadMedida')
                          ->when($unidadId, function ($query) use ($unidadId) {
                              return $query->where('calibre_unidad_id', $unidadId);
                          })
                          ->orderBy('calibre_nombre')
                          ->get();

        return response()->json($calibres);
    }
}