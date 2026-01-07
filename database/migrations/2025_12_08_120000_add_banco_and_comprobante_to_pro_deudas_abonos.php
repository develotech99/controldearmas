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
        Schema::table('pro_deudas_abonos', function (Blueprint $table) {
            if (!Schema::hasColumn('pro_deudas_abonos', 'banco_id')) {
                $table->unsignedBigInteger('banco_id')->nullable()->after('nota');
                // Optional: Add FK if pro_bancos exists
                // $table->foreign('banco_id')->references('id')->on('pro_bancos');
            }
            if (!Schema::hasColumn('pro_deudas_abonos', 'comprobante_path')) {
                $table->string('comprobante_path')->nullable()->after('banco_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_deudas_abonos', function (Blueprint $table) {
            $table->dropColumn(['banco_id', 'comprobante_path']);
        });
    }
};
