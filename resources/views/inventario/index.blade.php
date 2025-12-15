@extends('layouts.app')

@section('title', 'Sistema de Inventario - Armería')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate flex items-center gap-2">
                Sistema de Inventario
                <button type="button" class="text-blue-600 hover:text-blue-800 transition-colors btn-abrir-manual" data-section="inventario" title="Ver ayuda de Inventario">
                    <i class="fas fa-question-circle text-xl"></i>
                </button>
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestión integral para armería - Productos, stock y movimientos
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <!-- Alertas -->
            <div class="relative">
                <button onclick="inventarioManager.toggleAlertas()"
                    class="relative inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-bell mr-2"></i>
                    Alertas
                    <span
                        class="absolute -top-1 -right-1 h-4 w-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                        id="alertas-badge">0</span>
                </button>
            </div>

            <button onclick="inventarioManager.openRegistroModal()"
                class="relative z-10 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-plus mr-2"></i>
                Crear Producto
            </button>
            <button onclick="inventarioManager.openIngresoModal()"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                <i class="fas fa-box-open mr-2"></i>
                Ingreso a Inventario
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-boxes text-gray-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Productos Total
                            </dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="total-productos">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-warehouse text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Productos Disponibles</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="stock-total">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Productos Stock Bajo</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="stock-bajo">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Productos Agotados</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="stock-agotado">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta Informativa: Servicios y Productos sin Stock -->
    <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-r shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-lightbulb text-indigo-500 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-bold text-indigo-800">¿Vas a vender Servicios o Trámites?</h3>
                <div class="mt-2 text-sm text-indigo-700">
                    <p>
                        Los servicios como <strong>Trámites, Licencias de Importación, Papelería</strong>, etc., deben registrarse como productos normales.
                        <br>
                        <strong>IMPORTANTE:</strong> Al crearlos, asegúrate de marcar la opción <strong>"¿Este producto NO requiere STOCK?"</strong>.
                        Esto permitirá venderlos sin necesidad de realizar ingresos de inventario previos.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto</label>
                    <div class="relative">
                        <input type="text" id="search-productos" placeholder="Nombre, SKU o código de barra..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm pr-10">
                        <div id="loader-busqueda" class="absolute right-3 top-1/2 transform -translate-y-1/2 hidden">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                    <select id="filter-categoria"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado de stock</label>
                    <select id="filter-stock"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        <option value="disponible">Con stock</option>
                        <option value="bajo">Stock bajo</option>
                        <option value="agotado">Agotado</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="inventarioManager.clearFilters()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Lista de Productos -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Productos</h3>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <span id="productos-count">0</span> productos
                    </span>
                </div>

                <div class="overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">

                        <!-- Estado vacío inicial -->
                        <div id="empty-state" class="text-center py-12">
                            <i class="fas fa-boxes text-gray-400 text-4xl mb-4"></i>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No hay productos
                                registrados</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Comienza registrando tu primer
                                producto en el sistema.</p>
                            <button onclick="inventarioManager.openRegistroModal()"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fas fa-plus mr-2"></i>
                                Registrar Producto
                            </button>
                        </div>
                        <!-- AGREGA ESTA LÍNEA -->
                        <div id="productos-list" class="space-y-3">
                            <!-- Los productos se cargarán aquí dinámicamente -->
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="space-y-6">
            <!-- Alertas Recientes -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Alertas</h3>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <span id="alertas-count">0</span> alertas
                        </span>
                    </div>

                    <div id="alertas-list" class="space-y-2">
                        <div class="text-center py-6">
                            <i class="fas fa-check-circle text-green-400 text-2xl mb-2"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No hay alertas pendientes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Acciones Rápidas</h3>
                    <div class="space-y-3">
                        <button onclick="inventarioManager.openEgresoModal()"
                            class="w-full inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Registrar Egreso
                        </button>
                        <button onclick="inventarioManager.verHistorial()"
                            class="w-full inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-history mr-2"></i>
                            Ver Historial
                        </button>
                        <!-- <button onclick="inventarioManager.generarReporte()"
                            class="w-full inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Generar Reporte
                        </button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>


