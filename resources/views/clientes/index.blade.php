@extends('layouts.app')

@section('title', 'Gestión de Clientes')

@section('content')
<!-- Meta tag para CSRF token -->
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Pasar datos a JavaScript -->
<script id="clientes-data" type="application/json">
@json($clientesData->values())
</script>

<script id="usuarios-premium-data" type="application/json">
@json($usuariosPremium)
</script>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" id="clientes-container">
    
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate">
                Gestión de Clientes
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Administra la información de tus clientes
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button onclick="window.clientesManager.openCreateModal()" 
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Cliente
            </button>
        </div>
    </div>

    <!-- Alerts Container -->
    <div id="alerts-container" class="mb-6"></div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-5 mb-6">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total Clientes</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Normales</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $stats['normales'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Premium</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $stats['premium'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Empresas</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $stats['empresas'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Este Mes</dt>
                            <dd class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $stats['este_mes'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="search-clientes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Buscar
                </label>
                <input type="text" 
                       id="search-clientes" 
                       placeholder="Nombre, DPI, NIT, Empresa..."
                       class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
            </div>
            <div>
                <label for="tipo-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Tipo de Cliente
                </label>
                <select id="tipo-filter" 
                        class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                    <option value="">Todos</option>
                    <option value="1">Normal</option>
                    <option value="2">Premium</option>
                    <option value="3">Empresa</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="window.clientesManager.limpiarFiltros()" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            DPI / NIT
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody id="clientes-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Se llenará dinámicamente con JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay clientes</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Comienza creando un nuevo cliente.</p>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $clientes->links() }}
    </div>
</div>

<!-- Modal Cliente -->
<div id="cliente-modal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="window.clientesManager.closeModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <form id="cliente-form" enctype="multipart/form-data">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                Nuevo Cliente
                            </h3>
                            <div class="mt-4">

                            
                                <!-- Tipo y Asociación -->
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                        <i class="fas fa-tag mr-2"></i>Clasificación
                                    </h4>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="cliente_tipo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Tipo de Cliente <span class="text-red-500">*</span>
                                            </label>
                                            <select name="cliente_tipo" id="cliente_tipo" required onchange="window.clientesManager.toggleCamposEmpresa()"
                                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="">Seleccione...</option>
                                                <option value="1">Normal</option>
                                                <option value="2">Premium</option>
                                                <option value="3">Empresa</option>
                                            </select>
                                        </div>
                                        <!-- <div>
                                            <label for="cliente_user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Asociar a Usuario Premium
                                            </label>
                                            <select name="cliente_user_id" id="cliente_user_id"
                                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                                <option value="">Sin asignar</option>
                                                @foreach($usuariosPremium as $usuario)
                                                    <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                                @endforeach
                                            </select>
                                        </div> -->
                                    </div>
                                </div>
                                <!-- Información Personal -->
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                        <i class="fas fa-user mr-2"></i>Información Personal
                                    </h4>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="cliente_nombre1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Primer Nombre <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="cliente_nombre1" id="cliente_nombre1" required
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_nombre2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Segundo Nombre
                                            </label>
                                            <input type="text" name="cliente_nombre2" id="cliente_nombre2"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_apellido1" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Primer Apellido <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" name="cliente_apellido1" id="cliente_apellido1" required
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_apellido2" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Segundo Apellido
                                            </label>
                                            <input type="text" name="cliente_apellido2" id="cliente_apellido2"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_dpi" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                DPI
                                            </label>
                                            <input type="text" name="cliente_dpi" id="cliente_dpi"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_nit" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                NIT
                                            </label>
                                            <input type="text" name="cliente_nit" id="cliente_nit"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                    </div>
                                </div>

                                <!-- Contacto -->
                                <div class="mb-6">
                                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                        <i class="fas fa-phone mr-2"></i>Contacto
                                    </h4>
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <div>
                                            <label for="cliente_telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Teléfono
                                            </label>
                                            <input type="text" name="cliente_telefono" id="cliente_telefono"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div>
                                            <label for="cliente_correo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Correo Electrónico
                                            </label>
                                            <input type="email" name="cliente_correo" id="cliente_correo"
                                                   class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="cliente_direccion" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Dirección
                                            </label>
                                            <textarea name="cliente_direccion" id="cliente_direccion" rows="2"
                                                      class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-md"></textarea>
                                        </div>
                                    </div>
                                </div>


                                <!-- Campos Empresa (ocultos por defecto) -->
                                <div id="campos-empresa" class="mb-6 hidden">
                                    <div class="flex justify-between items-center mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
                                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                            <i class="fas fa-building mr-2"></i>Información de Empresas
                                        </h4>
                                        <button type="button" onclick="window.clientesManager.addEmpresaField()" 
                                                class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            <i class="fas fa-plus mr-1"></i> Agregar Empresa
                                        </button>
                                    </div>
                                    
                                    <div id="empresas-container" class="space-y-4">
                                        <!-- Las empresas se agregarán aquí dinámicamente -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <span id="btn-text">Guardar</span>
                        <span id="btn-loading" class="hidden">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                    <button type="button" 
                            onclick="window.clientesManager.closeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal PDF -->
<div id="pdf-modal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="pdf-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="window.clientesManager.closePdfModal()"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal Content -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="pdf-modal-title">
                        <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                        Licencia de Compraventa
                    </h3>
                    <button onclick="window.clientesManager.closePdfModal()" 
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- PDF Content -->
            <div class="bg-gray-100 dark:bg-gray-900" style="height: 80vh;">
                <!-- Loading -->
                <div id="pdf-loading" class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Cargando PDF...</p>
                    </div>
                </div>
                
                <!-- PDF Iframe -->
                <iframe id="pdf-iframe" 
                        class="w-full h-full hidden" 
                        frameborder="0">
                </iframe>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        onclick="window.clientesManager.closePdfModal()"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    </div>
</div>

<!-- Modal Gestión de Empresas -->
<div id="empresas-modal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="window.clientesManager.closeEmpresasModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="empresas-modal-title">
                                Gestión de Empresas
                            </h3>
                            <button onclick="window.clientesManager.closeEmpresasModal()" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Cerrar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Formulario Agregar/Editar Empresa -->
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3" id="form-empresa-title">Nueva Empresa</h4>
                            <form id="empresa-form" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <input type="hidden" name="emp_id" id="emp_id">
                                <input type="hidden" name="emp_cliente_id" id="emp_cliente_id">
                                
                                <div class="sm:col-span-1">
                                    <input type="text" name="emp_nombre" id="emp_nombre" placeholder="Nombre Comercial *" required
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-1">
                                    <input type="text" name="emp_nit" id="emp_nit" placeholder="NIT"
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-1">
                                    <input type="text" name="emp_direccion" id="emp_direccion" placeholder="Dirección"
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-1">
                                    <input type="text" name="emp_nom_vendedor" id="emp_nom_vendedor" placeholder="Nombre Vendedor"
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-1">
                                    <input type="text" name="emp_cel_vendedor" id="emp_cel_vendedor" placeholder="Celular Vendedor"
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Licencia Compraventa (PDF)</label>
                                    <input type="file" name="emp_licencia_compraventa" id="emp_licencia_compraventa" accept=".pdf"
                                           class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <div class="sm:col-span-1">
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Vencimiento Licencia</label>
                                    <input type="date" name="emp_licencia_vencimiento" id="emp_licencia_vencimiento"
                                           class="block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md">
                                </div>
                                <div class="sm:col-span-4 flex space-x-2 justify-end mt-2">
                                    <button type="submit" class="flex-1 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Guardar
                                    </button>
                                    <button type="button" onclick="window.clientesManager.resetEmpresaForm()" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Tabla de Empresas -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">NIT</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dirección</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Licencia</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="empresas-tbody" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <!-- JS render -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="window.clientesManager.closeEmpresasModal()"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection


@vite('resources/js/clientes/clientes.js')