<?php

use App\Models\Producto;
use App\Models\Ventas;
use App\Models\SerieProducto;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sku = 'ARM-SYS-0012';
$producto = Producto::where('pro_codigo_sku', $sku)->first();

if (!$producto) {
    echo "Producto no encontrado.\n";
    exit;
}

echo "Producto: {$producto->producto_nombre} (ID: {$producto->producto_id})\n";

// 1. Check StockActual
$stock = DB::table('pro_stock_actual')->where('stock_producto_id', $producto->producto_id)->first();
echo "\n--- Stock Actual ---\n";
echo "Total: {$stock->stock_cantidad_total}\n";
echo "Disponible: {$stock->stock_cantidad_disponible}\n";
echo "Reservada (Venta Pendiente): {$stock->stock_cantidad_reservada}\n";
echo "Reservada2 (En Reserva): {$stock->stock_cantidad_reservada2}\n";

// 2. Check Series Breakdown
echo "\n--- Series Breakdown ---\n";
$seriesStats = DB::table('pro_series_productos')
    ->where('serie_producto_id', $producto->producto_id)
    ->select('serie_estado', 'serie_situacion', DB::raw('count(*) as count'))
    ->groupBy('serie_estado', 'serie_situacion')
    ->get();

foreach ($seriesStats as $stat) {
    echo "Estado: {$stat->serie_estado} | Situacion: {$stat->serie_situacion} | Count: {$stat->count}\n";
}

// 3. Find Sales holding these 'pendiente' series
echo "\n--- Sales holding 'pendiente' series ---\n";
$pendienteSeries = DB::table('pro_series_productos')
    ->where('serie_producto_id', $producto->producto_id)
    ->where('serie_estado', 'pendiente')
    ->pluck('serie_id');

if ($pendienteSeries->isEmpty()) {
    echo "No pending series found.\n";
} else {
    // Find movements for these series that are 'venta' type and situation 3 (reserved/pending)
    $movements = DB::table('pro_movimientos')
        ->whereIn('mov_serie_id', $pendienteSeries)
        ->where('mov_tipo', 'venta')
        //->where('mov_situacion', 3) // Assuming 3 is reserved
        ->orderBy('mov_fecha', 'desc')
        ->get();

    $salesFound = [];

    foreach ($movements as $mov) {
        if (preg_match('/VENTA-(\d+)/', $mov->mov_documento_referencia, $matches)) {
            $ventaId = $matches[1];
            if (!isset($salesFound[$ventaId])) {
                $venta = DB::table('pro_ventas')->where('ven_id', $ventaId)->first();
                $salesFound[$ventaId] = [
                    'fecha' => $venta->ven_fecha ?? 'N/A',
                    'cliente' => $venta->ven_cliente_nombre ?? 'N/A',
                    'situacion' => $venta->ven_situacion ?? 'N/A',
                    'series_count' => 0
                ];
            }
            $salesFound[$ventaId]['series_count']++;
        }
    }

    foreach ($salesFound as $id => $info) {
        echo "Venta ID: $id | Fecha: {$info['fecha']} | Cliente: {$info['cliente']} | Situacion: {$info['situacion']} | Series: {$info['series_count']}\n";
    }
}
