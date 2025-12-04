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
        if (!Schema::hasTable('pro_deudas_abonos')) {
            Schema::create('pro_deudas_abonos', function (Blueprint $table) {
                $table->id('abono_id');
                $table->unsignedBigInteger('deuda_id');
                $table->decimal('monto', 10, 2);
                $table->string('metodo_pago'); // EFECTIVO, TARJETA, TRANSFERENCIA, CHEQUE
                $table->string('referencia')->nullable(); // No. Autorización, Cheque, Transferencia
                $table->string('nota')->nullable();
                $table->unsignedBigInteger('user_id'); // Usuario que registró el pago
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('deuda_id')->references('deuda_id')->on('pro_deudas_clientes')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_deudas_abonos');
    }
};
