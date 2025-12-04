
try {
    DB::beginTransaction();

    // 1. Get a user
    $user = App\Models\User::first();
    auth()->login($user);

    // 2. Create Client with Balance
    $clienteId = DB::table('pro_clientes')->insertGetId([
        'cliente_nombre1' => 'TestTinker',
        'cliente_apellido1' => 'Hybrid',
        'cliente_nit' => '99999999',
        'cliente_tipo' => 1,
        'cliente_situacion' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('pro_clientes_saldo')->insert([
        'saldo_cliente_id' => $clienteId,
        'saldo_monto' => 500.00,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 3. Create Product
    $productoId = DB::table('pro_productos')->insertGetId([
        'producto_nombre' => 'Producto Test Tinker',
        'producto_precio_venta' => 1000.00,
        'producto_stock_cantidad_total' => 10,
        'producto_requiere_stock' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 4. Call Controller Method
    $controller = new App\Http\Controllers\VentasController();
    $request = new Illuminate\Http\Request();
    $request->merge([
        'cliente_id' => $clienteId,
        'fecha_venta' => now()->toDateString(),
        'metodo_pago' => '1',
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
            'monto' => 1000.00
        ]
    ]);

    $response = $controller->procesarVenta($request);
    dump($response->getData());

    // 5. Verify
    $saldo = DB::table('pro_clientes_saldo')->where('saldo_cliente_id', $clienteId)->value('saldo_monto');
    dump("Saldo restante: " . $saldo);

    $ventaId = $response->getData()->venta_id;
    $pagoId = $response->getData()->pago_id;

    $detalles = DB::table('pro_detalle_pagos')->where('det_pago_pago_id', $pagoId)->get();
    dump("Detalles de pago:", $detalles->toArray());

    DB::rollBack(); // Always rollback in tinker to avoid garbage
    echo "Test completed and rolled back.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
