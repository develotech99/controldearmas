<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

    //     User::factory()->create([
    //         'name' => 'Test User',
    //         'email' => 'test@example.com',
    //     ]);

    // $this->call(UsuarioAdminSeeder::class);

    $this->call([
        ProUnidadesMedidaSeeder::class,
        ProCalibresSeeder::class,
        ProPaisesSeeder::class,
        ProCategoriasSeeder::class,
        ProSubcategoriasSeeder::class,
        ProMarcasSeeder::class,
        ProModelosSeeder::class,
        ProMetodosPagoSeeder::class,
        UsuarioAdmin1Seeder::class
    ]);
    

    }

    



}
