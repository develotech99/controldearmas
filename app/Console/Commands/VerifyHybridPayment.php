<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Http\Controllers\VentasController;
use Illuminate\Http\Request;

class VerifyHybridPayment extends Command
{
    protected $signature = 'verify:hybrid-payment';
    protected $description = 'Verify Hybrid Payment Logic';

    public function handle()
    {
        $this->info('Starting Hybrid Payment Verification...');

        try {
            DB::beginTransaction();

            // 1. Get a user
            $user = User::first();
            auth()->login($user);
            $this->info('User logged in: ' . $user->id);

            // 2. Create Client with Balance
            $clienteId = DB::table('pro_clientes')->insertGetId([
                'cliente_nombre1' => 'TestCommand',
                'cliente_apellido1' => 'Hybrid',
                'cliente_nit' => '88888888',
                'cliente_tipo' => 1,
                'cliente_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info('Client created: ' . $clienteId);

            DB::table('pro_clientes_saldo')->insert([
                'saldo_cliente_id' => $clienteId,
                'saldo_monto' => 500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info('Balance added: 500.00');

            // Create Category
            $categoriaId = DB::table('pro_categorias')->insertGetId([
                'categoria_nombre' => 'Test Category',
                'categoria_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Dependencies
            $subcategoriaId = DB::table('pro_subcategorias')->insertGetId([
                'subcategoria_nombre' => 'Test Subcategory',
                'subcategoria_idcategoria' => $categoriaId,
                'subcategoria_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $marcaId = DB::table('pro_marcas')->insertGetId([
                'marca_descripcion' => 'Test Brand',
                'marca_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $modeloId = DB::table('pro_modelo')->insertGetId([
                'modelo_descripcion' => 'Test Model',
                'modelo_marca_id' => $marcaId,
                'modelo_situacion' => 1,
            ]);

            $unidadId = DB::table('pro_unidades_medida')->insertGetId([
                'unidad_nombre' => 'Milimetros',
                'unidad_abreviacion' => 'mm',
                'unidad_tipo' => 'longitud',
                'unidad_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $calibreId = DB::table('pro_calibres')->insertGetId([
                'calibre_nombre' => 'Test Caliber',
                'calibre_unidad_id' => $unidadId,
                'calibre_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $paisId = DB::table('pro_paises')->insertGetId([
                'pais_descripcion' => 'Test Country',
                'pais_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 3. Create Product
            $productoId = DB::table('pro_productos')->insertGetId([
                'producto_nombre' => 'Producto Test Command',
                'pro_codigo_sku' => 'TEST-SKU-' . rand(1000, 9999),
                'producto_categoria_id' => $categoriaId,
                'producto_subcategoria_id' => $subcategoriaId,
                'producto_marca_id' => $marcaId,
                'producto_modelo_id' => $modeloId,
                'producto_calibre_id' => $calibreId,
                'producto_madein' => $paisId,
                'producto_requiere_stock' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info('Product created: ' . $productoId);

            // Create Price
            DB::table('pro_precios')->insert([
                'precio_producto_id' => $productoId,
                'precio_costo' => 500.00,
                'precio_venta' => 1000.00,
                'precio_situacion' => 1,
                'precio_fecha_asignacion' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info('Price assigned: 1000.00');

            // Create Stock
            DB::table('pro_stock_actual')->insert([
                'stock_producto_id' => $productoId,
                'stock_cantidad_total' => 10,
                'stock_cantidad_disponible' => 10,
                'stock_cantidad_reservada' => 0,
                'stock_valor_total' => 5000.00,
                'updated_at' => now(),
            ]);
            $this->info('Stock assigned: 10');

            // Create Enterprise
            $empresaId = DB::table('pro_clientes_empresas')->insertGetId([
                'emp_cliente_id' => $clienteId,
                'emp_nombre' => 'Test Enterprise',
                'emp_nit' => '11111111',
                'emp_situacion' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Call Controller Method
            $controller = new VentasController();
            $request = new Request();
            $request->merge([
                'cliente_id' => $clienteId,
                'fecha_venta' => now()->toDateString(),
                'metodo_pago' => '1',
                'subtotal' => 1000.00,
                'descuento_porcentaje' => 0,
                'descuento_monto' => 0,
                'total' => 1000.00,
                'empresa_id' => $empresaId,
                'saldo_favor_usado' => 500.00,
                'productos' => [
                    [
                        'producto_id' => $productoId,
                        'cantidad' => 1,
                        'precio_unitario' => 1000.00,
                        'subtotal_producto' => 1000.00,
                        'producto_requiere_stock' => 1,
                        'requiere_serie' => 0,
                        'tiene_lotes' => false,
                    ]
                ],
                'pago' => [
                    'tipo' => 'efectivo',
                    'monto' => 1000.00
                ]
            ]);

            $response = $controller->procesarVenta($request);
            $data = $response->getData();
            
            if (!$data->success) {
                $this->error('Sale failed: ' . $data->message);
                if (isset($data->errors)) {
                    foreach ($data->errors as $key => $err) {
                        $this->error("$key: " . implode(', ', $err));
                    }
                }
                throw new \Exception('Sale processing failed');
            }

            $this->info('Sale processed successfully. Venta ID: ' . $data->venta_id);

            // 5. Verify
            $saldo = DB::table('pro_clientes_saldo')->where('saldo_cliente_id', $clienteId)->value('saldo_monto');
            $this->info("Saldo restante: " . $saldo);

            if ($saldo != 0) {
                $this->error('Balance was not deducted correctly!');
            } else {
                $this->info('Balance deducted correctly.');
            }

            $pagoId = $data->pago_id;
            $detalles = DB::table('pro_detalle_pagos')->where('det_pago_pago_id', $pagoId)->get();
            
            $this->info('Payment Details:');
            foreach ($detalles as $det) {
                $this->info("- Method: {$det->det_pago_metodo_pago}, Amount: {$det->det_pago_monto}");
            }

            if ($detalles->count() !== 2) {
                $this->error('Expected 2 payment details, found ' . $detalles->count());
            } else {
                $this->info('Correct number of payment details.');
            }

            DB::rollBack();
            $this->info('Verification completed and rolled back.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            file_put_contents('verification_error.log', $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
