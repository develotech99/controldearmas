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
        if (!Schema::hasTable('pro_clientes_documentos')) {
            Schema::create('pro_clientes_documentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('cliente_id');
                $table->enum('tipo', ['TENENCIA', 'PORTACION']);
                $table->string('numero_documento'); // Tenencia # or Codigo 1
                $table->string('numero_secundario')->nullable(); // Propietario # or Codigo 2
                $table->date('fecha_vencimiento')->nullable();
                $table->string('imagen_path')->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->foreign('cliente_id')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pro_clientes_documentos');
    }
};
