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
        Schema::create('pro_deudas_clientes', function (Blueprint $table) {
            $table->id('deuda_id');
            $table->unsignedBigInteger('cliente_id');
            $table->decimal('monto', 10, 2);
            $table->string('descripcion')->nullable();
            $table->date('fecha_deuda');
            $table->enum('estado', ['PENDIENTE', 'PAGADO'])->default('PENDIENTE');
            $table->dateTime('fecha_pago')->nullable();
            $table->unsignedBigInteger('user_id'); // Usuario que registró la deuda
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cliente_id')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_deudas_clientes');
    }
};
