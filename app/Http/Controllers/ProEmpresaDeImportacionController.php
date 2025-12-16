<?php

namespace App\Http\Controllers;

use App\Models\ProEmpresaDeImportacion;
use App\Models\Pais;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Exception;

class ProEmpresaDeImportacionController extends Controller
{
    public function index()
    {
        try {
            $empresas = ProEmpresaDeImportacion::with('pais')->paginate(10);
            $paises = Pais::all();
            
            // DEBUG: Verificar que los países se cargan
            \Log::info('Países cargados: ' . $paises->count());
            \Log::info('Empresas cargadas: ' . $empresas->total());
            
            if (request()->has('edit')) {
                $empresa = ProEmpresaDeImportacion::findOrFail(request('edit'));
                return view('proempresas.index', compact('empresas', 'paises', 'empresa'));
            }

            return view('proempresas.index', compact('empresas', 'paises'));
        } catch (Exception $e) {
            \Log::error('Error en index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar los datos: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar el formulario para crear una nueva empresa de importación
     */
    public function create()
    {
        return redirect()->route('proempresas.index');
    }

    /**
     * Almacenar una nueva empresa de importación
     */
    public function store(Request $request)
    {
        try {
            // Validar los datos
            $validated = $request->validate([
                'empresaimp_pais' => [
                    'required',
                    'exists:pro_paises,pais_id' // Ajusta esto al nombre real de tu tabla de países
                ],
                'empresaimp_descripcion' => 'nullable|string|max:50',
                'empresaimp_situacion' => 'required|integer|in:1,0',
            ], [
                'empresaimp_pais.required' => 'El país es obligatorio.',
                'empresaimp_pais.exists' => 'El país seleccionado no existe.',
                'empresaimp_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
                'empresaimp_situacion.required' => 'La situación es obligatoria.',
                'empresaimp_situacion.in' => 'La situación debe ser Activa o Inactiva.',
            ]);

            // Crear la empresa
            $empresa = ProEmpresaDeImportacion::create($validated);
            
            \Log::info('Empresa creada exitosamente: ' . $empresa->empresaimp_id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Empresa de importación creada exitosamente.',
                    'empresa' => $empresa->load('pais')
                ], 201);
            }

            return redirect()->route('proempresas.index')
                ->with('success', 'Empresa de importación creada exitosamente.');
                
        } catch (ValidationException $e) {
            \Log::error('Errores de validación en store: ', $e->errors());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario.');
                
        } catch (Exception $e) {
            \Log::error('Error en store: ' . $e->getMessage());
            
            $msg = 'Error al crear la empresa.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una empresa con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->expectsJson()) {
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
     * Mostrar el formulario para editar una empresa de importación
     */
    public function edit($id)
    {
        return redirect()->route('proempresas.index', ['edit' => $id]);
    }

    /**
     * Actualizar una empresa de importación
     */
    public function update(Request $request, $id)
    {
        try {
            // Validar los datos
            $validated = $request->validate([
                'empresaimp_pais' => [
                    'required',
                    'exists:pro_paises,pais_id' // Ajusta esto al nombre real de tu tabla de países
                ],
                'empresaimp_descripcion' => 'nullable|string|max:50',
                'empresaimp_situacion' => 'required|integer|in:1,0',
            ], [
                'empresaimp_pais.required' => 'El país es obligatorio.',
                'empresaimp_pais.exists' => 'El país seleccionado no existe.',
                'empresaimp_descripcion.max' => 'La descripción no puede tener más de 50 caracteres.',
                'empresaimp_situacion.required' => 'La situación es obligatoria.',
                'empresaimp_situacion.in' => 'La situación debe ser Activa o Inactiva.',
            ]);

            // Buscar la empresa por el primary key correcto
            $empresa = ProEmpresaDeImportacion::where('empresaimp_id', $id)->first();
            
            if (!$empresa) {
                throw new Exception('Empresa no encontrada con ID: ' . $id);
            }

            // Actualizar la empresa
            $empresa->update($validated);
            
            \Log::info('Empresa actualizada exitosamente: ' . $empresa->empresaimp_id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Empresa de importación actualizada exitosamente.',
                    'empresa' => $empresa->load('pais')
                ]);
            }

            return redirect()->route('proempresas.index')
                ->with('success', 'Empresa de importación actualizada exitosamente.');
                
        } catch (ValidationException $e) {
            \Log::error('Errores de validación en update: ', $e->errors());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación.',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Por favor corrige los errores en el formulario.');
                
        } catch (Exception $e) {
            \Log::error('Error en update: ' . $e->getMessage());
            
            $msg = 'Error al actualizar la empresa.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'Ya existe una empresa con ese nombre.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            if ($request->expectsJson()) {
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
     * Eliminar una empresa de importación
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Buscar la empresa por el primary key correcto
            $empresa = ProEmpresaDeImportacion::where('empresaimp_id', $id)->first();
            
            if (!$empresa) {
                throw new Exception('Empresa no encontrada con ID: ' . $id);
            }

            // Eliminar la empresa
            $empresa->delete();
            
            \Log::info('Empresa eliminada exitosamente: ' . $id);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Empresa de importación eliminada exitosamente.'
                ]);
            }

            return redirect()->route('proempresas.index')
                ->with('success', 'Empresa de importación eliminada exitosamente.');
                
        } catch (Exception $e) {
            \Log::error('Error en destroy: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error interno del servidor: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Error al eliminar la empresa: ' . $e->getMessage());
        }
    }
}