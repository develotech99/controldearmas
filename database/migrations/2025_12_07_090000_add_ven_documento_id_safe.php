<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pro_ventas', 'ven_documento_id')) {
            Schema::table('pro_ventas', function (Blueprint $table) {
                $table->unsignedBigInteger('ven_documento_id')->nullable()->after('ven_cliente');
                $table->foreign('ven_documento_id')->references('id')->on('pro_clientes_documentos')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        //
    }
};
