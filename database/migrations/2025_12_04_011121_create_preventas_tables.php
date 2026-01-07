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
        // Fix for production: Check if backup table exists and holds the FKs (from a failed future migration)
        if (Schema::hasTable('pro_preventas_backup')) {
            try {
                Schema::table('pro_preventas_backup', function (Blueprint $table) {
                    $table->dropForeign('pro_preventas_prev_cliente_id_foreign');
                });
            } catch (\Throwable $e) {}
            
            try {
                Schema::table('pro_preventas_backup', function (Blueprint $table) {
                    $table->dropForeign('pro_preventas_prev_producto_id_foreign');
                });
            } catch (\Throwable $e) {}
        }

        Schema::create('pro_preventas', function (Blueprint $table) {
            $table->id('prev_id');
            $table->unsignedInteger('prev_cliente_id');
            $table->unsignedBigInteger('prev_producto_id');
            $table->integer('prev_cantidad');
            $table->decimal('prev_monto_pagado', 10, 2)->default(0);
            $table->dateTime('prev_fecha');
            $table->text('prev_observaciones')->nullable();
            $table->enum('prev_estado', ['PENDIENTE', 'COMPLETADA', 'CANCELADA'])->default('PENDIENTE');
            $table->timestamps();

            $table->foreign('prev_cliente_id')->references('cliente_id')->on('pro_clientes');
            $table->foreign('prev_producto_id')->references('producto_id')->on('pro_productos');
        });

        Schema::create('pro_clientes_saldo', function (Blueprint $table) {
            $table->id('saldo_id');
            $table->unsignedInteger('saldo_cliente_id')->unique();
            $table->decimal('saldo_monto', 10, 2)->default(0);
            $table->timestamps();

            $table->foreign('saldo_cliente_id')->references('cliente_id')->on('pro_clientes');
        });

        Schema::create('pro_clientes_saldo_historial', function (Blueprint $table) {
            $table->id('historial_id');
            $table->unsignedInteger('historial_cliente_id');
            $table->enum('historial_tipo', ['ABONO', 'CARGO']); // ABONO = Suma saldo, CARGO = Resta saldo
            $table->decimal('historial_monto', 10, 2);
            $table->string('historial_referencia')->nullable();
            $table->dateTime('historial_fecha');
            $table->timestamps();

            $table->foreign('historial_cliente_id')->references('cliente_id')->on('pro_clientes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pro_clientes_saldo_historial');
        Schema::dropIfExists('pro_clientes_saldo');
        Schema::dropIfExists('pro_preventas');
    }
};
