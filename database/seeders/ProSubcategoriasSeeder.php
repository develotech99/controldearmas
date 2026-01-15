<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subcategoria;
use App\Models\Categoria;

class ProSubcategoriasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapa de Categoría -> Subcategorías
        $data = [
            'Armas de Fuego' => ['Pistolas', 'Revólveres', 'Escopetas', 'Rifles', 'Carabinas', 'Fusiles'],
            'Municiones' => ['Calibre 9mm', 'Calibre .45', 'Calibre .223', 'Calibre 12GA', 'Calibre .38 Special'],
            'Accesorios para Armas' => ['Cargadores', 'Miras', 'Linternas', 'Empuñaduras', 'Silenciadores'],
            'Ropa Táctica' => ['Chalecos', 'Pantalones', 'Botas', 'Guantes', 'Gorras'],
            'Seguridad y Defensa' => ['Gas Pimienta', 'Bastones', 'Esposas', 'Chalecos Antibalas'],
            'Limpieza y Mantenimiento' => ['Aceites', 'Kits de Limpieza', 'Baquetas', 'Parches'],
            'Optica y Miras' => ['Miras Telescópicas', 'Miras de Punto Rojo', 'Binoculares', 'Telémetros'],
            'Fundas y Estuches' => ['Fundas de Cintura', 'Fundas de Pierna', 'Estuches Rígidos', 'Mochilas Tácticas'],
            'Cuchillos y Herramientas' => ['Navajas', 'Cuchillos de Combate', 'Multiherramientas', 'Hachas'],
            'Entrenamiento y Polígono' => ['Blancos de Papel', 'Siluetas Metálicas', 'Protectores Auditivos', 'Gafas de Protección']
        ];

        foreach ($data as $categoriaNombre => $subcategorias) {
            $categoria = Categoria::where('categoria_nombre', $categoriaNombre)->first();

            if ($categoria) {
                foreach ($subcategorias as $subcategoria) {
                    Subcategoria::firstOrCreate(
                        [
                            'subcategoria_nombre' => $subcategoria,
                            'subcategoria_idcategoria' => $categoria->categoria_id
                        ],
                        ['subcategoria_situacion' => 1]
                    );
                }
            }
        }
    }
}
