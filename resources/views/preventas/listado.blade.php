@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">
            <i class="fas fa-list-alt mr-3"></i>Listado de Preventas
        </h1>
        <a href="{{ route('preventas.index') }}" class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-lg transform hover:scale-105 transition duration-200 ease-in-out flex items-center">
            <i class="fas fa-plus mr-2"></i>Nueva Preventa
        </a>
    </div>

    <!-- Info Alert -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 rounded-r-lg shadow-sm dark:bg-gray-800 dark:border-blue-400">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500 dark:text-blue-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    <span class="font-bold">Información Importante:</span> Las preventas son reservas de productos con un precio de referencia. 
                    Al eliminar una preventa, esta acción es <span class="font-bold text-red-600 dark:text-red-400">irreversible</span>.
                </p>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table id="tablaPreventas" class="w-full whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Total (Ref.)</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">Abonado</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
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
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200 dark:border-gray-700">
            
            <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900 dark:text-white" id="modalTitle">
                                Detalle de Preventa
                            </h3>
                            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <div><span class="font-bold block text-gray-500 text-xs uppercase">Cliente</span> <span id="detCliente" class="text-lg font-semibold text-gray-800 dark:text-white"></span></div>
                            <div><span class="font-bold block text-gray-500 text-xs uppercase">Fecha</span> <span id="detFecha" class="text-lg font-semibold text-gray-800 dark:text-white"></span></div>
                            <div><span class="font-bold block text-gray-500 text-xs uppercase">Total Referencia</span> <span id="detTotal" class="text-lg font-bold text-emerald-600"></span></div>
                            <div><span class="font-bold block text-gray-500 text-xs uppercase">Monto Abonado</span> <span id="detAbonado" class="text-lg font-bold text-blue-600"></span></div>
                            <div><span class="font-bold block text-gray-500 text-xs uppercase">Estado</span> <span id="detEstado"></span></div>
                            <div class="col-span-2"><span class="font-bold block text-gray-500 text-xs uppercase">Observaciones</span> <span id="detObservaciones" class="italic"></span></div>
                        </div>

                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-bold text-gray-800 dark:text-white mb-4 flex items-center">
                                <i class="fas fa-box-open mr-2 text-blue-500"></i> Productos Reservados
                            </h4>
                            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 rounded-lg">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Ref.</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal Ref.</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody-productos" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"></tbody>
                                </table>
                            </div>
                            <p class="text-xs text-gray-400 mt-2 text-right">* Los precios son de referencia y pueden variar.</p>
                        </div>

                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 sm:flex sm:flex-row-reverse border-t border-gray-200 dark:border-gray-600">
                <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    Cerrar
                </button>
                <button type="button" id="btnImprimirModal" class="mt-3 w-full inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                    <i class="fas fa-print mr-2"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

@vite('resources/js/preventas/listado.js')
@endsection
