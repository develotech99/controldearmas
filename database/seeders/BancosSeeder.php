<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BancosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bancos = [
            ['banco_id' => 1, 'banco_nombre' => 'Banrural'],
            ['banco_id' => 2, 'banco_nombre' => 'Banco Industrial'],
            ['banco_id' => 3, 'banco_nombre' => 'G&T Continental'],
            ['banco_id' => 4, 'banco_nombre' => 'BAM'],
            ['banco_id' => 5, 'banco_nombre' => 'Interbanco'],
            ['banco_id' => 6, 'banco_nombre' => 'BAC Credomatic'],
            ['banco_id' => 7, 'banco_nombre' => 'Promerica'],
            ['banco_id' => 8, 'banco_nombre' => 'Banco de Antigua'],
            ['banco_id' => 9, 'banco_nombre' => 'Vivant'],
            ['banco_id' => 10, 'banco_nombre' => 'CHN'],
        ];

        foreach ($bancos as $banco) {
            DB::table('pro_bancos')->updateOrInsert(
                ['banco_id' => $banco['banco_id']],
                [
                    'banco_nombre' => $banco['banco_nombre'],
                    'banco_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
