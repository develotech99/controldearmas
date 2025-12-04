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
        Schema::create('facturacion_series', function (Blueprint $table) {
            $table->id('fac_serie_id');
            $table->unsignedBigInteger('fac_detalle_id');
            $table->unsignedBigInteger('serie_id');
            
            // Foreign keys
            // Assuming facturacion_detalle uses det_fac_id as PK (BigInteger)
            $table->foreign('fac_detalle_id')->references('det_fac_id')->on('facturacion_detalle')->onDelete('cascade');
            // Assuming pro_series_productos uses serie_id as PK (Integer)
            $table->foreign('serie_id')->references('serie_id')->on('pro_series_productos')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturacion_series');
    }
};
