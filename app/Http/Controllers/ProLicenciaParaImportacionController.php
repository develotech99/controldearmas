<?php

namespace App\Http\Controllers;

use Throwable;
use App\Models\ProModelo;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\DB;
use App\Models\ProArmaLicenciada as Arma;
use Illuminate\Support\Facades\Storage;

use App\Models\ProDocumentacionLicImport;
use App\Models\ProLicenciaParaImportacion as Licencia;

class ProLicenciaParaImportacionController extends Controller
{
    /** INDEX: lista licencias con sus armas (resumen) o render vista */
    public function index(Request $request)
    {
    $licencias = Licencia::query()
        ->with([
            'armas', // Relación armas
            'armas.subcategoria:subcategoria_id,subcategoria_nombre',
            'armas.modelo:modelo_id,modelo_descripcion,modelo_marca_id',
            'armas.modelo.marca:marca_id,marca_descripcion',
            'armas.calibre:calibre_id,calibre_nombre', 
            'armas.empresa:empresaimp_id,empresaimp_descripcion',
        ]) 
        ->orderByDesc('lipaimp_id')
        ->paginate(15, [
            'lipaimp_id', 'lipaimp_poliza', 'lipaimp_descripcion',
            'lipaimp_observaciones', // Asegúrate de incluir esta columna
            'lipaimp_fecha_emision', 'lipaimp_fecha_vencimiento',
            'lipaimp_situacion', 'created_at', 'updated_at',
        ]);

        // catálogos
        $subcategorias = \DB::table('pro_subcategorias')
            ->select('subcategoria_id','subcategoria_nombre')
            ->orderBy('subcategoria_nombre')->get();

      $modelosSelect = ProModelo::where('modelo_situacion', 1)
    ->orderBy('modelo_descripcion')
    ->get(['modelo_id','modelo_descripcion']);

        $empresas = \DB::table('pro_empresas_de_importacion')
            ->select('empresaimp_id','empresaimp_descripcion')
            ->orderBy('empresaimp_descripcion')->get();
            $calibresSelect = \DB::table('pro_calibres')   // si tu tabla es plural, usa 'pro_calibres'
    ->select('calibre_id','calibre_nombre')
    ->orderBy('calibre_nombre')->get();

        return view('prolicencias.index', compact('licencias','subcategorias','modelosSelect','empresas','calibresSelect'));
    }

