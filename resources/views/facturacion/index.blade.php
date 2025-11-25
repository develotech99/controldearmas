@extends('layouts.app')

@section('title', 'Modulo para Facturacion')

@section('content')

    <div class="space-y-6 mt-10">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Módulo para Facturación</h1>
            </div>

              <div class="flex gap-3">
            <button type="button" id="btnAbrirModalFactura" data-modal-open="modalFactura"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
                Nueva Factura
            </button>

            <button type="button" id="btnAbrirModalFacturaCambiaria" data-modal-open="modalFacturaCambiaria"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                </svg>
                Nueva Factura Cambiaria
            </button>
        </div>
        </div>

        <!-- Sección de Consulta Rápida DTE -->
        <div class="bg-white/90 backdrop-blur-sm border border-sky-200 rounded-xl shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="flex-1">
                    <label for="uuid_consulta" class="block text-sm font-medium text-gray-700 mb-1">
                        Consultar DTE por UUID
                    </label>
                    <div class="flex gap-2">
                        <input type="text" id="uuid_consulta"
                            class="flex-1 rounded-lg border-gray-300 focus:border-sky-400 focus:ring-sky-400 text-sm"
                            placeholder="Ingresa el UUID (Ej: E18F9242-8230-4AD3-9F2A-FE4D7DE94C87)">
                        <button type="button" id="btnConsultarDte"
                            class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 shadow-sm transition">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Consultar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resultados de Consulta DTE -->
        <div id="resultadoConsultaDte" class="hidden">
            <!-- Los resultados se mostrarán aquí -->
        </div>

        <!-- Filtros de Fechas para Facturas -->
        <div class="bg-white/90 backdrop-blur-sm border border-gray-200 rounded-xl shadow-sm p-4">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div class="flex-1">
                    <label for="filtroFechaInicio" class="block text-sm font-medium text-gray-700 mb-1">Fecha Inicio</label>
                    <input type="date" id="filtroFechaInicio"
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm">
                </div>
                <div class="flex-1">
                    <label for="filtroFechaFin" class="block text-sm font-medium text-gray-700 mb-1">Fecha Fin</label>
                    <input type="date" id="filtroFechaFin"
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm">
                </div>
                <button type="button" id="btnFiltrarFacturas"
                    class="px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 shadow-sm transition">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Filtrar
                </button>
            </div>
        </div>

        <!-- Tabla de Facturas -->
        <div class="bg-white/80 backdrop-blur-sm border border-emerald-100 rounded-xl shadow-sm dt-card">
            <div class="p-4">
                <table id="tablaFacturas" class="stripe hover w-full text-sm"></table>
            </div>
        </div>

    </div>

        <!-- MODAL NUEVA FACTURA CAMBIARIA -->
    <div id="modalFacturaCambiaria" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <!-- Fondo oscuro -->
        <div class="absolute inset-0 bg-black/40" data-modal-close="modalFacturaCambiaria"></div>

        <div class="relative mx-auto mt-8 mb-8 w-11/12 max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden">

            <!-- Header -->
            <div
                class="px-5 py-4 border-b bg-gradient-to-r from-emerald-50 to-emerald-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    <svg class="w-5 h-5 inline mr-2 text-emerald-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Nueva Factura Cambiaria
                </h3>
                <button type="button" class="p-2 rounded hover:bg-white/50 transition"
                    data-modal-close="modalFacturaCambiaria">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="formFacturaCambiaria">
                @csrf

                <div class="px-5 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                    {{-- DATOS DEL CLIENTE --}}
                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">

                        <h4 class="font-semibold text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Datos del Cliente
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <!-- NIT -->
                            <div>
                                <label for="fac_cam_nit_receptor" class="block text-sm font-medium text-gray-700 mb-1">
                                    NIT / CF <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" id="fac_cam_nit_receptor" name="fac_cam_nit_receptor"
                                        maxlength="20" required
                                        class="flex-1 rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                                        placeholder="Ej: 123456-7 o CF">

                                    <button type="button" id="btnBuscarNitCambiaria"
                                        class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- CUI -->
                            <div>
                                <label for="fac_cam_cui_receptor" class="block text-sm font-medium text-gray-700 mb-1">
                                    CUI <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" id="fac_cam_cui_receptor" name="fac_cam_cui_receptor"
                                        maxlength="20" 
                                        class="flex-1 rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                                        placeholder="Ej: 1234567890101">

                                    <button type="button" id="btnBuscarCuiCambiaria"
                                        class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Nombre (DESHABILITADO) -->
                            <div>
                                <label for="fac_cam_receptor_nombre"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="fac_cam_receptor_nombre" name="fac_cam_receptor_nombre" required
                                    class="w-full rounded-lg bg-gray-100 border-gray-300 text-sm cursor-not-allowed"
                                    placeholder="Nombre del cliente">
                            </div>

                            <!-- Dirección (DESHABILITADO) -->
                            <div>
                                <label for="fac_cam_receptor_direccion"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Dirección
                                </label>
                                <input type="text" id="fac_cam_receptor_direccion" name="fac_cam_receptor_direccion" required
                                    class="w-full rounded-lg bg-gray-100 border-gray-300 text-sm cursor-not-allowed"
                                    placeholder="Dirección del cliente">
                            </div>

                        </div>
                    </div>

                    {{-- CONDICIONES DE CRÉDITO / CAMBIARIA --}}
                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <h4 class="font-semibold text-gray-700 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8c-1.657 0-3 1.343-3 3v4h6v-4c0-1.657-1.343-3-3-3z" />
                            </svg>
                            Condiciones de Crédito
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="fac_cam_plazo_dias"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Plazo (días) <span class="text-red-500">*</span>
                                </label>
                                <input type="number" id="fac_cam_plazo_dias" name="fac_cam_plazo_dias"
                                    min="1" value="30"
                                    class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm">
                            </div>
                            <div>
                                <label for="fac_cam_fecha_vencimiento"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha de vencimiento <span class="text-red-500">*</span>
                                </label>
                                <input type="date" id="fac_cam_fecha_vencimiento" name="fac_cam_fecha_vencimiento"
                                    class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm">
                            </div>
                            <div>
                                <label for="fac_cam_interes"
                                    class="block text-sm font-medium text-gray-700 mb-1">
                                    Interés (% opcional)
                                </label>
                                <input type="number" id="fac_cam_interes" name="fac_cam_interes" min="0"
                                    step="0.01"
                                    class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                                    placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    {{-- ITEMS FACTURA CAMBIARIA --}}
                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-gray-700 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Detalle de Productos/Servicios
                            </h4>
                            <button type="button" id="btnAgregarItemCambiaria"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v12m6-6H6" />
                                </svg>
                                Agregar Producto
                            </button>
                        </div>

                        <div id="contenedorItemsCambiaria" class="space-y-3">
                            <!-- Items se agregan dinámicamente -->
                        </div>
                        <!-- TEMPLATE ITEM FACTURA CAMBIARIA -->
     

                        <template id="templateItemCambiaria">
    <div class="item-factura-cambiaria item-factura grid grid-cols-1 md:grid-cols-6 gap-2 border rounded-lg p-3 bg-white">

        <!-- Descripción -->
        <div class="md:col-span-2">
            <input type="text"
                   name="det_fac_producto_desc[]"
                   class="w-full rounded-lg border-gray-300 text-sm"
                   placeholder="Producto o servicio"
                   required>
        </div>

        <!-- Cantidad -->
        <div>
            <input type="number"
                   name="det_fac_cantidad[]"
                   class="cam-item-cantidad w-full rounded-lg border-gray-300 text-sm text-right"
                   min="0.01" step="0.01" value="1" required>
        </div>

        <!-- Precio unitario -->
        <div>
            <input type="number"
                   name="det_fac_precio_unitario[]"
                   class="cam-item-precio w-full rounded-lg border-gray-300 text-sm text-right"
                   min="0" step="0.01" value="0" required>
        </div>

        <!-- Descuento -->
        <div>
            <input type="number"
                   name="det_fac_descuento[]"
                   class="cam-item-descuento w-full rounded-lg border-gray-300 text-sm text-right"
                   min="0" step="0.01" value="0">
        </div>

        <!-- Total (solo lectura) -->
        <div class="flex items-center gap-2">
            <input type="text"
                   class="cam-item-total w-full rounded-lg border-gray-300 text-sm text-right bg-gray-50"
                   value="0.00" readonly>
            <button type="button"
                    class="btn-eliminar-item-cam px-2 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>
