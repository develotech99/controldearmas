@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-100" x-data="{ sidebarOpen: false }">
    <!-- Top Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-history text-blue-600"></i>
                            Historial de Pagos
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Header & Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="md:col-span-3">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Historial de Movimientos</h1>
                <p class="text-gray-500 text-sm">Consulta detallada de todas las transacciones, pagos y movimientos de caja.</p>
            </div>
            <div class="bg-white overflow-hidden shadow-sm rounded-xl border border-gray-200">
                <div class="p-5 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 truncate">Total en Pantalla</p>
                        <p class="text-2xl font-bold text-emerald-600" id="statTotalIngresos">Q 0.00</p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-lg">
                        <i class="fas fa-wallet text-emerald-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8" x-data="{ showFilters: true }">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50 rounded-t-xl">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-filter text-gray-400"></i> Filtros de Búsqueda
                </h3>
                <button @click="showFilters = !showFilters" class="text-gray-400 hover:text-gray-600 text-sm">
                    <span x-show="showFilters">Ocultar</span>
                    <span x-show="!showFilters">Mostrar</span>
                </button>
            </div>
            
            <div x-show="showFilters" class="p-6">
                <form id="filterForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Rango de Fechas -->
                    <div class="col-span-1 md:col-span-2 lg:col-span-1">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Desde</label>
                        <input type="date" id="filterFechaDesde" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <div class="col-span-1 md:col-span-2 lg:col-span-1">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Hasta</label>
                        <input type="date" id="filterFechaHasta" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    <!-- Búsqueda General -->
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Buscar</label>
                        <div class="relative">
                            <input type="text" id="filterBusqueda" placeholder="Referencia, Cliente, Observación..." class="block w-full pl-10 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Método de Pago -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Método de Pago</label>
                        <select id="filterMetodo" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    <!-- Tipo de Movimiento -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Tipo</label>
                        <select id="filterTipo" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="VENTA">Venta</option>
                            <option value="DEPOSITO">Depósito</option>
                            <option value="EGRESO">Egreso</option>
                            <option value="AJUSTE_POS">Ajuste Positivo</option>
                            <option value="AJUSTE_NEG">Ajuste Negativo</option>
                        </select>
                    </div>

                    <!-- Situación -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Estado</label>
                        <select id="filterSituacion" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Todos</option>
                            <option value="ACTIVO">Activo</option>
                            <option value="ANULADO">Anulado</option>
                        </select>
                    </div>

                    <!-- Botones -->
                    <div class="flex items-end gap-3">
                        <button type="button" id="btnAplicarFiltros" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg shadow-sm transition-colors text-sm flex justify-center items-center gap-2">
                            <i class="fas fa-sync-alt"></i> Filtrar
                        </button>
                        <button type="button" id="btnLimpiarFiltros" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-lg shadow-sm transition-colors text-sm" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table Card -->
        <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table id="tablaHistorial" class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Descripción / Cliente</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ref.</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Método</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Monto</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- DataTables -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Venta/Factura (MEGA GUI) -->
    <div id="modalDetalleVenta" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" data-modal-backdrop></div>
        <div class="relative max-w-6xl mx-auto mt-5 mb-10 bg-white rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <!-- Header Premium -->
            <div class="p-6 bg-gradient-to-r from-gray-900 to-gray-800 text-white flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-2xl font-bold flex items-center gap-3">
                        <i class="fas fa-file-invoice-dollar text-emerald-400"></i>
                        Detalle de Venta <span id="mdvVenta" class="text-emerald-400">#—</span>
                    </h3>
                    <p class="text-gray-400 mt-1 text-sm flex items-center gap-4">
                        <span id="mdvFecha"><i class="far fa-calendar-alt mr-1"></i> —</span>
                        <span id="mdvEstadoBadge" class="px-2 py-0.5 rounded bg-gray-700 text-xs font-semibold uppercase tracking-wider">—</span>
                    </p>
                </div>
                <button class="text-gray-400 hover:text-white transition-colors p-2 rounded-full hover:bg-white/10" data-modal-close>
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Content Scrollable -->
            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Columna Izquierda: Info Cliente y Productos -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Tarjeta Cliente -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-10 -mt-10 z-0"></div>
                            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4 relative z-10">Información del Cliente</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-10">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Cliente / Empresa</p>
                                    <p id="mdvCliente" class="font-semibold text-gray-900 text-lg leading-tight">—</p>
                                    <p id="mdvNit" class="text-sm text-gray-600 mt-1 font-mono bg-gray-100 inline-block px-2 py-0.5 rounded">NIT: —</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Vendedor Asignado</p>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <p id="mdvVendedor" class="font-medium text-gray-900">—</p>
                                            <p class="text-xs text-gray-500">Ventas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta Productos -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                                <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Productos Vendidos</h4>
                                <span id="mdvItemsCount" class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">0 items</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm text-left">
                                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                                        <tr>
                                            <th class="px-5 py-3 font-medium">Descripción</th>
                                            <th class="px-5 py-3 font-medium text-center">Cant.</th>
                                            <th class="px-5 py-3 font-medium text-right">Precio Unit.</th>
                                            <th class="px-5 py-3 font-medium text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="mdvTablaProductos" class="divide-y divide-gray-100">
                                        <!-- Dinámico -->
                                    </tbody>
                                    <tfoot class="bg-gray-50/50 font-semibold text-gray-900">
                                        <tr>
                                            <td colspan="3" class="px-5 py-3 text-right">Total Venta</td>
                                            <td id="mdvTotalVenta" class="px-5 py-3 text-right text-emerald-600 text-base">Q 0.00</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: Pagos y Facturación -->
                    <div class="space-y-6">
                        
                        <!-- Resumen Financiero -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-4">Estado Financiero</h4>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-lg border border-emerald-100">
                                    <span class="text-emerald-800 font-medium">Pagado</span>
                                    <span id="mdvPagado" class="text-emerald-700 font-bold text-lg">Q 0.00</span>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-rose-50 rounded-lg border border-rose-100">
                                    <span class="text-rose-800 font-medium">Pendiente</span>
                                    <span id="mdvPendiente" class="text-rose-700 font-bold text-lg">Q 0.00</span>
                                </div>
                            </div>

                            <!-- Barra de progreso -->
                            <div class="mt-4">
                                <div class="flex justify-between text-xs text-gray-500 mb-1">
                                    <span>Progreso de pago</span>
                                    <span id="mdvProgresoTexto">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="mdvBarraProgreso" class="bg-emerald-500 h-2.5 rounded-full transition-all duration-500" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Pagos -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider">Historial de Pagos</h4>
                            </div>
                            <div class="max-h-[300px] overflow-y-auto">
                                <div id="mdvListaPagos" class="divide-y divide-gray-100">
                                    <!-- Dinámico -->
                                </div>
                            </div>
                        </div>

                        <!-- Facturación -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                            <h4 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Facturación</h4>
                            <div id="mdvInfoFactura" class="text-center py-4">
                                <p class="text-gray-500 text-sm">No facturado</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-4 border-t border-gray-200 bg-white flex justify-end gap-3 shrink-0">
                <button class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors" data-modal-close>
                    Cerrar
                </button>
                <a id="btnIrVenta" href="#" target="_blank" class="px-5 py-2.5 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors shadow-lg shadow-blue-500/30 flex items-center gap-2">
                    <span>Ir a Venta</span>
                    <i class="fas fa-external-link-alt text-sm"></i>
                </a>
            </div>
        </div>
    </div>
</div>



@endsection

@vite('resources/js/pagos/historial.js')

