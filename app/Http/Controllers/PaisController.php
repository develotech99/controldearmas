<?php

namespace App\Http\Controllers;

use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paises = Pais::orderBy('pais_descripcion')->paginate(15);
        
        return view('paises.index', compact('paises'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'pais_descripcion' => 'required|string|max:50',
            'pais_situacion' => 'required|integer|in:0,1',
        ], [
            'pais_descripcion.required' => 'La descripción del país es obligatoria.',
            'pais_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
            'pais_situacion.required' => 'El estado es obligatorio.',
            'pais_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            Pais::create([
                'pais_descripcion' => ucwords(strtolower(trim($request->pais_descripcion))),
                'pais_situacion' => $request->pais_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'País creado exitosamente.'
                ]);
            }

            return redirect()->route('paises.index')
                           ->with('success', 'País creado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al crear el país.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un país con ese nombre.';
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
        $pais = Pais::findOrFail($id);

        $request->validate([
            'pais_descripcion' => [
                'required', 
                'string', 
                'max:50',
            ],
            'pais_situacion' => 'required|integer|in:0,1',
        ], [
            'pais_descripcion.required' => 'La descripción del país es obligatoria.',
            'pais_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
            'pais_situacion.required' => 'El estado es obligatorio.',
            'pais_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            $pais->update([
                'pais_descripcion' => ucwords(strtolower(trim($request->pais_descripcion))),
                'pais_situacion' => $request->pais_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'País actualizado exitosamente.'
                ]);
            }

            return redirect()->route('paises.index')
                           ->with('success', 'País actualizado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al actualizar el país.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un país con ese nombre.';
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
            $pais = Pais::findOrFail($id);
            
            // Verificar si tiene registros relacionados (aquí puedes agregar tus validaciones)
            // Por ejemplo: if ($pais->clientes()->count() > 0) { ... }

            $pais->delete();

            return redirect()->route('paises.index')
                           ->with('success', 'País eliminado exitosamente.');

        } catch (\Exception $e) {
            return redirect()->route('paises.index')
                           ->with('error', 'Error al eliminar el país. Puede tener registros relacionados.');
        }
    }

    /**
     * Search countries for AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $paises = Pais::when($search, function ($query) use ($search) {
                return $query->where('pais_descripcion', 'LIKE', "%{$search}%");
            })
            ->orderBy('pais_descripcion')
            ->limit(20)
            ->get();

        return response()->json($paises);
    }

    /**
     * Get active countries
     */
    public function getActivos()
    {
        $paises = Pais::activos()
                     ->orderBy('pais_descripcion')
                     ->get();

        return response()->json($paises);
    }
}