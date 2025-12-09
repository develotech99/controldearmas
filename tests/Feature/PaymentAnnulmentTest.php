<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentAnnulmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a user and authenticate
        $this->user = User::first(); // Use existing user
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_performs_full_reset_when_no_validated_payments_exist()
    {
        // 1. Create a Sale and Payment (Pending)
        $ventaId = DB::table('pro_ventas')->insertGetId([
            'ven_fecha' => now(),
            'ven_total_vendido' => 1000,
            'ven_cliente' => $this->user->id,
            'ven_user' => $this->user->id,
            'ven_situacion' => 'VIGENTE',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pagoId = DB::table('pro_pagos')->insertGetId([
            'pago_venta_id' => $ventaId,
            'pago_monto_total' => 1000,
            'pago_monto_pagado' => 0,
            'pago_monto_pendiente' => 1000,
            'pago_estado' => 'PENDIENTE',
            'pago_tipo_pago' => 'UNICO',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Add a pending upload (to be deleted)
        DB::table('pro_pagos_subidos')->insert([
            'ps_venta_id' => $ventaId,
            'ps_monto' => 1000,
            'ps_estado' => 'PENDIENTE_VALIDACION',
            'created_at' => now()
        ]);

        // 2. Call anularPago
        $response = $this->postJson('/pagos/anular', [
            'venta_id' => $ventaId,
            'motivo' => 'Test Full Reset'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 3. Verify Full Reset
        $this->assertDatabaseMissing('pro_pagos_subidos', ['ps_venta_id' => $ventaId]);
        
        $pago = DB::table('pro_pagos')->where('pago_id', $pagoId)->first();
        $this->assertEquals(0, $pago->pago_monto_pagado);
        $this->assertEquals(1000, $pago->pago_monto_pendiente);
        $this->assertEquals('PENDIENTE', $pago->pago_estado);
        
        // Cleanup
        DB::table('pro_pagos')->where('pago_id', $pagoId)->delete();
        DB::table('pro_ventas')->where('ven_id', $ventaId)->delete();
    }

    /** @test */
    public function it_performs_partial_reset_when_validated_payments_exist()
    {
        // 1. Create a Sale and Payment (Partially Paid)
        $ventaId = DB::table('pro_ventas')->insertGetId([
            'ven_fecha' => now(),
            'ven_total_vendido' => 1000,
            'ven_cliente' => $this->user->id,
            'ven_user' => $this->user->id,
            'ven_situacion' => 'VIGENTE',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $pagoId = DB::table('pro_pagos')->insertGetId([
            'pago_venta_id' => $ventaId,
            'pago_monto_total' => 1000,
            'pago_monto_pagado' => 500,
            'pago_monto_pendiente' => 500,
            'pago_estado' => 'PARCIAL', // Or whatever state
            'pago_tipo_pago' => 'CUOTAS',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Add a VALIDATED payment detail
        DB::table('pro_detalle_pagos')->insert([
            'det_pago_pago_id' => $pagoId,
            'det_pago_monto' => 500,
            'det_pago_fecha' => now(),
            'det_pago_estado' => 'VALIDO',
            'created_at' => now()
        ]);

        // Add a pending upload (should be deleted)
        DB::table('pro_pagos_subidos')->insert([
            'ps_venta_id' => $ventaId,
            'ps_monto' => 500,
            'ps_estado' => 'PENDIENTE_VALIDACION',
            'created_at' => now()
        ]);

        // 2. Call anularPago
        $response = $this->postJson('/pagos/anular', [
            'venta_id' => $ventaId,
            'motivo' => 'Test Partial Reset'
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // 3. Verify Partial Reset
        // Valid payment should remain
        $this->assertDatabaseHas('pro_detalle_pagos', [
            'det_pago_pago_id' => $pagoId,
            'det_pago_estado' => 'VALIDO'
        ]);

        // Pending upload should be gone
        $this->assertDatabaseMissing('pro_pagos_subidos', ['ps_venta_id' => $ventaId]);

        // Master record should reflect partial state
        $pago = DB::table('pro_pagos')->where('pago_id', $pagoId)->first();
        $this->assertEquals(500, $pago->pago_monto_pagado);
        $this->assertEquals(500, $pago->pago_monto_pendiente); // 1000 - 500
        $this->assertEquals('PENDIENTE', $pago->pago_estado); // Reset to pending
        $this->assertEquals('UNICO', $pago->pago_tipo_pago); // Reset to default

        // 4. Test Generating Cuotas on Remaining Balance
        $responseCuotas = $this->postJson('/pagos/generar-cuotas', [
            'venta_id' => $ventaId,
            'metodo_pago' => 6, // Cuotas
            'cantidad_cuotas' => 2,
            'abono_inicial' => 0
        ]);

        $responseCuotas->assertStatus(200)
            ->assertJson(['success' => true]);

        // Verify Cuotas are calculated on 500 (remaining), not 1000
        $cuotas = DB::table('pro_cuotas')->where('cuota_control_id', $pagoId)->get();
        $this->assertCount(2, $cuotas);
        $this->assertEquals(250, $cuotas[0]->cuota_monto); // 500 / 2 = 250

        // Cleanup
        DB::table('pro_cuotas')->where('cuota_control_id', $pagoId)->delete();
        DB::table('pro_detalle_pagos')->where('det_pago_pago_id', $pagoId)->delete();
        DB::table('pro_pagos')->where('pago_id', $pagoId)->delete();
        DB::table('pro_ventas')->where('ven_id', $ventaId)->delete();
    }
}
