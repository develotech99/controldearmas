                    'updated_at' => now(),
                ]);

            // 2. Registrar en Caja (cja_historial)
            // Asumiendo estructura de caja basada en ventas anteriores
            DB::table('cja_historial')->insert([
                'cja_tipo' => 'INGRESO', // O 'PAGO_DEUDA' si existe ese tipo
                'cja_id_venta' => null, // No es una venta directa de productos
                'cja_usuario' => auth()->id(),
                'cja_monto' => $deuda->monto,
                'cja_fecha' => now(),
                'cja_metodo_pago' => $request->metodo_pago,
                'cja_no_referencia' => "PAGO-DEUDA-{$id}",
                'cja_situacion' => 'PAGADO', // O 'CONFIRMADO'
                'cja_observaciones' => "Pago de deuda ID {$id} - Cliente ID {$deuda->cliente_id}",
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Deuda pagada correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al pagar deuda: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar el pago.'], 500);
        }
    }
}
