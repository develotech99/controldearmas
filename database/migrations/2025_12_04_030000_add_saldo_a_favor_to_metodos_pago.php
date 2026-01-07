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
        DB::table('pro_metodos_pago')->insert([
            'metpago_id' => 7,
            'metpago_descripcion' => 'Saldo a Favor',
            'metpago_situacion' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('pro_metodos_pago')->where('metpago_id', 7)->delete();
    }
};