<!-- ==================================================== -->
<!-- div tipo excel que pidio cliente -->
<!-- ==================================================== -->

    <!-- Vista Excel Expandible -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <!-- Header del div expandible -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 cursor-pointer" 
             onclick="inventarioManager.toggleExcelView()" 
             id="excel-header">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-table text-green-600"></i>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Vista Detallada (Modo Excel)</h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">- Ver todos los productos con series individuales</span>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400" id="excel-count">0 registros</span>
                    <i class="fas fa-chevron-down transition-transform duration-200 text-gray-500 dark:text-gray-400" id="excel-chevron"></i>
                </div>
            </div>
        </div>

        <!-- Contenido expandible -->
        <div id="excel-content" class="hidden">
            <!-- Buscador interno -->
           <!-- SECCIÓN DE BÚSQUEDA Y FILTROS ACTUALIZADA -->
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Buscador de texto -->
            <div class="md:col-span-2 relative">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" 
                       id="excel-search" 
                       placeholder="Buscar por nombre, SKU, marca, modelo, serie..." 
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <!-- Filtro por Estado -->
            <div>
                <select id="excel-filter-estado" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="reservado">Reservado</option>
                    <option value="vendido">Vendido</option>
                    <option value="baja">Baja</option>
                </select>
            </div>

            <!-- Filtro por Categoría -->
            <div>
                <select id="excel-filter-categoria" 
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">Todas las categorías</option>
                </select>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex items-center justify-between mt-3">
            <button onclick="inventarioManager.limpiarFiltrosExcel()" 
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-white border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                <i class="fas fa-times mr-1"></i> Limpiar filtros
            </button>
            
            <div id="excel-filtros-activos" class="text-sm text-blue-600 dark:text-blue-400 hidden">
                <i class="fas fa-filter mr-1"></i>
                <span id="excel-filtros-count">0</span> filtro(s) activo(s)
            </div>
        </div>
    </div>

        <!-- Tabla Excel -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <!-- Actualizar el thead de la tabla -->
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">SKU</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Categoría</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Subcategoría</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Marca</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Modelo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Calibre</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Serie</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Licencia</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lote</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stock</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Costo</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Individual</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Empresa</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Precio Especial</th>
                        <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha Ingreso</th>
                    </tr>
                </thead>
                <tbody id="excel-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Los datos se llenan con JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Mostrando <span id="excel-showing-start">0</span> a <span id="excel-showing-end">0</span> 
                    de <span id="excel-total-records">0</span> registros
                </div>
                <div class="flex space-x-2">
                    <button onclick="inventarioManager.cambiarPaginaExcel('prev')" 
                            id="excel-btn-prev"
                            class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                    <div id="excel-page-numbers" class="flex space-x-1">
                        <!-- Los números de página se generan con JavaScript -->
                    </div>
                    <button onclick="inventarioManager.cambiarPaginaExcel('next')" 
                            id="excel-btn-next"
                            class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


    

    <!-- Modal Registrar Producto -->
    <div id="registro-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('registro')"></div>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">

                <form id="registro-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Registrar Nuevo Producto</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Información básica del producto</p>
                    </div>

                    <!-- Alerta de Configuración Faltante -->
                    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-yellow-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>¿No encuentras una opción?</strong>
                                    <br>
                                    Si la <strong>Marca, Modelo, País, Categoría o Subcategoría</strong> que necesitas no aparece en los listados, debes ir a la sección de <strong>Configuración</strong> para registrarla primero.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nombre del producto -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del
                                producto *</label>
                            <input type="text" id="producto_nombre" name="producto_nombre" required
                                placeholder="Ej: "
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <div id="producto_nombre_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Descripción del producto -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <i class="fas fa-align-left mr-1"></i>
                                Descripción del producto
                            </label>
                            <textarea id="producto_descripcion" name="producto_descripcion" rows="3"
                                placeholder="Descripción detallada del producto, características, especificaciones técnicas..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm resize-vertical"></textarea>
                            <div id="producto_descripcion_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            <small class="text-xs text-gray-500 dark:text-gray-400">
                                Información adicional sobre el producto que será útil para ventas e identificación
                            </small>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría
                                *</label>
                            <select id="producto_categoria" name="producto_categoria_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar categoría</option>
                            </select>
                            <div id="producto_categoria_id_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Subcategoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subcategoría
                                *</label>
                            <select id="producto_subcategoria" name="producto_subcategoria_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar subcategoría</option>
                            </select>
                            <div id="producto_subcategoria_id_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca *</label>
                            <select id="producto_marca" name="producto_marca_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar marca</option>
                            </select>
                            <div id="producto_marca_id_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Modelo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo</label>
                            <select id="producto_modelo" name="producto_modelo_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar modelo</option>
                            </select>
                        </div>

                        <!-- Calibre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Calibre</label>
                            <select id="producto_calibre" name="producto_calibre_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar calibre</option>
                            </select>
                        </div>


                        <!-- País de fabricación -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">País de
                                fabricación</label>
                            <select id="producto_madein" name="producto_madein"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar país</option>
                            </select>
                        </div>

                        <!-- Código de barra (ya existente) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código de
                                barra</label>
                            <input type="text" id="producto_codigo_barra" name="producto_codigo_barra"
                                placeholder="Opcional"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Stock mínimo y máximo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock mínimo
                                <small class="text-gray-500">(para alertas)</small></label>
                            <input type="number" id="producto_stock_minimo" name="producto_stock_minimo" min="0"
                                value="0" placeholder="Ej: 5"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <small class="text-xs text-gray-500 dark:text-gray-400">Se generará alerta cuando el stock
                                esté por debajo de este número</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock máximo
                                <small class="text-gray-500">(recomendado)</small></label>
                            <input type="number" id="producto_stock_maximo" name="producto_stock_maximo" min="0"
                                value="0" placeholder="Ej: 50"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <small class="text-xs text-gray-500 dark:text-gray-400">Stock máximo recomendado para
                                reportes</small>
                        </div>

                        <!-- Requiere serie -->
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="producto_requiere_serie" name="producto_requiere_serie"
                                    value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="producto_requiere_serie"
                                    class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Este producto requiere número de serie (armas)
                                </label>
                            </div>
                        </div>


                        
                        <!-- Requiere stock -->
                        <div class="md:col-span-2">
                            <div class="flex items-center">
                                <input type="checkbox" id="producto_requiere_stock" name="producto_requiere_stock"
                                    value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="producto_requiere_stock"
                                    class="ml-2 block text-sm font-bold text-gray-800 dark:text-gray-200">
                                    ¿Este producto NO requiere STOCK?
                                </label>
                            </div>
                            <p class="mt-1 ml-6 text-xs text-gray-600 dark:text-gray-400 bg-yellow-50 dark:bg-gray-700 p-2 rounded border border-yellow-200 dark:border-gray-600">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-1"></i>
                                <strong>¡ATENCIÓN!</strong> Marca esta casilla <strong>ÚNICAMENTE</strong> si estás registrando un <strong>SERVICIO</strong> (ej: Trámites, Licencias, Papelería) que no necesita conteo físico.
                                <br>Si es un producto físico real, <strong>NO</strong> marques esta opción.
                            </p>
                            </div>
                        </div>


                        <!-- Sección de Fotos -->
                        <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-4">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="agregar_fotos" name="agregar_fotos"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="agregar_fotos"
                                    class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ¿Desea agregar fotos a este producto?
                                </label>
                            </div>

                            <!-- Sección de upload de fotos (oculta por defecto) -->
                            <div id="seccion_fotos" class="hidden">
                                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6">
                                    <div id="foto_drop_zone"
                                        class="text-center cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-lg p-4">
                                        <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-2"></i>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                            <span class="font-medium">Arrastra fotos aquí</span> o haz clic para
                                            seleccionar
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500">
                                            Máximo 5 fotos • JPG, PNG, WebP • Hasta 2MB cada una
                                        </p>
                                        <input type="file" id="fotos_producto" name="fotos[]" multiple
                                            accept="image/jpeg,image/jpg,image/png,image/webp" class="hidden">
                                    </div>

                                    <!-- Preview de fotos seleccionadas -->
                                    <div id="preview_fotos" class="mt-4 flex flex-wrap gap-3">
                                        <!-- Las previews se cargarán aquí dinámicamente -->
                                    </div>
                                </div>
                            </div>
                            <!-- AGREGAR ESTA SECCIÓN en el modal de registro, después de seccion_fotos -->
                            <div class="md:col-span-2 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="flex items-center mb-3">
                                    <input type="checkbox" id="agregar_precios" name="agregar_precios"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="agregar_precios"
                                        class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-dollar-sign mr-1"></i>
                                        ¿Desea agregar precios a este producto?
                                    </label>
                                </div>

                                <!-- Sección de precios (oculta por defecto) -->
                            
                                <div id="seccion_precios" class="hidden">
                                    <div class="bg-blue-50 dark:bg-blue-900 p-3 rounded-lg mb-3">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Los precios se registrarán como precios base del producto
                                        </p>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <!-- Precio de Costo -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio de costo *</label>
                                            <input type="number" id="precio_costo" name="precio_costo" step="0.01"
                                                min="0" placeholder="0.00"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="precio_costo_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                            <small class="text-xs text-gray-500 dark:text-gray-400">Costo real de compra/importación</small>
                                        </div>

                                        <!-- Precio de Venta Individual -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio venta (Individual) *</label>
                                            <input type="number" id="precio_venta" name="precio_venta" step="0.01"
                                                min="0" placeholder="0.00"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="precio_venta_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                            <small class="text-xs text-gray-500 dark:text-gray-400">Precio de venta al público</small>
                                        </div>

                                        <!-- Precio de Venta Empresa -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio venta (Empresa) *</label>
                                            <input type="number" id="precio_venta_empresa" name="precio_venta_empresa" step="0.01"
                                                min="0" placeholder="0.00"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="precio_venta_empresa_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                            <small class="text-xs text-gray-500 dark:text-gray-400">Precio de venta a empresas</small>
                                        </div>

                                        <!-- Precio Especial -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio especial</label>
                                            <input type="number" id="precio_especial" name="precio_especial" step="0.01"
                                                min="0" placeholder="0.00"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <small class="text-xs text-gray-500 dark:text-gray-400">Precio promocional (opcional)</small>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                        <!-- Moneda -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moneda</label>
                                            <select id="precio_moneda" name="precio_moneda"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="GTQ">Quetzales (GTQ)</option>
                                            </select>
                                        </div>

                                        <!-- Justificación -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Justificación</label>
                                            <input type="text" id="precio_justificacion" name="precio_justificacion"
                                                placeholder="Ej: Precio inicial, Precio de lanzamiento"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        </div>
                                    </div>

                                    <!-- Cálculo automático de margen -->
                                    <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Cálculos Individual -->
                                            <div class="border-r border-gray-200 dark:border-gray-600 pr-4">
                                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Precio Individual</h5>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                    <div class="flex justify-between">
                                                        <span>Margen:</span>
                                                        <span id="margen_calculado" class="text-green-600 font-bold">0%</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Ganancia:</span>
                                                        <span id="ganancia_calculada" class="text-blue-600 font-bold">Q0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Cálculos Empresa -->
                                            <div class="pl-4">
                                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Precio Empresa</h5>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                                    <div class="flex justify-between">
                                                        <span>Margen:</span>
                                                        <span id="margen_calculado_empresa" class="text-green-600 font-bold">0%</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span>Ganancia:</span>
                                                        <span id="ganancia_calculada_empresa" class="text-blue-600 font-bold">Q0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 sticky bottom-0 bg-white dark:bg-gray-800 pt-4 pb-2 border-t border-gray-100 dark:border-gray-700 z-10">
                        <button type="button" onclick="inventarioManager.closeModal('registro')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" id="registro-submit-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="registro-submit-text">Registrar Producto</span>
                            <span id="registro-loading" class="hidden flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Procesando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Modal Ver Detalle Producto (agregar después de los modales existentes) -->
    <div id="detalle-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('detalle')"></div>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100"
                                id="detalle_producto_nombre">
                                Detalle del Producto
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" id="detalle_producto_sku">
                                SKU: -
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <!-- <button onclick="inventarioManager.editarProducto(inventarioManager.currentProductoId)"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-edit mr-2"></i>
                                Editar
                            </button> -->
                            <button onclick="inventarioManager.closeModal('detalle')"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                <i class="fas fa-times mr-2"></i>
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Contenido Principal -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- Columna Izquierda: Información General -->
                    <div class="lg:col-span-2 space-y-6">

                        <!-- Información Básica -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-info-circle mr-2"></i>
                                Información General
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Categoría:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_categoria">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Subcategoría:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_subcategoria">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Marca:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_marca">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Modelo:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_modelo">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Calibre:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_calibre">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">País de origen:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_pais">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Código de barra:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-gray-100"
                                        id="detalle_codigo_barra">-</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Requiere serie:</span>
                                    <span class="ml-2" id="detalle_requiere_serie">
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            <i class="fas fa-check mr-1"></i>
                                            Sí
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div id="detalle_descripcion_container"
                            class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hidden">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-align-left mr-2"></i>
                                Descripción
                            </h4>
                            <p class="text-sm text-gray-700 dark:text-gray-300" id="detalle_descripcion">
                                Sin descripción disponible
                            </p>
                        </div>

                        <!-- Stock y Precios -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Stock -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                    <i class="fas fa-warehouse mr-2"></i>
                                    Stock Actual
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Total:</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100"
                                            id="detalle_stock_total">0</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Disponible:</span>
                                        <span class="font-medium text-green-600" id="detalle_stock_disponible">0</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Venta Pendiente:</span>
                                        <span class="font-medium text-yellow-600" id="detalle_stock_venta_pendiente">0</span>
                                    </div>
                                       <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">En Reserva:</span>
                                        <span class="font-medium text-yellow-600" id="detalle_stock_reservado">0</span>
                                    </div>
                                    <hr class="border-gray-300 dark:border-gray-600">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">Stock mínimo:</span>
                                        <span class="font-medium text-red-600" id="detalle_stock_minimo">0</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Precios -->
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                    <i class="fas fa-dollar-sign mr-2"></i>
                                    Precios Actuales
                                </h4>
                                <div class="space-y-2 text-sm" id="detalle_precios_container">
                                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                                        Sin precios registrados
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Movimientos Recientes -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <i class="fas fa-history mr-2"></i>
                                    Últimos Movimientos
                                </h4>
                                <button
                                    onclick="inventarioManager.verHistorialCompleto(inventarioManager.currentProductoId)"
                                    class="text-blue-600 hover:text-blue-800 text-xs">
                                    Ver todo
                                </button>
                            </div>
                            <div id="detalle_movimientos_container" class="space-y-2">
                                <div class="text-center text-gray-500 dark:text-gray-400 py-4 text-sm">
                                    Cargando movimientos...
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: Fotos y Acciones -->
                    <div class="space-y-6">

                        <!-- Foto Principal -->
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-image mr-2"></i>
                                Foto Principal
                            </h4>
                            <div class="text-center">
                                <div id="detalle_foto_principal"
                                    class="w-full h-48 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                                    <div class="text-center">
                                        <i class="fas fa-image text-gray-400 text-3xl mb-2"></i>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin foto</p>
                                    </div>
                                </div>
                                <!-- <button onclick="inventarioManager.openFotosModal(inventarioManager.currentProductoId)"
                                    class="mt-3 inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <i class="fas fa-camera mr-2"></i>
                                    Gestionar Fotos
                                </button> -->
                            </div>
                        </div>

                        <!-- Acciones Rápidas -->
                        <!-- <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-bolt mr-2"></i>
                                Acciones Rápidas
                            </h4>
                            <div class="space-y-2">
                                <button onclick="inventarioManager.ingresoRapido(inventarioManager.currentProductoId)"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Ingreso Rápido
                                </button>
                                <button onclick="inventarioManager.egresoRapido(inventarioManager.currentProductoId)"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                                    <i class="fas fa-minus mr-2"></i>
                                    Egreso Rápido
                                </button>
                                <button
                                    onclick="inventarioManager.gestionarPrecios(inventarioManager.currentProductoId)"
                                    class="w-full inline-flex items-center justify-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <i class="fas fa-dollar-sign mr-2"></i>
                                    Gestionar Precios
                                </button>
                            </div>
                        </div> -->

                        <!-- Series (solo si requiere serie) -->
                        <div id="detalle_series_container" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hidden">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <i class="fas fa-barcode mr-2"></i>
                                    Series Registradas
                                </h5>
                                <span class="text-xs text-gray-500 dark:text-gray-400" id="detalle_series_count">
                                    0 series
                                </span>
                            </div>
                            <!-- Contenedor para el resumen de series -->
                            <div id="series_resumen_container"></div>
                            <div id="detalle_series_lista" class="space-y-1 max-h-32 overflow-y-auto">
                                <!-- Series se cargarán aquí -->
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>


    
    <!-- Modal de Gestión de Fotos (agregar después de los modales existentes) -->
    <!-- Modal de Gestión de Fotos Mejorado -->
