<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Pais;

class ProPaisesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paises = [
            'Guatemala',
            'Estados Unidos',
            'México',
            'El Salvador',
            'Honduras',
            'Nicaragua',
            'Costa Rica',
            'Panamá',
            'Colombia',
            'Brasil',
            'Argentina',
            'Chile',
            'Perú',
            'Ecuador',
            'Venezuela',
            'Bolivia',
            'Paraguay',
            'Uruguay',
            'España',
            'Italia',
            'Alemania',
            'Francia',
            'Reino Unido',
            'Rusia',
            'China',
            'Japón',
            'Corea del Sur',
            'Israel',
            'Turquía',
            'Austria',
            'Bélgica',
            'República Checa',
            'Suiza',
            'Canadá',
            'Australia'
        ];

        foreach ($paises as $pais) {
            Pais::firstOrCreate(
                ['pais_descripcion' => $pais],
                ['pais_situacion' => 1]
            );
        }
    }
}
