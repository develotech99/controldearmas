<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migration = DB::table('migrations')->where('migration', '2025_12_16_170000_add_editable_to_det_situacion')->first();
if ($migration) {
    echo "Migration exists in table.\n";
} else {
    echo "Migration does NOT exist in table.\n";
}