</template>

                    </div>

                    {{-- TOTALES FACTURA CAMBIARIA --}}
                    <div class="bg-gradient-to-r from-emerald-50 to-sky-50 rounded-lg p-4">
                        <div class="flex justify-end">
                            <div class="w-full md:w-1/2 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-semibold" id="subtotalFacturaCambiaria">Q 0.00</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Descuento:</span>
                                    <span class="font-semibold text-red-600" id="descuentoFacturaCambiaria">Q 0.00</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">IVA (12%):</span>
                                    <span class="font-semibold" id="ivaFacturaCambiaria">Q 0.00</span>
                                </div>
                                <div class="border-t-2 border-gray-300 pt-2 flex justify-between">
                                    <span class="text-lg font-bold text-gray-800">TOTAL:</span>
                                    <span class="text-lg font-bold text-emerald-600" id="totalFacturaCambiaria">Q 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer botones -->
                <div
                    class="px-6 py-5 border-t bg-gradient-to-r from-gray-50 to-gray-100 flex items-center justify-end gap-3">
                    <button type="button"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium bg-white hover:bg-gray-100 transition"
                        data-modal-close="modalFacturaCambiaria">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancelar
                    </button>

                    <button type="submit" id="btnGuardarFacturaCambiaria"
                        class="px-5 py-2.5 rounded-xl font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-md">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        Certificar Factura Cambiaria
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- MODAL NUEVA FACTURA -->
    <div id="modalFactura" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="absolute inset-0 bg-black/40" data-modal-close="modalFactura"></div>

        <div class="relative mx-auto mt-8 mb-8 w-11/12 max-w-4xl bg-white rounded-xl shadow-2xl overflow-hidden">

            <div
                class="px-5 py-4 border-b bg-gradient-to-r from-emerald-50 to-emerald-100 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                    <svg class="w-5 h-5 inline mr-2 text-emerald-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Nueva Factura
                </h3>
                <button type="button" class="p-2 rounded hover:bg-white/50 transition" data-modal-close="modalFactura">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="formFactura">
                @csrf

                <div class="px-5 py-4 space-y-5 max-h-[70vh] overflow-y-auto">

                   <div class="bg-gray-50 rounded-lg p-4 space-y-4">

    <h4 class="font-semibold text-gray-700 flex items-center">
        <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Datos del Cliente
    </h4>

    <!-- GRID 2x2 -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- NIT -->
        <div>
            <label for="fac_nit_receptor" class="block text-sm font-medium text-gray-700 mb-1">
                NIT / CF <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
                <input type="text" id="fac_nit_receptor" name="fac_nit_receptor" maxlength="20" required
                    class="flex-1 rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                    placeholder="Ej: 123456-7 o CF">

                <button type="button" id="btnBuscarNit"
                    class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- CUI -->
        <div>
            <label for="fac_cui_receptor" class="block text-sm font-medium text-gray-700 mb-1">
                CUI <span class="text-red-500">*</span>
            </label>
            <div class="flex gap-2">
                <input type="text" id="fac_cui_receptor" name="fac_cui_receptor" maxlength="20" 
                    class="flex-1 rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                    placeholder="Ej: 1234567890101">

                <button type="button" id="btnBuscarCui"
                    class="px-3 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:bg-sky-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Nombre (DESHABILITADO) -->
        <div>
            <label for="fac_receptor_nombre" class="block text-sm font-medium text-gray-700 mb-1">
                Nombre <span class="text-red-500">*</span>
            </label>
            <input type="text" id="fac_receptor_nombre" name="fac_receptor_nombre"  required
                class="w-full rounded-lg bg-gray-100 border-gray-300 text-sm cursor-not-allowed"
                placeholder="Nombre del cliente">
        </div>

        <!-- Dirección (DESHABILITADO) -->
        <div>
            <label for="fac_receptor_direccion" class="block text-sm font-medium text-gray-700 mb-1">
                Dirección
            </label>
            <input type="text" id="fac_receptor_direccion" name="fac_receptor_direccion" required
                class="w-full rounded-lg bg-gray-100 border-gray-300 text-sm cursor-not-allowed"
                placeholder="Dirección del cliente">
        </div>

    </div>
