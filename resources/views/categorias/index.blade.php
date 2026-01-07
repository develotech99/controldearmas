@extends('layouts.app')

@section('title', 'Gestión de Categorías y Subcategorías')

@section('content')
<!-- Meta tag para CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Pasar datos a JavaScript -->
<script id="categorias-data" type="application/json">
@json($categoriasData->values())
</script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="categorias-container">
    
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate">
                Gestión de Categorías y Subcategorías
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Administra las categorías y subcategorías de productos
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            <button onclick="categoriasManager.openCreateCategoriaModal()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Categoría
            </button>
            <button onclick="categoriasManager.openCreateSubcategoriaModal()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nueva Subcategoría
            </button>
        </div>
    </div>

    <!-- Alerts Container -->
    <div id="alerts-container" class="mb-6">
        <!-- Alerta de Buenas Prácticas de Categorización -->
        <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded shadow-sm mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-indigo-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-indigo-800">Consejo para un Inventario Limpio y Organizado</h3>
                    <div class="mt-2 text-sm text-indigo-700">
                        <p class="mb-2">
                            Para mantener un orden lógico, te recomendamos estructurar tus productos de lo <strong>General</strong> a lo <strong>Específico</strong>:
                        </p>
                        <ul class="list-disc list-inside space-y-1 ml-2">
                            <li>
                                <strong>Categoría (General):</strong> Ej. <em>"Armas"</em>, <em>"Accesorios"</em>, <em>"Municiones"</em>.
                            </li>
                            <li>
                                <strong>Subcategoría (Específico):</strong> Ej. Dentro de "Armas" &rightarrow; <em>"Pistolas"</em>, <em>"Carabinas"</em>. Dentro de "Accesorios" &rightarrow; <em>"Gorras"</em>, <em>"Fundas"</em>.
                            </li>
                        </ul>
                        <p class="mt-2 font-medium">
                            <i class="fas fa-times-circle text-red-500 mr-1"></i> Evita crear "Pistolas" o "Gorras" como Categorías principales. Úsalas como Subcategorías.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-4 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4L5 3m14 8l-14 4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Categorías</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="total-categorias">{{ $categorias->total() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Categorías Activas</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="categorias-activas">{{ $categorias->where('categoria_situacion', 1)->count() }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Subcategorías</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="total-subcategorias">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Subcategorías Activas</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100" id="subcategorias-activas">0</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar categoría</label>
                    <input type="text" 
                           id="search-categorias"
                           placeholder="Escribir nombre de categoría..."
                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Buscar subcategoría</label>
                    <input type="text" 
                           id="search-subcategorias"
                           placeholder="Escribir nombre de subcategoría..."
                           class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar categorías por estado</label>
                    <select id="status-filter-categorias"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filtrar subcategorías por estado</label>
                    <select id="status-filter-subcategorias"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                        <option value="">Todos los estados</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="categoriasManager.clearFilters()" 
                            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Categorías y Subcategorías -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Panel de Categorías -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Categorías</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <span id="categorias-count">0</span> categorías
                    </span>
                </div>
                
                <div class="overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <div id="categorias-list" class="space-y-3">
                            <!-- Las categorías se cargarán aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Subcategorías -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Subcategorías</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <span id="subcategorias-count">0</span> subcategorías
                    </span>
                </div>
                
                <div class="overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <div id="subcategorias-list" class="space-y-3">
                            <!-- Las subcategorías se cargarán aquí dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    @if($categorias->hasPages())
        <div class="mt-6">
            {{ $categorias->links() }}
        </div>
    @endif

    <!-- Modal para Crear/Editar Categoría -->
    <div id="categoria-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="categoriasManager.closeModal('categoria')"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                
                <form id="categoria-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100" id="categoria-modal-title">Crear Nueva Categoría</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Nombre de la Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la categoría</label>
                            <input type="text" 
                                   id="categoria_nombre"
                                   name="categoria_nombre"
                                   required
                                   maxlength="100"
                                   placeholder="Ej: Armas, Accesorios, Municiones, etc."
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                            <div id="categoria_nombre_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Estado -->
                        <!-- <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                            <select id="categoria_situacion"
                                    name="categoria_situacion"
                                    required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm">
                                <option value="">Seleccionar estado</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            <div id="categoria_situacion_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div> -->
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="categoriasManager.closeModal('categoria')" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" 
                                id="categoria-submit-btn"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="categoria-submit-text">Crear</span>
                            <span id="categoria-loading" class="hidden flex items-center">
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

    <!-- Modal para Crear/Editar Subcategoría -->
    <div id="subcategoria-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="categoriasManager.closeModal('subcategoria')"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                
                <form id="subcategoria-form">
                    <div class="mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100" id="subcategoria-modal-title">Crear Nueva Subcategoría</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categoría</label>
                            <select id="subcategoria_idcategoria"
                                    name="subcategoria_idcategoria"
                                    required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar categoría</option>
                            </select>
                            <div id="subcategoria_idcategoria_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Nombre de la Subcategoría -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la subcategoría</label>
                            <input type="text" 
                                   id="subcategoria_nombre"
                                   name="subcategoria_nombre"
                                   required
                                   maxlength="100"
                                   placeholder="Ej: Pistola, Fusil, Chaleco, Mira, etc."
                                   class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <div id="subcategoria_nombre_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div>

                        <!-- Estado -->
                        <!-- <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                            <select id="subcategoria_situacion"
                                    name="subcategoria_situacion"
                                    required
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">Seleccionar estado</option>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            <div id="subcategoria_situacion_error" class="mt-1 text-sm text-red-600 hidden"></div>
                        </div> -->
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" 
                                onclick="categoriasManager.closeModal('subcategoria')" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" 
                                id="subcategoria-submit-btn"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span id="subcategoria-submit-text">Crear</span>
                            <span id="subcategoria-loading" class="hidden flex items-center">
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
</div>

@endsection

@vite('resources/js/categorias/index.js')