<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class ContadorRolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Rol::firstOrCreate(
            ['nombre' => 'contador'],
            ['descripcion' => 'Rol para el personal de contabilidad']
        );
    }
}
