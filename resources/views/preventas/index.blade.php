@extends('layouts.app')

@section('title', 'Gestión de Preventas')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar: Cliente y Filtros -->
        <div class="lg:col-span-1 lg:sticky lg:top-4 lg:self-start flex flex-col gap-6">
            
            <!-- Filtros de Producto -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-filter mr-2"></i>Filtros de Producto
                </h2>

                <div class="space-y-4">
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccionar...</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->categoria_id }}">
                                    {{ $categoria->categoria_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="subcategoria" class="block text-sm font-medium text-gray-700 mb-2">Subcategoría</label>
                        <select id="subcategoria" name="subcategoria"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                        <select id="marca" name="marca"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="modelo" class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                        <select id="modelo" name="modelo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="calibre" class="block text-sm font-medium text-gray-700 mb-2">Calibre</label>
                        <select id="calibre" name="calibre"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Cliente -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-user mr-2"></i>Cliente
                </h2>
                <div class="relative">
                    <input type="text" id="cliente_busqueda" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                        placeholder="Buscar cliente por nombre o NIT..." autocomplete="off">
                    <input type="hidden" id="cliente_id">
                    <div id="resultados-clientes" class="absolute z-10 w-full bg-white border rounded-lg shadow-lg mt-1 hidden max-h-48 overflow-y-auto"></div>
                </div>
                <div id="cliente-seleccionado" class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 font-semibold hidden"></div>
                
                <!-- Selector de Empresa (Oculto por defecto) -->
                <div id="div-empresa-select" class="hidden mt-4">
                    <label for="empresa_id" class="block text-sm font-medium text-gray-700 mb-1">Empresa / Sucursal</label>
                    <select id="empresa_id" name="empresa_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Seleccionar empresa...</option>
                    </select>
                </div>
            </div>

            <!-- Datos de Preventa -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Datos Preventa
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                        <input type="date" id="fecha" value="{{ date('Y-m-d') }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                        <textarea id="observaciones" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main: Buscador y Grid de Productos -->
        <div class="lg:col-span-2 flex flex-col h-full">
            <!-- Buscador -->
            <div class="flex-shrink-0 mb-6">
                <div class="relative">
                    <input type="text" id="producto_busqueda" placeholder="Buscar productos..."
                        class="w-full px-4 py-3 pl-12 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-lg"
                        autocomplete="off">
                    <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            <!-- Grid de Productos -->
            <div class="flex-1 flex flex-col min-h-0 bg-white rounded-lg shadow-sm border">
                <div class="flex-shrink-0 px-6 py-4 border-b bg-gray-50">
                    <span id="contador-resultados" class="text-sm text-gray-600">Resultados de búsqueda</span>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <div id="grid-productos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Productos renderizados aquí -->
                        <div class="col-span-full text-center text-gray-500 py-8">
                            <i class="fas fa-search text-4xl mb-2 opacity-30"></i>
                            <p>Busca productos para agregar a la preventa</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botón Flotante Carrito -->
    <button id="btn-abrir-carrito"
        class="fixed top-4 right-4 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 z-40 transition-transform transform hover:scale-110">
        <i class="fas fa-shopping-cart text-xl"></i>
        <span id="contador-carrito"
            class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
    </button>

    <!-- Modal Carrito (Slide-over) -->
    <div id="modal-carrito" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity" id="overlay-carrito"></div>
        <div id="panel-carrito"
            class="absolute right-0 top-0 h-full w-full sm:w-96 bg-white shadow-xl transform transition-transform duration-300 translate-x-full">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="bg-blue-600 text-white p-4 flex items-center justify-between flex-shrink-0">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Carrito Preventa
                    </h2>
                    <button id="btn-cerrar-carrito" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Lista de Items -->
                <div class="flex-1 overflow-y-auto p-4 space-y-4" id="lista-carrito">
                    <!-- Items del carrito -->
                    <div id="carrito-vacio" class="text-center py-8 text-gray-500">
                        <i class="fas fa-shopping-cart text-4xl mb-2 opacity-30"></i>
                        <p>Tu carrito está vacío</p>
                    </div>
                </div>

                <!-- Footer: Totales y Acción -->
                <div class="border-t bg-gray-50 p-4 flex-shrink-0 space-y-4">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total:</span>
                        <span id="carrito-total" class="text-blue-600">Q0.00</span>
                    </div>

                    <div class="space-y-3">
                        <!-- Método de Pago -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Método de Pago</label>
                            <select id="metodo_pago" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="CHEQUE">Cheque</option>
                                <option value="TARJETA">Tarjeta</option>
                            </select>
                        </div>

                        <!-- Banco (Oculto por defecto) -->
                        <div id="div-banco" class="hidden">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Banco</label>
                            <select id="banco_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccionar banco...</option>
                                <option value="1">Banrural</option>
                                <option value="2">Banco Industrial</option>
                                <option value="3">G&T Continental</option>
                                <option value="4">BAM</option>
                                <option value="5">Interbanco</option>
                            </select>
                        </div>

                        <!-- Fecha Pago (Oculto por defecto) -->
                        <div id="div-fecha-pago" class="hidden">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Fecha de Pago</label>
                            <input type="datetime-local" id="fecha_pago" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Referencia (Oculto por defecto) -->
                        <div id="div-referencia" class="hidden">
                            <label id="lbl-referencia" class="block text-sm font-semibold text-gray-700 mb-1">No. Autorización</label>
                            <input type="text" id="referencia" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Monto Anticipo -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Monto Anticipo (Q)</label>
                            <input type="number" id="monto_pagado" min="0" step="0.01" value="0.00"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-bold text-green-600">
                        </div>
                    </div>

                    <button id="btn-procesar" type="button"
                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-colors shadow-md">
                        <i class="fas fa-check mr-2"></i>Registrar Preventa
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@vite('resources/js/preventas/index.js')
