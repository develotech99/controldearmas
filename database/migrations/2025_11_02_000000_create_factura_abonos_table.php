<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_abonos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('factura_id');
            $table->unsignedInteger('numero'); 
            $table->date('fecha_vencimiento');
            $table->decimal('monto', 14, 2);
            $table->timestamps();

            $table->foreign('factura_id')
                  ->references('fac_id')->on('facturacion')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_abonos');
    }
};
