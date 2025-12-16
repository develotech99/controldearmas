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
                    'empresas' => $cliente->empresas, // Include full companies data
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
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
            'cliente_nom_empresa' => ['nullable', 'string', 'max:250'],
            'cliente_nom_vendedor' => ['nullable', 'string', 'max:250'],
            'cliente_cel_vendedor' => ['nullable', 'string', 'max:250'],
            'cliente_ubicacion' => ['nullable', 'string', 'max:250'],
            'cliente_pdf_licencia' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            // Validación para empresas múltiples
            'empresas' => ['nullable', 'array'],
            'empresas.*.nombre' => ['required_with:empresas', 'string', 'max:255'],
            'empresas.*.direccion' => ['nullable', 'string', 'max:255'],
            'empresas.*.vendedor' => ['nullable', 'string', 'max:255'],
            'empresas.*.cel_vendedor' => ['nullable', 'string', 'max:30'],
            'empresas.*.licencia' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'cliente_nombre1.required' => 'El primer nombre es obligatorio',
            'cliente_nombre1.max' => 'El primer nombre no puede exceder 50 caracteres',
            'cliente_nombre2.max' => 'El segundo nombre no puede exceder 50 caracteres',
            'cliente_apellido1.required' => 'El primer apellido es obligatorio',
            'cliente_apellido1.max' => 'El primer apellido no puede exceder 50 caracteres',
            'cliente_apellido2.max' => 'El segundo apellido no puede exceder 50 caracteres',
            'cliente_tipo.required' => 'El tipo de cliente es obligatorio',
            'cliente_correo.email' => 'El correo electrónico no es válido',
            'cliente_correo.max' => 'El correo no puede exceder 150 caracteres',
            'cliente_pdf_licencia.mimes' => 'El archivo debe ser un PDF',
            'cliente_pdf_licencia.max' => 'El archivo no debe superar los 10MB',
            'cliente_dpi.unique' => 'Ya existe un cliente registrado con este DPI',
            'cliente_dpi.max' => 'El DPI no puede exceder 20 caracteres',
            'cliente_nit.unique' => 'Ya existe un cliente registrado con este NIT',
            'cliente_nit.max' => 'El NIT no puede exceder 20 caracteres',
            'cliente_telefono.max' => 'El teléfono no puede exceder 30 caracteres',
            'cliente_direccion.max' => 'La dirección no puede exceder 255 caracteres',
            'cliente_nom_empresa.max' => 'El nombre de la empresa no puede exceder 250 caracteres',
            'cliente_nom_vendedor.max' => 'El nombre del vendedor no puede exceder 250 caracteres',
            'cliente_cel_vendedor.max' => 'El celular del vendedor no puede exceder 250 caracteres',
            'cliente_ubicacion.max' => 'La ubicación no puede exceder 250 caracteres',
            'empresas.*.nombre.required_with' => 'El nombre de la empresa es obligatorio',
            'empresas.*.nombre.max' => 'El nombre de la empresa no puede exceder 255 caracteres',
            'empresas.*.direccion.max' => 'La dirección de la empresa no puede exceder 255 caracteres',
            'empresas.*.vendedor.max' => 'El nombre del vendedor no puede exceder 255 caracteres',
            'empresas.*.cel_vendedor.max' => 'El teléfono del vendedor no puede exceder 30 caracteres',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();

            // Limpiar cliente_user_id
            if (!isset($validated['cliente_user_id']) || $validated['cliente_user_id'] === '' || $validated['cliente_user_id'] === 'null') {
                $validated['cliente_user_id'] = null;
            }

            // Manejo de archivo PDF
            if ($request->hasFile('cliente_pdf_licencia')) {
                $file = $request->file('cliente_pdf_licencia');
                $fileName = 'licencia_' . time() . '_' . uniqid() . '.pdf';
                $path = $file->storeAs('clientes/licencias', $fileName, 'public');
                $validated['cliente_pdf_licencia'] = $path;
            }

            // Verificar existencia (DPI o NIT)
            $existingClient = null;
            if (!empty($validated['cliente_dpi'])) {
                $existingClient = Clientes::where('cliente_dpi', $validated['cliente_dpi'])->first();
            }
            if (!$existingClient && !empty($validated['cliente_nit'])) {
                $existingClient = Clientes::where('cliente_nit', $validated['cliente_nit'])->first();
            }

            if ($existingClient) {
                if ($existingClient->cliente_situacion == 0) {
                    // Reactivar
                    $validated['cliente_situacion'] = 1;
                    $existingClient->update($validated);
                    $cliente = $existingClient;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'El cliente ya existe (DPI o NIT duplicado) y está activo.'
                    ], 422);
                }
            } else {
                $validated['cliente_situacion'] = 1;
                $cliente = Clientes::create($validated);
            }

            // Si es empresa (tipo 3) y tiene empresas
            if ($request->cliente_tipo == 3 && $request->has('empresas')) {
                foreach ($request->empresas as $index => $empData) {
                    $empresaData = [
                        'emp_nombre' => $empData['nombre'],
                        'emp_nit' => $empData['nit'] ?? null,
                        'emp_direccion' => $empData['direccion'] ?? null,
                        'emp_telefono' => $empData['cel_vendedor'] ?? null, // Legacy mapping
                        'emp_nom_vendedor' => $empData['vendedor'] ?? null,
                        'emp_cel_vendedor' => $empData['cel_vendedor'] ?? null,
                    ];

                    // Manejo de archivo
                    if ($request->hasFile("empresas.{$index}.licencia")) {
                        $file = $request->file("empresas.{$index}.licencia");
                        $fileName = 'licencia_emp_' . time() . '_' . uniqid() . '.pdf';
                        $path = $file->storeAs('clientes/empresas/licencias', $fileName, 'public');
                        $empresaData['emp_licencia_compraventa'] = $path;
                    }

                    $cliente->empresas()->create($empresaData);
                }
                
                // Actualizar campos legacy del cliente con la primera empresa para compatibilidad
                if (count($request->empresas) > 0) {
                    $first = $request->empresas[0];
                    $cliente->update([
                        'cliente_nom_empresa' => $first['nombre'],
                        'cliente_nom_vendedor' => $first['vendedor'] ?? null,
                        'cliente_cel_vendedor' => $first['cel_vendedor'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => $cliente
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear cliente:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            $msg = 'Ocurrió un error al procesar su solicitud.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida (ej. teléfono muy largo). Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'El registro ya existe (posible duplicado de DPI o NIT).';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json(['success' => false, 'message' => $msg], 500);
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
            'cliente_nom_empresa' => ['nullable', 'string', 'max:250'],
            'cliente_nom_vendedor' => ['nullable', 'string', 'max:250'],
            'cliente_cel_vendedor' => ['nullable', 'string', 'max:250'],
            'cliente_ubicacion' => ['nullable', 'string', 'max:250'],
            'cliente_pdf_licencia' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'cliente_nombre1.required' => 'El primer nombre es obligatorio',
            'cliente_nombre1.max' => 'El primer nombre no puede exceder 50 caracteres',
            'cliente_nombre2.max' => 'El segundo nombre no puede exceder 50 caracteres',
            'cliente_apellido1.required' => 'El primer apellido es obligatorio',
            'cliente_apellido1.max' => 'El primer apellido no puede exceder 50 caracteres',
            'cliente_apellido2.max' => 'El segundo apellido no puede exceder 50 caracteres',
            'cliente_tipo.required' => 'El tipo de cliente es obligatorio',
            'cliente_correo.email' => 'El correo electrónico no es válido',
            'cliente_correo.max' => 'El correo no puede exceder 150 caracteres',
            'cliente_pdf_licencia.mimes' => 'El archivo debe ser un PDF',
            'cliente_pdf_licencia.max' => 'El archivo no debe superar los 10MB',
            'cliente_dpi.unique' => 'Ya existe un cliente registrado con este DPI',
            'cliente_dpi.max' => 'El DPI no puede exceder 20 caracteres',
            'cliente_nit.unique' => 'Ya existe un cliente registrado con este NIT',
            'cliente_nit.max' => 'El NIT no puede exceder 20 caracteres',
            'cliente_telefono.max' => 'El teléfono no puede exceder 30 caracteres',
            'cliente_direccion.max' => 'La dirección no puede exceder 255 caracteres',
            'cliente_nom_empresa.max' => 'El nombre de la empresa no puede exceder 250 caracteres',
            'cliente_nom_vendedor.max' => 'El nombre del vendedor no puede exceder 250 caracteres',
            'cliente_cel_vendedor.max' => 'El celular del vendedor no puede exceder 250 caracteres',
            'cliente_ubicacion.max' => 'La ubicación no puede exceder 250 caracteres',
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

            $msg = 'Error al actualizar: ' . $e->getMessage();
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
                $msg = 'El registro ya existe (posible duplicado de DPI o NIT).';
            } elseif (config('app.debug')) {
                // Keep original message if debug
            } else {
                $msg = 'Error al actualizar el cliente.';
            }

            return response()->json([
                'success' => false,
                'message' => $msg
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
            'emp_nom_vendedor' => ['nullable', 'string', 'max:255'],
            'emp_cel_vendedor' => ['nullable', 'string', 'max:30'],
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
            
            $msg = 'Ocurrió un error al crear la empresa.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida (ej. teléfono muy largo). Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json(['success' => false, 'message' => $msg], 500);
        }
    }

    public function updateEmpresa(Request $request, \App\Models\ClienteEmpresa $empresa)
    {
        $validator = Validator::make($request->all(), [
            'emp_nombre' => ['required', 'string', 'max:250'],
            'emp_nit' => ['nullable', 'string', 'max:20'],
            'emp_direccion' => ['nullable', 'string', 'max:255'],
            'emp_telefono' => ['nullable', 'string', 'max:30'],
            'emp_nom_vendedor' => ['nullable', 'string', 'max:255'],
            'emp_cel_vendedor' => ['nullable', 'string', 'max:30'],
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
            
            $msg = 'Ocurrió un error al actualizar la empresa.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida (ej. teléfono muy largo). Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json(['success' => false, 'message' => $msg], 500);
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
    public function buscarClientes(Request $request)
    {
        $term = $request->get('q');

        $clientes = Clientes::where('cliente_situacion', 1)
            ->where(function ($query) use ($term) {
                $query->where('cliente_nombre1', 'LIKE', "%$term%")
                    ->orWhere('cliente_apellido1', 'LIKE', "%$term%")
                    ->orWhere('cliente_nit', 'LIKE', "%$term%")
                    ->orWhere('cliente_nom_empresa', 'LIKE', "%$term%")
                    ->orWhereHas('empresas', function ($q) use ($term) {
                        $q->where('emp_nombre', 'LIKE', "%$term%")
                          ->orWhere('emp_nit', 'LIKE', "%$term%");
                    });
            })
            ->with('empresas') // Eager load companies
            ->limit(20)
            ->get();

        return response()->json($clientes);
    }

    // ==========================================
    // MÉTODOS PARA DOCUMENTOS (LICENCIAS/TENENCIAS)
    // ==========================================

    public function getDocumentos($clienteId)
    {
        $documentos = \App\Models\ClienteDocumento::where('cliente_id', $clienteId)
            ->where('estado', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'documentos' => $documentos
        ]);
    }

    public function storeDocumento(Request $request, $clienteId)
    {
        $validator = Validator::make($request->all(), [
            'tipo' => 'required|in:TENENCIA,PORTACION',
            'numero_documento' => 'required|string|max:50',
            'numero_secundario' => 'nullable|string|max:50',
            'fecha_vencimiento' => 'nullable|date',
            'imagen' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $validator->validated();
            $data['cliente_id'] = $clienteId;

            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $filename = 'doc_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('clientes/documentos', $filename, 'public');
                $data['imagen_path'] = $path;
            }

            $documento = \App\Models\ClienteDocumento::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Documento agregado correctamente',
                'documento' => $documento
            ]);

        } catch (\Exception $e) {
            Log::error('Error al guardar documento:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el documento'
            ], 500);
        }
    }

    public function deleteDocumento($id)
    {
        try {
            $documento = \App\Models\ClienteDocumento::findOrFail($id);
            $documento->update(['estado' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el documento'
            ], 500);
        }
    }
}