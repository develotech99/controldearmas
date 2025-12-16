<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$values = DB::table('pro_ventas')->distinct()->pluck('ven_situacion');
echo "Distinct values in pro_ventas.ven_situacion:\n";
foreach ($values as $val) {
    echo "- " . $val . "\n";
}

$valuesDet = DB::table('pro_detalle_ventas')->distinct()->pluck('det_situacion');
echo "Distinct values in pro_detalle_ventas.det_situacion:\n";
foreach ($valuesDet as $val) {
    echo "- " . $val . "\n";
}
