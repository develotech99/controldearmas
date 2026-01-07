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
        Schema::table('pro_ventas', function (Blueprint $table) {
            $table->unsignedBigInteger('ven_documento_id')->nullable()->after('ven_cliente');
            $table->foreign('ven_documento_id')->references('id')->on('pro_clientes_documentos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_ventas', function (Blueprint $table) {
            $table->dropForeign(['ven_documento_id']);
            $table->dropColumn('ven_documento_id');
        });
    }
};
