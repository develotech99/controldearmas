@extends('layouts.app')

@section('title', 'Gestión de Comisiones')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Gestión de Comisiones</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Control de comisiones por vendedor</p>
                <div class="flex items-center mt-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        {{ $usuarioLogueado->user_primer_nombre }} {{ $usuarioLogueado->user_primer_apellido }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button id="btnResumen"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Ver Resumen Ejecutivo
                </button>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Filtros de Búsqueda</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <div class="@if($usuarioLogueado->rol && strtolower($usuarioLogueado->rol->nombre) === 'vendedor') hidden @endif">
                    <label for="filtroVendedor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Vendedor:</label>
                    <select id="filtroVendedor" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                        <option value="">Todos los vendedores</option>
                        @foreach($vendedores as $vendedor)
                            <option value="{{ $vendedor->user_id }}">
                                {{ trim($vendedor->user_primer_nombre . ' ' . $vendedor->user_primer_apellido) }}
                                {{ $usuarioLogueado->user_id == $vendedor->user_id ? ' (Tú)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filtroFechaInicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Inicio:</label>
                    <input type="date" id="filtroFechaInicio" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="filtroFechaFin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fecha Fin:</label>
                    <input type="date" id="filtroFechaFin" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                </div>
                <div>
                    <label for="filtroEstado" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Estado:</label>
                    <select id="filtroEstado" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 transition-colors">
                        <option value="">Todos</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="PAGADO">Pagado</option>
                        <option value="CANCELADO">Cancelado</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button id="btnBuscar" class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        Buscar
                    </button>
                    <button id="btnLimpiar" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Lista de Comisiones</h3>
            </div>
            <div class="overflow-x-auto">
                <table id="datatableComisiones" class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Vendedor</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Venta #</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Fecha Venta</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Monto Venta</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">% Comisión</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ganancia</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Días</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Resumen MEJORADO --}}
    <div id="modalResumen" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-backdrop></div>
        <div class="relative mx-auto mt-4 w-full max-w-7xl h-[95vh] flex items-center justify-center px-4">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700 w-full max-h-full flex flex-col">
                {{-- Header del Modal --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">📊 Resumen Ejecutivo de Comisiones</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Análisis detallado por vendedor</p>
                    </div>
                    <button class="p-2 rounded-xl hover:bg-white/50 dark:hover:bg-gray-800 transition-colors" data-modal-close>
                        <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Tarjetas de Totales --}}
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Vendedores</p>
                                    <p id="totalVendedores" class="text-lg font-bold text-gray-900 dark:text-gray-100">0</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Ventas</p>
                                    <p id="totalVentas" class="text-lg font-bold text-gray-900 dark:text-gray-100">0</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Vendido</p>
                                    <p id="totalVendido" class="text-sm font-bold text-gray-900 dark:text-gray-100">Q0.00</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Comisiones</p>
                                    <p id="totalComisiones" class="text-sm font-bold text-gray-900 dark:text-gray-100">Q0.00</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pagadas</p>
                                    <p id="totalPagadas" class="text-sm font-bold text-green-600 dark:text-green-400">Q0.00</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600">
                            <div class="flex items-center">
                                <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                                    <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pendientes</p>
                                    <p id="totalPendientes" class="text-sm font-bold text-orange-600 dark:text-orange-400">Q0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contenido del Modal --}}
                <div class="flex-1 overflow-hidden">
                    <div class="h-full overflow-auto px-6 py-4">
                        <div class="mb-4">
                            <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Detalle por Vendedor</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Análisis individual de comisiones y rendimiento</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table id="datatableResumen" class="min-w-full">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700 dark:to-gray-800">
                                    <tr>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Vendedor</th>
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ventas</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Total Vendido</th>
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">% Promedio</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Comisiones</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Pagadas</th>
                                        <th class="px-4 py-4 text-left text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Pendientes</th>
                                        <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">% Pagado</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Footer del Modal --}}
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Tip:</span> Haz clic en las columnas para ordenar los datos
                        </div>
                        <button class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-colors font-medium" data-modal-close>
                            Cerrar Resumen
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Marcar Como Pagado MEJORADO --}}
    <div id="modalPagar" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-backdrop></div>
        <div class="relative mx-auto mt-20 w-full max-w-lg px-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl overflow-hidden border border-gray-200 dark:border-gray-700">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-gray-800 dark:to-gray-900">
                    <div class="flex items-center">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Marcar como Pagado</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Confirmar pago de comisión</p>
                        </div>
                    </div>
                    <button class="p-2 rounded-xl hover:bg-white/50 dark:hover:bg-gray-800 transition-colors" data-modal-close>
                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form id="formPagar" class="px-6 py-6 space-y-6">
                    <input type="hidden" id="comision_id" name="id">
                    
                    {{-- Información de la comisión --}}
                    <div id="infoComision" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 p-4 rounded-xl border border-blue-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 gap-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Vendedor:</span>
                                <span id="infoPagoVendedor" class="text-sm font-semibold text-gray-900 dark:text-gray-100"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Venta:</span>
                                <span id="infoPagoVenta" class="text-sm font-semibold text-blue-600 dark:text-blue-400"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Ganancia:</span>
                                <span id="infoPagoGanancia" class="text-lg font-bold text-green-600 dark:text-green-400"></span>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Campo de observaciones --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            Observaciones (opcional)
                        </label>
                        <textarea id="observaciones_pago" name="observaciones" rows="4" 
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors resize-none" 
                            placeholder="Detalles del pago, método utilizado, fecha específica, etc..."></textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="flex items-center justify-end gap-3 pt-4">
                        <button type="button" class="px-6 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" data-modal-close>
                            <svg class="w-4 h-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Cancelar
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-lg hover:shadow-xl transition-all duration-200">
                            <svg id="iconSpinnerPagar" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span id="btnSubmitPagarText">Confirmar Pago</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('styles')
<style>
    /* Estilos adicionales para mejorar la apariencia */
    .swal2-confirm-btn {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        border: none !important;
        box-shadow: 0 4px 14px 0 rgba(220, 38, 38, 0.39) !important;
    }
    
    .swal2-cancel-btn {
        background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%) !important;
        border: none !important;
        box-shadow: 0 4px 14px 0 rgba(107, 114, 128, 0.39) !important;
    }

    /* Mejoras para el DataTable */
    .dataTable-wrapper .dataTable-top,
    .dataTable-wrapper .dataTable-bottom {
        padding: 1rem;
    }

    .dataTable-search {
        margin-bottom: 0;
    }

    .dataTable-search input {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .dataTable-dropdown select {
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .dataTable-pagination ul {
        gap: 0.25rem;
    }

    .dataTable-pagination a {
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }

    .dataTable-pagination .active a {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.4);
    }
</style>
@endpush

@vite('resources/js/comisiones/index.js')