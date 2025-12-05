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
        // Using raw SQL to avoid doctrine/dbal dependency issues
        DB::statement("ALTER TABLE pro_detalle_pagos MODIFY COLUMN det_pago_banco_id VARCHAR(100) NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting back to unsignedBigInteger (might fail if non-numeric data exists)
        // DB::statement("ALTER TABLE pro_detalle_pagos MODIFY COLUMN det_pago_banco_id BIGINT UNSIGNED NULL");
    }
};
