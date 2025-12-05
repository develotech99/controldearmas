<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Rename existing table to backup if it exists
        if (Schema::hasTable('pro_preventas')) {
            Schema::table('pro_preventas', function (Blueprint $table) {
                $table->dropForeign('pro_preventas_prev_cliente_id_foreign');
            });
            Schema::rename('pro_preventas', 'pro_preventas_backup');
        } elseif (Schema::hasTable('pro_preventas_backup')) {
            // If table was already renamed but migration failed later, ensure FK is gone
            try {
                Schema::table('pro_preventas_backup', function (Blueprint $table) {
                    $table->dropForeign('pro_preventas_prev_cliente_id_foreign');
                });
            } catch (\Throwable $e) {
                // Ignore if FK doesn't exist on backup
            }
        }

        // 2. Create new Header table
        Schema::create('pro_preventas', function (Blueprint $table) {
            $table->id('prev_id');
            $table->unsignedInteger('prev_cliente_id');
            $table->date('prev_fecha');
            $table->decimal('prev_total', 10, 2)->default(0);
            $table->decimal('prev_monto_pagado', 10, 2)->default(0); // Anticipo total
            $table->text('prev_observaciones')->nullable();
            $table->string('prev_estado', 20)->default('PENDIENTE'); // PENDIENTE, COMPLETADA, ANULADA
            $table->timestamps();

            $table->foreign('prev_cliente_id')->references('cliente_id')->on('pro_clientes');
        });

        // 3. Create Details table
        Schema::create('pro_detalle_preventas', function (Blueprint $table) {
            $table->id('det_prev_id');
            $table->unsignedBigInteger('prev_id');
            $table->unsignedBigInteger('producto_id');
            $table->integer('det_cantidad');
            $table->decimal('det_precio_unitario', 10, 2);
            $table->decimal('det_subtotal', 10, 2);
            $table->timestamps();

            $table->foreign('prev_id')->references('prev_id')->on('pro_preventas')->onDelete('cascade');
            $table->foreign('producto_id')->references('producto_id')->on('pro_productos');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pro_detalle_preventas');
        Schema::dropIfExists('pro_preventas');

        if (Schema::hasTable('pro_preventas_backup')) {
            Schema::rename('pro_preventas_backup', 'pro_preventas');
        }
    }
};
