<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categoria;

class ProCategoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            'Armas de Fuego',
            'Municiones',
            'Accesorios para Armas',
            'Ropa Táctica',
            'Seguridad y Defensa',
            'Limpieza y Mantenimiento',
            'Optica y Miras',
            'Fundas y Estuches',
            'Cuchillos y Herramientas',
            'Entrenamiento y Polígono'
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['categoria_nombre' => $categoria],
                ['categoria_situacion' => 1]
            );
        }
    }
}