<div id="fotos-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            onclick="inventarioManager.closeModal('fotos')"></div>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
            
            <!-- Header mejorado -->
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                            <i class="fas fa-camera mr-2 text-blue-500"></i>
                            Gestión de Fotos
                        </h3>
                        <div id="fotos_producto_info" class="mt-2">
                            <!-- Info del producto se carga dinámicamente -->
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <span id="fotos_count" class="text-sm text-gray-600">0/5 fotos</span>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Máximo permitido</div>
                        </div>
                        <button onclick="inventarioManager.closeModal('fotos')"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-times mr-2"></i>
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Columna Izquierda: Fotos Existentes -->
                <div>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <i class="fas fa-images mr-2"></i>
                                Fotos Actuales
                            </h4>
                            <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                Haz clic para acciones
                            </div>
                        </div>

                        <!-- Grid de fotos existentes -->
                        <div id="fotos_existentes" class="grid grid-cols-2 sm:grid-cols-3 gap-3 min-h-[250px]">
                            <!-- Las fotos se cargarán aquí dinámicamente -->
                            <div class="col-span-full text-center py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-3"></div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Cargando fotos...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna Derecha: Subir Nuevas Fotos -->
                <div>
                    <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center">
                                <i class="fas fa-cloud-upload-alt mr-2"></i>
                                Agregar Nuevas Fotos
                            </h4>
                        </div>

                        <!-- Zona de subida -->
                        <div id="seccion_subir_fotos">
                            <div class="border-2 border-dashed border-blue-300 dark:border-blue-600 rounded-lg p-6 text-center hover:border-blue-400 transition-colors cursor-pointer"
                                 onclick="document.getElementById('nuevas_fotos').click()">
                                <div class="space-y-3">
                                    <div class="mx-auto w-12 h-12 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center">
                                        <i class="fas fa-images text-blue-600 dark:text-blue-300 text-xl"></i>
                                    </div>
                                    
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Selecciona o arrastra fotos aquí
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            JPG, PNG, WebP hasta 2MB cada una
                                        </p>
                                    </div>
                                    
                                    <input type="file" 
                                           id="nuevas_fotos" 
                                           multiple 
                                           accept="image/jpeg,image/jpg,image/png,image/webp"
                                           class="hidden">
                                    
                                    <button type="button" 
                                            onclick="event.stopPropagation(); inventarioManager.subirNuevasFotos()"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <i class="fas fa-upload mr-2"></i>
                                        Subir Fotos
                                    </button>
                                </div>
                            </div>
                              <!-- ✅ AGREGAR ESTE CONTENEDOR DE PREVIEW -->
                            <div id="preview_nuevas_fotos" class="mt-4 flex flex-wrap gap-3">
                                <!-- Las previews de nuevas fotos aparecerán aquí -->
                            </div>
                                                    <!-- Mensaje de límite -->
                            <p id="mensaje_limite_fotos" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Máximo 5 fotos por producto
                            </p>
                        </div>

                        <!-- Consejos y ayuda -->
                        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-3 flex items-center">
                                <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                                Consejos para mejores fotos
                            </h5>
                            <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                <li class="flex items-start">
                                    <i class="fas fa-star text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                    La primera foto será la imagen principal
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sun text-yellow-500 mr-2 mt-0.5 text-xs"></i>
                                    Usa buena iluminación natural
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-eye text-green-500 mr-2 mt-0.5 text-xs"></i>
                                    Muestra diferentes ángulos del producto
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-search text-purple-500 mr-2 mt-0.5 text-xs"></i>
                                    Incluye detalles importantes
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-palette text-indigo-500 mr-2 mt-0.5 text-xs"></i>
                                    Mantén fondos limpios y neutros
                                </li>
                            </ul>
                        </div>

                        <!-- Estadísticas de fotos -->
                        <div class="mt-4 bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                <i class="fas fa-chart-bar mr-2 text-blue-500"></i>
                                Estadísticas
                            </h5>
                            <div id="fotos_estadisticas" class="text-xs">
                                <div class="grid grid-cols-2 gap-2">
                                    <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-center">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">0/5</div>
                                        <div class="text-gray-500 dark:text-gray-400">Fotos</div>
                                    </div>
                                    <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-center">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">0 KB</div>
                                        <div class="text-gray-500 dark:text-gray-400">Tamaño</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Acciones del pie -->
            <div class="mt-6 flex justify-between items-center pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    Las fotos se guardan automáticamente
                </div>
                
                <div class="flex space-x-3">
                    <button onclick="inventarioManager.closeModal('fotos')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-check mr-2"></i>
                        Finalizar
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal de Vista Previa de Fotos (opcional) -->
<div id="preview-fotos-modal" class="fixed inset-0 z-[60] overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity"
            onclick="inventarioManager.closeModal('preview-fotos')"></div>

        <div class="inline-block align-middle bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-4xl sm:w-full">
            
            <div class="mb-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                        <i class="fas fa-search mr-2 text-blue-500"></i>
                        Vista Previa del Producto
                    </h3>
                    <button onclick="inventarioManager.closeModal('preview-fotos')"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Galería de vista previa -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Imagen principal -->
                <div>
                    <div class="aspect-w-1 aspect-h-1 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden">
                        <img id="preview_imagen_principal" 
                             src="" 
                             alt="Imagen principal"
                             class="w-full h-full object-cover">
                    </div>
                </div>
                
                <!-- Miniaturas -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Todas las fotos</h4>
                    <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto" id="preview_miniaturas">
                        <!-- Miniaturas se cargan dinámicamente -->
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <button onclick="inventarioManager.closeModal('preview-fotos')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                    <i class="fas fa-times mr-2"></i>
                    Cerrar Vista Previa
                </button>
            </div>

        </div>
    </div>
