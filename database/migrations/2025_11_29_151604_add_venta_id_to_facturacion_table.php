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
        Schema::table('facturacion', function (Blueprint $table) {
            $table->unsignedInteger('fac_venta_id')->nullable()->after('fac_id');
            $table->foreign('fac_venta_id')->references('ven_id')->on('pro_ventas')->onDelete('set null');
        });

        Schema::table('facturacion_detalle', function (Blueprint $table) {
            $table->unsignedBigInteger('det_fac_producto_id')->nullable()->after('det_fac_tipo');
            $table->foreign('det_fac_producto_id')->references('producto_id')->on('pro_productos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturacion', function (Blueprint $table) {
            $table->dropForeign(['fac_venta_id']);
            $table->dropColumn('fac_venta_id');
        });

        Schema::table('facturacion_detalle', function (Blueprint $table) {
            $table->dropForeign(['det_fac_producto_id']);
            $table->dropColumn('det_fac_producto_id');
        });
    }
};
