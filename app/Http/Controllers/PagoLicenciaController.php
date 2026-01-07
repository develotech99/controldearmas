<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PagoLicencia;
use App\Models\PagoMetodo;
use App\Models\PagoComprobante;

class PagoLicenciaController extends Controller
{
    /**
     * Obtener todos los pagos de una licencia
     */
public function index($licenciaId)
{
    $pagos = PagoLicencia::with(['metodos.comprobantes'])
              ->where('pago_lic_licencia_id', $licenciaId)
              ->orderByDesc('pago_lic_id')
              ->get();

    return response()->json($pagos->map(fn($p) => $this->toApi($p))->values());
}

    /**
     * Crear un nuevo pago
     */
    public function store(Request $request, $licenciaId)
    {
        try {
            DB::beginTransaction();

            $payload = json_decode($request->input('payload', '{}'), true);
            Log::info('Creando pago:', ['payload' => $payload, 'licencia' => $licenciaId]);

            // Crear el pago principal - ESTRUCTURA SIMPLIFICADA
            $pago = PagoLicencia::create([
                'pago_lic_licencia_id' => $licenciaId,  // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
                'pago_lic_total' => $payload['pago_lic_total'] ?? 0,  // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
                'pago_lic_situacion' => $payload['pago_lic_situacion'] ?? 1,  // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
            ]);

            // Procesar métodos de pago
            $this->procesarMetodosPago($pago, $payload['metodos'] ?? [], $request);

            DB::commit();

            // Recargar con relaciones para devolver
            $pago->load(['metodos.comprobantes']);
            return response()->json($this->transformToApi($pago), 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al crear pago:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            $msg = 'Error al crear pago.';
            if (str_contains($e->getMessage(), 'Data too long')) {
                $msg = 'Uno de los campos excede la longitud permitida. Verifique los datos.';
            } elseif (config('app.debug')) {
                $msg .= ' ' . $e->getMessage();
            }
            
            return response()->json(['error' => $msg], 500);
        }
    }

    /**
     * Mostrar un pago específico
     */
    public function show(PagoLicencia $pago)
    {
        try {
            $pago->load(['metodos.comprobantes']);
            return response()->json($this->transformToApi($pago));

        } catch (\Exception $e) {
            Log::error('Error al mostrar pago:', ['error' => $e->getMessage(), 'pago_id' => $pago->pago_lic_id]);
            return response()->json(['error' => 'Error al obtener pago'], 500);
        }
        
    }

    

    /**
     * Actualizar un pago existente
     */
  public function update(Request $request, PagoLicencia $pago)
{
    $payload = json_decode($request->input('payload','{}'), true);

    DB::transaction(function () use ($payload, $request, $pago) {
        // (A) BORRADOS DE MÉTODOS EXISTENTES (y sus archivos)
        foreach (($payload['_deleted_metodos'] ?? []) as $delMet) {
            $this->deleteMetodoAndFiles((int)$delMet);   // 👈 AQUÍ USAS EL FOREACH DE MÉTODOS
        }

        // (B) UPSERT DEL ENCABEZADO
        $pago->fill([
            'pago_lic_total'     => $payload['pago_lic_total'] ?? 0,
            'pago_lic_situacion' => $payload['pago_lic_situacion'] ?? 1,
        ])->save();

        // (C) PROCESAR MÉTODOS (create/update + borrados de comprobantes + archivos nuevos)
        $this->procesarMetodosPago($pago, $payload['metodos'] ?? [], $request);
    });

    $pago->load(['metodos.comprobantes']);
    return response()->json($this->toApi($pago));
}

public function serveComprobante($filename)
{
    // Construir la ruta al archivo
    $filePath = storage_path('app/public/pagos/comprobantes/' . $filename);
    
    // Verificar que el archivo existe
    if (!file_exists($filePath)) {
        abort(404, 'Comprobante no encontrado: ' . $filename);
    }
    
    // Verificar que es un archivo (no directorio)
    if (!is_file($filePath)) {
        abort(403, 'Ruta inválida');
    }
    
    // Obtener información del archivo
    $mimeType = mime_content_type($filePath);
    $fileSize = filesize($filePath);
    
    // Headers para mostrar el archivo en el navegador (no descargar)
    $headers = [
        'Content-Type' => $mimeType,
        'Content-Length' => $fileSize,
        'Content-Disposition' => 'inline; filename="' . basename($filename) . '"',
        'Cache-Control' => 'public, max-age=31536000', // Cache por 1 año
        'Pragma' => 'public',
    ];
    
    return response()->file($filePath, $headers);
}
public function destroy(PagoLicencia $pago)
{
    try {
        // Cargar relaciones
        $pago->load(['metodos.comprobantes']);

        // 1) Recolectar rutas (solo las que existan en DB)
        $rutas = [];
        foreach ($pago->metodos as $m) {
            foreach ($m->comprobantes as $c) {
                if (!empty($c->comprob_ruta)) {
                    // normalizar separadores
                    $rutas[] = str_replace('\\', '/', $c->comprob_ruta);
                }
            }
        }

        // 2) Borrar en DB con cascada
        DB::transaction(function () use ($pago) {
            $pago->delete(); // FKs con ON DELETE CASCADE eliminan métodos y comprobantes
        });

        // 3) Intentar borrar archivos (si existen)
        foreach ($rutas as $ruta) {
            try {
                // No falla si no existe
                Storage::disk('public')->delete($ruta);
            } catch (\Throwable $e) {
                Log::warning('No se pudo borrar archivo (puede no existir).', [
                    'ruta' => $ruta,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['message' => 'Pago eliminado correctamente']);
    } catch (\Throwable $e) {
        Log::error('Error al eliminar pago', [
            'pago_id' => $pago->pago_lic_id ?? null,
            'error'   => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Error al eliminar pago'], 500);
    }
}
  public function serveFile($path)
{
    // Decodificar la ruta
    $path = urldecode($path);
    
    // Construir la ruta completa
    $filePath = storage_path('app/public/' . $path);
    
    // Verificar que el archivo existe y está dentro del directorio permitido
    if (!file_exists($filePath) || !str_starts_with(realpath($filePath), storage_path('app/public'))) {
        abort(404, 'Archivo no encontrado o acceso denegado');
    }
    
    if (!is_file($filePath)) {
        abort(403, 'Ruta inválida');
    }
    
    $mimeType = mime_content_type($filePath);
    $fileSize = filesize($filePath);
    
    $headers = [
        'Content-Type' => $mimeType,
        'Content-Length' => $fileSize,
        'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        'Cache-Control' => 'public, max-age=31536000',
        'Pragma' => 'public',
    ];
    
    return response()->file($filePath, $headers);
}
private function deleteComprobanteAndFile(int $id): void
{
    if ($c = PagoComprobante::find($id)) {
        $ruta = $c->comprob_ruta ? str_replace('\\', '/', $c->comprob_ruta) : null;
        $c->delete(); // borra fila
        if ($ruta) {
            try { Storage::disk('public')->delete($ruta); } catch (\Throwable $e) {
                \Log::warning('No se pudo borrar archivo de comprobante', ['ruta'=>$ruta,'error'=>$e->getMessage()]);
            }
        }
    }
}

private function deleteMetodoAndFiles(int $metodoId): void
{
    $m = PagoMetodo::with('comprobantes')->find($metodoId);
    if (!$m) return;
    foreach ($m->comprobantes as $c) {
        $this->deleteComprobanteAndFile($c->comprob_id);
    }
    $m->delete();
}


private function procesarMetodosPago(PagoLicencia $pago, array $metodos, Request $request)
{
    $filesPorMetodo = $request->file('files', []); // acepta files[mi][] y files[mi][ci]

    foreach ($metodos as $index => $mData) {
        // upsert método
        $metodo = !empty($mData['pagomet_id'])
            ? \App\Models\PagoMetodo::findOrFail($mData['pagomet_id'])
            : new \App\Models\PagoMetodo(['pagomet_pago_lic' => $pago->pago_lic_id]);

        $metodo->fill($this->getMetodoData($mData, $pago->pago_lic_id))->save(); // 👈 ya tiene pagomet_id

        // borrados de comprobantes (si el usuario quitó algunos)
        foreach (($mData['_deleted_comprobantes'] ?? []) as $delId) {
            $this->deleteComprobanteAndFile((int)$delId);
        }

        // archivos nuevos
        if (isset($filesPorMetodo[$index])) {
            $grupo = $filesPorMetodo[$index];
            if ($grupo instanceof \Illuminate\Http\UploadedFile) $grupo = [$grupo];
            foreach ($grupo as $maybeArray) {
                if (is_array($maybeArray)) {
                    foreach ($maybeArray as $file) $this->procesarComprobantes($metodo, $file);
                } else {
                    $this->procesarComprobantes($metodo, $maybeArray);
                }
            }
        }

        // actualizar metadatos de comprobantes existentes (sin archivo nuevo)
        $this->procesarComprobantesExistentes($metodo, $mData['comprobantes'] ?? []);
    }
}
  protected function toApi(PagoLicencia $pago): array
    {
        // Asegura relaciones cargadas
        $pago->loadMissing(['metodos.comprobantes']);

        return [
            'pago_lic_id'           => $pago->pago_lic_id,
            'pago_lic_licencia_id'  => $pago->pago_lic_licencia_id,
            'pago_lic_total'        => (float) $pago->pago_lic_total,
            'pago_lic_situacion'    => (int) $pago->pago_lic_situacion,

            'metodos' => $pago->metodos->map(function ($m) {
                return [
                    'pagomet_id'         => $m->pagomet_id,
                    'pagomet_metodo'     => $m->pagomet_metodo,
                    'pagomet_monto'      => (float) $m->pagomet_monto,
                    'pagomet_moneda'     => $m->pagomet_moneda,
                    'pagomet_referencia' => $m->pagomet_referencia,
                    'pagomet_banco'      => $m->pagomet_banco,
                    'pagomet_situacion'  => (int) $m->pagomet_situacion,
                    'pagomet_nota'       => $m->pagomet_nota,

                    'comprobantes' => $m->comprobantes->map(function ($c) {
                        return [
                            'comprob_id'              => $c->comprob_id,
                            'comprob_nombre_original' => $c->comprob_nombre_original,
                            'comprob_size_bytes'      => (int) $c->comprob_size_bytes,
                            'comprob_mime'            => $c->comprob_mime,
                            'comprob_url'             => Storage::disk('public')->url(str_replace('\\','/',$c->comprob_ruta)),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
private function procesarComprobantes(\App\Models\PagoMetodo $metodo, $archivos)
{
    if (!is_array($archivos)) $archivos = [$archivos];

    // Garantiza ID del método por si acaso
    if (!$metodo->pagomet_id) { $metodo->save(); $metodo->refresh(); }

    foreach ($archivos as $archivo) {
        if ($archivo && $archivo->isValid()) {
            $nombreOriginal   = $archivo->getClientOriginalName();
            $extension        = $archivo->getClientOriginalExtension();
            $nombreAlmacenado = time().'_'.uniqid().'.'.$extension;

            $ruta = $archivo->storeAs('pagos/comprobantes', $nombreAlmacenado, 'public');

            \App\Models\PagoComprobante::create([
                'comprob_pagomet_id'      => $metodo->pagomet_id,   // 👈 FK REAL AQUÍ
                'comprob_ruta'            => $ruta,
                'comprob_nombre_original' => $nombreOriginal,
                'comprob_size_bytes'      => $archivo->getSize(),
                'comprob_mime'            => $archivo->getClientMimeType(),
                'comprob_situacion'       => 1,
            ]);
        }
    }
}

 

  private function procesarComprobantesExistentes(PagoMetodo $metodo, array $comprobantes)
    {
        foreach ($comprobantes as $comprobanteData) {
            if (!empty($comprobanteData['comprob_id']) && empty($comprobanteData['file'])) {
                // Actualizar comprobante existente
                PagoComprobante::where('comprob_id', $comprobanteData['comprob_id'])
                    ->update([
                        'comprob_nombre_original' => $comprobanteData['comprob_nombre_original'] ?? null,
                        'comprob_size_bytes' => $comprobanteData['comprob_size_bytes'] ?? 0,
                        'comprob_mime' => $comprobanteData['comprob_mime'] ?? null,
                    ]);
            }
        }
    }

    private function getMetodoData(array $data, int $pagoId): array
    {
        return [
            'pagomet_pago_lic' => $pagoId,
            'pagomet_metodo' => $data['pagomet_metodo'] ?? null,
            'pagomet_monto' => $data['pagomet_monto'] ?? 0,
            'pagomet_moneda' => $data['pagomet_moneda'] ?? 'GTQ',
            'pagomet_referencia' => $data['pagomet_referencia'] ?? null,
            'pagomet_banco' => $data['pagomet_banco'] ?? null,
            'pagomet_nota' => $data['pagomet_nota'] ?? null,
            'pagomet_situacion' => $data['pagomet_situacion'] ?? 1,
        ];
    }
    private function transformToApi(PagoLicencia $pago): array
    {
        return [
            'pago_lic_id' => $pago->pago_lic_id,
            'pago_lic_licencia_id' => $pago->pago_lic_licencia_id,  // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
            'pago_lic_total' => (float) $pago->pago_lic_total,      // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
            'pago_lic_situacion' => (int) $pago->pago_lic_situacion, // ← VOLVIMOS A LA ESTRUCTURA ORIGINAL
            'metodos' => $pago->metodos->map(function ($metodo) {
                return [
                    'pagomet_id' => $metodo->pagomet_id,
                    'pagomet_metodo' => $metodo->pagomet_metodo,
                    'pagomet_monto' => (float) $metodo->pagomet_monto,
                    'pagomet_moneda' => $metodo->pagomet_moneda,
                    'pagomet_referencia' => $metodo->pagomet_referencia,
                    'pagomet_banco' => $metodo->pagomet_banco,
                    'pagomet_situacion' => (int) $metodo->pagomet_situacion,
                    'pagomet_nota' => $metodo->pagomet_nota,
                    'comprobantes' => $metodo->comprobantes->map(function ($comprobante) {
                        return [
                            'comprob_id' => $comprobante->comprob_id,
                            'comprob_nombre_original' => $comprobante->comprob_nombre_original,
                            'comprob_size_bytes' => (int) $comprobante->comprob_size_bytes,
                            'comprob_mime' => $comprobante->comprob_mime,
                            'comprob_url' => Storage::url($comprobante->comprob_ruta),
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }
}