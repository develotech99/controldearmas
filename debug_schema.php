<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$columns = Schema::getColumnListing('pro_licencias_para_importacion');
echo "Columns: " . implode(', ', $columns) . "\n";

$type = Schema::getColumnType('pro_licencias_para_importacion', 'lipaimp_id');
echo "lipaimp_id type: " . $type . "\n";

if (in_array('lipaimp_numero', $columns)) {
    echo "lipaimp_numero exists.\n";
} else {
    echo "lipaimp_numero MISSING.\n";
}

// Check migration status
$migration = DB::table('migrations')->where('migration', 'like', '%restructure_licencias_table%')->first();
if ($migration) {
    echo "Migration entry found: " . $migration->migration . " (Batch: " . $migration->batch . ")\n";
} else {
    echo "Migration entry NOT found.\n";
}
