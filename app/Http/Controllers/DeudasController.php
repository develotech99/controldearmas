                    'updated_at' => now(),
                ]);

            // 2. Registrar en Caja (cja_historial)
            // Buscar ID del método de pago
            $metodoId = DB::table('pro_metodos_pago')
                ->where('metpago_descripcion', $request->metodo_pago)
                ->value('metpago_id');

            // Si no encuentra el método, usar uno por defecto o lanzar error (aquí asumimos 1 o el primero)
            if (!$metodoId) {
                // Fallback: buscar por similitud o usar ID 1 (Efectivo usualmente)
                $metodoId = 1; 
            }

            DB::table('cja_historial')->insert([
                'cja_tipo' => 'PAGO_DEUDA',
                'cja_id_venta' => null,
                'cja_usuario' => auth()->id(),
                'cja_monto' => $request->monto, // Usar el monto del pago, no el total de la deuda
                'cja_fecha' => now(),
                'cja_metodo_pago' => $metodoId,
                'cja_no_referencia' => $request->referencia ?? "PAGO-DEUDA-{$id}",
                'cja_situacion' => 'ACTIVO',
                'cja_observaciones' => "Abono a deuda ID {$id}. Nota: " . ($request->nota ?? ''),
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
