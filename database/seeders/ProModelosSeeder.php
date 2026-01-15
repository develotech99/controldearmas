<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProModelo;
use App\Models\Marcas;

class ProModelosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapa de Marca -> Modelos
        $data = [
            'Glock' => ['G17', 'G19', 'G26', 'G43', 'G45'],
            'Beretta' => ['92FS', 'PX4 Storm', 'APX', 'M9A3', '1301 Tactical'],
            'Sig Sauer' => ['P320', 'P365', 'P226', 'P229', 'MCX'],
            'Smith & Wesson' => ['M&P9', 'M&P Shield', 'Model 686', 'Model 29', 'M&P15'],
            'CZ (Česká Zbrojovka)' => ['P-10 C', 'Shadow 2', '75 B', 'Scorpion EVO 3', 'Bren 2'],
            'Remington' => ['Model 870', 'Model 700', 'Versa Max', 'V3'],
            'Winchester' => ['Model 70', 'SXP', 'Model 94', 'Super X4'],
            'Colt' => ['1911 Government', 'Python', 'King Cobra', 'M4 Carbine'],
            'Ruger' => ['10/22', 'LCP II', 'Security-9', 'Mark IV', 'AR-556'],
            'Heckler & Koch' => ['VP9', 'P30', 'HK45', 'MP5', 'MR556'],
            'Taurus' => ['G2c', 'G3', 'TX22', 'Judge', 'Raging Hunter'],
            'Mossberg' => ['500', '590 Shockwave', 'Maverick 88', 'MVP Patrol'],
            'Benelli' => ['M4', 'M2', 'Super Black Eagle 3', 'Nova'],
            'Walther' => ['PPQ M2', 'PDP', 'PPS M2', 'P99'],
            'FN Herstal' => ['FN 509', 'FNX-45', 'SCAR 17S', 'PS90'],
        ];

        foreach ($data as $marcaNombre => $modelos) {
            $marca = Marcas::where('marca_descripcion', $marcaNombre)->first();

            if ($marca) {
                foreach ($modelos as $modelo) {
                    ProModelo::firstOrCreate(
                        [
                            'modelo_descripcion' => $modelo,
                            'modelo_marca_id' => $marca->marca_id
                        ],
                        ['modelo_situacion' => 1]
                    );
                }
            }
        }
    }
}
