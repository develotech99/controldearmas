@extends('layouts.app')

@section('content')
<header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 mb-6 py-4 px-4 sm:px-6 lg:px-8 shadow-sm rounded-md">
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-slate-800 dark:text-white leading-tight">
            Dashboard - Sistema de Inventario
        </h2>
        <div class="flex items-center space-x-4">
            <button id="btn-actualizar-dashboard" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2">
                <i class="fas fa-sync-alt"></i>
                <span>Actualizar</span>
            </button>
            <div class="text-sm text-slate-600 dark:text-gray-400">
                Bienvenido, {{ Auth::user()->name }}
            </div>
        </div>
    </div>
</header>

    <!-- Quick Actions (Moved to Top) -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Acciones Rápidas</h3>
            <button id="btn-abrir-manual" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 flex items-center gap-2">
                <i class="fas fa-book-open"></i>
                <span>Manual de Sistema</span>
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Nueva Venta -->
            <button data-href="{{ route('ventas.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-green-300 dark:hover:border-green-500 transition-all text-left group">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                    <i class="fas fa-shopping-cart text-green-600 dark:text-green-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Nueva Venta</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Registrar venta</p>
                </div>
            </button>

            <!-- Realizar Preventa -->
            <button data-href="{{ route('preventas.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-indigo-300 dark:hover:border-indigo-500 transition-all text-left group">
                <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 dark:group-hover:bg-indigo-900/50 transition-colors">
                    <i class="fas fa-clipboard-list text-indigo-600 dark:text-indigo-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Preventa</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Cotizar / Reservar</p>
                </div>
            </button>

            <!-- Autorizar Ventas -->
            <button data-href="{{ route('reportes.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-amber-300 dark:hover:border-amber-500 transition-all text-left group">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50 transition-colors">
                    <i class="fas fa-check-circle text-amber-600 dark:text-amber-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Autorizar</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Revisar pendientes</p>
                </div>
            </button>

            <!-- Facturar -->
            <button data-href="{{ route('facturacion.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-teal-300 dark:hover:border-teal-500 transition-all text-left group">
                <div class="w-10 h-10 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center group-hover:bg-teal-200 dark:group-hover:bg-teal-900/50 transition-colors">
                    <i class="fas fa-file-invoice text-teal-600 dark:text-teal-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Facturar</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Emitir DTE</p>
                </div>
            </button>

             <!-- Agregar Arma -->
             <button data-href="{{ route('inventario.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-blue-300 dark:hover:border-blue-500 transition-all text-left group">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                    <i class="fas fa-box-open text-blue-600 dark:text-blue-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Inventario</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Agregar / Editar</p>
                </div>
            </button>

            <!-- Nuevo Cliente -->
            <button data-href="{{ route('clientes.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-purple-300 dark:hover:border-purple-500 transition-all text-left group">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                    <i class="fas fa-user-plus text-purple-600 dark:text-purple-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Clientes</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Gestionar cartera</p>
                </div>
            </button>

            <!-- Generar Reporte -->
            <button data-href="{{ route('reportes.index') }}" 
                    class="flex items-center space-x-3 p-4 border border-slate-200 dark:border-gray-700 rounded-lg hover:bg-slate-50 dark:hover:bg-gray-700 hover:border-orange-300 dark:hover:border-orange-500 transition-all text-left group">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors">
                    <i class="fas fa-chart-line text-orange-600 dark:text-orange-400 text-lg"></i>
                </div>
                <div>
                    <p class="font-medium text-slate-800 dark:text-white">Reportes</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">Ventas y stock</p>
                </div>
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Armas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 dark:text-gray-400">Total Productos registrados</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white" data-stat="total-armas">0</p>
                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-1">En inventario</p>
                </div>
                <div class="w-12 h-12 bg-slate-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-slate-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Ventas del Mes -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 dark:text-gray-400">Ventas del Mes</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white" data-stat="ventas-mes">0</p>
                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-1">Transacciones</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m-2.4 0L5 7h14m-4 6v6a1 1 0 01-1 1H6a1 1 0 01-1-1v-6m6 0V9a1 1 0 011-1h2a1 1 0 011 1v4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Clientes Registrados -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 dark:text-gray-400">Clientes</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white" data-stat="total-clientes">0</p>
                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-1">Registrados</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Licencias Activas -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-600 dark:text-gray-400">Licencias</p>
                    <p class="text-3xl font-bold text-slate-900 dark:text-white" data-stat="licencias-activas">0</p>
                    <p class="text-xs text-slate-500 dark:text-gray-500 mt-1">Activas</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Sales -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
            <div class="p-6 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Ventas Recientes</h3>
                <a href="/reportes" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Ver todas →
                </a>
            </div>
            <div class="p-6" id="ventas-recientes-container">
                <div class="text-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-slate-500 dark:text-gray-400 mt-4">Cargando ventas...</p>
                </div>
            </div>
        </div>

        <!-- Low Stock Alerts -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700">
            <div class="p-6 border-b border-slate-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-slate-800 dark:text-white">Alertas de Stock</h3>
                <a href="/inventario" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                    Ver inventario →
                </a>
            </div>
            <div class="p-6" id="alertas-stock-container">
                <div class="text-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-600 mx-auto"></div>
                    <p class="text-slate-500 dark:text-gray-400 mt-4">Cargando alertas...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Manual de Sistema -->
