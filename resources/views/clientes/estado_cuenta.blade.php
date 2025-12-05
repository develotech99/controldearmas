@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
            <i class="fas fa-file-invoice-dollar mr-2"></i>Estado de Cuenta Clientes
        </h1>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Buscar Cliente</label>
                <input type="text" id="searchCliente" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Nombre, Empresa o NIT...">
            </div>
            <div class="flex items-end">
                <button id="btnFiltrar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                    <i class="fas fa-search mr-2"></i>Filtrar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table id="tablaEstadoCuenta" class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente / Empresa</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Saldo a Favor</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider">Deudas Manuales</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-orange-600 dark:text-orange-400 uppercase tracking-wider">Pagos Pendientes (Ventas)</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Data loaded via JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalle -->
<div id="modalDetalle" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4" id="modalTitle">
                            Detalle de Cuenta
                        </h3>
                        
                        <!-- Tabs -->
                        <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                                <button onclick="switchTab('saldo')" id="tab-saldo" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Historial Saldo a Favor
                                </button>
                                <button onclick="switchTab('deudas')" id="tab-deudas" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Deudas Manuales
                                </button>
                                <button onclick="switchTab('pagos')" id="tab-pagos" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                                    Ventas al Crédito
                                </button>
                            </nav>
                        </div>

                        <!-- Content Saldo -->
                        <div id="content-saldo" class="tab-content hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Saldo Nuevo</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ref/Obs</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-saldo" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                            </table>
                        </div>

                        <!-- Content Deudas -->
                        <div id="content-deudas" class="tab-content hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Monto Original</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Pendiente</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-deudas" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                            </table>
                        </div>

                        <!-- Content Pagos -->
                        <div id="content-pagos" class="tab-content hidden">
                            <div id="container-pagos" class="space-y-4">
                                <!-- Cards de ventas se inyectarán aquí -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@vite('resources/js/clientes/estado_cuenta.js')
@endsection
