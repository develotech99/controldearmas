    @extends('layouts.app')

    @section('title', 'Validación de Pagos y Caja')

    @section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .upload-zone {
            border: 2px dashed #d1d5db;
            transition: all .2s ease;
        }

        .upload-zone:hover {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, .05);
        }

        .upload-zone.dragover {
            border-color: #1d4ed8;
            background: rgba(29, 78, 216, .10);
        }

        .chip {
            @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium;
        }


        /* Vista Previa - que se vea como tus otras tablas */
        #tablaPrevia.dataTable-table {
            width: 100%;
            table-layout: auto;
        }

        #tablaPrevia thead th,
        #tablaPrevia tbody td {
            padding: .5rem .75rem;
        }

        #tablaPrevia thead th {
            background: #f9fafb;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .dataTable-container {
            overflow: auto;
        }

        /* permite scroll si hay muchas filas */
        .tabular-nums {
            font-variant-numeric: tabular-nums;
        }

        /* números con ancho fijo */
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Gestión de Caja y Validación de Pagos</h1>
                <p class="mt-2 text-gray-600">Valida comprobantes, controla caja (ingresos/egresos) y concilia estados de cuenta.</p>
            </div>
            <div class="flex items-center gap-3">
                <button id="btnAbrirIngreso"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold">
                    + Registrar Ingreso
                </button>
                <button id="btnAbrirEgreso"
                    class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg font-semibold">
                    + Registrar Egreso
                </button>
                <button id="btnRefrescar"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg font-semibold">
                    Refrescar
                </button>
            </div>
        </div>

        <!-- Tarjetas de estado SIMPLIFICADAS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Saldo total -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="bg-emerald-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-10v10m8-5a9 9 0 11-18 0 9 9 0 0118 0" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900" id="saldoCajaTotalGTQ">Q 0.00</h3>
                        <p class="text-sm text-gray-500">Saldo total en caja</p>
                    </div>
                </div>
            </div>

            <!-- Pendientes de validación -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="bg-amber-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M5.09 19h13.82A2 2 0 0021 17.09L13.91 4.26a2 2 0 00-3.82 0L3 17.09A2 2 0 005.09 19z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900" id="contadorPendientes">0</h3>
                        <p class="text-sm text-gray-500">Pagos pendientes</p>
                    </div>
                </div>
            </div>

            <!-- Último estado de cuenta -->
            <div class="bg-white rounded-xl shadow-sm border p-6">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-3 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900" id="ultimaCargaEstado">—</h3>
                        <p class="text-sm text-gray-500">Última carga bancaria</p>
                    </div>
                </div>
            </div>
        </div>


        <!-- Secciones principales -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Columna izquierda: Bandeja + Movimientos -->
            <div class="lg:col-span-2 space-y-8">

                <!-- Bandeja de validación de facturas -->
                <div class="bg-white rounded-xl shadow-sm border">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Bandeja de Validación de Facturas</h2>
                            <p class="text-sm text-gray-600 mt-1">Revisa el detalle, compara lo debido vs depositado y
                                valida.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="buscarFactura"
                                class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Buscar por cliente, ref, venta...">
                            <select id="filtroEstado"
                                class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="APROBADO">Aprobado</option>
                                <option value="RECHAZADO">Rechazado</option>
                            </select>
                        </div>
                    </div>

                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="tablaPendientes">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Venta</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Cliente
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Concepto
                                    </th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Debía
                                    </th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Depositado</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">
                                        Diferencia</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">
                                        Comprobante</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPendientes" class="bg-white divide-y divide-gray-100">
                                <!-- filas dinámicas -->
                            </tbody>
                        </table>

                        <div id="emptyPendientes" class="text-center text-gray-500 py-10 hidden">
                            <p>No hay pagos pendientes.</p>
                        </div>
                    </div>
                </div>

                <!-- Movimientos del mes -->
                <div class="bg-white rounded-xl shadow-sm border">
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Movimientos del Mes</h2>
                            <p class="text-sm text-gray-600 mt-1">Filtra por método y rango de fechas.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="month" id="filtroMes"
                                class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            <select id="filtroMetodo"
                                class="rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Todos los métodos</option>
                                <!-- JS inyecta opciones -->
                            </select>
                            <button id="btnFiltrarMovs"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg font-semibold">
                                Aplicar
                            </button>
                        </div>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="tablaMovimientos">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Fecha</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Tipo</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Referencia</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Método</th>
                                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Monto</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Estado</th>
                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyMovimientos" class="bg-white divide-y divide-gray-100">
                                <!-- dinámico -->
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50">
                                    <td colspan="6" class="px-3 py-2 text-right font-semibold">Total</td>
                                    <td class="px-3 py-2 text-right font-semibold" id="totalMovimientosMes">Q 0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Columna derecha: Estados de cuenta + Estado proceso -->
            <div class="space-y-8">

                <!-- Subir estado de cuenta -->
                <div class="bg-white rounded-xl shadow-sm border">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Subir Estado de Cuenta</h3>
                        <p class="text-sm text-gray-600 mt-1">Carga CSV/Excel para conciliar con lo debido vs depositado.
                        </p>
                    </div>
                    <div class="p-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Banco</label>
                            <select id="bancoOrigen"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                                <option value="">— Selecciona —</option>
                                <option value="1">Banrural</option>
                                <option value="2">Banco Industrial</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input type="date" id="fechaInicio"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input type="date" id="fechaFin"
                                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>

                        <div id="uploadZone" class="upload-zone p-6 rounded-xl text-center cursor-pointer">
                            <input id="archivoMovimientos" type="file" accept=".csv,.xlsx,.xls" class="hidden">
                            <div id="uploadContent">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 12l3 3m0 0l3-3m-3 3V9" />
                                </svg>
                                <p class="text-sm text-gray-700">Arrastra tu archivo aquí o haz clic para seleccionar</p>
                                <p class="text-xs text-gray-500">Formatos: CSV, XLSX, XLS</p>
                            </div>
                            <div id="fileInfo" class="hidden">
                                <p class="text-sm font-medium text-green-700" id="fileName">Archivo seleccionado</p>
                                <p class="text-xs text-green-600" id="fileSize">Tamaño</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <button id="btnVistaPrevia"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold disabled:opacity-50"
                                disabled>
                                Vista previa
                            </button>
                            <button id="btnProcesar"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg font-semibold disabled:opacity-50"
                                disabled>
                                Procesar y conciliar
                            </button>
                            <button id="btnLimpiar"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold">
                                Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Estado del procesamiento -->
                <div class="bg-white rounded-xl shadow-sm border">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Estado del Procesamiento</h3>
                    </div>
                    <div class="p-6">
                        <div id="procesamientoEstado" class="space-y-3">
                            <div class="flex items-center text-gray-500">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm">Esperando archivo...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Últimas cargas -->
                <div class="bg-white rounded-xl shadow-sm border">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Últimas Cargas</h3>
                    </div>
                    <div class="p-6">
                        <div id="historialCargas" class="space-y-3">
                            <div class="text-center text-gray-500 py-8">
                                <svg class="mx-auto h-10 w-10 text-gray-400 mb-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V6a2 2 0 012-2h5.5L16 8.5V19a2 2 0 01-2 2z" />
                                </svg>
                                <p class="text-sm">No hay cargas recientes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="seccionConciliacion" class="bg-white rounded-xl shadow-sm border hidden">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Resultados de Conciliación</h3>
                        <p class="text-sm text-gray-600 mt-1">Coincidencias encontradas automáticamente</p>
                    </div>
                    <div class="p-6">

                        <div id="matchesList" class="space-y-3 mb-6">

                        </div>

                        <div id="noMatchList" class="space-y-3">

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Vista previa de CSV -->
        <div id="vistaPrevia" class="hidden mt-8">
            <div class="bg-white rounded-xl shadow-sm border">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Vista Previa del Estado de Cuenta</h3>
                    <p class="text-sm text-gray-600 mt-1">Revisa antes de procesar.</p>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200" id="tablaPrevia">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Referencia</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Monto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detectado</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTablaPrevia" class="bg-white divide-y divide-gray-200">
                            <!-- dinámico -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <!-- MODALES -->
    <!-- Modal Ver Comprobante -->
    <div id="modalComprobante" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" data-modal-backdrop></div>
        <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold">Comprobante</h3>
                <button class="text-gray-500 hover:text-gray-700" data-modal-close>&times;</button>
            </div>
            <div class="p-4">
                <img id="imgComprobante" src="" alt="Comprobante"
                    class="w-full rounded-lg object-contain max-h-[70vh]">
                <div class="mt-3 text-sm text-gray-600">
                    <p><span class="font-semibold">Referencia: </span><span id="refComprobante">—</span></p>
                    <p><span class="font-semibold">Fecha: </span><span id="fechaComprobante">—</span></p>
                    <p><span class="font-semibold">Monto: </span><span id="montoComprobante">—</span></p>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
                <a id="btnDescargarComprobante" href="#" target="_blank"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Descargar</a>
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg"
                    data-modal-close>Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Validar/Rechazar -->
    <div id="modalValidar" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" data-modal-backdrop></div>
        <div class="relative max-w-2xl mx-auto mt-10 bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Validación de Pago</h3>
                <p class="text-sm text-gray-600">Confirma la acción para la venta <span id="mvVenta">#—</span>.</p>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Debía depositar</p>
                        <p id="mvDebia" class="font-semibold">Q 0.00</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500">Depositado</p>
                        <p id="mvHizo" class="font-semibold">Q 0.00</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Diferencia</p>
                        <p id="mvDif" class="font-semibold">Q 0.00</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500">Método</p>
                        <p id="mvMetodo" class="font-semibold">—</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones (opcional)</label>
                    <textarea id="mvObs" rows="3"
                        class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Comentario para el cliente o nota interna..."></textarea>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 flex justify-between">
                <button id="btnRechazar" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg">
                    Rechazar
                </button>
                <div class="flex gap-2">
                    <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg"
                        data-modal-close>Cancelar</button>
                    <button id="btnAprobar" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg">
                        Aprobar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Egreso -->
    <div id="modalEgreso" class="hidden fixed inset-0 z-50">
        <!-- overlay -->
        <div class="absolute inset-0 bg-black/50 z-0" data-modal-backdrop></div>

        <!-- panel -->
        <div class="relative z-10 max-w-xl mx-auto mt-10 bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Registrar Egreso de Caja</h3>
            </div>
            <form id="formEgreso">
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                            <input type="datetime-local" id="egFecha" name="fecha"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
                            <input type="number" step="0.01" id="egMonto" name="monto"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="0.00">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
                            <input type="text" id="egMotivo" name="motivo"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Compra insumos, servicios, otros...">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Documento/Referencia</label>
                            <input type="text" id="egReferencia" name="referencia"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Factura/Serie/Folio">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Evidencia (opcional)</label>
                            <input type="file" id="egArchivo" name="archivo" accept="image/*,application/pdf"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </form>

            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg"
                    data-modal-close>Cancelar</button>
                <button id="btnGuardarEgreso" class="bg-rose-600 hover:bg-rose-700 text-white px-4 py-2 rounded-lg">
                    Guardar egreso
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Detalle Venta/Factura (Simple) -->
    <div id="modalDetalleVenta" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50" data-modal-backdrop></div>
        <div class="relative max-w-4xl mx-auto mt-10 bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Detalle de Venta <span id="mdvVenta">#—</span></h3>
                    <p class="text-sm text-gray-600">Resumen de ítems y totales.</p>
                </div>
                <button class="text-gray-500 hover:text-gray-700" data-modal-close>&times;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Cliente</p>
                        <p id="mdvCliente" class="font-medium text-gray-900">—</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Fecha</p>
                        <p id="mdvFecha" class="font-medium text-gray-900">—</p>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-2">Productos</p>
                    <div class="border rounded-lg overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="px-4 py-2">Descripción</th>
                                    <th class="px-4 py-2 text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody id="mdvTablaProductos" class="divide-y divide-gray-100">
                                <!-- Dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end pt-2 border-t">
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total Venta</p>
                        <p id="mdvTotalVenta" class="text-xl font-bold text-emerald-600">Q 0.00</p>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t bg-gray-50 text-right">
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg"
                    data-modal-close>Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Registrar Ingreso -->
    <div id="modalIngreso" class="hidden fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/50 z-0" data-modal-backdrop></div>
        <div class="relative z-10 max-w-xl mx-auto mt-10 bg-white rounded-xl shadow-xl overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">Registrar Ingreso de Caja</h3>
            </div>
            <form id="formIngreso">
                <div class="p-4 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha</label>
                            <input type="datetime-local" id="ingFecha" name="fecha"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monto</label>
                            <input type="number" step="0.01" id="ingMonto" name="monto"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="0.00">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Concepto</label>
                            <input type="text" id="ingConcepto" name="concepto"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Venta, depósito, ajuste...">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Referencia</label>
                            <input type="text" id="ingReferencia" name="referencia"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Número de referencia">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Comprobante (opcional)</label>
                            <input type="file" id="ingArchivo" name="archivo" accept="image/*,application/pdf"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </form>

            <div class="p-4 border-t bg-gray-50 flex justify-end gap-2">
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg"
                    data-modal-close>Cancelar</button>
                <button id="btnGuardarIngreso" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg">
                    Guardar ingreso
                </button>
            </div>
        </div>
    </div>

    @endsection
    <script>
    window.URL_MIS_PAGOS = "{{ route('misfacturas.pendientes') }}";
</script>


    @vite('resources/js/pagos/administrar.js')