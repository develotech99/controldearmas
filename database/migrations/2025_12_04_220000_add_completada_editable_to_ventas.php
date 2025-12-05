<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add COMPLETADA and EDITABLE states to ven_situacion enum
        DB::statement("ALTER TABLE pro_ventas MODIFY COLUMN ven_situacion ENUM('ACTIVA', 'ANULADA', 'PENDIENTE', 'RESERVADA', 'AUTORIZADA', 'EDITABLE', 'COMPLETADA') DEFAULT 'ACTIVA'");
    }

    public function down(): void
    {
        // Revert to previous enum values
        DB::statement("ALTER TABLE pro_ventas MODIFY COLUMN ven_situacion ENUM('ACTIVA', 'ANULADA', 'PENDIENTE', 'RESERVADA', 'AUTORIZADA') DEFAULT 'ACTIVA'");
    }
};
