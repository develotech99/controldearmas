@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
            <i class="fas fa-file-invoice-dollar mr-3"></i>Estado de Cuenta Clientes
        </h1>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Saldo a Favor Card -->
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold opacity-90">Saldo a Favor Total</h3>
                <i class="fas fa-wallet text-2xl opacity-75"></i>
            </div>
            <p class="text-3xl font-bold" id="totalSaldoFavor">Q0.00</p>
            <p class="text-sm opacity-75 mt-2">Crédito disponible de clientes</p>
        </div>

        <!-- Deudas Manuales Card -->
        <div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold opacity-90">Deudas Manuales</h3>
                <i class="fas fa-hand-holding-usd text-2xl opacity-75"></i>
            </div>
            <p class="text-3xl font-bold" id="totalDeudas">Q0.00</p>
            <p class="text-sm opacity-75 mt-2">Total pendiente de cobro</p>
        </div>

        <!-- Pagos Pendientes Card -->
        <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-xl shadow-lg p-6 text-white transform hover:scale-105 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold opacity-90">Ventas al Crédito</h3>
                <i class="fas fa-clock text-2xl opacity-75"></i>
            </div>
            <p class="text-3xl font-bold" id="totalPendiente">Q0.00</p>
            <p class="text-sm opacity-75 mt-2">Pagos pendientes de ventas</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8 border border-gray-100 dark:border-gray-700">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar Cliente</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchCliente" class="pl-10 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-blue-500 focus:ring-blue-500 py-2.5" placeholder="Nombre, Empresa o NIT...">
                </div>
            </div>
            <div class="flex items-end">
                <button id="btnFiltrar" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition duration-150 ease-in-out flex items-center justify-center">
                    <i class="fas fa-filter mr-2"></i>Filtrar Resultados
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table id="tablaEstadoCuenta" class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente / Empresa</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Saldo a Favor</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider">Deudas Manuales</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-orange-600 dark:text-orange-400 uppercase tracking-wider">Pagos Pendientes</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
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
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-gray-200 dark:border-gray-700">
            
            <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white" id="modalTitle">
                                Detalle de Cuenta
                            </h3>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Tabs -->
                        <div class="flex space-x-2 mb-6 bg-gray-100 dark:bg-gray-700 p-1 rounded-lg">
                            <button onclick="switchTab('saldo')" id="tab-saldo" class="flex-1 py-2.5 px-4 rounded-md text-sm font-medium transition-all duration-200 focus:outline-none">
                                <i class="fas fa-wallet mr-2"></i>Historial Saldo
                            </button>
                            <button onclick="switchTab('deudas')" id="tab-deudas" class="flex-1 py-2.5 px-4 rounded-md text-sm font-medium transition-all duration-200 focus:outline-none">
                                <i class="fas fa-hand-holding-usd mr-2"></i>Deudas Manuales
                            </button>
                            <button onclick="switchTab('pagos')" id="tab-pagos" class="flex-1 py-2.5 px-4 rounded-md text-sm font-medium transition-all duration-200 focus:outline-none">
                                <i class="fas fa-clock mr-2"></i>Ventas al Crédito
                            </button>
                        </div>

                        <!-- Content Saldo -->
                        <div id="content-saldo" class="tab-content hidden animate-fade-in">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo Nuevo</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref/Obs</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-saldo" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Content Deudas -->
                        <div id="content-deudas" class="tab-content hidden animate-fade-in">
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto Original</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Pendiente</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-deudas" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Content Pagos -->
                        <div id="content-pagos" class="tab-content hidden animate-fade-in">
                            <div id="container-pagos" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Cards de ventas se inyectarán aquí -->
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-gray-600">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .animate-fade-in {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

@vite('resources/js/clientes/estado_cuenta.js')
@endsection
