<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MetodoPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metodosPago = MetodoPago::orderBy('metpago_descripcion')->paginate(15);
        
        return view('metodos-pago.index', compact('metodosPago'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'metpago_descripcion' => 'required|string|max:50|unique:pro_metodos_pago,metpago_descripcion',
            'metpago_situacion' => 'required|integer|in:0,1',
        ], [
            'metpago_descripcion.required' => 'La descripción es obligatoria.',
            'metpago_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
            'metpago_descripcion.unique' => 'Este método de pago ya existe.',
            'metpago_situacion.required' => 'El estado es obligatorio.',
            'metpago_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            MetodoPago::create([
                'metpago_descripcion' => ucfirst(strtolower(trim($request->metpago_descripcion))),
                'metpago_situacion' => $request->metpago_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Método de pago creado exitosamente.'
                ]);
            }

            return redirect()->route('metodos-pago.index')
                           ->with('success', 'Método de pago creado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al crear el método de pago.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un método de pago con ese nombre.';
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
        $metodoPago = MetodoPago::findOrFail($id);

        $request->validate([
            'metpago_descripcion' => [
                'required', 
                'string', 
                'max:50',
                Rule::unique('pro_metodos_pago', 'metpago_descripcion')->ignore($metodoPago->metpago_id, 'metpago_id')
            ],
            'metpago_situacion' => 'required|integer|in:0,1',
        ], [
            'metpago_descripcion.required' => 'La descripción es obligatoria.',
            'metpago_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
            'metpago_descripcion.unique' => 'Este método de pago ya existe.',
            'metpago_situacion.required' => 'El estado es obligatorio.',
            'metpago_situacion.in' => 'El estado debe ser activo o inactivo.',
        ]);

        try {
            $metodoPago->update([
                'metpago_descripcion' => ucfirst(strtolower(trim($request->metpago_descripcion))),
                'metpago_situacion' => $request->metpago_situacion,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Método de pago actualizado exitosamente.'
                ]);
            }

            return redirect()->route('metodos-pago.index')
                           ->with('success', 'Método de pago actualizado exitosamente.');

        } catch (\Exception $e) {
            $msg = 'Error al actualizar el método de pago.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe un método de pago con ese nombre.';
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
            $metodoPago = MetodoPago::findOrFail($id);
            
            // Verificar si tiene registros relacionados (aquí puedes agregar tus validaciones)
            // Por ejemplo: if ($metodoPago->ventas()->count() > 0) { ... }

            $metodoPago->delete();

            return redirect()->route('metodos-pago.index')
                           ->with('success', 'Método de pago eliminado exitosamente.');

        } catch (\Exception $e) {
            return redirect()->route('metodos-pago.index')
                           ->with('error', 'Error al eliminar el método de pago. Puede tener registros relacionados.');
        }
    }

    /**
     * Search methods for AJAX
     */
    public function search(Request $request)
    {
        $search = $request->get('search', '');
        
        $metodosPago = MetodoPago::when($search, function ($query) use ($search) {
                return $query->where('metpago_descripcion', 'LIKE', "%{$search}%");
            })
            ->orderBy('metpago_descripcion')
            ->limit(20)
            ->get();

        return response()->json($metodosPago);
    }

    /**
     * Get active payment methods
     */
    public function getActivos()
    {
        $metodosPago = MetodoPago::activos()
                               ->orderBy('metpago_descripcion')
                               ->get();

        return response()->json($metodosPago);
    }
}