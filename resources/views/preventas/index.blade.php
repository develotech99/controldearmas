@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Módulo de Preventas</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario de Nueva Preventa -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Nueva Preventa</h2>
                
                <form id="form-preventa">
                    @csrf
                    
                    <!-- Cliente -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="cliente_busqueda">
                            Cliente
                        </label>
                        <div class="relative">
                            <input type="text" id="cliente_busqueda" 
                                class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Buscar cliente..." autocomplete="off">
                            <input type="hidden" id="cliente_id" name="cliente_id">
                            <div id="resultados-clientes" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                        </div>
                        <div id="cliente-seleccionado" class="mt-2 text-sm text-green-600 font-semibold hidden"></div>
                    </div>

                    <!-- Sección Agregar Producto -->
                    <div class="bg-gray-50 p-4 rounded-lg mb-4 border border-gray-200">
                        <h3 class="text-sm font-bold text-gray-700 mb-2">Agregar Producto</h3>
                        
                        <!-- Producto -->
                        <div class="mb-2">
                            <div class="relative">
                                <input type="text" id="producto_busqueda" 
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
                                    placeholder="Buscar producto..." autocomplete="off">
                                <input type="hidden" id="producto_id">
                                <input type="hidden" id="producto_precio">
                                <div id="resultados-productos" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                            </div>
                            <div id="producto-seleccionado" class="mt-1 text-xs text-green-600 font-semibold hidden"></div>
                        </div>

                        <div class="flex gap-2">
                            <div class="w-1/2">
                                <input type="number" id="cantidad" min="1" value="1" placeholder="Cant."
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            </div>
                            <div class="w-1/2">
                                <button type="button" id="btn-agregar"
                                    class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-green-700 transition duration-300 text-sm">
                                    + Agregar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Carrito -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Productos en Preventa</label>
                        <div class="border rounded-lg overflow-hidden">
                            <table class="min-w-full leading-normal">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Producto</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase">Cant.</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
                                        <th class="px-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody id="carrito-body">
                                    <!-- Items -->
                                </tbody>
                                <tfoot class="bg-gray-50 font-bold">
                                    <tr>
                                        <td colspan="2" class="px-3 py-2 text-right text-sm">Total:</td>
                                        <td class="px-3 py-2 text-right text-sm" id="carrito-total">Q0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div id="carrito-empty" class="text-center text-gray-500 text-sm py-2">Carrito vacío</div>
                    </div>

                    <!-- Monto Pagado (Anticipo) -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="monto_pagado">
                            Monto Anticipo (Q)
                        </label>
                        <input type="number" id="monto_pagado" name="monto_pagado" min="0" step="0.01" value="0.00"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Fecha -->
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha">
                            Fecha
                        </label>
                        <input type="date" id="fecha" name="fecha" value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="observaciones">
                            Observaciones
                        </label>
                        <textarea id="observaciones" name="observaciones" rows="2"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <button type="submit" 
                        class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                        Registrar Preventa
                    </button>
                </form>
            </div>
        </div>

        <!-- Listado de Preventas Pendientes -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-gray-700">Preventas Pendientes</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Cliente
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Cant.
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Anticipo
                                </th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody id="tabla-preventas">
                            <!-- Datos cargados vía JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @vite(['resources/js/preventas/index.js'])
@endpush
@endsection