</div>
    <!-- Modal Ingreso a Inventario -->
    <div id="ingreso-modal" class="fixed inset-0 z-[60] overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('ingreso')"></div>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">

                <form id="ingreso-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ingreso a Inventario</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Agregar stock de productos existentes</p>
                    </div>

                    <div id="ingreso-step-1">
                        <!-- Selección de producto -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto
                                *</label>
                            <input type="text" id="buscar_producto" placeholder="Escribir nombre, SKU o código..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                            <div id="productos_encontrados" class="mt-2 max-h-48 overflow-y-auto hidden">
                                <!-- Resultados de búsqueda -->
                            </div>
                        </div>
                    </div>

                    <!-- dl step-2 en el modal de ingreso -->

                    <div id="ingreso-step-2" class="hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100" id="producto_seleccionado_nombre">
                                Producto seleccionado</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400" id="producto_seleccionado_info">Stock
                                actual: 0</p>
                        </div>

                        <!-- Tipo de ingreso y Origen -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de
                                    movimiento *</label>
                                <select id="mov_tipo" name="mov_tipo" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="ingreso">Ingreso (compra)</option>
                                    <option value="ajuste_positivo">Ajuste Positivo</option>
                                    <option value="devolucion">Devolución</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                                <div id="mov_tipo_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Origen
                                    *</label>
                                <select id="mov_origen" name="mov_origen" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                    <option value="">Seleccione el origen...</option>
                                    <option value="Compra Nacional">Compra Nacional</option>
                                    <option value="Importación">Importación</option>
                                    <option value="Proveedor">Proveedor</option>
                                    <option value="Cliente">Cliente</option>
                                    <option value="Sucursal">Sucursal</option>
                                    <option value="Almacén Principal">Almacén Principal</option>
                                    <option value="Bodega">Bodega</option>
                                    <option value="Vitrina">Vitrina</option>
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Devolución">Devolución</option>
                                    <option value="Ajuste">Ajuste</option>
                                    <option value="Auditoría">Auditoría</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <div id="mov_origen_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            </div>
                        </div>

                        <!-- CANTIDAD (para productos sin serie) -->
                        <div id="cantidad_section" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad *</label>
                            <input type="number" id="mov_cantidad" name="mov_cantidad" min="1" placeholder="Ej: 10"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                            <div id="mov_cantidad_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            <small class="text-xs text-gray-500 dark:text-gray-400">
                                Cantidad de unidades a ingresar al inventario
                            </small>
                        </div>

                        <!-- SERIES (para productos con serie) -->
                        <div id="series_section" class="mb-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Números de serie
                                *</label>
                            <textarea id="numeros_series" name="numeros_series" rows="4"
                                placeholder="Un número de serie por línea&#10;Ejemplo:&#10;GLK123456&#10;GLK123457&#10;GLK123458"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Cantidad detectada: <span id="series_count"
                                    class="font-semibold text-green-600">0</span> series
                            </div>
                            <div id="numeros_series_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            <small class="text-xs text-gray-500 dark:text-gray-400">
                                Cada línea debe contener un número de serie único
                            </small>
                        </div>

                        <!-- GESTIÓN DE LOTES (SOLO para productos SIN serie) -->
                        <div id="lote_section" class="mb-4 hidden">
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <div class="flex items-center mb-3">
                                    <input type="checkbox" id="usar_lotes" name="usar_lotes"
                                        class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                    <label for="usar_lotes"
                                        class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <i class="fas fa-boxes mr-1"></i>
                                        ¿Desea gestionar por lotes?
                                    </label>
                                </div>

                                <!-- Opciones de lote (ocultas por defecto) -->
                                <div id="opciones_lote" class="hidden">
                                    <div class="bg-blue-50 dark:bg-blue-900 p-3 rounded-lg mb-3">
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Los lotes ayudan a rastrear grupos de productos para mejor control de
                                            inventario
                                        </p>
                                    </div>

                                    <div class="flex items-center space-x-4 mb-3">
                                        <label class="flex items-center">
                                            <input type="radio" name="tipo_lote" value="automatico" checked
                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Generar
                                                automáticamente</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="tipo_lote" value="manual"
                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Ingresar
                                                manualmente</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="tipo_lote" value="buscar"
                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Buscar
                                                existente</span>
                                        </label>
                                    </div>

                                    <!-- Lote manual -->
                                    <div id="lote_manual_input" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código
                                            de lote</label>
                                        <input type="text" id="numero_lote" name="numero_lote"
                                            placeholder="Ej: L2025-01-SYS-001"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                        <div id="numero_lote_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        <small class="text-xs text-gray-500 dark:text-gray-400">
                                            Formato recomendado: L[AÑO]-[MES]-[MARCA]-[SECUENCIAL]
                                        </small>
                                    </div>

                                    <!-- Lote automático preview -->
                                    <div id="lote_automatico_preview" class="text-sm text-gray-500 dark:text-gray-400">
                                        El sistema generará: <span id="lote_preview"
                                            class="font-mono text-green-600">L2025-09-AUTO-001</span>
                                        <small class="block text-xs text-gray-400 mt-1">Basado en:
                                            Año-Mes-Marca-Secuencial</small>
                                    </div>

                                    <!-- Buscar lote existente -->
                                    <div id="lote_buscar_input" class="hidden">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar lote</label>
                                        <input type="text" id="buscar_lote" placeholder="Buscar por código de lote..."
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">

                                        <!-- Resultados de búsqueda de lotes -->
                                        <div id="lotes_encontrados"
                                            class="mt-2 max-h-32 overflow-y-auto hidden border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700">
                                            <!-- Resultados dinámicos -->
                                        </div>

                                        <!-- Lote seleccionado - AGREGAR ESTE CONTENEDOR -->
                                        <div class="mt-3">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Lote seleccionado</label>
                                            <div id="lote_seleccionado" class="mt-1 p-3 bg-gray-100 dark:bg-gray-600 rounded-md text-sm text-gray-500 dark:text-gray-400">
                                                Ningún lote seleccionado
                                            </div>
                                            <input type="hidden" id="lote_id" name="lote_id">
                                        </div>

                                        <!-- Información adicional del lote -->
                                        <div class="mt-2 p-2 bg-blue-50 dark:bg-blue-900 rounded text-xs text-blue-700 dark:text-blue-300">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Al seleccionar un lote existente, los productos se agregarán a ese lote específico
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Producto Importado y Licencias -->
                        <div class="border-gray-200 dark:border-gray-700 pt-4 mb-4" id="contenedor_importacion">
                            <div class="flex items-center mb-3">
                                <input type="checkbox" id="producto_es_importado" name="producto_es_importado" value="1"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <label for="producto_es_importado"
                                    class="ml-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <i class="fas fa-globe mr-1"></i>
                                    ¿Es un producto importado?
                                </label>
                            </div>

                            <!-- Sección de asignación de licencia (oculta por defecto) -->
                            <div id="seccion_licencia_registro" class="hidden">
                                <div class="bg-blue-50 dark:bg-blue-900 p-3 rounded-lg mb-3">
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Los productos importados deben asignarse a una licencia de importación válida
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar
                                            licencia</label>
                                        <input type="text" id="buscar_licencia_registro"
                                            placeholder="Número de póliza o descripción..."
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <div id="licencias_encontradas_registro"
                                            class="mt-2 max-h-32 overflow-y-auto hidden border border-gray-300 dark:border-gray-600 rounded-md">
                                            <!-- Resultados de búsqueda -->
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">Licencia
                                            seleccionada</label>
                                        <div id="licencia_seleccionada_registro"
                                            class="mt-1 p-3 bg-gray-100 dark:bg-gray-600 rounded-md text-sm text-gray-500 dark:text-gray-400">
                                            Ninguna licencia seleccionada
                                        </div>
                                        <input type="hidden" id="licencia_id_registro" name="licencia_id_registro">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad a
                                        asignar a esta licencia</label>
                                    <input type="number" id="cantidad_licencia_registro" name="cantidad_licencia"
                                        min="1" value="1" placeholder="Ej: 50"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <small class="text-xs text-gray-500 dark:text-gray-400">
                                        Cantidad de este producto que será asignada a la licencia seleccionada
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mb-4">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                            <textarea id="mov_observaciones" name="mov_observaciones" rows="2"
                                placeholder="Detalles adicionales del ingreso..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"></textarea>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="inventarioManager.closeModal('ingreso')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" id="ingreso-submit-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="ingreso-submit-text">Procesar Ingreso</span>
                            <span id="ingreso-loading" class="hidden flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Procesando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div id="editar-modal" class="fixed inset-0 z-[60] overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('editar')"></div>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">

                <form id="editar-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Editar Producto</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Modificar información del producto</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nombre del producto -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del
                                producto *</label>
                            <input type="text" id="editar_producto_nombre" name="producto_nombre" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <div id="editar_producto_nombre_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Descripción -->
                        <div class="md:col-span-2">
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                            <textarea id="editar_producto_descripcion" name="producto_descripcion" rows="3"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría
                                *</label>
                            <select id="editar_producto_categoria" name="producto_categoria_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar categoría</option>
                            </select>
                            <div id="editar_producto_categoria_id_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Subcategoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subcategoría
                                *</label>
                            <select id="editar_producto_subcategoria" name="producto_subcategoria_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar subcategoría</option>
                            </select>
                            <div id="editar_producto_subcategoria_id_error" class="mt-1 text-sm text-red-600 hidden">
                            </div>
                        </div>

                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marca *</label>
                            <select id="editar_producto_marca" name="producto_marca_id" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar marca</option>
                            </select>
                            <div id="editar_producto_marca_id_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Modelo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo</label>
                            <select id="editar_producto_modelo" name="producto_modelo_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar modelo</option>
                            </select>
                        </div>

                        <!-- Calibre -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Calibre</label>
                            <select id="editar_producto_calibre" name="producto_calibre_id"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar calibre</option>
                            </select>
                        </div>

                        <!-- País -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">País de
                                fabricación</label>
                            <select id="editar_producto_madein" name="producto_madein"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar país</option>
                            </select>
                        </div>

                        <!-- Código de barra -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Código de
                                barra</label>
                            <input type="text" id="editar_producto_codigo_barra" name="producto_codigo_barra"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Stock mínimo y máximo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock
                                mínimo</label>
                            <input type="number" id="editar_producto_stock_minimo" name="producto_stock_minimo" min="0"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Stock
                                máximo</label>
                            <input type="number" id="editar_producto_stock_maximo" name="producto_stock_maximo" min="0"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        </div>

                        <!-- Requiere serie (solo mostrar, no editable) -->
                        <div class="md:col-span-2">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                                <div class="flex items-center">
                                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        El tipo de control (con/sin serie) no se puede modificar después del registro
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Control actual:
                                    </span>
                                    <span id="editar_requiere_serie_display" class="text-sm font-medium">-</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field para el ID -->
                    <input type="hidden" id="editar_producto_id" name="producto_id">

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="inventarioManager.closeModal('editar')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" id="editar-submit-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="editar-submit-text">Guardar Cambios</span>
                            <span id="editar-loading" class="hidden flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Guardando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal Gestión de Precios (agregar después de los modales existentes) -->
    <div id="precios-modal" class="fixed inset-0 z-[60] overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('precios')"></div>

            <div
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Gestión de Precios
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" id="precios_producto_nombre">
                                Producto seleccionado
                            </p>
                        </div>
                        <button onclick="inventarioManager.closeModal('precios')"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <i class="fas fa-times mr-2"></i>
                            Cerrar
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- Columna Izquierda: Precios Actuales -->
                    <div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-history mr-2"></i>
                                Historial de Precios
                            </h4>
                            <div id="precios_historial_container" class="space-y-3 max-h-80 overflow-y-auto">
                                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                    <i class="fas fa-dollar-sign text-gray-400 text-3xl mb-2"></i>
                                    <p class="text-sm">Cargando historial de precios...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Nuevo Precio -->
                    <div>
                        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 mb-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                <i class="fas fa-plus mr-2"></i>
                                Actualizar Precios
                            </h4>

                          <!-- Formulario de precios corregido - reemplazar la sección existente -->
                                <form id="precio-form">
                                    <div class="space-y-4">
                                        <!-- Precio de Costo -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio de costo *</label>
                                            <input type="number" id="nuevo_precio_costo" name="precio_costo" step="0.01"
                                                min="0" placeholder="0.00" required
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="nuevo_precio_costo_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        </div>

                                        <!-- Precio de Venta Individual -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio de venta (Individual) *</label>
                                            <input type="number" id="nuevo_precio_venta" name="precio_venta" step="0.01"
                                                min="0" placeholder="0.00" required
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="nuevo_precio_venta_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        </div>

                                        <!-- Precio de Venta Empresa -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio de venta (Empresa) *</label>
                                            <input type="number" id="nuevo_precio_venta_empresa" name="precio_venta_empresa" step="0.01"
                                                min="0" placeholder="0.00" required
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="nuevo_precio_venta_empresa_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        </div>

                                        <!-- Precio Especial -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Precio especial</label>
                                            <input type="number" id="nuevo_precio_especial" name="precio_especial"
                                                step="0.01" min="0" placeholder="0.00"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <small class="text-xs text-gray-500 dark:text-gray-400">Precio promocional (opcional)</small>
                                        </div>

                                        <!-- Justificación -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Motivo del cambio *</label>
                                            <input type="text" id="nuevo_precio_justificacion" name="precio_justificacion"
                                                placeholder="Ej: Actualización por inflación, Promoción especial..."
                                                required
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                            <div id="nuevo_precio_justificacion_error" class="mt-1 text-sm text-red-600 hidden"></div>
                                        </div>

                                        <!-- Moneda -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Moneda</label>
                                            <select id="nuevo_precio_moneda" name="precio_moneda"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="GTQ">Quetzales (GTQ)</option>
                                            </select>
                                        </div>

                                        <!-- Cálculo automático - VERSIÓN CORREGIDA -->
                                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                                            <div class="text-sm space-y-2">
                                                <!-- Cálculos para precio individual -->
                                                <div class="border-b border-gray-200 dark:border-gray-600 pb-2">
                                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Precio Individual</h5>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 dark:text-gray-400">Margen:</span>
                                                        <span id="nuevo_margen_calculado" class="font-medium text-green-600">0%</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 dark:text-gray-400">Ganancia por unidad:</span>
                                                        <span id="nueva_ganancia_calculada" class="font-medium text-blue-600">Q0.00</span>
                                                    </div>
                                                </div>
                                                
                                                <!-- Cálculos para precio empresa -->
                                                <div class="pt-2">
                                                    <h5 class="font-medium text-gray-700 dark:text-gray-300 mb-2">Precio Empresa</h5>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 dark:text-gray-400">Margen:</span>
                                                        <span id="nuevo_margen_calculado_empresa" class="font-medium text-green-600">0%</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600 dark:text-gray-400">Ganancia por unidad:</span>
                                                        <span id="nueva_ganancia_calculada_empresa" class="font-medium text-blue-600">Q0.00</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Botón Guardar -->
                                        <button type="submit" id="precios-submit-btn"
                                            class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                            <span id="precios-submit-text">Actualizar Precios</span>
                                            <span id="precios-loading" class="hidden flex items-center">
                                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Guardando...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- Modal Egreso de Inventario -->
    <div id="egreso-modal" class="fixed inset-0 z-[60] overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('egreso')"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">

                <form id="egreso-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Egreso de Inventario</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Registrar salida de productos del inventario</p>
                    </div>

                    <div id="egreso-step-1">
                        <!-- Selección de producto -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto *</label>
                            <input type="text" id="buscar_producto_egreso" placeholder="Escribir nombre, SKU o código..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            <div id="productos_encontrados_egreso" class="mt-2 max-h-48 overflow-y-auto hidden">
                                <!-- Resultados de búsqueda -->
                            </div>
                        </div>
                    </div>

                    <div id="egreso-step-2" class="hidden">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-4">
                            <h4 class="font-medium text-gray-900 dark:text-gray-100" id="producto_seleccionado_nombre_egreso">
                                Producto seleccionado</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400" id="producto_seleccionado_info_egreso">Stock actual: 0</p>
                        </div>

                        <!-- Tipo de egreso y Destino -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de movimiento *</label>
                                <select id="egr_tipo" name="egr_tipo" required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="egreso">Egreso</option>
                                    <option value="transferencia">Transferencia</option>
                                    <option value="devolución_proveedor">Devolución a Proveedor</option>
                                    <option value="baja">Baja por deterioro</option>
                                </select>
                                <div id="egr_tipo_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Destino *</label>
                                <select id="egr_destino" name="egr_destino" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                    <option value="">Seleccionar destino...</option>
                                    <option value="sucursal_zona10">Traslado (otra tienda)</option>
                                    <option value="bodega_externa">Bodega Externa</option>
                                    <option value="transferencia_proveedor">Transferencia a Proveedor</option>
                                    <option value="devolucion_garantia">Devolución por Garantía</option>
                                    <option value="baja_deterioro">Baja por Deterioro</option>
                                    <option value="muestra_comercial">Muestra Comercial</option>
                                    <option value="prestamo_temporal">Préstamo Temporal</option>
                                    <option value="exhibicion_feria">Exhibición en Feria</option>
                                    <option value="otro">Otro</option>
                                </select>
                                <div id="egr_destino_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            </div>
                        </div>

                        <!-- CANTIDAD (para productos sin serie) -->
                        <div id="cantidad_section_egreso" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Cantidad *</label>
                            <input type="number" id="egr_cantidad" name="egr_cantidad" min="1" placeholder="Ej: 5"
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            <div id="egr_cantidad_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            <small class="text-xs text-gray-500 dark:text-gray-400">
                                Cantidad de unidades a egresar del inventario
                            </small>
                        </div>

                        <!-- SERIES (para productos con serie) -->
                       <!-- SERIES (para productos con serie) -->
                        <div id="series_section_egreso" class="mb-4 hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seleccionar series a egresar *</label>
                            
                            <!-- Contador de series seleccionadas -->
                            <div class="mb-3 p-3 bg-blue-50 dark:bg-blue-900 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-blue-700 dark:text-blue-300">Series seleccionadas:</span>
                                    <span id="series_seleccionadas_count" class="font-semibold text-blue-600">0</span>
                                </div>
                            </div>
                            
                            <!-- Lista de series disponibles -->
                            <div id="series_disponibles_container" class="border border-gray-300 dark:border-gray-600 rounded-lg max-h-64 overflow-y-auto bg-white dark:bg-gray-700">
                                <div class="p-4 text-center text-gray-500">
                                    <i class="fas fa-spinner fa-spin mb-2"></i>
                                    <p class="text-sm">Cargando series disponibles...</p>
                                </div>
                            </div>
                            
                            <div id="numeros_series_egreso_error" class="mt-1 text-sm text-red-600 hidden"></div>
                            
                            <!-- Input hidden para enviar las series seleccionadas -->
                            <input type="hidden" id="series_seleccionadas_input" name="series_seleccionadas">
                        </div>
                        <!-- Agregar después del container de series/lotes -->
                        <div id="origen_seleccionado_info"></div>
                        <!-- Observaciones -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Observaciones</label>
                            <textarea id="egr_observaciones" name="egr_observaciones" rows="2"
                                placeholder="Detalles adicionales del egreso..."
                                class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="inventarioManager.closeModal('egreso')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" id="egreso-submit-btn"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="egreso-submit-text">Procesar Egreso</span>
                            <span id="egreso-loading" class="hidden flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Procesando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal Historial de Movimientos -->
    <div id="historial-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                onclick="inventarioManager.closeModal('historial')"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
                
                <!-- Header -->
                <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historial de Movimientos</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Consulta todos los movimientos de inventario</p>
                </div>

                <!-- Filtros -->
                <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar producto</label>
                        <input type="text" id="filtro_producto" placeholder="Nombre o SKU..."
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de movimiento</label>
                        <select id="filtro_tipo" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Todos los tipos</option>
                            <option value="ingreso">Ingresos</option>
                            <option value="egreso">Egresos</option>
                            <option value="venta">Ventas</option>
                            <option value="ajuste_positivo">Ajustes Positivos</option>
                            <option value="ajuste_negativo">Ajustes Negativos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha</label>
                        <input type="date" id="filtro_fecha" 
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table id="historial-table" class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Producto</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cantidad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Origen/Destino</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Lote/Serie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            <!-- DataTable cargará los datos aquí -->
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="mt-6 flex justify-end">
                    <button onclick="inventarioManager.closeModal('historial')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>





</div>

@endsection

@vite('resources/js/inventario/index.js')