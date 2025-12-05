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
            $table->unsignedInteger('ps_venta_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_pagos_subidos', function (Blueprint $table) {
            $table->unsignedInteger('ps_venta_id')->nullable(false)->change();
        });
    }
};
