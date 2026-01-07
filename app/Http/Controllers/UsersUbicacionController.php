<?php

namespace App\Http\Controllers;

use App\Models\UsersHistorialVisita;
use App\Models\UsersUbicacion;
use App\Models\UsersVisita;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;

class UsersUbicacionController extends Controller
{

    public function index()
    {
     
    }

  

    public function create(Request $request)
    {

        $input = $request->all();

        if (isset(($input['lat']))) $input['lat'] = str_replace(',', '.', $input['lat']);
        if (isset(($input['lng']))) $input['lng'] = str_replace(',', '.', $input['lng']);
        if (isset(($input['cantidad_vendida']))) $input['cantidad_vendida'] = str_replace(',', '.', $input['cantidad_vendida']);

        $rules = [
            'cliente_id'        => ['required', 'integer', 'exists:pro_clientes,cliente_id'],
            'empresa_id'        => ['nullable', 'integer', 'exists:pro_clientes_empresas,emp_id'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lng'               => ['required', 'numeric', 'between:-180,180'],
            'direccion'         => ['nullable', 'string', 'max:255'],
            'visitado'          => ['required', 'in:1,2,3'],
            'fecha_visita'      => ['nullable', 'date'],
            'cantidad_vendida'  => ['nullable', 'numeric', 'min:0'],
            'descripcion_venta' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        $messages = [
            'cliente_id.required' => 'Seleccione un cliente.',
            'cliente_id.exists'   => 'El cliente no existe.',
            'lat.required'        => 'La latitud es requerida.',
            'lng.required'        => 'La longitud es requerida.',
            'visitado.in'         => 'Estado de visita inválido.',
            'fecha_visita.date'   => 'La fecha de visita no es válida.',
        ];

        $visitado = (string)($input['visitado'] ?? '');

        if ($visitado === '1') {
            $rules['fecha_visita'] = ['required', 'date'];
            $rules['cantidad_vendida'] = ['nullable'];
            $rules['descripcion_venta'] = ['nullable'];
        } elseif ($visitado === '2') {

            $rules['fecha_visita'] = ['required', 'date'];
            $rules['cantidad_vendida'] = ['required', 'numeric', 'min:0.01'];
            $rules['descripcion_venta'] = ['nullable', 'string'];
        } elseif ($visitado === '3') {
            $rules['fecha_visita'] = ['nullable'];
            $rules['cantidad_vendida'] = ['nullable'];
            $rules['descripcion_venta'] = ['nullable'];
        }

        $validaciones = validator::make($input, $rules, $messages);

        if ($validaciones->fails()) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'Errores de validación',
                'detalle' => $validaciones->errors(),
            ], 422);
        }

        $clienteId = (int)$input['cliente_id'];
        $lat = round((float)$input['lat'], 6);
        $lng = round((float)$input['lng'], 6);
        $direccion = $input['direccion'] ?? null;

        $estado      = (int)$input['visitado'];
        $fechaVisita = null;

        if (!empty($input['fecha_visita'])) {
            $fechaVisita = Carbon::parse($input['fecha_visita'])->format('Y-m-d H:i:s');
        }

        $venta = 0;
        $descVenta = null;

        if ($estado === 2) {
            $venta = (float)$input['cantidad_vendida'];
            $descVenta = $input['descripcion_venta'] ?? null;
        } elseif ($estado === 1) {
            $venta = 0.00;
            $descVenta = null;
        } else {
            $fechaVisita = null;
            $venta = 0.00;
            $descVenta = null;
        }