    /** STORE: crea licencia + armas (array) */
 public function store(Request $request)
    {
        try {
            \Log::info('=== CREAR LICENCIA (sin archivos) ===');
            \Log::info('Request data:', $request->except(['_token']));

            // Normaliza aliases de armas y valida
            [$licData, $armasData] = $this->validateCompound($request);

            $licencia = DB::transaction(function () use ($licData, $armasData) {
                // Crear licencia
                $lic = Licencia::create($licData);
                \Log::info('Licencia created', ['lipaimp_id' => $lic->lipaimp_id]);

                // Insertar armas
                $prepared = $this->prepareArmas($armasData, $lic->lipaimp_id);
                if (!empty($prepared)) {
                    Arma::insert($prepared);
                    \Log::info('Armas inserted', ['count' => count($prepared)]);
                }

                return $lic;
            });

            return $this->jsonOrRedirect(
                $request,
                [
                    'licencia' => $licencia->load('armas'), 
                    'lipaimp_id' => $licencia->lipaimp_id,
                    'message' => 'Licencia creada exitosamente'
                ],
                201,
                back()->with('success', 'Licencia creada correctamente')
            );

        } catch (Throwable $e) {
            \Log::error('Error creating licencia', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            $msg = 'Error al crear la licencia.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }

            return $this->handleException($request, $e, $msg);
        }
    }

    /**
     * Subir PDFs por separado - FUNCIÓN NUEVA
     */
 public function getDocumentos($licenciaId)
    {
        try {
            $documentos = ProDocumentacionLicImport::where('doclicimport_num_lic', $licenciaId)
                ->where('doclicimport_situacion', 1)
                ->get();

            return response()->json([
                'documentos' => $documentos,
                'count' => $documentos->count()
            ]);

        } catch (Throwable $e) {
            \Log::error('Error obteniendo documentos', [
                'licencia_id' => $licenciaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error obteniendo documentos',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function uploadPdfs(Request $request, $licenciaId)
    {
        try {
            \Log::info('=== UPLOAD PDFS ===');
            \Log::info('Licencia ID: ' . $licenciaId);
            \Log::info('Has files pdfs: ' . ($request->hasFile('pdfs') ? 'YES' : 'NO'));
            \Log::info('All files:', $request->allFiles());

            // Verificar que la licencia existe
            $licencia = Licencia::findOrFail($licenciaId);
            \Log::info('Licencia found: ' . $licencia->lipaimp_id);

            // Validar archivos
            $request->validate([
                'pdfs.*' => 'required|file|mimes:pdf|max:10240', // 10MB max por archivo
            ]);

            $processedFiles = 0;

            if ($request->hasFile('pdfs')) {
                $pdfFiles = $request->file('pdfs');
                
                // Convertir a array si es un solo archivo
                if (!is_array($pdfFiles)) {
                    $pdfFiles = [$pdfFiles];
                }

                \Log::info('Processing files:', [
                    'count' => count($pdfFiles),
                    'files' => array_map(function($file) {
                        return [
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'mime' => $file->getMimeType(),
                            'is_valid' => $file->isValid()
                        ];
                    }, $pdfFiles)
                ]);

                $processedFiles = $this->procesarPdfs($pdfFiles, $licenciaId);
            }

            return response()->json([
                'message' => 'Archivos procesados exitosamente',
                'processed_files' => $processedFiles,
                'licencia_id' => $licenciaId
            ]);

        } catch (Throwable $e) {
            \Log::error('Error uploading PDFs', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'licencia_id' => $licenciaId
            ]);

            return response()->json([
                'error' => 'Error procesando archivos',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function procesarPdfs($pdfFiles, $licenciaId)
    {
        $processedCount = 0;
        
        \Log::info('=== PROCESAR PDFS ===');
        \Log::info('Licencia ID: ' . $licenciaId);
        \Log::info('Files to process: ' . count($pdfFiles));

        try {
            $disk = 'public';
            $baseDir = 'documentacion';

            // Crear directorio base
            if (!Storage::disk($disk)->exists($baseDir)) {
                Storage::disk($disk)->makeDirectory($baseDir, 0755, true);
                \Log::info('Base directory created: ' . $baseDir);
            }

            // Crear subdirectorio por licencia
            $licenciaDir = $baseDir.'/licencia_'.$licenciaId;
            if (!Storage::disk($disk)->exists($licenciaDir)) {
                Storage::disk($disk)->makeDirectory($licenciaDir, 0755, true);
                \Log::info('Licencia directory created: ' . $licenciaDir);
            }

            // Procesar cada archivo
            foreach ($pdfFiles as $index => $pdfFile) {
                \Log::info("Processing file {$index}:", [
                    'name' => $pdfFile->getClientOriginalName(),
                    'size' => $pdfFile->getSize(),
                    'mime' => $pdfFile->getMimeType(),
                    'is_valid' => $pdfFile->isValid(),
                    'error' => $pdfFile->getError()
                ]);

                // Validar archivo individual
                if (!$pdfFile->isValid()) {
                    \Log::error("Invalid file at index {$index}: " . $pdfFile->getError());
                    continue;
                }

                // Generar nombre único
                $originalName = $pdfFile->getClientOriginalName();
                $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $fileName = time().'_'.rand(1000,9999).'_'.$safeOriginal;

                // Guardar archivo
                \Log::info('Storing file: ' . $fileName);
                $path = $pdfFile->storeAs($licenciaDir, $fileName, $disk);
                
                if (!$path) {
                    \Log::error('Failed to store file: ' . $fileName);
                    continue;
                }

                \Log::info('File stored at: ' . $path);

                // Verificar que se guardó
                if (!Storage::disk($disk)->exists($path)) {
                    \Log::error("File not found after storing: " . $path);
                    continue;
                }

                // Obtener información del archivo guardado
                $storedSize = Storage::disk($disk)->size($path);
                $storedMime = $pdfFile->getMimeType();

                // Guardar registro en BD
                \Log::info('Creating database record');
                $doc = ProDocumentacionLicImport::create([
                    'doclicimport_ruta'            => $path,
                    'doclicimport_num_lic'         => $licenciaId,
                    'doclicimport_situacion'       => 1,
                    'doclicimport_nombre_original' => $originalName,
                    'doclicimport_size_bytes'      => $storedSize,
                    'doclicimport_mime'            => $storedMime,
                ]);

                \Log::info('Document saved to DB:', [
                    'id' => $doc->doclicimport_id,
                    'path' => $path,
                    'size' => $storedSize,
                    'original_name' => $originalName
                ]);
                
                $processedCount++;
            }
            
        } catch (Throwable $e) {
            \Log::error('Error in procesarPdfs:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        \Log::info('PDF processing completed', [
            'total_processed' => $processedCount,
            'licencia_id' => $licenciaId
        ]);
        
        return $processedCount;
    }


    /** SHOW: una licencia con sus armas (útil para modal de edición) */
    public function show(Request $request, $id)
    {
        $licencia = Licencia::with('armas')->findOrFail($id);
        return response()->json(['licencia' => $licencia]);
    }

    /** UPDATE: actualiza licencia + sincroniza armas (upsert + delete) */
    public function update(Request $request, $id)
    {
        try {
            $licencia = Licencia::findOrFail($id);
            [$licData, $armasData] = $this->validateCompound($request, $licencia->lipaimp_id);

            DB::transaction(function () use ($licencia, $licData, $armasData) {
                // 1) Actualizar licencia
                if (!empty($licData)) {
                    $licencia->fill($licData)->save();
                }

                // 2) Sincronizar armas
                $incoming   = collect($armasData);
                $existing   = $licencia->armas()->get(['arma_lic_id']);
                $incomingIds= $incoming->pluck('arma_lic_id')->filter()->map(fn($v)=>(int)$v)->all();
                $toDelete   = $existing->pluck('arma_lic_id')->reject(fn($id) => in_array($id, $incomingIds))->all();

                if (!empty($toDelete)) {
                    Arma::whereIn('arma_lic_id', $toDelete)->delete();
                }

                foreach ($incoming as $row) {
                    $row = $this->prepareUnaArma($row, $licencia->lipaimp_id);

                    if (!empty($row['arma_lic_id'])) {
                        $armaId = (int)$row['arma_lic_id'];
                        unset($row['arma_lic_id'], $row['arma_num_licencia']); // no cambiar FK ni PK en update
                        Arma::where('arma_lic_id', $armaId)->update($row);
                    } else {
                        Arma::create($row);
                    }
                }
            });

            return $this->jsonOrRedirect(
                $request,
                ['licencia' => $licencia->fresh()->load('armas'), 'message' => 'Actualizado'],
                200,
                back()->with('success', 'Licencia actualizada correctamente')
            );

        } catch (Throwable $e) {
            $msg = 'Error al actualizar la licencia/armas.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            return $this->handleException($request, $e, $msg);
        }
    }

    /** DESTROY: borra licencia (armas se borran por FK CASCADE) */
  public function destroy($id)
    {
        try {
            \Log::info('Eliminando licencia y archivos', ['licencia_id' => $id]);

            return DB::transaction(function () use ($id) {
                // Buscar la licencia
                $licencia = Licencia::findOrFail($id);

                // 1. Obtener y eliminar todos los documentos físicos
                $documentos = ProDocumentacionLicImport::where('doclicimport_num_lic', $id)->get();
                
                foreach ($documentos as $doc) {
                    // Eliminar archivo físico
                    if (Storage::disk('public')->exists($doc->doclicimport_ruta)) {
                        Storage::disk('public')->delete($doc->doclicimport_ruta);
                        \Log::info('Archivo eliminado: ' . $doc->doclicimport_ruta);
                    }
                    
                    // Eliminar registro de BD
                    $doc->delete();
                }

                // 2. Eliminar directorio de la licencia si existe
                $licenciaDir = 'documentacion/licencia_' . $id;
                if (Storage::disk('public')->exists($licenciaDir)) {
                    Storage::disk('public')->deleteDirectory($licenciaDir);
                    \Log::info('Directorio eliminado: ' . $licenciaDir);
                }

                // 3. Eliminar armas asociadas
                $armasEliminadas = Arma::where('arma_lic_id', $id)->delete();
                \Log::info('Armas eliminadas: ' . $armasEliminadas);

                // 4. Eliminar la licencia
                $licencia->delete();
                \Log::info('Licencia eliminada: ' . $id);

                return response()->json([
                    'message' => 'Licencia eliminada exitosamente',
                    'documentos_eliminados' => $documentos->count(),
                    'armas_eliminadas' => $armasEliminadas
                ]);
            });

        } catch (Throwable $e) {
            \Log::error('Error eliminando licencia', [
                'licencia_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error eliminando licencia',
                'message' => $e->getMessage()
            ], 500);
        }}

    /* ===================== VALIDACIÓN COMPUESTA ===================== */

    /**
     * Normaliza aliases de armas -> nombres canónicos y valida
     * En update, puedes pasar $ignoreId si tuvieras reglas unique sobre lipaimp_id (no parece el caso).
     */
 private function validateCompound(Request $request, $ignoreId = null): array
{
    // Detecta si es CREATE (no hay $ignoreId / o ruta Store) o UPDATE
    $isUpdate = $ignoreId !== null;

    // 1) Normaliza armas (tu bloque actual)...
    $armas = collect($request->input('armas', []))->map(function ($a) use ($request) {
        $a = is_array($a) ? $a : [];

        // En create, el ID no existe aún, así que usamos null o placeholder.
        // En update, usamos el ID de la licencia.
        $numLic = $a['arma_num_licencia'] ?? $a['arma_licencia_id'] ?? ($isUpdate ? $request->input('lipaimp_id') : null);

        return [
            'arma_lic_id'       => $a['arma_lic_id'] ?? null,
            'arma_num_licencia' => $numLic !== null ? (int)$numLic : null,
            'arma_sub_cat'      => $a['arma_sub_cat'] ?? null,
            'arma_modelo'       => $a['arma_modelo'] ?? null,
            'arma_calibre'      => $a['arma_calibre'] ?? null,
            'arma_empresa'      => $a['arma_empresa'] ?? null,
            'arma_largo_canon'  => $a['arma_largo_canon'] ?? null,
            'arma_cantidad'     => $a['arma_cantidad'] ?? null,
        ];
    })->all();

    $request->merge(['armas' => $armas]);

    // 2) Reglas base
    $rules = [
        // 'lipaimp_id' => ['required','integer'], // YA NO SE PIDE ID MANUALMENTE
        'lipaimp_numero'            => ['required','string','max:50'], // ✅ Nuevo campo obligatorio
        'lipaimp_poliza'            => ['nullable','string','max:50'], // ✅ Opcional
        'armas'                     => ['required','array','min:1'],
        'lipaimp_observaciones'     => 'nullable|string|max:255',
        'armas.*.arma_sub_cat'      => ['required','integer','exists:pro_subcategorias,subcategoria_id'],
        'armas.*.arma_modelo'       => ['required','integer','exists:pro_modelo,modelo_id'], 
        'armas.*.arma_calibre'      => ['required','integer','exists:pro_calibres,calibre_id'], 
        'armas.*.arma_empresa'      => ['required','integer','exists:pro_empresas_de_importacion,empresaimp_id'],
        'armas.*.arma_largo_canon'  => ['required','numeric','min:0'],
        'armas.*.arma_cantidad'     => ['required','integer','min:1'],
    ];

    // 3) Regla especial para arma_num_licencia
    if ($isUpdate) {
        // En UPDATE, se permite actualizar sin validar duplicado, pero aseguramos que exista
        $rules['armas.*.arma_num_licencia'] = ['required','integer','exists:pro_licencias_para_importacion,lipaimp_id'];
        
        // Validar unicidad del número de licencia (excepto la actual)
        $rules['lipaimp_numero'] = ['required','string','max:50','unique:pro_licencias_para_importacion,lipaimp_numero,'.$ignoreId.',lipaimp_id'];
    } else {
        // En CREATE, el arma_num_licencia se asignará después de crear la licencia
        // $rules['armas.*.arma_num_licencia'] = ['required','integer','unique:pro_licencias_para_importacion,lipaimp_id']; // YA NO APLICA
        
        // Validar unicidad del número de licencia
        $rules['lipaimp_numero'] = ['required','string','max:50','unique:pro_licencias_para_importacion,lipaimp_numero'];
    }

    // Realiza la validación
    $validated = validator($request->all(), $rules)->validate();

    // 4) Separa los datos de la licencia y las armas
    $licData = $request->only([
        // 'lipaimp_id', // Auto-increment
        'lipaimp_numero',
        'lipaimp_poliza',
        'lipaimp_descripcion',
        'lipaimp_fecha_emision',
        'lipaimp_fecha_vencimiento',
        'lipaimp_observaciones',
        'lipaimp_situacion',
    ]);

    // Asegurarse de que las variables sean del tipo correcto
    // if (isset($licData['lipaimp_id'])) $licData['lipaimp_id'] = (int)$licData['lipaimp_id'];
    if (isset($licData['lipaimp_situacion']) && $licData['lipaimp_situacion'] !== '') $licData['lipaimp_situacion'] = (int)$licData['lipaimp_situacion'];

    // Convertir strings vacíos a null para campos opcionales
    if (array_key_exists('lipaimp_poliza', $licData) && trim($licData['lipaimp_poliza']) === '') {
        $licData['lipaimp_poliza'] = null;
    }
    if (array_key_exists('lipaimp_observaciones', $licData) && trim($licData['lipaimp_observaciones']) === '') {
        $licData['lipaimp_observaciones'] = null;
    }

    $armasData = $validated['armas'];
    return [$licData, $armasData];
}


    /** Prepara arreglo de armas para insert masivo */
    protected function prepareArmas(array $armas, int $licenciaId): array
    {
        $out = [];
        foreach ($armas as $row) {
            $out[] = $this->prepareUnaArma($row, $licenciaId, /*forBulk*/ true);
        }
        // filtra nulos/filas incompletas por seguridad
        return array_values(array_filter($out));
    }

    /** Normaliza/calcula una fila de arma -> campos EXACTOS de tu tabla */
protected function prepareUnaArma(array $row, int $licenciaId, bool $forBulk = false): array
{
    $payload = [
        'arma_num_licencia' => $licenciaId, // Siempre usar el ID de la licencia creada/actualizada
        'arma_sub_cat'      => (int)$row['arma_sub_cat'],
        'arma_modelo'       => (int)$row['arma_modelo'],
        'arma_calibre'      => (int)$row['arma_calibre'], // 👈 NUEVO
        'arma_empresa'      => (int)$row['arma_empresa'],
        'arma_largo_canon'  => (float)$row['arma_largo_canon'],
        'arma_cantidad'     => (int)($row['arma_cantidad'] ?? 1),
    ];

    if (!$forBulk && !empty($row['arma_lic_id'])) {
        $payload['arma_lic_id'] = (int)$row['arma_lic_id'];
    }

    return $payload;
}


    /* ===================== HELPERS ===================== */

    protected function wantsJson(Request $request): bool
    {
        return $request->wantsJson() || $request->ajax();
    }

    protected function jsonOrRedirect(Request $request, array $payload, int $status, $redirect)
    {
        if ($this->wantsJson($request)) {
            return response()->json($payload, $status);
        }
        return $redirect;
    }

    protected function handleException(Request $request, Throwable $e, string $friendlyMessage)
    {
        // Log::error($e); // si quieres logear
        if ($this->wantsJson($request)) {
            return response()->json([
                'message' => $friendlyMessage,
                'detalle' => $e->getMessage(),
            ], 422);
        }
        return back()->with('error', $friendlyMessage)->with('detalle', $e->getMessage());
    }


    protected $table = 'pro_licencias_para_importacion';
    protected $primaryKey = 'lipaimp_id';

    // Mapa de estados
    public const ESTADOS = [
        1 => 'Pendiente',
        2 => 'Autorizado',
        3 => 'Rechazado',
        4 => 'En tránsito',
        5 => 'Recibido',
        6 => 'Vencido',
        7 => 'Recibido vencido',
    ];

    public function getLipaimpSituacionTextoAttribute(): string
    {
        return self::ESTADOS[$this->lipaimp_situacion] ?? '—';
    }

    public function getLipaimpSituacionBadgeClassAttribute(): string
    {
        return match ((int)$this->lipaimp_situacion) {
            1 => 'bg-amber-100 text-amber-800 ring-1 ring-amber-200',   // Pendiente
            2 => 'bg-green-100 text-green-800 ring-1 ring-green-200',   // Autorizado
            3 => 'bg-red-100 text-red-800 ring-1 ring-red-200',         // Rechazado
            4 => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',      // En tránsito
            5 => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200', // Recibido
            6 => 'bg-gray-300 text-gray-800 ring-1 ring-gray-400',      // Recibido vencido
            default => 'bg-slate-100 text-slate-700 ring-1 ring-slate-200',
        };
    }

    public function updateEstado(Request $request, int $id)
{

$request->validate([
    'lipaimp_situacion' => 'required|integer|in:1,2,3,4,5,6,7',
]);



    $licencia = Licencia::findOrFail($id);
    $licencia->lipaimp_situacion = $request->lipaimp_situacion;
    $licencia->save();

    if ($request->wantsJson()) {
        return response()->json([
            'ok' => true,
            'message' => 'Estado actualizado',
            'estado' => $licencia->lipaimp_situacion,
        ]);
    }

    return back()->with('success', 'Estado actualizado correctamente');
}

public function listDocumentos($licenciaId)
{
    try {
        $licencia = Licencia::findOrFail($licenciaId);
        $docs = $licencia->documentos()
            ->latest('doclicimport_id')
            ->get();

        return response()->json([
            'ok' => true,
            'docs' => $docs->map(function($doc) {
                return [
                    'doclicimport_id' => $doc->doclicimport_id,
                    'doclicimport_ruta' => $doc->doclicimport_ruta,
                    'doclicimport_num_lic' => $doc->doclicimport_num_lic,
                    'doclicimport_nombre_original' => $doc->doclicimport_nombre_original,
                    'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    'url' => asset('storage/' . $doc->doclicimport_ruta)
                ];
            })
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'ok' => false,
            'message' => 'Error al cargar documentos'
        ], 500);
    }
}




public function destroyDocumento($documento)
{
    try {
        \Log::info('Eliminando documento', ['documento_id' => $documento]);

        return DB::transaction(function () use ($documento) {
            // Buscar el documento
            $doc = ProDocumentacionLicImport::findOrFail($documento);

            // 1. Eliminar archivo físico si existe
            if (!empty($doc->doclicimport_ruta) && Storage::disk('public')->exists($doc->doclicimport_ruta)) {
                Storage::disk('public')->delete($doc->doclicimport_ruta);
                \Log::info('Archivo eliminado', ['ruta' => $doc->doclicimport_ruta]);
            }

            // 2. Eliminar registro de BD
            $doc->delete();

            // 3. (Opcional) borrar carpeta si quedó vacía
            $carpeta = dirname($doc->doclicimport_ruta);
            if ($carpeta && $carpeta !== '.' && Storage::disk('public')->exists($carpeta)) {
                $archivos = Storage::disk('public')->files($carpeta);
                $subdirs  = Storage::disk('public')->directories($carpeta);
                if (empty($archivos) && empty($subdirs)) {
                    Storage::disk('public')->deleteDirectory($carpeta);
                    \Log::info('Carpeta eliminada', ['carpeta' => $carpeta]);
                }
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Documento eliminado correctamente.'
            ]);
        });

    } catch (\Throwable $e) {
        \Log::error('Error eliminando documento', [
            'documento_id' => $documento,
            'error'        => $e->getMessage(),
        ]);

        return response()->json([
            'ok'      => false,
            'message' => 'Error eliminando documento',
            'error'   => $e->getMessage()
        ], 500);
    }
}

private function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    // fuerza a número
    $bytes = is_numeric($bytes) ? (float)$bytes : 0.0;

    $pow = ($bytes > 0) ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units) - 1);

    $bytes = $bytes / (1024 ** $pow);

    return round($bytes, (int)$precision).' '.$units[$pow];
}
public function serveComprobante(string $filename)
{
    try {
        // Decodificar y sanitizar
        $filename = urldecode($filename);
        $filename = basename($filename); // Evita path traversal
        
        // Ruta completa
        $path = storage_path('app/public/pagos/comprobantes/' . $filename);
        
        // Log para debug
        \Log::info('serveComprobante - Buscando archivo', [
            'filename' => $filename,
            'path' => $path,
            'exists' => file_exists($path)
        ]);
        
        // Verificar existencia
        if (!file_exists($path)) {
            \Log::error('Archivo no encontrado', [
                'path' => $path,
                'dir' => dirname($path),
                'files_in_dir' => file_exists(dirname($path)) ? scandir(dirname($path)) : []
            ]);
            abort(404, 'Archivo no encontrado');
        }
        
        // Obtener MIME type
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        
        // Retornar archivo
        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error en serveComprobante', [
            'filename' => $filename ?? 'unknown',
            'error' => $e->getMessage()
        ]);
        abort(500, 'Error al servir el archivo');
    }
}

}

