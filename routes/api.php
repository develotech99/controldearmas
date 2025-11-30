<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VentasController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('ventas/reservas')->group(function () {
    Route::get('/listar', [VentasController::class, 'listarReservas']);
    Route::post('/cancelar', [VentasController::class, 'cancelarReserva']);
});
