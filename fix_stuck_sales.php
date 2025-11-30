<?php

use Illuminate\Support\Facades\DB;
use App\Models\Ventas;
use App\Models\SerieProducto;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Iniciando reparación de ventas estancadas...\n";

// Ventas a reparar
$ventasIds = [16, 17, 19];

foreach ($ventasIds as $venId) {
    echo "\nProcesando Venta #{$venId}...\n";
    
    $venta = DB::table('pro_ventas')->where('ven_id', $venId)->first();
    
    if (!$venta) {
        echo "Venta no encontrada.\n";
        continue;
    }

    echo "Estado actual: {$venta->ven_situacion}\n";

    DB::transaction(function () use ($venta, $venId) {
        // 1. Asegurar que la venta esté ACTIVA
        if ($venta->ven_situacion !== 'ACTIVA') {
            DB::table('pro_ventas')
                ->where('ven_id', $venId)
                ->update(['ven_situacion' => 'ACTIVA']);
            echo "-> Venta marcada como ACTIVA.\n";
            
            DB::table('pro_detalle_ventas')
                ->where('det_ven_id', $venId)
                ->update(['det_situacion' => 'ACTIVA']);
            echo "-> Detalles marcados como ACTIVOS.\n";
        }

        // 2. Procesar Series
        $refVenta = 'VENTA-' . $venId;
        
        // Buscar movimientos reservados (situacion 3)
        $seriesMovs = DB::table('pro_movimientos')
            ->where('mov_documento_referencia', $refVenta)
            ->where('mov_situacion', 3) // Reservado
            ->whereNotNull('mov_serie_id')
            ->get();

        if ($seriesMovs->isEmpty()) {
            echo "-> No se encontraron movimientos reservados (ya procesados o inexistentes).\n";
            
            // Caso especial Venta #16: Puede que los movimientos no estén pero las series sigan pendientes
            // Forzar actualización de series si están pendientes y asociadas a esta venta (via logs o inferencia)
            // Pero mejor confiamos en los movimientos. Si no hay movimientos reservados, asumimos que ya se procesaron o se perdieron.
            // Para #16, verifiquemos si las series están pendientes.
        } else {
            echo "-> Encontrados " . $seriesMovs->count() . " movimientos reservados.\n";
            
            foreach ($seriesMovs as $mov) {
                // Actualizar serie a vendida
                DB::table('pro_series_productos')
                    ->where('serie_id', $mov->mov_serie_id)
                    ->update(['serie_estado' => 'vendido', 'serie_situacion' => 1]);
                
                // Actualizar movimiento a confirmado
                DB::table('pro_movimientos')
                    ->where('mov_id', $mov->mov_id)
                    ->update(['mov_situacion' => 1]);
                
                // Descontar de stock (reservado y total)
                DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $mov->mov_producto_id)
                    ->decrement('stock_cantidad_reservada', $mov->mov_cantidad);
                    
                DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $mov->mov_producto_id)
                    ->decrement('stock_cantidad_disponible', $mov->mov_cantidad);
                    
                DB::table('pro_stock_actual')
                    ->where('stock_producto_id', $mov->mov_producto_id)
                    ->decrement('stock_cantidad_total', $mov->mov_cantidad);
            }
            echo "-> Stock y series actualizados correctamente.\n";
        }
    });
}

echo "\nReparación completada.\n";
