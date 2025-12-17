<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$migration = DB::table('migrations')->where('migration', '2025_12_15_160000_restructure_licencias_table')->first();
if ($migration) {
    echo "Migration exists in table.\n";
} else {
    echo "Migration does NOT exist in table.\n";
}
