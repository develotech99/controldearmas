<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Dropping tables...\n";

Schema::disableForeignKeyConstraints();
Schema::dropIfExists('pro_clientes_saldo_historial');
Schema::dropIfExists('pro_clientes_saldo');
Schema::dropIfExists('pro_preventas');
Schema::enableForeignKeyConstraints();

echo "Tables dropped.\n";
