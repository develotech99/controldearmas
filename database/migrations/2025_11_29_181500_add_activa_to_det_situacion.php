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
        // Add 'ACTIVA' to det_situacion enum in pro_detalle_ventas to match code usage
        DB::statement("ALTER TABLE pro_detalle_ventas MODIFY COLUMN det_situacion ENUM('ACTIVO', 'ACTIVA', 'ANULADA', 'PENDIENTE', 'AUTORIZADA') DEFAULT 'ACTIVO'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to previous enum list
        DB::statement("ALTER TABLE pro_detalle_ventas MODIFY COLUMN det_situacion ENUM('ACTIVO', 'ANULADA', 'PENDIENTE', 'AUTORIZADA') DEFAULT 'ACTIVO'");
    }
};
