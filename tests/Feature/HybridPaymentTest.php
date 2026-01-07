<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Clientes;
use App\Models\ClienteSaldo;
use Illuminate\Support\Facades\DB;

class HybridPaymentTest extends TestCase
{
    // use RefreshDatabase; // Be careful with this on existing DB

    public function test_hybrid_payment_logic()
    {
        // 1. Create a test user (vendedor)
        $user = User::first(); // Use existing user
        $this->actingAs($user);

        // 2. Create a test client with balance
        $clienteId = DB::table('pro_clientes')->insertGetId([
            'cliente_nombre1' => 'Test',
            'cliente_apellido1' => 'Hybrid',
            'cliente_nit' => '12345678',
            'cliente_tipo' => 1,
            'cliente_situacion' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add Saldo a Favor
        DB::table('pro_clientes_saldo')->insert([
            'saldo_cliente_id' => $clienteId,
            'saldo_monto' => 500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create a test product
        $productoId = DB::table('pro_productos')->insertGetId([
            'producto_nombre' => 'Producto Test',
            'producto_precio_venta' => 1000.00,
            'producto_stock_cantidad_total' => 10,
            'producto_requiere_stock' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Simulate Sale Request
        $response = $this->postJson('/api/ventas/procesar-venta', [
            'cliente_id' => $clienteId,
            'fecha_venta' => now()->toDateString(),
            'metodo_pago' => '1', // Efectivo for the rest
            'total' => 1000.00,
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
                'monto' => 1000.00 // Total logic handled in backend
            ]
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // 5. Verify Database
        // Check Balance Deduction
        $this->assertDatabaseHas('pro_clientes_saldo', [
            'saldo_cliente_id' => $clienteId,
            'saldo_monto' => 0.00 // 500 - 500 = 0
        ]);

        // Check Sale Created
        $ventaId = $response->json('venta_id');
        $this->assertDatabaseHas('pro_ventas', ['ven_id' => $ventaId]);

        // Check Payments
        $pagoId = $response->json('pago_id');
        
        // Check Saldo a Favor Detail
        $this->assertDatabaseHas('pro_detalle_pagos', [
            'det_pago_pago_id' => $pagoId,
            'det_pago_metodo_pago' => 7, // Saldo a Favor
            'det_pago_monto' => 500.00
        ]);

        // Check Main Payment Detail
        $this->assertDatabaseHas('pro_detalle_pagos', [
            'det_pago_pago_id' => $pagoId,
            'det_pago_metodo_pago' => 1, // Efectivo
            'det_pago_monto' => 500.00
        ]);

        // Cleanup (Optional, but good practice if not using RefreshDatabase)
        DB::table('pro_detalle_pagos')->where('det_pago_pago_id', $pagoId)->delete();
        DB::table('pro_pagos')->where('pago_id', $pagoId)->delete();
        DB::table('pro_detalle_ventas')->where('det_ven_id', $ventaId)->delete();
        DB::table('pro_ventas')->where('ven_id', $ventaId)->delete();
        DB::table('pro_clientes_saldo')->where('saldo_cliente_id', $clienteId)->delete();
        DB::table('pro_clientes')->where('cliente_id', $clienteId)->delete();
        DB::table('pro_productos')->where('producto_id', $productoId)->delete();
    }
}
