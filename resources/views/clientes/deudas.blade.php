@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header Gradient -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 rounded-xl shadow-lg p-6 mb-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-blue-500 opacity-10 rounded-full blur-xl"></div>
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold">Clientes Morosos</h1>
                <p class="text-slate-300 mt-1">Gestión manual de deudas y saldos pendientes</p>
            </div>
            <button id="btnNuevaDeuda" class="bg-blue-600 hover:bg-blue-500 text-white px-6 py-3 rounded-xl shadow-lg transition-all transform hover:scale-105 flex items-center gap-2 font-semibold border border-blue-400/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Nueva Deuda Manual
            </button>
        </div>
    </div>

    <!-- Warning Alert -->
    <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-8 rounded-r-lg shadow-sm">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm leading-5 font-medium text-amber-800">
                    Uso Recomendado
                </h3>
                <div class="mt-2 text-sm leading-5 text-amber-700">
                    <p>
                        Esta sección está diseñada para el <strong>ingreso manual de datos</strong> en casos especiales o para migrar registros antiguos (papel) al sistema.
                    </p>
                    <p class="mt-1">
                        Para operaciones regulares, se recomienda encarecidamente generar las deudas automáticamente a través del módulo de <strong>Ventas</strong> al realizar una venta al crédito.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Filtrar por Cliente</label>
                <select id="filtroCliente" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Todos</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Estado</label>
                <select id="filtroEstado" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="PENDIENTE">Pendientes</option>
                    <option value="PAGADO">Pagados</option>
                    <option value="TODOS">Todos</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="btnBuscar" class="w-full bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg transition-colors flex justify-center items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Buscar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Cliente / Empresa</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Descripción</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Monto Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Pagado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Saldo</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaDeudas" class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                    <!-- JS Render -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nueva Deuda -->
<div id="modalDeuda" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Registrar Nueva Deuda</h3>
            <button class="cerrarModal text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="formDeuda" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIT / Nombre Cliente *</label>
                <div class="flex gap-2">
                    <input type="text" id="inputNIT" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Ingrese NIT o Nombre">
                    <button type="button" id="btnBuscarCliente" class="bg-slate-600 hover:bg-slate-700 text-white px-3 py-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
                <input type="hidden" name="cliente_id" id="cliente_id_hidden">
                <div id="infoCliente" class="hidden mt-2 p-2 bg-blue-50 dark:bg-blue-900 rounded text-sm text-blue-800 dark:text-blue-200 flex justify-between items-center">
                    <span class="font-bold" id="nombreClienteSeleccionado"></span>
                    <button type="button" id="btnLimpiarCliente" class="ml-2 text-red-600 hover:text-red-800 font-bold px-2">X</button>
                </div>
            </div>

            <div id="divEmpresa" class="hidden">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Empresa (Opcional)</label>
                <select name="empresa_id" id="selectEmpresa" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Seleccione una empresa...</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Monto (Q) *</label>
                <input type="number" step="0.01" name="monto" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha *</label>
                <input type="date" name="fecha_deuda" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex justify-end pt-4">
                <button type="button" class="cerrarModal mr-2 px-4 py-2 text-slate-500 hover:text-slate-700">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Pagar (Abono) -->
<div id="modalPago" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Registrar Pago</h3>
            <button class="cerrarModalPago text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="formPago" class="p-6 space-y-4">
            <input type="hidden" id="pago_deuda_id">
            
            <div class="grid grid-cols-2 gap-4 text-sm mb-2">
                <div>
                    <span class="block text-slate-500">Total Deuda:</span>
                    <span id="pago_total" class="font-bold text-slate-800 dark:text-white"></span>
                </div>
                <div>
                    <span class="block text-slate-500">Saldo Pendiente:</span>
                    <span id="pago_saldo" class="font-bold text-red-600"></span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Monto a Pagar (Q) *</label>
                <input type="number" step="0.01" id="pago_monto" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Método de Pago *</label>
                <select id="pago_metodo" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required>
                    <option value="EFECTIVO">Efectivo</option>
                    <option value="TARJETA">Tarjeta</option>
                    <option value="TRANSFERENCIA">Transferencia</option>
                    <option value="CHEQUE">Cheque</option>
                </select>
            </div>

            <div id="divReferencia" class="hidden">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" id="lblReferencia">Referencia</label>
                <input type="text" id="pago_referencia" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nota (Opcional)</label>
                <input type="text" id="pago_nota" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
            </div>

            <div class="flex justify-end pt-4">
                <button type="button" class="cerrarModalPago mr-2 px-4 py-2 text-slate-500 hover:text-slate-700">Cancelar</button>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Historial -->
<div id="modalHistorial" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Historial de Pagos</h3>
            <button class="cerrarModalHistorial text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                    <thead class="bg-slate-50 dark:bg-slate-700">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Fecha</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Método</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Ref.</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Monto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase">Usuario</th>
                        </tr>
                    </thead>
                    <tbody id="tablaHistorial" class="divide-y divide-slate-200 dark:divide-slate-700">
                        <!-- JS Render -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
            <button type="button" class="cerrarModalHistorial bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg">Cerrar</button>
        </div>
    </div>
</div>
@endsection

@vite(['resources/js/clientes/deudas.js'])
