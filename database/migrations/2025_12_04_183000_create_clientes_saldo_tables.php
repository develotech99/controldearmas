<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('pro_clientes_saldo')) {
            Schema::create('pro_clientes_saldo', function (Blueprint $table) {
                $table->id('saldo_id');
                $table->unsignedInteger('saldo_cliente_id')->unique(); // One balance record per client
                $table->decimal('saldo_monto', 10, 2)->default(0);
                $table->timestamps();

                $table->foreign('saldo_cliente_id')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('pro_clientes_saldo_historial')) {
            Schema::create('pro_clientes_saldo_historial', function (Blueprint $table) {
                $table->id('hist_id');
                $table->unsignedInteger('hist_cliente_id');
                $table->string('hist_tipo', 20); // 'ABONO', 'CARGO'
                $table->decimal('hist_monto', 10, 2);
                $table->decimal('hist_saldo_anterior', 10, 2);
                $table->decimal('hist_saldo_nuevo', 10, 2);
                $table->string('hist_referencia')->nullable(); // ID Venta, ID Recibo, etc.
                $table->text('hist_observaciones')->nullable();
                $table->timestamps();

                $table->foreign('hist_cliente_id')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pro_clientes_saldo_historial');
        Schema::dropIfExists('pro_clientes_saldo');
    }
};
