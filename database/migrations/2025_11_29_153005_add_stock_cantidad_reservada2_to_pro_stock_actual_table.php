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
        Schema::table('pro_stock_actual', function (Blueprint $table) {
            $table->integer('stock_cantidad_reservada2')->default(0)->after('stock_cantidad_reservada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pro_stock_actual', function (Blueprint $table) {
            $table->dropColumn('stock_cantidad_reservada2');
        });
    }
};
