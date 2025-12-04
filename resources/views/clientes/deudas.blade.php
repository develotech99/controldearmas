@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-white">Control de Deudas de Clientes</h1>
        <button id="btnNuevaDeuda" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Registrar Deuda
        </button>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cliente</label>
                <select id="filtroCliente" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Todos</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Estado</label>
                <select id="filtroEstado" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Todos</option>
                    <option value="PENDIENTE" selected>Pendiente</option>
                    <option value="PAGADO">Pagado</option>
                </select>
            </div>
            <div class="flex items-end">
                <button id="btnBuscar" class="w-full bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg transition-colors">
                    Buscar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Descripción</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Monto</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaDeudas" class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
                <!-- JS render -->
            </tbody>
        </table>
        <div id="paginacion" class="px-6 py-4 border-t border-slate-200 dark:border-slate-700"></div>
    </div>
</div>

<!-- Modal Nueva Deuda -->
<div id="modalDeuda" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b border-slate-200 dark:border-slate-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-slate-800 dark:text-white">Registrar Nueva Deuda</h3>
            <button class="cerrarModal text-slate-400 hover:text-slate-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <form id="formDeuda" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">NIT / Nombre Cliente *</label>
                <div class="flex gap-2">
                    <input type="text" id="inputNIT" class="flex-1 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" placeholder="Ingrese NIT o Nombre">
                    <button type="button" id="btnBuscarCliente" class="bg-slate-600 hover:bg-slate-700 text-white px-3 py-2 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
                <input type="hidden" name="cliente_id" id="cliente_id_hidden">
                <div id="infoCliente" class="hidden mt-2 p-2 bg-blue-50 dark:bg-blue-900 rounded text-sm text-blue-800 dark:text-blue-200">
                    <span class="font-bold" id="nombreClienteSeleccionado"></span>
                    <button type="button" id="btnLimpiarCliente" class="ml-2 text-red-600 hover:text-red-800">x</button>
                </div>
            </div>
            <div id="divEmpresa" class="hidden">
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Empresa (Opcional)</label>
                <select name="empresa_id" id="selectEmpresa" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                    <option value="">Seleccione una empresa...</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha *</label>
                <input type="date" name="fecha_deuda" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required value="{{ date('Y-m-d') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Monto (Q) *</label>
                <input type="number" step="0.01" name="monto" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white" required min="0.01">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Descripción</label>
                <textarea name="descripcion" rows="3" class="w-full rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" class="cerrarModal px-4 py-2 text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white">Cancelar</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Guardar</button>
            </div>
        </form>
    </div>
</div>


@endsection

@vite(['resources/js/clientes/deudas.js'])
