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
        Schema::table('pro_clientes_empresas', function (Blueprint $table) {
            $table->string('emp_licencia_compraventa')->nullable()->after('emp_telefono');
            $table->date('emp_licencia_vencimiento')->nullable()->after('emp_licencia_compraventa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_clientes_empresas', function (Blueprint $table) {
            $table->dropColumn(['emp_licencia_compraventa', 'emp_licencia_vencimiento']);
        });
    }
};
