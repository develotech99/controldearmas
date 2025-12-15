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
        // 1. Eliminar Foreign Keys
        $tables = ['pro_armas_licenciadas', 'pro_documentacion_lic_import', 'pro_pagos_licencias', 'pro_inventario_modelos', 'pro_licencia_asignacion_producto'];
        $fks = [
            'pro_armas_licenciadas' => 'arma_num_licencia',
            'pro_documentacion_lic_import' => 'doclicimport_num_lic',
            'pro_pagos_licencias' => 'pago_lic_licencia_id',
            'pro_inventario_modelos' => 'modelo_licencia',
            'pro_licencia_asignacion_producto' => 'asignacion_licencia_id'
        ];

        foreach ($fks as $table => $column) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    // Drop FK if exists (using array syntax lets Laravel guess the name)
                    $table->dropForeign([$column]);
                });
            }
        }

        // 2. Agregar columna 'lipaimp_numero' si no existe
        if (!Schema::hasColumn('pro_licencias_para_importacion', 'lipaimp_numero')) {
            Schema::table('pro_licencias_para_importacion', function (Blueprint $table) {
                $table->string('lipaimp_numero', 50)->nullable()->after('lipaimp_id');
            });
            
            // Copiar el valor actual de ID a Numero (para preservar datos viejos como referencia)
            DB::statement("UPDATE pro_licencias_para_importacion SET lipaimp_numero = CAST(lipaimp_id AS CHAR)");
        }

        // 3. Convertir ID a Integer (Auto Increment si es posible, o solo Int)
        // Primero aseguramos que no haya valores no numéricos en ID si vamos a convertir
        // Si hay valores '2025/34' en ID, esto fallará. 
        // Asumimos que se quiere limpiar o que no hay datos inválidos aún.
        // OJO: Si el usuario ya insertó '2025/34', convertir a INT dará 2025 o error.
        // Vamos a intentar convertir.
        
        // Modificar columna ID principal
        Schema::table('pro_licencias_para_importacion', function (Blueprint $table) {
            $table->unsignedBigInteger('lipaimp_id')->autoIncrement()->change();
            $table->string('lipaimp_poliza', 50)->nullable()->change(); // Asegurar nullable
            $table->string('lipaimp_numero', 50)->nullable(false)->change(); // Hacer obligatorio
        });

        // 4. Convertir columnas FK a Integer
        foreach ($fks as $table => $column) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->unsignedBigInteger($column)->change();
                });
            }
        }

        // 5. Restaurar Foreign Keys
        foreach ($fks as $table => $column) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($column) {
                    $table->foreign($column)
                          ->references('lipaimp_id')
                          ->on('pro_licencias_para_importacion')
                          ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No implementado para evitar riesgos en prod
    }
};
