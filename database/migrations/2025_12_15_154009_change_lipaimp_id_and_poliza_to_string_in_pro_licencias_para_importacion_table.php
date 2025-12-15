<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar Foreign Keys existentes (las que quedan)
        Schema::table('pro_inventario_modelos', function (Blueprint $table) {
            $table->dropForeign(['modelo_licencia']);
        });

        Schema::table('pro_licencia_asignacion_producto', function (Blueprint $table) {
            $table->dropForeign(['asignacion_licencia_id']);
        });

        // Nota: Las FKs de pro_pagos_licencias, pro_armas_licenciadas y pro_documentacion_lic_import 
        // ya fueron eliminadas en intentos anteriores fallidos.

        // 2. Modificar la tabla principal (Licencias)
        Schema::table('pro_licencias_para_importacion', function (Blueprint $table) {
            $table->string('lipaimp_id', 50)->change();
            $table->string('lipaimp_poliza', 50)->nullable()->change();
        });

        // 3. Modificar tablas relacionadas
        Schema::table('pro_armas_licenciadas', function (Blueprint $table) {
            $table->string('arma_num_licencia', 50)->change();
        });

        Schema::table('pro_documentacion_lic_import', function (Blueprint $table) {
            $table->string('doclicimport_num_lic', 50)->change();
        });

        Schema::table('pro_pagos_licencias', function (Blueprint $table) {
            $table->string('pago_lic_licencia_id', 50)->change();
        });

        Schema::table('pro_inventario_modelos', function (Blueprint $table) {
            $table->string('modelo_licencia', 50)->change();
        });

        Schema::table('pro_licencia_asignacion_producto', function (Blueprint $table) {
            $table->string('asignacion_licencia_id', 50)->change();
        });

        // 4. Restaurar/Crear TODAS las Foreign Keys
        Schema::table('pro_armas_licenciadas', function (Blueprint $table) {
            $table->foreign('arma_num_licencia')
                  ->references('lipaimp_id')
                  ->on('pro_licencias_para_importacion')
                  ->onDelete('cascade');
        });

        Schema::table('pro_documentacion_lic_import', function (Blueprint $table) {
            $table->foreign('doclicimport_num_lic')
                  ->references('lipaimp_id')
                  ->on('pro_licencias_para_importacion')
                  ->onDelete('cascade');
        });

        Schema::table('pro_pagos_licencias', function (Blueprint $table) {
            $table->foreign('pago_lic_licencia_id')
                  ->references('lipaimp_id')
                  ->on('pro_licencias_para_importacion')
                  ->onDelete('cascade');
        });

        Schema::table('pro_inventario_modelos', function (Blueprint $table) {
            $table->foreign('modelo_licencia')
                  ->references('lipaimp_id')
                  ->on('pro_licencias_para_importacion')
                  ->onDelete('cascade');
        });

        Schema::table('pro_licencia_asignacion_producto', function (Blueprint $table) {
            $table->foreign('asignacion_licencia_id')
                  ->references('lipaimp_id')
                  ->on('pro_licencias_para_importacion')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Se deja vacío
    }
};