</div>


                    <!-- ITEMS DE LA FACTURA -->
                    <div class="bg-gray-50 rounded-lg p-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-gray-700 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Detalle de Productos/Servicios
                            </h4>
                            <button type="button" id="btnAgregarItem"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-medium hover:bg-emerald-700 transition">
                                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v12m6-6H6" />
                                </svg>
                                Agregar Producto
                            </button>
                        </div>

                        <div id="contenedorItems" class="space-y-3">
                            <!-- Los items se agregarán dinámicamente aquí -->
                        </div>
                    </div>

                    <!-- TOTALES -->
                    <div class="bg-gradient-to-r from-emerald-50 to-sky-50 rounded-lg p-4">
                        <div class="flex justify-end">
                            <div class="w-full md:w-1/2 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-semibold" id="subtotalFactura">Q 0.00</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Descuento:</span>
                                    <span class="font-semibold text-red-600" id="descuentoFactura">Q 0.00</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">IVA (12%):</span>
                                    <span class="font-semibold" id="ivaFactura">Q 0.00</span>
                                </div>
                                <div class="border-t-2 border-gray-300 pt-2 flex justify-between">
                                    <span class="text-lg font-bold text-gray-800">TOTAL:</span>
                                    <span class="text-lg font-bold text-emerald-600" id="totalFactura">Q 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div
                    class="px-6 py-5 border-t bg-gradient-to-r from-gray-50 to-gray-100 flex items-center justify-end gap-3">
                    <button type="button"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-medium bg-white hover:bg-gray-100 transition"
                        data-modal-close="modalFactura">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancelar
                    </button>

                    <button type="submit" id="btnGuardarFactura"
                        class="px-5 py-2.5 rounded-xl font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-md">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Certificar Factura
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Template para items (oculto) -->
    <template id="templateItem">
        <div class="item-factura bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-start">

                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Descripción <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="det_fac_producto_desc[]" required
                        class="w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="Producto o servicio">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cantidad <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="det_fac_cantidad[]" required min="0.01" step="0.01"
                        value="1"
                        class="item-cantidad w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="1">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Precio Unit. <span
                            class="text-red-500">*</span></label>
                    <input type="number" name="det_fac_precio_unitario[]" required min="0" step="0.01"
                        value="0"
                        class="item-precio w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="0.00">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Descuento</label>
                    <input type="number" name="det_fac_descuento[]" min="0" step="0.01" value="0"
                        class="item-descuento w-full rounded-lg border-gray-300 focus:border-emerald-400 focus:ring-emerald-400 text-sm"
                        placeholder="0.00">
                </div>

                <div class="md:col-span-2 flex items-end gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                        <input type="number" name="det_fac_total[]" readonly
                            class="item-total w-full rounded-lg border-gray-300 bg-gray-50 text-sm font-semibold text-gray-700"
                            value="0.00">
                    </div>
                    <button type="button"
                        class="btn-eliminar-item p-2 rounded-lg bg-red-500 text-white hover:bg-red-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </template>


    <!-- Template para resultados DTE -->
    <template id="templateResultadoDte">
        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-gray-800">Resultado de la Consulta</h4>
                <div class="flex gap-2">
                    <span class="px-2 py-1 rounded-full text-xs font-medium" data-estado-badge>
                        <!-- Estado dinámico -->
                    </span>
                    <button type="button" class="p-1 text-gray-400 hover:text-gray-600 transition" data-limpiar-consulta
                        title="Limpiar consulta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <span class="text-gray-600">UUID:</span>
                    <span class="font-mono text-gray-800 text-xs" data-uuid></span>
                </div>
                <div>
                    <span class="text-gray-600">Documento:</span>
                    <span class="font-semibold" data-documento></span>
                </div>
                <div>
                    <span class="text-gray-600">Fecha Certificación:</span>
                    <span data-fecha-certificacion></span>
                </div>
                <div>
                    <span class="text-gray-600">Estado:</span>
                    <span data-estado></span>
                </div>
            </div>
        </div>
    </template>
@endsection

@vite('resources/js/facturacion/index.js')
