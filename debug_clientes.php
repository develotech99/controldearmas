<?php

use App\Models\Clientes;
use Illuminate\Support\Facades\Log;

try {
    echo "Testing Clientes search...\n";
    $clientes = Clientes::with(['empresas', 'saldo'])
        ->where('cliente_situacion', 1)
        ->limit(1)
        ->get();
    
    echo "Success! Found " . $clientes->count() . " clients.\n";
    if ($clientes->count() > 0) {
        print_r($clientes->first()->toArray());
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
