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

        <!-- Alerta Informativa: Certificación FEL -->
        <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-file-invoice text-indigo-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-indigo-800">Información sobre Certificación FEL</h3>
                    <div class="mt-2 text-sm text-indigo-700">
                        <p>
                            Al hacer clic en <strong>"Certificar"</strong>, la factura se envía inmediatamente a la <strong>SAT</strong>.
                            <br>
                            Esta acción es definitiva. Si cometes un error después de certificar, deberás emitir una <strong>Nota de Crédito</strong> para anularla fiscalmente.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alerta Informativa: Anulación de Factura -->
        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-red-800">Advertencia sobre Anulación de Facturas</h3>
                    <div class="mt-2 text-sm text-red-700 space-y-2">
                        <p>
                            <strong>No es recomendable anular facturas</strong> a menos que sea estrictamente necesario.
                        </p>
                        <p>
                            <span class="font-semibold">Consecuencias:</span> Al anular una factura (clic en el botón rojo <i class="fas fa-trash"></i>), la venta asociada regresará a estado <strong>PENDIENTE</strong>.
                        </p>
                        <ul class="list-disc list-inside ml-2">
                            <li>Si la anulación fue por <strong>error en datos</strong> (dirección, NIT, etc.): Podrás buscar la venta nuevamente en "Nueva Factura" (por NIT o ID de venta) y volver a facturarla con los datos correctos.</li>
                            <li>Si deseas <strong>eliminar la venta completamente</strong>: Solo un <strong>Administrador</strong> puede eliminar una venta que ha quedado en estado pendiente.</li>
                        </ul>
                    </div>
                </div>
            </div>
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

        @include('facturacion.modals.factura_cambiaria')
    @include('facturacion.modals.nueva_factura')

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
                    <input type="hidden" name="det_fac_producto_id[]" class="item-producto-id">
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
            <!-- Alerta Informativa: Producto no encontrado -->
          
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
