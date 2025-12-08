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
                    
                    <!-- BUSCAR VENTA -->
                    <div class="bg-blue-50 rounded-lg p-4 space-y-4 border border-blue-100">
                        <h4 class="font-semibold text-blue-700 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Buscar Venta Pendiente
                        </h4>
                        
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <input type="text" id="busquedaVenta" 
                                    class="w-full rounded-lg border-blue-200 focus:border-blue-400 focus:ring-blue-400 text-sm"
                                    placeholder="Buscar por ID Venta, Cliente, NIT, SKU o # Serie...">
                            </div>
                            <button type="button" id="btnBuscarVenta"
                                class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition">
                                Buscar
                            </button>
                        </div>

                        <!-- Resultados de búsqueda -->
                        <div id="resultadosVenta" class="hidden space-y-2 max-h-40 overflow-y-auto">
                            <!-- Items dinámicos -->
                        </div>

                        <!-- Venta Seleccionada -->
                        <div id="ventaSeleccionadaInfo" class="hidden bg-white p-3 rounded border border-blue-200 text-sm">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="font-bold text-blue-800">Venta #<span id="lblVentaId"></span></span>
                                    <p class="text-gray-600" id="lblCliente"></p>
                                </div>
                                <button type="button" id="btnQuitarVenta" class="text-red-500 hover:text-red-700 text-xs underline">
                                    Quitar
                                </button>
                            </div>
                            <input type="hidden" name="fac_venta_id" id="fac_venta_id">
                        </div>
                    </div>

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

    <!-- MODAL SELECCIONAR PRODUCTOS (PARCIAL) -->
    <div id="modalSeleccionProductos" class="hidden fixed inset-0 z-[60] overflow-y-auto">
        <div class="absolute inset-0 bg-black/50"></div>
        <div class="relative mx-auto mt-10 w-11/12 max-w-5xl bg-white rounded-xl shadow-2xl overflow-hidden">
            
            <div class="px-5 py-4 border-b bg-blue-50 flex items-center justify-between">
                <h3 class="text-lg font-bold text-blue-800">
                    Seleccionar Productos a Facturar
                </h3>
                <button type="button" id="btnCerrarSeleccion" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="p-5 max-h-[70vh] overflow-y-auto">
                <div class="mb-4 bg-blue-50 p-3 rounded border border-blue-100 text-sm text-blue-800">
                    <p>Seleccione los productos que desea incluir en esta factura. Puede ajustar las cantidades si desea realizar una facturación parcial.</p>
                </div>

                <table class="w-full text-sm text-left border-collapse">
                    <thead class="bg-gray-100 text-gray-700 uppercase font-semibold">
                        <tr>
                            <th class="p-3 border-b w-10">
                                <input type="checkbox" id="chkSelectAll" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </th>
                            <th class="p-3 border-b">Producto</th>
                            <th class="p-3 border-b text-center">Pendiente</th>
                            <th class="p-3 border-b w-32">A Facturar</th>
                            <th class="p-3 border-b">Series</th>
                        </tr>
                    </thead>
                    <tbody id="tbodySeleccionProductos" class="divide-y divide-gray-100">
                        <!-- Items dinámicos -->
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-4 border-t bg-gray-50 flex justify-end gap-3">
                <button type="button" id="btnCancelarSeleccion" class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="button" id="btnConfirmarSeleccion" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm">
                    Confirmar y Agregar
                </button>
            </div>
        </div>
    </div>
