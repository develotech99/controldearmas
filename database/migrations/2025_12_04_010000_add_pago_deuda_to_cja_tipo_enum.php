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
        // Modify the enum column to include 'PAGO_DEUDA'
        // Note: We must include all existing values to avoid data loss
        DB::statement("ALTER TABLE cja_historial MODIFY COLUMN cja_tipo ENUM('VENTA', 'IMPORTACION', 'EGRESO', 'DEPOSITO', 'AJUSTE_POS', 'PAGO_DEUDA') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        // WARNING: This might fail if there are records with 'PAGO_DEUDA'
        // We generally don't revert data-loss changes, but for completeness:
        DB::statement("ALTER TABLE cja_historial MODIFY COLUMN cja_tipo ENUM('VENTA', 'IMPORTACION', 'EGRESO', 'DEPOSITO', 'AJUSTE_POS') NOT NULL");
    }
};
