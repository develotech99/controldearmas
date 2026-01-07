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
        Schema::table('pro_pagos_subidos', function (Blueprint $table) {
            $table->unsignedBigInteger('ps_deuda_id')->nullable()->after('ps_venta_id');
            // Optional: Add FK if pro_deudas_clientes exists
            // $table->foreign('ps_deuda_id')->references('deuda_id')->on('pro_deudas_clientes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_pagos_subidos', function (Blueprint $table) {
            $table->dropColumn('ps_deuda_id');
        });
    }
};
