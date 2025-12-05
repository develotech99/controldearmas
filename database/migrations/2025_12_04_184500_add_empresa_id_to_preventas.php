<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pro_preventas', function (Blueprint $table) {
            $table->unsignedInteger('prev_empresa_id')->nullable()->after('prev_cliente_id');
            // Assuming pro_clientes_empresas.emp_id is the key, but it might not be a foreign key constraint if engines differ or to avoid strictness, but let's try to add it if possible.
            // Given previous issues with FKs, I'll just add the column for now.
        });
    }

    public function down()
    {
        Schema::table('pro_preventas', function (Blueprint $table) {
            $table->dropColumn('prev_empresa_id');
        });
    }
};
