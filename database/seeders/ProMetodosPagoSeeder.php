<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\MetodoPago;

class ProMetodosPagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Desactivar restricciones de clave foránea para poder truncar
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncar la tabla para eliminar registros existentes y reiniciar IDs
        MetodoPago::truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $metodos = [
            ['metpago_id' => 1, 'metpago_descripcion' => 'Efectivo'],
            ['metpago_id' => 2, 'metpago_descripcion' => 'Tarjeta de credito'],
            ['metpago_id' => 3, 'metpago_descripcion' => 'Tarjeta de debito'],
            ['metpago_id' => 4, 'metpago_descripcion' => 'Transferencia'],
            ['metpago_id' => 5, 'metpago_descripcion' => 'Cheque'],
            ['metpago_id' => 6, 'metpago_descripcion' => 'Pagos/cuotas'],
            ['metpago_id' => 7, 'metpago_descripcion' => 'Saldo a Favor'],
        ];

        foreach ($metodos as $metodo) {
            MetodoPago::create([
                'metpago_id' => $metodo['metpago_id'],
                'metpago_descripcion' => $metodo['metpago_descripcion'],
                'metpago_situacion' => 1
            ]);
        }
    }
}