        // Manejo de la foto
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $file = $request->file('foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/ubicaciones'), $filename);
            $fotoPath = 'storage/ubicaciones/' . $filename;
        }
        try {

            $resultado = DB::transaction(function () use ($clienteId, $lat, $lng, $direccion, $estado, $fechaVisita, $venta, $descVenta, $fotoPath) {

                $ubicacion = UsersUbicacion::create([
                    'ubi_user' => $clienteId,
                    'empresa_id' => $empresaId,
                    'ubi_latitud' => $lat,
                    'ubi_longitud' => $lng,
                    'ubi_descripcion' => $direccion, 
                    'ubi_foto' => $fotoPath  
                ]);

                $ubiId = $ubicacion->ubi_id;

                $visita = UsersVisita::create([
                    'visita_user' => $clienteId,
                    'visita_fecha' => $fechaVisita,
                    'visita_estado' => $estado,
                    'visita_venta' => $venta,
                    'visita_descripcion' => $descVenta
                ]);

                $visitaId = $visita->visita_id;

                $historial = UsersHistorialVisita::create([
                    'hist_visita_id' => $visitaId,
                    'hist_fecha_actualizacion' => now(),
                    'hist_estado_anterior' => null,
                    'hist_estado_nuevo' => $estado,
                    'hist_total_venta_anterior' => null,
                    'hist_total_venta_nuevo' => $venta,
                    'hist_descripcion' => $descVenta
                ]);

                $histId = $historial->hist_id;
            });

            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'Registro creado correctamente',
                'data' => $resultado
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al ingresar el Registro',
                'detalle' => $e->getMessage(),
            ], 404);
        }
    }

    public function getDatos(Request $request)
    {
        try {

            $incluirTodos = (string)$request->query('todos', '0') === '1';

            $Ubicaciones = DB::table('users_ubicaciones')
                ->selectRaw('MAX(ubi_id) AS ubi_id, ubi_user')
                ->groupBy('ubi_user');

            $UltimaVisita = DB::table('users_visitas')
                ->selectRaw('MAX(visita_id) AS visita_id, visita_user')
                ->groupBy('visita_user');

            // Nombre completo del cliente según su tipo
            $fullName = DB::raw("
                CASE 
                    WHEN c.cliente_tipo = 3 THEN CONCAT(c.cliente_nom_empresa, ' | ', TRIM(CONCAT_WS(' ', c.cliente_nombre1, c.cliente_apellido1)))
                    ELSE TRIM(CONCAT_WS(' ', c.cliente_nombre1, c.cliente_nombre2, c.cliente_apellido1, c.cliente_apellido2))
                END as name
            ");

            $empresaField = DB::raw('c.cliente_nom_empresa AS user_empresa');

            $query = DB::table('pro_clientes AS c')
                ->leftJoinSub($Ubicaciones, 'lu', function ($join) {
                    $join->on('lu.ubi_user', '=', 'c.cliente_id');
                })
                ->leftJoin('users_ubicaciones AS ub', 'ub.ubi_id', '=', 'lu.ubi_id')
                ->leftJoinSub($UltimaVisita, 'lv', function ($join) {
                    $join->on('lv.visita_user', '=', 'c.cliente_id');
                })
                ->leftJoin('users_visitas AS vv', 'vv.visita_id', '=', 'lv.visita_id')
                ->select([
                    'c.cliente_id AS user_id',
                    $fullName,
                    $empresaField,
                    'ub.ubi_id',
                    'ub.ubi_latitud',
                    'ub.ubi_longitud',
                    'ub.ubi_descripcion',
                    'ub.ubi_foto', 
                    'ub.created_at AS ubi_created_at',
                    'vv.visita_id',
                    'vv.visita_estado',
                    'vv.visita_fecha',
                    'vv.visita_venta',
                    'vv.visita_descripcion',
                    'vv.created_at AS visita_created_at',
                    'ub.empresa_id',
                    DB::raw('(SELECT emp_nombre FROM pro_clientes_empresas WHERE emp_id = ub.empresa_id) as nombre_empresa_ubicacion')
                ])
                ->orderByRaw("CASE WHEN c.cliente_tipo = 3 THEN c.cliente_nom_empresa ELSE c.cliente_nombre1 END");

            if (!$incluirTodos) {
                $query->whereNotNull('ub.ubi_id');
            }

            $rows = $query->get();

            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'OK',
                'data'    => $rows,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'Error al obtener los datos',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalles de un cliente, incluyendo sus visitas e historial
     */
    /**
 * Obtener detalles de un cliente, incluyendo sus visitas e historial
 */
public function getDetallesCliente($clienteId)
{
    try {
        // Info del cliente
        $cliente = DB::table('pro_clientes')
            ->where('cliente_id', $clienteId)
            ->select([
                'cliente_id',
                DB::raw("
                    CASE 
                        WHEN cliente_tipo = 3 THEN CONCAT(cliente_nom_empresa, ' | ', TRIM(CONCAT_WS(' ', cliente_nombre1, cliente_apellido1)))
                        ELSE TRIM(CONCAT_WS(' ', cliente_nombre1, cliente_nombre2, cliente_apellido1, cliente_apellido2))
                    END as nombre_completo
                "),
                'cliente_nom_empresa'
            ])
            ->first();

        if (!$cliente) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Cliente no encontrado',
            ], 404);
        }

        // Obtener última ubicación con foto
        $ubicacion = DB::table('users_ubicaciones')
            ->where('ubi_user', $clienteId)
            ->orderBy('ubi_id', 'desc')
            ->select(['ubi_foto', 'ubi_latitud', 'ubi_longitud', 'ubi_descripcion'])
            ->first();

        // Visitas
        $visitas = DB::table('users_visitas')
            ->where('visita_user', $clienteId)
            ->orderBy('visita_fecha', 'desc')
            ->get();

        // Calcular resumen
        $totalVisitas = $visitas->count();
        $totalCompras = $visitas->where('visita_estado', 2)->count();
        $totalNoCompras = $visitas->where('visita_estado', 1)->count();
        $totalNoVisitados = $visitas->where('visita_estado', 3)->count();
        $totalVendido = $visitas->where('visita_estado', 2)->sum('visita_venta');

        // Historial (de todas las visitas, no solo la última)
        $historial = DB::table('users_historial_visitas as h')
            ->join('users_visitas as v', 'v.visita_id', '=', 'h.hist_visita_id')
            ->where('v.visita_user', $clienteId)
            ->select([
                'h.hist_id',
                'h.hist_visita_id',
                'h.hist_fecha_actualizacion',
                'h.hist_estado_anterior',
                'h.hist_estado_nuevo',
                'h.hist_total_venta_anterior',
                'h.hist_total_venta_nuevo',
                'h.hist_descripcion'
            ])
            ->orderBy('h.hist_fecha_actualizacion', 'desc')
            ->get();

            return response()->json([
                'codigo' => 1,
                'mensaje' => 'OK',
                'data' => [
                    // Info del cliente (formato plano para JS)
                    'name' => $cliente->nombre_completo,
                    'user_id' => $cliente->cliente_id,
                    'user_empresa' => $cliente->cliente_nom_empresa,
                    
                    // Ubicación con foto
                    'ubicacion' => $ubicacion,
                    
                    // Última visita
                    'ult_visita_fecha' => $visitas->first()->visita_fecha ?? null,
                    
                    // Resumen (formato plano)
                    'total_visitas' => $totalVisitas,
                    'total_compras' => $totalCompras,
                    'total_no_compra' => $totalNoCompras,
                    'total_no_visitado' => $totalNoVisitados,
                    'total_venta' => $totalVendido,
                    
                    // Arrays
                    'visitas' => $visitas,
                    'historial' => $historial,
                ]
            ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'codigo' => 0,
            'mensaje' => 'Error al obtener detalles',
            'detalle' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $input = $request->all();

        // Reemplazar comas por puntos en valores numéricos
        if (isset($input['lat'])) $input['lat'] = str_replace(',', '.', $input['lat']);
        if (isset($input['lng'])) $input['lng'] = str_replace(',', '.', $input['lng']);
        if (isset($input['cantidad_vendida'])) $input['cantidad_vendida'] = str_replace(',', '.', $input['cantidad_vendida']);

        // 1. VALIDACIONES
        $rules = [
            'cliente_id'        => ['required', 'integer', 'exists:pro_clientes,cliente_id'],
            'empresa_id'        => ['nullable', 'integer', 'exists:pro_clientes_empresas,emp_id'],
            'lat'               => ['required', 'numeric', 'between:-90,90'],
            'lng'               => ['required', 'numeric', 'between:-180,180'],
            'direccion'         => ['nullable', 'string', 'max:255'],
            'visitado'          => ['required', 'in:1,2,3'],
            'fecha_visita'      => ['nullable', 'date'],
            'cantidad_vendida'  => ['nullable', 'numeric', 'min:0'],
            'descripcion_venta' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

        $messages = [
            'cliente_id.required' => 'Seleccione un cliente.',
            'cliente_id.exists'   => 'El cliente no existe.',
            'lat.required'        => 'La latitud es requerida.',
            'lng.required'        => 'La longitud es requerida.',
            'visitado.in'         => 'Estado de visita inválido.',
            'fecha_visita.date'   => 'La fecha de visita no es válida.',
        ];

        $visitado = (string)($input['visitado'] ?? '');

        // Ajustar reglas según el estado de la visita
        if ($visitado === '1') {
            $rules['fecha_visita'] = ['required', 'date'];
            $rules['cantidad_vendida'] = ['nullable'];
            $rules['descripcion_venta'] = ['nullable'];
        } elseif ($visitado === '2') {
            $rules['fecha_visita'] = ['required', 'date'];
            $rules['cantidad_vendida'] = ['required', 'numeric', 'min:0.01'];
            $rules['descripcion_venta'] = ['nullable', 'string'];
        } elseif ($visitado === '3') {
            $rules['fecha_visita'] = ['nullable'];
            $rules['cantidad_vendida'] = ['nullable'];
            $rules['descripcion_venta'] = ['nullable'];
        }

        $validaciones = Validator::make($input, $rules, $messages);

        if ($validaciones->fails()) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'Errores de validación',
                'detalle' => $validaciones->errors(),
            ], 422);
        }

        // Buscar la ubicación existente
        $ubicacionExistente = UsersUbicacion::find($id);

        if (!$ubicacionExistente) {
            return response()->json([
                'codigo'  => 0,
                'mensaje' => 'Ubicación no encontrada',
            ], 404);
        }

        $clienteId = (int)$input['cliente_id'];
        $lat = round((float)$input['lat'], 6);
        $lng = round((float)$input['lng'], 6);
        $direccion = $input['direccion'] ?? null;

        $estado      = (int)$input['visitado'];
        $fechaVisita = null;

        if (!empty($input['fecha_visita'])) {
            $fechaVisita = Carbon::parse($input['fecha_visita'])->format('Y-m-d H:i:s');
        }

        $venta = 0;
        $descVenta = null;

        if ($estado === 2) {
            $venta = (float)$input['cantidad_vendida'];
            $descVenta = $input['descripcion_venta'] ?? null;
        } elseif ($estado === 1) {
            $venta = 0.00;
            $descVenta = null;
        } else {
            $fechaVisita = null;
            $venta = 0.00;
            $descVenta = null;
        }

        // Manejo de la foto (línea ~402)
        $fotoPath = $ubicacionExistente->ubi_foto; // Mantener la foto actual por defecto

        if ($request->hasFile('foto')) {
            // Eliminar foto anterior si existe
            if ($ubicacionExistente->ubi_foto && file_exists(public_path($ubicacionExistente->ubi_foto))) {
                unlink(public_path($ubicacionExistente->ubi_foto));
            }
            
            $file = $request->file('foto');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/ubicaciones'), $filename);
            $fotoPath = 'storage/ubicaciones/' . $filename;
        }

        try {

          
            $resultado = DB::transaction(function () use ($id, $clienteId, $lat, $lng, $direccion, $estado, $fechaVisita, $venta, $descVenta, $ubicacionExistente, $fotoPath) {

                $ubicacionExistente->update([
                    'ubi_user' => $clienteId,
                    'empresa_id' => $empresaId,
                    'ubi_latitud' => $lat,
                    'ubi_longitud' => $lng,
                    'ubi_descripcion' => $direccion, 
                    'ubi_foto' => $fotoPath 
                ]);

                $visitaExistente = UsersVisita::where('visita_user', $clienteId)
                    ->orderBy('visita_id', 'desc')
                    ->first();

                $estadoAnterior = null;
                $ventaAnterior = 0;
                $visitaId = null;

                if ($visitaExistente) {

                    $estadoAnterior = $visitaExistente->visita_estado;
                    $ventaAnterior = $visitaExistente->visita_venta;

                    $visitaExistente->update([
                        'visita_fecha' => $fechaVisita,
                        'visita_estado' => $estado,
                        'visita_venta' => $venta,
                        'visita_descripcion' => $descVenta
                    ]);

                    $visitaId = $visitaExistente->visita_id;
                } else {
                    // CREAR nueva visita si no existe ninguna para este cliente
                    $nuevaVisita = UsersVisita::create([
                        'visita_user' => $clienteId,
                        'visita_fecha' => $fechaVisita,
                        'visita_estado' => $estado,
                        'visita_venta' => $venta,
                        'visita_descripcion' => $descVenta
                    ]);

                    $visitaId = $nuevaVisita->visita_id;
                    $estadoAnterior = null;
                    $ventaAnterior = 0;
                }

                // 3. REGISTRAR EN EL HISTORIAL
                $historial = UsersHistorialVisita::create([
                    'hist_visita_id' => $visitaId,
                    'hist_fecha_actualizacion' => now(),
                    'hist_estado_anterior' => $estadoAnterior,
                    'hist_estado_nuevo' => $estado,
                    'hist_total_venta_anterior' => $ventaAnterior,
                    'hist_total_venta_nuevo' => $venta,
                    'hist_descripcion' => $this->generarDescripcionHistorial($estadoAnterior, $estado, $ventaAnterior, $venta, $descVenta)
                ]);

                return [
                    'ubicacion_id' => $ubicacionExistente->ubi_id,
                    'visita_id' => $visitaId,
                    'historial_id' => $historial->hist_id,
                    'cambios' => [
                        'estado' => ['anterior' => $estadoAnterior, 'nuevo' => $estado],
                        'venta' => ['anterior' => $ventaAnterior, 'nueva' => $venta]
                    ]
                ];
            });

            return response()->json([
                'codigo'  => 1,
                'mensaje' => 'Ubicación y visita actualizadas correctamente. Historial registrado.',
                'data' => $resultado
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0,
                'mensaje' => 'Error al actualizar el registro',
                'detalle' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generar descripción para el historial basada en los cambios
     */
    private function generarDescripcionHistorial($estadoAnterior, $estadoNuevo, $ventaAnterior, $ventaNueva, $descripcionVenta)
    {
        $estados = [
            1 => 'Visitado sin venta',
            2 => 'Visitado con venta',
            3 => 'No visitado'
        ];

        if ($estadoAnterior === null) {
            $descripcion = "Primera visita registrada: " . $estados[$estadoNuevo];
        } else {
            $descripcion = "Modificación: Estado cambió de '{$estados[$estadoAnterior]}' a '{$estados[$estadoNuevo]}'";
        }

        if ($ventaAnterior != $ventaNueva) {
            $descripcion .= " | Venta cambió de Q" . number_format($ventaAnterior, 2) . " a Q" . number_format($ventaNueva, 2);
        }

        if (!empty($descripcionVenta)) {
            $descripcion .= " | Nota: " . $descripcionVenta;
        }

        return $descripcion;
    }


    public function agregarVisita(Request $request)
    {
        $input = $request->all();

        $rules = [
            'user_id' => ['required', 'exists:pro_clientes,cliente_id'],
            'estado' => ['required', 'in:1,2,3'],
            'fecha' => ['nullable', 'date'],
            'venta' => ['nullable', 'numeric', 'min:0'],
            'descripcion' => ['nullable', 'string'],
        ];

        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response()->json(['codigo' => 0, 'mensaje' => 'Validación fallida', 'detalle' => $validator->errors()], 422);
        }

        $userId = (int)$input['user_id'];
        $estado = (int)$input['estado'];
        $fecha = !empty($input['fecha']) ? Carbon::parse($input['fecha'])->format('Y-m-d H:i:s') : null;
        $venta = $estado === 2 ? (float)($input['venta'] ?? 0) : 0;
        $descripcion = $input['descripcion'] ?? null;

        try {
            DB::transaction(function () use ($userId, $estado, $fecha, $venta, $descripcion) {
                // Obtener visita anterior
                $visitaAnterior = UsersVisita::where('visita_user', $userId)
                    ->orderBy('visita_id', 'desc')
                    ->first();

                $estadoAnt = $visitaAnterior->visita_estado ?? null;
                $ventaAnt = $visitaAnterior->visita_venta ?? 0;

                // Crear nueva visita
                $nuevaVisita = UsersVisita::create([
                    'visita_user' => $userId,
                    'visita_fecha' => $fecha,
                    'visita_estado' => $estado,
                    'visita_venta' => $venta,
                    'visita_descripcion' => $descripcion
                ]);

                // Registrar historial
                UsersHistorialVisita::create([
                    'hist_visita_id' => $nuevaVisita->visita_id,
                    'hist_fecha_actualizacion' => now(),
                    'hist_estado_anterior' => $estadoAnt,
                    'hist_estado_nuevo' => $estado,
                    'hist_total_venta_anterior' => $ventaAnt,
                    'hist_total_venta_nuevo' => $venta,
                    'hist_descripcion' => "Nueva visita registrada"
                ]);
            });

            return response()->json(['codigo' => 1, 'mensaje' => 'Visita registrada correctamente'], 200);
        } catch (\Throwable $e) {
            return response()->json(['codigo' => 0, 'mensaje' => 'Error', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        try {
            $ubicacion = UsersUbicacion::findOrFail($id);
            
            // Eliminar foto si existe
            if ($ubicacion->ubi_foto && file_exists(public_path($ubicacion->ubi_foto))) {
                unlink(public_path($ubicacion->ubi_foto));
            }
            
            $ubicacion->delete();
    
            return response()->json([
                'codigo' => 1, 
                'mensaje' => 'Ubicación eliminada correctamente'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'codigo' => 0, 
                'mensaje' => 'Error al eliminar', 
                'detalle' => $e->getMessage()
            ], 500);
        }
    }
}