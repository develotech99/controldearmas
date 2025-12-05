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

    <div class="flex">
        <!-- Sidebar Filters (Desktop) -->
        <aside class="hidden lg:block w-64 bg-white border-r border-gray-200 min-h-[calc(100vh-4rem)] p-6 overflow-y-auto fixed left-0 top-16 bottom-0 z-20">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Filtros Avanzados</h2>
            
            <form id="filterForm" class="space-y-6">
                <!-- Rango de Fechas -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rango de Fechas</label>
                    <div class="space-y-2">
                        <input type="date" id="filterFechaDesde" name="from" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <input type="date" id="filterFechaHasta" name="to" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>

                <!-- Método de Pago -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                    <select id="filterMetodo" name="metodo_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">Todos</option>
                        <!-- Se llenará dinámicamente -->
                    </select>
                </div>

                <!-- Botones -->
                <div class="pt-4 border-t border-gray-200 space-y-2">
                    <button type="button" id="btnAplicarFiltros" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Aplicar Filtros
                    </button>
                    <button type="button" id="btnLimpiarFiltros" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Limpiar
                    </button>
                </div>
            </form>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 lg:ml-64 p-8">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-emerald-100 rounded-md p-3">
                                <i class="fas fa-money-bill-wave text-emerald-600 text-xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Ingresos</dt>
                                    <dd class="text-lg font-bold text-gray-900" id="statTotalIngresos">Q 0.00</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Más stats aquí si se desea -->
            </div>

            <!-- Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Movimientos Registrados</h3>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Buscar referencia, cliente..." class="block w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table id="tablaHistorial" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción / Cliente</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ref.</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- DataTables -->
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
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

@push('scripts')
    @vite(['resources/js/pagos/historial.js'])
@endpush
@endsection
