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
            $table->unsignedBigInteger('ps_detalle_pago_id')->nullable()->after('ps_venta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_pagos_subidos', function (Blueprint $table) {
            $table->dropColumn('ps_detalle_pago_id');
        });
    }
};