<div id="modalManual" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalManualBackdrop"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-4 py-3 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg leading-6 font-medium text-white flex items-center gap-2" id="modal-title">
                    <i class="fas fa-book-reader"></i> Manual de Usuario
                </h3>
                <button type="button" class="text-white hover:text-gray-200 focus:outline-none" id="btn-cerrar-manual">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4 bg-white dark:bg-gray-800">
                <div class="flex flex-col md:flex-row gap-6 h-[500px]">
                    <!-- Sidebar Navigation -->
                    <div class="w-full md:w-1/4 border-r border-gray-200 dark:border-gray-700 pr-4 overflow-y-auto">
                        <nav class="space-y-1" id="manual-nav">
                            <button data-step="1" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                1. Inventario
                            </button>
                            <button data-step="2" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                2. Ventas
                            </button>
                            <button data-step="3" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                3. Preventas
                            </button>
                            <button data-step="4" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                4. Autorización
                            </button>
                            <button data-step="5" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                5. Facturación
                            </button>
                        </nav>
                    </div>

                    <!-- Content Area -->
                    <div class="w-full md:w-3/4 overflow-y-auto" id="manual-content">
                        <!-- Step 1: Inventario -->
                        <div data-content="1" class="space-y-4">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Gestión de Inventario</h4>
                            <p class="text-gray-600 dark:text-gray-300">
                                El módulo de inventario es el corazón del sistema. Aquí puedes registrar productos, gestionar stock y subir imágenes.
                            </p>
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                <h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Pasos Clave:</h5>
                                <ul class="list-disc list-inside space-y-1 text-sm text-blue-700 dark:text-blue-200">
                                    <li>Ve a <strong>Inventario</strong> desde el menú o acciones rápidas.</li>
                                    <li>Usa el botón <strong>"Agregar Arma"</strong> para nuevos registros.</li>
                                    <li>Sube fotos y asigna categorías para mantener el orden.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 2: Ventas -->
                        <div data-content="2" class="hidden space-y-4">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Realizar Ventas</h4>
                            <p class="text-gray-600 dark:text-gray-300">
                                El proceso de venta es rápido y eficiente. Puedes buscar productos por código, nombre o serie.
                            </p>
                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-100 dark:border-green-800">
                                <h5 class="font-semibold text-green-800 dark:text-green-300 mb-2">Flujo de Venta:</h5>
                                <ul class="list-disc list-inside space-y-1 text-sm text-green-700 dark:text-green-200">
                                    <li>Selecciona <strong>"Nueva Venta"</strong>.</li>
                                    <li>Busca y agrega productos al carrito.</li>
                                    <li>Asigna un cliente (o crea uno nuevo).</li>
                                    <li>Procesa el pago (Efectivo, Tarjeta, Mixto).</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 3: Preventas -->
                        <div data-content="3" class="hidden space-y-4">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Preventas y Cotizaciones</h4>
                            <p class="text-gray-600 dark:text-gray-300">
                                Ideal para apartar productos o generar cotizaciones formales sin descontar stock inmediatamente.
                            </p>
                            <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                <h5 class="font-semibold text-indigo-800 dark:text-indigo-300 mb-2">Cómo funciona:</h5>
                                <ul class="list-disc list-inside space-y-1 text-sm text-indigo-700 dark:text-indigo-200">
                                    <li>Ve a <strong>"Preventa"</strong>.</li>
                                    <li>Crea la cotización igual que una venta.</li>
                                    <li>Puedes descargar el PDF para enviarlo al cliente.</li>
                                    <li>Cuando el cliente confirme, usa <strong>"Convertir a Venta"</strong>.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 4: Autorización -->
                        <div data-content="4" class="hidden space-y-4">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Autorización de Ventas</h4>
                            <p class="text-gray-600 dark:text-gray-300">
                                Las ventas pendientes requieren revisión antes de ser facturadas o entregadas.
                            </p>
                            <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-100 dark:border-amber-800">
                                <h5 class="font-semibold text-amber-800 dark:text-amber-300 mb-2">Opciones:</h5>
                                <ul class="list-disc list-inside space-y-1 text-sm text-amber-700 dark:text-amber-200">
                                    <li><strong>Autorizar y Facturar:</strong> Finaliza y emite DTE.</li>
                                    <li><strong>Autorizar sin Facturar:</strong> Descuenta stock pero deja la factura pendiente.</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 5: Facturación -->
                        <div data-content="5" class="hidden space-y-4">
                            <h4 class="text-xl font-bold text-gray-900 dark:text-white">Facturación FEL</h4>
                            <p class="text-gray-600 dark:text-gray-300">
                                Emisión de Documentos Tributarios Electrónicos (DTE) certificados por SAT/Digifact.
                            </p>
                            <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg border border-teal-100 dark:border-teal-800">
                                <h5 class="font-semibold text-teal-800 dark:text-teal-300 mb-2">Proceso:</h5>
                                <ul class="list-disc list-inside space-y-1 text-sm text-teal-700 dark:text-teal-200">
                                    <li>Ve a <strong>"Facturar"</strong>.</li>
                                    <li>Busca la venta autorizada.</li>
                                    <li>Verifica los datos del cliente (NIT).</li>
                                    <li>Haz clic en <strong>"Certificar"</strong>.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" id="btn-entendido-manual">
                    Entendido
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@vite('resources/js/dashboard.js')
