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
        Schema::table('pro_detalle_ventas', function (Blueprint $table) {
            $table->integer('det_cantidad_facturada')->default(0)->after('det_cantidad');
        });

        Schema::table('facturacion_detalle', function (Blueprint $table) {
            $table->unsignedInteger('det_fac_detalle_venta_id')->nullable()->after('det_fac_producto_id');
            $table->foreign('det_fac_detalle_venta_id')->references('det_id')->on('pro_detalle_ventas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_detalle_ventas', function (Blueprint $table) {
            $table->dropColumn('det_cantidad_facturada');
        });

        Schema::table('facturacion_detalle', function (Blueprint $table) {
            $table->dropForeign(['det_fac_detalle_venta_id']);
            $table->dropColumn('det_fac_detalle_venta_id');
        });
    }
};
