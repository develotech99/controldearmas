<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marcas;

class ProMarcasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marcas = [
            'Glock',
            'Beretta',
            'Sig Sauer',
            'Smith & Wesson',
            'CZ (Česká Zbrojovka)',
            'Remington',
            'Winchester',
            'Colt',
            'Ruger',
            'Heckler & Koch',
            'Taurus',
            'Mossberg',
            'Benelli',
            'Walther',
            'FN Herstal',
            '5.11 Tactical',
            'Blackhawk',
            'Magpul',
            'Vortex Optics',
            'Leupold'
        ];

        foreach ($marcas as $marca) {
            Marcas::firstOrCreate(
                ['marca_descripcion' => $marca],
                ['marca_situacion' => 1]
            );
        }
    }
}
