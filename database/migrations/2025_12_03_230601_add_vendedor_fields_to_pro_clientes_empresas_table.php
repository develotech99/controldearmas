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
            $table->string('emp_nom_vendedor', 255)->nullable()->after('emp_direccion');
            $table->string('emp_cel_vendedor', 30)->nullable()->after('emp_nom_vendedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_clientes_empresas', function (Blueprint $table) {
            $table->dropColumn(['emp_nom_vendedor', 'emp_cel_vendedor']);
        });
    }
};
