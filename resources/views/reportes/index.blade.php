@extends('layouts.app')

@section('title', 'Sistema de Reportes - Armería')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="min-w-0 flex-1">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate">
                    Sistema de Reportes
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Análisis integral de ventas, productos y comisiones
                </p>
            </div>
            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                <div
                    class="flex items-center space-x-2 bg-white dark:bg-gray-800 px-3 py-2 rounded-md border border-gray-300 dark:border-gray-600">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Período:</label>
                    <input type="date" id="fecha_inicio"
                        class="border-0 focus:ring-0 text-sm dark:bg-gray-800 dark:text-gray-100">
                    <span class="text-gray-500">—</span>
                    <input type="date" id="fecha_fin"
                        class="border-0 focus:ring-0 text-sm dark:bg-gray-800 dark:text-gray-100">
                    <button onclick="reportesManager.aplicarFiltroFecha()"
                        class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-filter mr-1"></i>
                        Aplicar
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard de KPIs -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
            <!-- Total Ventas -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-shopping-cart text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Ventas</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="kpi-total-ventas">0
                                </dd>
                                <dd class="text-sm text-gray-500 dark:text-gray-400" id="kpi-porcentaje-ventas">0% vs
                                    período anterior</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monto Total -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-green-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Monto Total Ventas</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="kpi-monto-total">Q0.00
                                </dd>
                                <dd class="text-sm text-gray-500 dark:text-gray-400" id="kpi-porcentaje-monto">0% vs período
                                    anterior</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos Vendidos -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-boxes text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Productos Vendidos
                                </dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                    id="kpi-productos-vendidos">0</dd>
                                <dd class="text-sm text-gray-500 dark:text-gray-400" id="kpi-promedio-productos">0 promedio
                                    por venta</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Comisiones Pendientes -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg border-l-4 border-red-500">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-percent text-red-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Comisiones
                                    Pendientes</dt>
                                <dd class="text-lg font-medium text-gray-900 dark:text-gray-100"
                                    id="kpi-comisiones-pendientes">Q0.00</dd>
                                <dd class="text-sm text-gray-500 dark:text-gray-400" id="kpi-total-comisiones">Q0.00 total
                                    del período</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navegación de Reportes -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6 overflow-x-auto" aria-label="Tabs">
                    <button onclick="reportesManager.cambiarTab('dashboard')"
                        class="tab-button active border-blue-500 text-blue-600 dark:text-blue-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-tachometer-alt mr-2"></i>
                        Dashboard
                    </button>
                    <button onclick="reportesManager.cambiarTab('ventas')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Ventas Pendientes
                    </button>
                    <button onclick="reportesManager.cambiarTab('historial-ventas')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-history mr-2"></i>
                        Historial de Ventas
                    </button>
                    <button onclick="reportesManager.cambiarTab('productos')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-boxes mr-2"></i>
                        Productos Más Vendidos
                    </button>
                    <!-- <button onclick="reportesManager.cambiarTab('comisiones')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-percent mr-2"></i>
                        Comisiones
                    </button> -->
                    <button onclick="reportesManager.cambiarTab('pagos')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-credit-card mr-2"></i>
                        Estado de Pagos
                    </button>
                    <button onclick="reportesManager.cambiarTab('digecam-armas')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-gun mr-2"></i>
                        DIGECAM - Armas
                    </button>

                    <button onclick="reportesManager.cambiarTab('digecam-municiones')"
                        class="tab-button border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-circle mr-2"></i>
                        DIGECAM - Municiones
                    </button>



                </nav>
            </div>
        </div>


        <!-- Contenido de los Tabs -->

        <!-- Tab Dashboard -->
        <div id="tab-dashboard" class="tab-content">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Gráfico de Ventas por Día -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ventas por Día</h3>
                        <div class="flex space-x-2">
                            <button onclick="reportesManager.cambiarTipoGrafico('ventas', 'line')"
                                class="grafico-tipo-btn active p-1 rounded" data-tipo="line" title="Líneas">
                                <i class="fas fa-chart-line text-sm"></i>
                            </button>
                            <button onclick="reportesManager.cambiarTipoGrafico('ventas', 'bar')"
                                class="grafico-tipo-btn p-1 rounded" data-tipo="bar" title="Barras">
                                <i class="fas fa-chart-bar text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="grafico-ventas-dias"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Productos Más Vendidos -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Top 10 Productos</h3>
                        <button onclick="reportesManager.verDetalleProductos()"
                            class="text-blue-600 hover:text-blue-800 text-sm">
                            Ver todos
                        </button>
                    </div>
                    <div class="h-64">
                        <canvas id="grafico-productos-top"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Ventas por Vendedor -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ventas por Vendedor</h3>
                        <div class="flex space-x-2">
                            <button onclick="reportesManager.cambiarTipoGrafico('vendedor', 'doughnut')"
                                class="grafico-tipo-btn active p-1 rounded" data-tipo="doughnut" title="Dona">
                                <i class="fas fa-chart-pie text-sm"></i>
                            </button>
                            <button onclick="reportesManager.cambiarTipoGrafico('vendedor', 'bar')"
                                class="grafico-tipo-btn p-1 rounded" data-tipo="bar" title="Barras">
                                <i class="fas fa-chart-bar text-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="grafico-vendedores"></canvas>
                    </div>
                </div>

                <!-- Gráfico de Métodos de Pago -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Métodos de Pago</h3>
                    </div>
                    <div class="h-64">
                        <canvas id="grafico-metodos-pago"></canvas>
                    </div>
                </div>

            </div>
        </div>

        <!-- Tab Reporte de Ventas -->
        <div id="tab-ventas" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ventas Pendientes</h3>
                        <!-- <div class="flex space-x-3">
                            <button onclick="reportesManager.exportarReporte('ventas', 'excel')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </button>
                            <button onclick="reportesManager.exportarReporte('ventas', 'pdf')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </button>
                        </div> -->
                    </div>
                </div>

                <!-- Filtros para Reporte de Ventas -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendedor</label>
                            <select id="filtro-vendedor-ventas"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los vendedores</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                            <select id="filtro-cliente-ventas"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                style="width: 100%;">
                                <option value="">Todos los clientes</option>
                            </select>
                        </div>
                 
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosVentas()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Ventas -->
                <div class="overflow-x-auto">
                    <table id="tabla-ventas" class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Fecha</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cliente/Empresa</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Vendedor</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Productos</th>
                                    
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Total</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Estado Venta</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-ventas"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div id="paginacion-ventas" class="flex items-center justify-between">
                        <!-- La paginación se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Historial de Ventas -->
        <div id="tab-historial-ventas" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historial Completo de Ventas</h3>
                        <div class="flex space-x-3">
                            <button onclick="reportesManager.exportarReporte('ventas', 'excel')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </button>
                            <button onclick="reportesManager.exportarReporte('ventas', 'pdf')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                <i class="fas fa-file-pdf mr-2"></i>
                                PDF
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros para Historial de Ventas -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendedor</label>
                            <select id="filtro-vendedor-historial"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los vendedores</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cliente</label>
                            <input type="text" id="filtro-cliente-historial" placeholder="Buscar cliente..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado Venta</label>
                            <select id="filtro-estado-historial"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="ACTIVA">Activa</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="ANULADA">Anulada</option>
                            </select>
                        </div>
                         <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado Pago</label>
                            <select id="filtro-pago-historial"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos</option>
                                <option value="COMPLETADO">Pagado</option>
                                <option value="PARCIAL">Parcial</option>
                                <option value="PENDIENTE">Pendiente</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosHistorial()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Historial -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Vendedor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Productos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pagado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Saldo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Pago</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado Venta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-historial-ventas" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- Datos dinámicos -->
                        </tbody>
                    </table>
                </div>
                
                 <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    <div id="paginacion-historial-ventas" class="flex items-center justify-between">
                        <!-- La paginación se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Productos Más Vendidos -->
        <div id="tab-productos" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Productos Más Vendidos</h3>
                        <div class="flex space-x-3">
                            <button onclick="reportesManager.exportarReporte('productos', 'excel')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtros para Productos -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                            <select id="filtro-categoria-productos"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todas las categorías</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca</label>
                            <select id="filtro-marca-productos"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todas las marcas</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Límite</label>
                            <select id="filtro-limite-productos"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="25">Top 25</option>
                                <option value="50">Top 50</option>
                                <option value="100">Top 100</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosProductos()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Productos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Ranking</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Producto</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Categoría</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Marca</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cantidad Vendida</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Precio Promedio</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Total Ingresos</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Transacciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-productos"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Comisiones -->
        <div id="tab-comisiones" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reporte de Comisiones</h3>
                        <div class="flex space-x-3">
                            <button onclick="reportesManager.exportarReporte('comisiones', 'excel')"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-file-excel mr-2"></i>
                                Excel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Comisiones -->
                <div class="px-6 py-4 bg-blue-50 dark:bg-blue-900">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="resumen-total-comisiones">
                                Q0.00</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Comisiones</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                id="resumen-pendientes-comisiones">Q0.00</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Pendientes</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400"
                                id="resumen-pagadas-comisiones">Q0.00</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Pagadas</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400"
                                id="resumen-canceladas-comisiones">Q0.00</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Canceladas</div>
                        </div>
                    </div>
                </div>

                <!-- Filtros para Comisiones -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Vendedor</label>
                            <select id="filtro-vendedor-comisiones"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los vendedores</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                            <select id="filtro-estado-comisiones"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los estados</option>
                                <option value="PENDIENTE">Pendientes</option>
                                <option value="PAGADO">Pagadas</option>
                                <option value="CANCELADO">Canceladas</option>
                            </select>
                        </div>
                        <div></div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosComisiones()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Comisiones -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Fecha</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Vendedor</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Venta</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Monto Base</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Porcentaje</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Comisión</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Estado</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-comisiones"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Estado de Pagos -->
        <div id="tab-pagos" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Estado de Pagos y Cuotas</h3>
                </div>

                <!-- Filtros para Pagos -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado de Pago</label>
                            <select id="filtro-estado-pagos"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los estados</option>
                                <option value="PENDIENTE">Pendientes</option>
                                <option value="PARCIAL">Parciales</option>
                                <option value="COMPLETADO">Completados</option>
                                <option value="VENCIDO">Vencidos</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Pago</label>
                            <select id="filtro-tipo-pagos"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Todos los tipos</option>
                                <option value="UNICO">Pago único</option>
                                <option value="CUOTAS">Cuotas</option>
                            </select>
                        </div>
                        <div></div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosPagos()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Filtrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Pagos -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Fecha Inicio</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cliente</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Vendedor</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Monto Total</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Pagado</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Pendiente</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Tipo</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-pagos"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- Los datos se cargarán dinámicamente -->
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB: REPORTE DIGECAM - ARMAS DE FUEGO -->
        <!-- ========================================== -->

        <div id="tab-digecam-armas" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reporte Mensual de Venta de
                                Armas de Fuego</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Formato oficial DIGECAM - Ministerio de
                                la Defensa Nacional</p>
                        </div>
                        <button onclick="reportesManager.exportarReporteDigecam('armas')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Exportar PDF Oficial
                        </button>
                    </div>
                </div>

                <!-- Filtros específicos -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Mes</label>
                            <select id="filtro-mes-digecam-armas"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="1">ENERO</option>
                                <option value="2">FEBRERO</option>
                                <option value="3">MARZO</option>
                                <option value="4">ABRIL</option>
                                <option value="5">MAYO</option>
                                <option value="6">JUNIO</option>
                                <option value="7">JULIO</option>
                                <option value="8">AGOSTO</option>
                                <option value="9">SEPTIEMBRE</option>
                                <option value="10">OCTUBRE</option>
                                <option value="11">NOVIEMBRE</option>
                                <option value="12">DICIEMBRE</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Año</label>
                            <select id="filtro-anio-digecam-armas"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                            </select>
                        </div>
                        <div></div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosDigecamArmas()"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información del reporte -->
                <div class="px-6 py-3 bg-blue-50 dark:bg-blue-900 border-y border-blue-200 dark:border-blue-800">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">MES:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" id="digecam-armas-mes">-</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">AÑO:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" id="digecam-armas-anio">-</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">TOTAL REGISTROS:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" id="digecam-armas-total">0</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de armas -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-800 dark:bg-gray-900">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">No.</th>
                                <!-- {{-- <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">
                                    Tenencia<br>Anterior</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Tenencia<br>Nueva
                                </th> --}} -->
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Tipo</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Serie</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Marca</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Modelo</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Calibre</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Comprador</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Autorización</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Fecha</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Factura</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-digecam-armas"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <tr>
                                <td colspan="12" class="px-6 py-8 text-center text-gray-500">
                                    Seleccione un mes y año para generar el reporte
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB: REPORTE DIGECAM - MUNICIONES -->
        <!-- ========================================== -->

        <div id="tab-digecam-municiones" class="tab-content hidden">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reporte Mensual de Venta de
                                Munición</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Formato oficial DIGECAM - Ministerio de
                                la Defensa Nacional</p>
                        </div>
                        <button onclick="reportesManager.exportarReporteDigecam('municiones')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-2"></i>
                            Exportar PDF Oficial
                        </button>
                    </div>
                </div>

                <!-- Filtros específicos -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Inicio</label>
                            <input type="date" id="filtro-fecha-inicio-municiones"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha Fin</label>
                            <input type="date" id="filtro-fecha-fin-municiones"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>
                        <div></div>
                        <div class="flex items-end">
                            <button onclick="reportesManager.aplicarFiltrosDigecamMuniciones()"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>
                                Generar Reporte
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Información del reporte -->
                <div class="px-6 py-3 bg-green-50 dark:bg-green-900 border-y border-green-200 dark:border-green-800">
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">FECHA INICIO:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100"
                                id="digecam-municiones-fecha-inicio">-</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">FECHA FIN:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100"
                                id="digecam-municiones-fecha-fin">-</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">TOTAL REGISTROS:</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-100" id="digecam-municiones-total">0
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-600 dark:text-gray-400">MUNICIONES VENDIDAS:</div>
                            <div class="text-sm font-bold text-green-600 dark:text-green-400"
                                id="total-municiones-vendidas">0</div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de municiones -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-800 dark:bg-gray-900">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">No.</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Autorización</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Documento</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Nombre</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Factura</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Fecha</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Serie Arma</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Clase<br>Arma</th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Calibre<br>Arma
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Calibre<br>Vendido
                                </th>
                                <th class="px-2 py-2 text-left text-xs font-medium text-white uppercase">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-digecam-municiones"
                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <tr>
                                <td colspan="11" class="px-6 py-8 text-center text-gray-500">
                                    Seleccione un rango de fechas para generar el reporte
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    

    @include('facturacion.modals.factura_cambiaria')
    @include('facturacion.modals.nueva_factura')

    <!-- Template para items (oculto) -->
    <template id="templateItem">
        <div class="item-factura bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">

                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Descripción <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="det_fac_producto_desc[]" required
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="Producto o servicio">
                    <input type="hidden" name="det_fac_producto_id[]" class="item-producto-id">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="det_fac_cantidad[]" required min="0.01" step="0.01"
                        value="1"
                        class="item-cantidad w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="1">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Precio Unit. <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="det_fac_precio_unitario[]" required min="0" step="0.01"
                        value="0"
                        class="item-precio w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="0.00">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Descuento</label>
                    <input type="number" name="det_fac_descuento[]" min="0" step="0.01" value="0"
                        class="item-descuento w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="0.00">
                </div>

                <div class="md:col-span-2 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                        <input type="number" name="det_fac_total[]" readonly
                            class="item-total w-full rounded-lg border-gray-300 bg-gray-50 text-sm font-semibold text-gray-700"
                            value="0.00">
                    </div>
                    <button type="button"
                        class="btn-eliminar-item p-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </template>

    @vite('resources/js/reportes/index.js')
    @vite('resources/js/facturacion/index.js')
@endsection
