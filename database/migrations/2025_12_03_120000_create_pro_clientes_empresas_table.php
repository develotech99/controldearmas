<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Crear tabla pro_clientes_empresas
        Schema::create('pro_clientes_empresas', function (Blueprint $table) {
            $table->id('emp_id');
            $table->unsignedInteger('emp_cliente_id'); // Referencia a pro_clientes.cliente_id (que es int increment)
            $table->string('emp_nombre', 250);
            $table->string('emp_nit', 20)->nullable();
            $table->string('emp_direccion', 255)->nullable();
            $table->string('emp_telefono', 30)->nullable();
            $table->integer('emp_situacion')->default(1);
            $table->timestamps();

            $table->foreign('emp_cliente_id')->references('cliente_id')->on('pro_clientes')->onDelete('cascade');
            $table->index('emp_cliente_id');
            $table->index('emp_nit');
        });

        // 2. Agregar columna ven_empresa_id a pro_ventas
        Schema::table('pro_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('ven_empresa_id')->nullable()->after('ven_cliente');
            $table->foreign('ven_empresa_id')->references('emp_id')->on('pro_clientes_empresas');
        });

        // 3. MIGRACIÓN DE DATOS
        // Convertir la información actual de cada cliente en su primera "Empresa"
        $clientes = DB::table('pro_clientes')->get();

        foreach ($clientes as $cliente) {
            // Usar datos existentes o defaults
            $nombreEmpresa = $cliente->cliente_nom_empresa ?: ($cliente->cliente_nombre1 . ' ' . $cliente->cliente_apellido1);
            $nit = $cliente->cliente_nit ?: 'CF';
            $direccion = $cliente->cliente_direccion ?: 'Ciudad';
            $telefono = $cliente->cliente_telefono;

            // Insertar empresa
            $empresaId = DB::table('pro_clientes_empresas')->insertGetId([
                'emp_cliente_id' => $cliente->cliente_id,
                'emp_nombre' => $nombreEmpresa,
                'emp_nit' => $nit,
                'emp_direccion' => $direccion,
                'emp_telefono' => $telefono,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Actualizar ventas históricas de este cliente
            DB::table('pro_ventas')
                ->where('ven_cliente', $cliente->cliente_id)
                ->update(['ven_empresa_id' => $empresaId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_ventas', function (Blueprint $table) {
            $table->dropForeign(['ven_empresa_id']);
            $table->dropColumn('ven_empresa_id');
        });

        Schema::dropIfExists('pro_clientes_empresas');
    }
};
