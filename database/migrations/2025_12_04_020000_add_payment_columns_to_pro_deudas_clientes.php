<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pro_deudas_clientes', function (Blueprint $table) {
            $table->decimal('monto_pagado', 10, 2)->default(0)->after('monto');
            $table->decimal('saldo_pendiente', 10, 2)->default(0)->after('monto_pagado');
        });

        // Update existing records to set saldo_pendiente = monto
        DB::statement('UPDATE pro_deudas_clientes SET saldo_pendiente = monto WHERE saldo_pendiente = 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_deudas_clientes', function (Blueprint $table) {
            $table->dropColumn(['monto_pagado', 'saldo_pendiente']);
        });
    }
};
