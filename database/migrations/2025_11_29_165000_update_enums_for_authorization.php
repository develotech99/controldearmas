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
        // Update pro_ventas ven_situacion
        DB::statement("ALTER TABLE pro_ventas MODIFY COLUMN ven_situacion ENUM('ACTIVA', 'ANULADA', 'PENDIENTE', 'RESERVADA', 'AUTORIZADA') DEFAULT 'ACTIVA'");

        // Update pro_detalle_ventas det_situacion
        DB::statement("ALTER TABLE pro_detalle_ventas MODIFY COLUMN det_situacion ENUM('ACTIVO', 'ANULADA', 'PENDIENTE', 'AUTORIZADA') DEFAULT 'ACTIVO'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert pro_ventas ven_situacion (WARNING: 'AUTORIZADA' values will cause issues if present, but this is best effort)
        DB::statement("ALTER TABLE pro_ventas MODIFY COLUMN ven_situacion ENUM('ACTIVA', 'ANULADA', 'PENDIENTE', 'RESERVADA') DEFAULT 'ACTIVA'");

        // Revert pro_detalle_ventas det_situacion
        DB::statement("ALTER TABLE pro_detalle_ventas MODIFY COLUMN det_situacion ENUM('ACTIVO', 'ANULADA', 'PENDIENTE') DEFAULT 'ACTIVO'");
    }
};
