<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Clientes::where('cliente_situacion', 1);

            // Filtros
            if ($request->filled('buscar')) {
                $buscar = $request->buscar;
                $query->where(function($q) use ($buscar) {
                    $q->where('cliente_nombre1', 'like', "%{$buscar}%")
                      ->orWhere('cliente_apellido1', 'like', "%{$buscar}%")
                      ->orWhere('cliente_dpi', 'like', "%{$buscar}%")
                      ->orWhere('cliente_nit', 'like', "%{$buscar}%")
                      ->orWhere('cliente_nom_empresa', 'like', "%{$buscar}%");
                });
            }

            if ($request->filled('tipo')) {
                $query->where('cliente_tipo', $request->tipo);
            }

            $clientes = $query->with('empresas')->orderBy('cliente_id', 'desc')->paginate(10);
            
            // Para uso en JavaScript
            $clientesData = $clientes->getCollection()->map(function($cliente) {
                return [
                    'cliente_id' => $cliente->cliente_id,
                    'cliente_nombre1' => $cliente->cliente_nombre1,
                    'cliente_nombre2' => $cliente->cliente_nombre2,
                    'cliente_apellido1' => $cliente->cliente_apellido1,
                    'cliente_apellido2' => $cliente->cliente_apellido2,
                    'cliente_dpi' => $cliente->cliente_dpi,
                    'cliente_nit' => $cliente->cliente_nit,
                    'cliente_direccion' => $cliente->cliente_direccion,
                    'cliente_telefono' => $cliente->cliente_telefono,
                    'cliente_correo' => $cliente->cliente_correo,
                    'cliente_tipo' => $cliente->cliente_tipo,
                    'cliente_situacion' => $cliente->cliente_situacion,
                    'cliente_user_id' => $cliente->cliente_user_id,
                    'cliente_nom_empresa' => $cliente->cliente_nom_empresa,
                    'cliente_nom_vendedor' => $cliente->cliente_nom_vendedor,
                    'cliente_cel_vendedor' => $cliente->cliente_cel_vendedor,
                    'cliente_ubicacion' => $cliente->cliente_ubicacion,
                    'cliente_pdf_licencia' => $cliente->cliente_pdf_licencia,
                    'nombre_completo' => trim($cliente->cliente_nombre1 . ' ' . 
                                             ($cliente->cliente_nombre2 ?? '') . ' ' . 
                                             $cliente->cliente_apellido1 . ' ' . 
                                             ($cliente->cliente_apellido2 ?? '')),
                    'created_at' => $cliente->created_at,
                    'tiene_pdf' => !empty($cliente->cliente_pdf_licencia),
                    'empresas' => $cliente->empresas->where('emp_situacion', 1)->values(), // Solo empresas activas
                ];
            });

            // Obtener usuarios premium para el select
            // CORREGIDO: Verificar primero qué columna existe para el rol
            try {
                // Intenta con user_rol primero
                $usuariosPremium = DB::table('users')
                    ->where('user_rol', 2)
                    ->select('id', 'name', 'email')
                    ->get();
            } catch (\Exception $e) {
                // Si falla, intenta con role o role_id
                try {
                    $usuariosPremium = DB::table('users')
                        ->where('role', 2)
                        ->select('id', 'name', 'email')
                        ->get();
                } catch (\Exception $e2) {
                    // Si todo falla, devuelve array vacío
                    Log::warning('No se pudo determinar la columna de rol de usuarios', [
                        'error' => $e2->getMessage()
                    ]);
                    $usuariosPremium = collect([]);
                }
            }

            // Estadísticas para KPIs
            $stats = [
                'total' => Clientes::where('cliente_situacion', 1)->count(),
                'normales' => Clientes::where('cliente_situacion', 1)->where('cliente_tipo', 1)->count(),
                'premium' => Clientes::where('cliente_situacion', 1)->where('cliente_tipo', 2)->count(),
                'empresas' => Clientes::where('cliente_situacion', 1)->where('cliente_tipo', 3)->count(),
                'este_mes' => Clientes::where('cliente_situacion', 1)
                                     ->whereMonth('created_at', now()->month)
                                     ->whereYear('created_at', now()->year)
                                     ->count(),
            ];

            return view('clientes.index', compact('clientes', 'clientesData', 'usuariosPremium', 'stats'));

        } catch (\Exception $e) {
            Log::error('Error en ClientesController@index:', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            // En desarrollo, mostrar el error
            if (config('app.debug')) {
                throw $e;
            }

            // En producción, mostrar página de error amigable
            return response()->view('errors.500', [], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Clientes $cliente)
    {
        $validator = Validator::make($request->all(), [
            'cliente_nombre1' => ['required', 'string', 'max:50'],
            'cliente_nombre2' => ['nullable', 'string', 'max:50'],
            'cliente_apellido1' => ['required', 'string', 'max:50'],
            'cliente_apellido2' => ['nullable', 'string', 'max:50'],
            'cliente_dpi' => ['nullable', 'string', 'max:20'],
            'cliente_nit' => ['nullable', 'string', 'max:20'],
            'cliente_direccion' => ['nullable', 'string', 'max:255'],
            'cliente_telefono' => ['nullable', 'string', 'max:30'],
            'cliente_correo' => ['nullable', 'email', 'max:150'],
            'cliente_tipo' => ['required', 'integer', 'in:1,2,3'],
            'cliente_user_id' => ['nullable', 'integer'],
            'cliente_nom_empresa' => ['nullable', 'string', 'max:255'],
            'cliente_nom_vendedor' => ['nullable', 'string', 'max:255'],
            'cliente_cel_vendedor' => ['nullable', 'string', 'max:30'],
            'cliente_ubicacion' => ['nullable', 'string', 'max:255'],
            'cliente_pdf_licencia' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'cliente_nombre1.required' => 'El primer nombre es obligatorio',
            'cliente_apellido1.required' => 'El primer apellido es obligatorio',
            'cliente_tipo.required' => 'El tipo de cliente es obligatorio',
            'cliente_correo.email' => 'El correo electrónico no es válido',
            'cliente_pdf_licencia.mimes' => 'El archivo debe ser un PDF',
            'cliente_pdf_licencia.max' => 'El archivo no debe superar los 10MB',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $validated = $validator->validated();

            // Limpiar cliente_user_id si está vacío
            if (!isset($validated['cliente_user_id']) || $validated['cliente_user_id'] === '' || $validated['cliente_user_id'] === 'null') {
                $validated['cliente_user_id'] = null;
            }

            // Si no es tipo empresa (3), limpiar campos de empresa
            if ($validated['cliente_tipo'] != 3) {
                $validated['cliente_nom_empresa'] = null;
                $validated['cliente_nom_vendedor'] = null;
                $validated['cliente_cel_vendedor'] = null;
                $validated['cliente_ubicacion'] = null;
                
                // Si tenía PDF anterior, eliminarlo
                if ($cliente->cliente_pdf_licencia) {
                    Storage::disk('public')->delete($cliente->cliente_pdf_licencia);
                    Log::info('PDF eliminado al cambiar tipo de cliente:', [
                        'path' => $cliente->cliente_pdf_licencia
                    ]);
                    $validated['cliente_pdf_licencia'] = null;
                }
            } else {
                // Si es empresa y se sube nuevo PDF
                if ($request->hasFile('cliente_pdf_licencia')) {
                    // Eliminar PDF anterior si existe
                    if ($cliente->cliente_pdf_licencia) {
                        Storage::disk('public')->delete($cliente->cliente_pdf_licencia);
                        Log::info('PDF anterior eliminado:', [
                            'path' => $cliente->cliente_pdf_licencia
                        ]);
                    }
                    
                    $file = $request->file('cliente_pdf_licencia');
                    
                    // IMPORTANTE: Usar la misma ruta que en VentasController
                    $fileName = 'licencia_' . time() . '_' . uniqid() . '.pdf';
                    $path = $file->storeAs('clientes/licencias', $fileName, 'public');
                    
                    $validated['cliente_pdf_licencia'] = $path;
                    
                    Log::info('Nuevo PDF guardado:', [
                        'path' => $path
                    ]);
                } else {
                    // Mantener el PDF actual si no se sube uno nuevo
                    unset($validated['cliente_pdf_licencia']);
                }
            }

            $cliente->update($validated);

            Log::info('Cliente actualizado exitosamente:', [
                'cliente_id' => $cliente->cliente_id
            ]);

            // Recargar el modelo para obtener los datos actualizados
            $cliente->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente',
                'data' => $cliente
            ]);
                
        } catch (\Exception $e) {
            Log::error('Error al actualizar cliente:', [
                'message' => $e->getMessage(),
                'cliente_id' => $cliente->cliente_id,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Clientes $cliente)
    {
        try {
            // Soft delete - cambiar situación a 0
            $cliente->update(['cliente_situacion' => 0]);

            Log::info('Cliente eliminado (soft delete):', [
                'cliente_id' => $cliente->cliente_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente eliminado correctamente'
            ]);
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar cliente:', [
                'message' => $e->getMessage(),
                'cliente_id' => $cliente->cliente_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el cliente'
            ], 500);
        }
    }

    /**
     * Mostrar el PDF de licencia del cliente
     */
    public function verPdfLicencia(Clientes $cliente)
    {
        try {
            if (!$cliente->cliente_pdf_licencia) {
                Log::warning('Intento de ver PDF sin archivo:', [
                    'cliente_id' => $cliente->cliente_id
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Este cliente no tiene PDF de licencia'
                ], 404);
            }

            $path = storage_path('app/public/' . $cliente->cliente_pdf_licencia);
            
            Log::info('Intentando mostrar PDF:', [
                'cliente_id' => $cliente->cliente_id,
                'path' => $path,
                'exists' => file_exists($path)
            ]);
            
            if (!file_exists($path)) {
                Log::error('Archivo PDF no encontrado:', [
                    'cliente_id' => $cliente->cliente_id,
                    'path' => $path,
                    'cliente_pdf_licencia' => $cliente->cliente_pdf_licencia
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo PDF no se encuentra en el servidor'
                ], 404);
            }

            return response()->file($path, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="licencia_cliente_' . $cliente->cliente_id . '.pdf"'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al mostrar PDF:', [
                'message' => $e->getMessage(),
                'cliente_id' => $cliente->cliente_id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el PDF: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // MÉTODOS PARA GESTIÓN DE EMPRESAS
    // ==========================================

    public function storeEmpresa(Request $request, Clientes $cliente)
    {
        $validator = Validator::make($request->all(), [
            'emp_nombre' => ['required', 'string', 'max:250'],
            'emp_nit' => ['nullable', 'string', 'max:20'],
            'emp_direccion' => ['nullable', 'string', 'max:255'],
            'emp_telefono' => ['nullable', 'string', 'max:30'],
            'emp_licencia_compraventa' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'emp_licencia_vencimiento' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Manejo de archivo
            if ($request->hasFile('emp_licencia_compraventa')) {
                $file = $request->file('emp_licencia_compraventa');
                $fileName = 'licencia_emp_' . time() . '_' . uniqid() . '.pdf';
                $path = $file->storeAs('clientes/empresas/licencias', $fileName, 'public');
                $data['emp_licencia_compraventa'] = $path;
            }

            $empresa = $cliente->empresas()->create($data);

            return response()->json([
                'success' => true,
                'message' => 'Empresa agregada correctamente',
                'data' => $empresa
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear empresa:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al crear empresa'], 500);
        }
    }

    public function updateEmpresa(Request $request, \App\Models\ClienteEmpresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'emp_nombre' => ['required', 'string', 'max:250'],
            'emp_nit' => ['nullable', 'string', 'max:20'],
            'emp_direccion' => ['nullable', 'string', 'max:255'],
            'emp_telefono' => ['nullable', 'string', 'max:30'],
            'emp_licencia_compraventa' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'emp_licencia_vencimiento' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();

            // Manejo de archivo
            if ($request->hasFile('emp_licencia_compraventa')) {
                // Eliminar anterior
                if ($empresa->emp_licencia_compraventa) {
                    Storage::disk('public')->delete($empresa->emp_licencia_compraventa);
                }

                $file = $request->file('emp_licencia_compraventa');
                $fileName = 'licencia_emp_' . time() . '_' . uniqid() . '.pdf';
                $path = $file->storeAs('clientes/empresas/licencias', $fileName, 'public');
                $data['emp_licencia_compraventa'] = $path;
            } else {
                unset($data['emp_licencia_compraventa']);
            }

            $empresa->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada correctamente',
                'data' => $empresa
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar empresa:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al actualizar empresa'], 500);
        }
    }

    public function destroyEmpresa(\App\Models\ClienteEmpresa $empresa)
    {
        try {
            // Soft delete (cambiar situación a 0)
            $empresa->update(['emp_situacion' => 0]);

            return response()->json([
                'success' => true,
                'message' => 'Empresa eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al eliminar empresa:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al eliminar empresa'], 500);
        }
    }
    public function buscar(Request $request)
    {
        $term = $request->get('q');

        $clientes = Clientes::where('cliente_situacion', 1)
            ->where(function ($query) use ($term) {
                $query->where('cliente_nombre1', 'LIKE', "%$term%")
                    ->orWhere('cliente_apellido1', 'LIKE', "%$term%")
                    ->orWhere('cliente_nit', 'LIKE', "%$term%")
                    ->orWhere('cliente_nom_empresa', 'LIKE', "%$term%");
            })
            ->with('empresas') // Eager load companies
            ->limit(20)
            ->get();

        return response()->json($clientes);
    }
}