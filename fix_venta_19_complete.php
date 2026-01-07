<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ventaId = 19;
$refVenta = 'VENTA-' . $ventaId;

echo "Iniciando corrección manual completa para Venta #$ventaId...\n";

DB::transaction(function () use ($ventaId, $refVenta) {
    // 1. Revertir estado de la venta
    DB::table('pro_ventas')
        ->where('ven_id', $ventaId)
        ->update([
            'ven_situacion' => 'PENDIENTE',
            'ven_observaciones' => DB::raw("CONCAT(ven_observaciones, ' [Corrección manual SQL]')")
        ]);
    echo "- Estado de venta actualizado a PENDIENTE.\n";

    // 2. Revertir estado de los detalles
    DB::table('pro_detalle_ventas')
        ->where('det_ven_id', $ventaId)
        ->update(['det_situacion' => 'PENDIENTE']);
    echo "- Detalles de venta actualizados a PENDIENTE.\n";

    // 3. Revertir Movimientos (Vendido 1 -> Reservado 3)
    $movs = DB::table('pro_movimientos')
        ->where('mov_documento_referencia', $refVenta)
        ->where('mov_situacion', 1) // Solo los que están como vendidos
        ->get();

    $countMovs = 0;
    foreach ($movs as $mov) {
        // Update movimiento
        DB::table('pro_movimientos')
            ->where('mov_id', $mov->mov_id)
            ->update(['mov_situacion' => 3]);
        
        // Update serie si existe
        if ($mov->mov_serie_id) {
            DB::table('pro_series_productos')
                ->where('serie_id', $mov->mov_serie_id)
                ->update(['serie_estado' => 'pendiente', 'serie_situacion' => 0]);
        }
        
        // Update Stock
        DB::table('pro_stock_actual')
            ->where('stock_producto_id', $mov->mov_producto_id)
            ->increment('stock_cantidad_reservada', $mov->mov_cantidad);

        DB::table('pro_stock_actual')
            ->where('stock_producto_id', $mov->mov_producto_id)
            ->increment('stock_cantidad_total', $mov->mov_cantidad);
            
        DB::table('pro_stock_actual')
            ->where('stock_producto_id', $mov->mov_producto_id)
            ->increment('stock_cantidad_disponible', $mov->mov_cantidad);

        $countMovs++;
    }
    echo "- $countMovs movimientos revertidos a RESERVADO y stock ajustado.\n";

    // 4. Eliminar facturacion_series para liberar los productos facturados
    // Buscar facturas anuladas de esta venta
    $facturasAnuladas = DB::table('facturacion')
        ->where('fac_venta_id', $ventaId)
        ->where('fac_estado', 'ANULADO')
        ->pluck('fac_id');

    if ($facturasAnuladas->isNotEmpty()) {
        $detallesFactura = DB::table('facturacion_detalle')
            ->whereIn('det_fac_factura_id', $facturasAnuladas)
            ->pluck('det_fac_id');
        
        if ($detallesFactura->isNotEmpty()) {
            $deletedSeries = DB::table('facturacion_series')
                ->whereIn('fac_detalle_id', $detallesFactura)
                ->delete();
            echo "- $deletedSeries registros eliminados de facturacion_series.\n";
        }
    }
    
    // 5. Revertir cantidad facturada en detalle de venta
    // Esto es importante para que el sistema permita volver a facturar
    $facturasAnuladasIds = $facturasAnuladas->toArray();
    if (!empty($facturasAnuladasIds)) {
        $detallesFacturaRows = DB::table('facturacion_detalle')
            ->whereIn('det_fac_factura_id', $facturasAnuladasIds)
            ->get();

        foreach ($detallesFacturaRows as $df) {
            if ($df->det_fac_detalle_venta_id) {
                // Resetear cantidad facturada a 0 (o restar lo que se anuló)
                // Asumimos que queremos limpiar todo lo de esta factura anulada
                // Pero cuidado si ya se había restado antes.
                // Si la factura está ANULADA, el código de anulación DEBIÓ haber restado.
                // Si corremos este script es porque algo falló.
                // Vamos a forzar a 0 si es la única factura, o restar.
                // Mejor restar la cantidad de esta factura específica.
                
                // Verificar si ya se restó? Difícil saber.
                // Vamos a asumir que NO se restó correctamente o queremos asegurar.
                // Pero si restamos doble, queda negativo.
                // Mejor setear det_cantidad_facturada = 0 para esta venta si no hay otras facturas activas.
                
                $otrasFacturasActivas = DB::table('facturacion')
                    ->where('fac_venta_id', $ventaId)
                    ->where('fac_estado', '!=', 'ANULADO')
                    ->exists();
                
                if (!$otrasFacturasActivas) {
                    DB::table('pro_detalle_ventas')
                        ->where('det_id', $df->det_fac_detalle_venta_id)
                        ->update(['det_cantidad_facturada' => 0]);
                }
            }
        }
        echo "- Cantidades facturadas reseteadas (si no hay otras facturas activas).\n";
    }

});

echo "Corrección completada con éxito.\n";
