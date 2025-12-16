<?php

namespace App\Http\Controllers;

use App\Models\Marcas; // Cambiado de Marcas a Marca (singular)
use Illuminate\Http\Request;

class MarcasController extends Controller
{
    public function index()
    {
        $marcas = Marcas::all();
        return view('marcas.index', compact('marcas')); // Pasar las marcas a la vista
    }

    // Método para buscar marcas con filtros
    public function search(Request $request)
    {
        $query = Marcas::query();

        // Buscar por descripción
        if ($request->filled('descripcion')) {
            $query->where('marca_descripcion', 'LIKE', '%' . $request->descripcion . '%');
        }

        // Filtrar por situación
        if ($request->filled('situacion')) {
            $query->where('marca_situacion', $request->situacion);
        }

        $marcas = $query->get();

        return view('marcas.index', compact('marcas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'marca_descripcion' => 'required|string|max:50|unique:pro_marcas,marca_descripcion',
            'marca_situacion'   => 'required|in:1,0',  
        ], [
            'marca_descripcion.required' => 'La descripción es obligatoria',
            'marca_descripcion.max' => 'La descripción no puede exceder 50 caracteres',
            'marca_descripcion.unique' => 'Ya existe una marca con ese nombre',
            'marca_situacion.required' => 'La situación es obligatoria',
        ]);

        try {
            Marcas::create([
                'marca_descripcion' => $request->marca_descripcion,
                'marca_situacion'   => (int)$request->marca_situacion, 
            ]);

            return redirect()->route('marcas.index')->with('success', 'Marca creada exitosamente');
        } catch (\Exception $e) {
            $msg = 'Error al crear la marca';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una marca con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }
            return back()->with('error', $msg)->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $marca = Marcas::findOrFail($id);
        
        $request->validate([
            'marca_descripcion' => 'required|string|max:50|unique:pro_marcas,marca_descripcion,' . $id . ',marca_id',
            'marca_situacion'   => 'required|in:1,0',
        ], [
            'marca_descripcion.required' => 'La descripción es obligatoria',
            'marca_descripcion.max' => 'La descripción no puede exceder 50 caracteres',
            'marca_descripcion.unique' => 'Ya existe una marca con ese nombre',
            'marca_situacion.required' => 'La situación es obligatoria',
        ]);

        try {
            $marca->update([
                'marca_descripcion' => $request->marca_descripcion,
                'marca_situacion'   => (int)$request->marca_situacion,
            ]);

            return redirect()->route('marcas.index')->with('success', 'Marca actualizada exitosamente');
        } catch (\Exception $e) {
            $msg = 'Error al actualizar la marca';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una marca con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ': ' . $e->getMessage();
            }
            return back()->with('error', $msg)->withInput();
        }
    }

}
