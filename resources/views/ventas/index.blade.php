@extends('layouts.app')

@section('title', 'Gestión de Ventas')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 lg:sticky lg:top-4 lg:self-start flex flex-col gap-6">

            <!-- Filtros de Producto -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-filter mr-2"></i>Filtros de Producto
                </h2>

                <div class="space-y-4">
                    <div>
                        <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                        <select id="categoria" name="categoria"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Seleccionar...</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->categoria_id }}">
                                    {{ $categoria->categoria_nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="subcategoria" class="block text-sm font-medium text-gray-700 mb-2">Subcategoría</label>
                        <select id="subcategoria" name="subcategoria"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                        <select id="marca" name="marca"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="modelo" class="block text-sm font-medium text-gray-700 mb-2">Modelo</label>
                        <select id="modelo" name="modelo"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                    <div>
                        <label for="calibre" class="block text-sm font-medium text-gray-700 mb-2">Calibre</label>
                        <select id="calibre" name="calibre"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            disabled>
                            <option value="">Seleccionar...</option>
                        </select>
                    </div>

                </div>
            </div>


            <!-- Cliente -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-2">
                    <i class="fas fa-user mr-2"></i>Clientes
                </h2>

                {{-- Selector de clientes particulares --}}
                <div class="mb-3">
                    <label for="clientes" class="block text-sm font-medium text-gray-700 mb-1">Clientes</label>

                    <div class="flex gap-2">
                        <input id="dpiClientes" type="number" placeholder="Buscar por DPI"
                            class="mb-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nitClientes" type="number" placeholder="Buscar por NIT"
                            class="mb-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-center gap-4 mt-2">

                        <button id="btnBuscarCliente"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 hover:text-white transition-all duration-300">
                            <i class="bi bi-search mr-2"></i>Buscar
                        </button>
                        <button type="button" id="btnLimpiarBusqueda"
                            class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 hover:text-gray-900 transition-all duration-300">
                            <i class="bi bi-x-circle mr-2"></i>Limpiar
                        </button>
                    </div>

                    <select id="clienteSelect" name="clienteSelect"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 mt-2">
                        <option value="">Seleccionar...</option>
                        {{-- Se llena dinámicamente con JS --}}
                    </select>

                    {{-- Selector de Empresa (Oculto por defecto) --}}
                    <div id="divEmpresaSelect" class="hidden mt-2">
                        <label for="empresaSelect" class="block text-sm font-medium text-gray-700 mb-1">Empresa / Sucursal</label>
                        <select id="empresaSelect" name="empresaSelect"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Seleccionar empresa...</option>
                        </select>
                    </div>

                    <div id="estadoBusquedaCliente" class="text-xs text-gray-500 mt-1"></div>
                </div>


                {{-- Botón para nuevo cliente --}}
                <button id="btnNuevoCliente"
                    class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                    + Nuevo Cliente
                </button>


            </div>





            {{-- <!-- Método de Pago -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    <i class="fas fa-credit-card mr-2"></i>Método de Pago
                </h2>

                <div class="space-y-2">
                    @foreach ($metodopago as $metodo)
                    <label class="flex items-center">
                        <input type="radio" name="metodoPago" value="{{ $metodo->metpago_id }}" class="mr-2">
                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>{{ $metodo->metpago_descripcion }}
                    </label>
                    @endforeach
                </div>
            </div> --}}
        </div>

        <div class="lg:col-span-2 flex flex-col h-full">
            <!-- Buscador fijo -->
            <div class="flex-shrink-0 mb-6">
                <div class="relative">
                    <div class="relative">
                        <input type="text" id="busquedaProductos" placeholder="Buscar productos..."
                            class="w-full px-4 py-3 pl-12 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                            autocomplete="off">

                        <div class="absolute left-4 top-1/2 transform -translate-y-1/2">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </div>

                    <div id="resultadosBusqueda"
                        class="absolute top-full left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg max-h-64 overflow-y-auto z-50 hidden">
                        <!-- resultados -->
                    </div>
                </div>
            </div>

            <!-- Contenedor de productos con altura fija y scroll interno -->
            <div class="flex-1 flex flex-col min-h-0 bg-white rounded-lg shadow-sm border">
                <!-- Contador fijo -->
                <div class="flex-shrink-0 px-6 py-4 border-b bg-gray-50">
                    <span id="contadorResultados" class="text-sm text-gray-600">Mostrando 0 productos</span>
                </div>

                <!-- Grid con scroll independiente -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div id="gridProductos" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        <!-- Los productos se mostrarán aquí -->
                    </div>
                </div>
            </div>
        </div>
    </div>









    <!-- Botón para abrir carrito -->

    <!-- Botón Carrito de Compras -->
    <button id="btnAbrirCarrito"
        class="fixed top-4 right-4 bg-blue-600 text-white p-3 rounded-full shadow-lg hover:bg-blue-700 z-40">
        <i class="fas fa-shopping-cart text-xl"></i>
        <span id="contadorCarrito"
            class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
    </button>





    <!-- Modal del carrito (deslizante desde la derecha) -->
    <div id="modalCarrito" class="fixed inset-0 z-50 hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black bg-opacity-50" id="overlayCarrito"></div>

        <!-- Panel deslizante -->
        <!-- Agrega 'relative' al panel del carrito -->
        <div id="panelCarrito"
            class="absolute right-0 top-0 h-full w-0 bg-white shadow-xl transition-all duration-300 ease-in-out overflow-hidden ">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="bg-blue-600 text-white p-4 flex items-center justify-between flex-shrink-0">
                    <h2 class="text-xl font-semibold flex items-center">
                        <i class="fas fa-shopping-cart mr-2"></i>
                        Carrito de Compras
                    </h2>
                    <button id="btnCerrarCarrito" class="text-white hover:text-gray-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Contenido principal con scroll -->
                <div class="flex-1 overflow-y-auto">
                    <div class="p-4 space-y-4">
                        <!-- Fecha de venta -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Fecha de Venta</label>
                            <input type="datetime-local" id="fechaVenta" value=""
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Productos en el carrito -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-gray-800">Productos Seleccionados</h3>
                            <div id="productosCarrito" class="space-y-3">
                                <!-- Mensaje cuando está vacío -->
                                <div id="carritoVacio" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-shopping-cart text-4xl mb-2 opacity-30"></i>
                                    <p>Tu carrito está vacío</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold mb-3 text-gray-800">Productos Reservados</h3>
                            <div id="productosCarritoReserva" class="space-y-3">

                                <!-- Mensaje cuando está vacío -->
                                <div id="carritoVacioReserva" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-shopping-cart text-4xl mb-2 opacity-30"></i>
                                    <p>Tu carrito no tiene Reservas</p>
                                </div>
                            </div>
                        </div>

                        <!-- Método de pago -->
                        <div class="border-t pt-4">
                            <div class="flex items-center justify-between mb-3 cursor-pointer" onclick="toggleMetodoPago()"
                                id="metodoPagoHeader">
                                <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    <span id="metodoPagoTitulo">Método de Pago</span>
                                </h3>
                                <i class="fas fa-chevron-down text-gray-500 transition-transform duration-200"
                                    id="metodoPagoIcon"></i>
                            </div>

                            <!-- Contenido del método de pago (acordeón) -->
                            <div id="metodoPagoContenido" class="space-y-3">
                                <!-- Saldo a Favor Section -->
                                <div id="saldoFavorContainer" class="hidden bg-blue-50 p-3 rounded-lg border border-blue-200 mb-3">
                                    <label class="flex items-center justify-between cursor-pointer">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="checkSaldoFavor" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <span class="ml-2 text-sm font-semibold text-blue-800">Usar Saldo a Favor</span>
                                        </div>
                                        <span id="textoSaldoFavor" class="text-sm font-bold text-blue-700">Q0.00</span>
                                    </label>
                                    <div id="infoSaldoRestante" class="text-xs text-gray-600 mt-1 hidden">
                                        <div class="flex items-center gap-2 mb-2">
                                            <label class="font-semibold">Monto a usar:</label>
                                            <input type="number" id="inputSaldoUsar" step="0.01" min="0" 
                                                class="w-24 px-2 py-1 border rounded text-right text-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        Restante a pagar con otro método: <span class="font-bold text-red-600" id="montoRestantePagar">Q0.00</span>
                                    </div>
                                </div>

                                <div class="grid gap-2" id="listaMetodosPago">
                                    @foreach ($metodopago as $metodo)
                                        @if(strtolower($metodo->metpago_descripcion) !== 'saldo a favor')
                                        <label class="flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                                            <input type="radio" name="metodoPago" value="{{ $metodo->metpago_id }}" class="mr-3">
                                                <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                                            <span class="text-sm">{{ $metodo->metpago_descripcion }}</span>
                                        </label>
                                        @endif
                                    @endforeach
                                    
                                    <!-- Mensaje cuando se cubre todo con saldo a favor -->
                                    <div id="mensajePagoCompletoSaldo" class="hidden p-3 bg-green-50 text-green-700 rounded-lg text-sm text-center border border-green-200">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        El saldo a favor cubre el total de la venta.
                                    </div>
                                </div>

                                <!-- Autorización (para métodos 1–5) -->
                                <!-- Autorización (para métodos 1–5) -->
                                <div id="autorizacionContainer" class="hidden">
                                    <div class="grid grid-cols-2 gap-4">
                                        <!-- Selección del Banco -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Seleccionar
                                                Banco</label>
                                            <select id="selectBanco"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                                <option value="">Seleccione un banco</option>
                                                <option value="banrural">Banrural</option>
                                                <option value="banco_industrial">Banco Industrial</option>
                                                <option value="banco_bam">Banco BAM</option>
                                                <option value="banco_gyt">Banco GYT</option>
                                            </select>
                                        </div>

                                        <!-- Número de Autorización -->
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Número de
                                                Autorización / No. Cheque</label>
                                            <input type="text" id="numeroAutorizacion"
                                                placeholder="Ingrese el número de autorización"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Pagos en cuotas (para método 6) -->
                                <div id="cuotasContainer" class="hidden">
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="grid sm:grid-cols-2 gap-3 mb-3">
                                            <!-- Abono -->
                                            <div>
                                                <label class="text-sm font-semibold text-gray-700">Abono inicial</label>
                                                <input type="number" id="abonoInicial" step="0.01" min="0" value=""
                                                    class="mt-1 w-full px-2 py-1 border rounded text-right text-sm"
                                                    placeholder="Ingrese el abono"
                                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '')" />
                                            </div>

                                            <!-- Método de Abono (Efectivo o Transferencia) -->
                                            <div>
                                                <label class="text-sm font-semibold text-gray-700">Método de Abono</label>
                                                <div class="mt-1 flex items-center gap-2">
                                                    <input type="radio" name="metodoAbono" value="efectivo"
                                                        id="efectivoAbono" checked>
                                                    <label for="efectivoAbono" class="text-sm">Efectivo</label>
                                                    <input type="radio" name="metodoAbono" value="transferencia"
                                                        id="transferenciaAbono">
                                                    <label for="transferenciaAbono" class="text-sm">Transferencia</label>
                                                    <input type="radio" name="metodoAbono" value="cheque" id="chequeAbono">
                                                    <label for="chequeAbono" class="text-sm">Cheque</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-xs text-gray-600 mb-2">
                                            Saldo a cuotas: <span id="saldoCuotas">Q0.00</span>
                                        </div>

                                        <!-- Lista de cuotas con altura limitada -->
                                        <div id="cuotasLista"
                                            class="space-y-2 max-h-40 overflow-y-auto border rounded bg-white p-2"></div>

                                        <!-- Número de pagos + repartir -->
                                        <div class="mt-3 flex items-center gap-2">
                                            <label class="text-sm font-semibold text-gray-700">Número de pagos</label>
                                            <input type="number" id="cuotasNumero" min="1" max="36" value="2"
                                                class="w-20 px-2 py-1 border rounded text-center text-sm">
                                            <button type="button" id="cuotasRepartir"
                                                class="px-3 py-1 rounded text-sm border bg-white hover:bg-gray-50">
                                                Repartir
                                            </button>
                                        </div>

                                        <!-- Mostrar contenedor de autorización si se selecciona Transferencia -->
                                        <div id="autorizacionContainer" class="hidden mt-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Número de
                                                Autorización</label>
                                            <input type="text" id="numeroAutorizacion"
                                                placeholder="Ingrese el número de autorización"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        </div>

                                        <div id="cuotasMensaje" class="text-xs mt-2 text-center"></div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer fijo con resumen y botón -->
                <div class="border-t bg-gray-50 p-4 flex-shrink-0">



                    <!-- Checkbox para requerir documentación -->
                    <label class="flex items-center gap-2 cursor-pointer mb-4">
                        <input type="checkbox" id="checkRequiereDocumentacion"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm font-semibold text-gray-800">
                            <i class="fas fa-file-contract text-blue-600"></i>
                            Esta venta requiere documentación
                        </span>
                    </label>

                    <!-- Modal flotante de documentación -->

                    <div id="modalDocumentacion"
                        class="absolute inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40">
                        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md relative">
                            <h3 class="text-lg font-semibold mb-4 text-gray-800 flex justify-between items-center">
                                Agregar Documento
                                <button type="button" id="btnCerrarModalDocumentacion"
                                    class="p-2 rounded hover:bg-gray-100">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de
                                        Documento</label>
                                    <select id="tipoDocumentoSelect"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="">Seleccionar...</option>
                                        <option value="licencia_portacion">Licencia de Portación</option>
                                        <option value="licencia_tenencia">Tenencia</option>

                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Número de
                                        Documento</label>
                                    <input type="text" id="numeroDocumentoInput" placeholder="Ej: 123456789"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <button type="button" id="btnCerrarModalDocumentacion"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                    <i class="fas fa-plus"></i> Agregar Documento
                                </button>
                            </div>
                        </div>
                    </div>





                    <!-- Resumen compacto -->
                    <div class="mb-4">







                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600">Subtotal:</span>
                            <span id="subtotalModal" class="font-semibold">Q0.00</span>
                        </div>

                        <div class="flex items-center justify-between mb-2 hidden">
                            <label class="text-sm text-gray-600 hidden">Descuento (%):</label>
                            <input type="number" id="descuentoModal" min="0" max="100" value="0"
                                class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm hidden">
                        </div>

                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Total:</span>
                            <span id="totalModal" class="text-green-600">Q0.00</span>
                        </div>
                    </div>

                    <!-- Botón procesar venta -->
                    <button id="procesarVentaModal" type="button"
                        class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 transition-color">
                        <i class="fas fa-check mr-2"></i>Procesar Venta
                    </button>
                </div>
            </div>
        </div>
    </div>







    <div id="modalNuevoCliente" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <!-- Overlay -->
        <div id="modalOverlayNC" class="fixed inset-0 bg-black/40"></div>

        <!-- Contenido -->
        <div class="relative mx-auto my-4 sm:my-12 w-11/12 sm:w-[42rem] bg-white rounded-xl shadow-2xl min-h-min">
            <!-- Header -->
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="text-lg font-semibold">
                    <i class="fas fa-user-plus mr-2 text-emerald-600"></i>
                    Registrar nuevo cliente
                </h3>
                <button id="modalCerrarNC" class="p-2 rounded hover:bg-gray-100">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <form id="formNuevoCliente">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-2">

                        <div class="grid grid-cols-2 gap-4 sm:col-span-2">
                            <div>
                                <label for="tipoCliente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Cliente
                                </label>
                                <select id="tipoCliente" name="cliente_tipo" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar...</option>
                                    <option value="1">Cliente Normal</option>
                                    <option value="2">Cliente Premium</option>
                                    <option value="3">Cliente Empresa</option>
                                </select>
                            </div>

                            <!-- Cliente Premium -->
                            <div id="selectorPremium" style="display: none;">
                                <label for="clientePremium" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cliente Premium
                                </label>
                                <select id="clientePremium" name="clientePremium"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach ($clientes as $cliente)
                                        <option value="{{ $cliente->user_id }}" data-clienteid="{{ $cliente->user_id }}"
                                            data-nombre1="{{ $cliente->user_primer_nombre }}"
                                            data-nombre2="{{ $cliente->user_segundo_nombre ?? '' }}"
                                            data-apellido1="{{ $cliente->user_primer_apellido }}"
                                            data-apellido2="{{ $cliente->user_segundo_apellido ?? '' }}"
                                            data-dpi="{{ $cliente->user_dpi_dni }}">
                                            {{ $cliente->user_primer_nombre }} {{ $cliente->user_segundo_nombre }}
                                            {{ $cliente->user_primer_apellido }} {{ $cliente->user_segundo_apellido }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <input id="idCliente" name="cliente_user_id" type="hidden"
                            class="w-full  py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                        <div id="contenedorempresa" class="sm:col-span-2 w-full">
                            <input id="nombreEmpresa" name="cliente_nom_empresa" type="text"
                                placeholder="Nombre de la empresa" disabled
                                class=" hidden  w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                            <!-- Fila siguiente: título "Propietario de la empresa" -->
                            <p id="titulopropietario" class=" hidden mt-2 text-sm font-medium text-gray-700">
                                Propietario de la empresa
                            </p>
                        </div>

                        <!-- Nombres -->
                        <input id="nc_nombre1" name="cliente_nombre1" type="text" placeholder="Primer nombre"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nc_nombre2" name="cliente_nombre2" type="text" placeholder="Segundo nombre"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                        <!-- Apellidos -->
                        <input id="nc_apellido1" name="cliente_apellido1" type="text" placeholder="Primer apellido"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nc_apellido2" name="cliente_apellido2" type="text" placeholder="Segundo apellido"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                        <!-- DPI y NIT -->
                        <input id="nc_dpi" name="cliente_dpi" type="text" placeholder="DPI"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nc_nit" name="cliente_nit" type="text" placeholder="NIT"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                        <!-- Teléfono y Correo -->
                        <input id="nc_telefono" name="cliente_telefono" type="tel" placeholder="Teléfono"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nc_correo" name="cliente_correo" type="email" placeholder="Correo"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">

                        <!-- nombre de vendedor y telefono -->
                        <input id="nc_nombre_vendedor" name="cliente_nom_vendedor" type="tel" placeholder="Nombre vendedor"
                            disabled class=" hidden  w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <input id="nc_telefono_vendedor" name="cliente_cel_vendedor" disabled type="email"
                            placeholder="Telefono Vendedor"
                            class=" hidden  w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">


                        <!-- Dirección -->
                        <div class="sm:col-span-2">
                            <input id="nc_direccion" name="cliente_direccion" type="text" placeholder="Dirección"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- Ubicacion Empresa -->
                        <div class="sm:col-span-2">
                            <input id="nc_ubicacion" name="cliente_ubicacion" type="text" placeholder="Referencia empresa"
                                disabled
                                class=" hidden  w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- PDF Licencia de Compraventa (solo para empresas) -->
                        <div id="contenedor_pdf_licencia" class="sm:col-span-2 hidden">
                            <label for="nc_pdf_licencia" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-file-pdf text-red-600 mr-1"></i>
                                PDF Licencia de Compraventa
                            </label>
                            <input id="nc_pdf_licencia" name="cliente_pdf_licencia" type="file" accept=".pdf"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> Solo archivos PDF, máximo 2MB
                            </p>
                        </div>
                    </div>

                    <!-- Estado mensajes -->
                    <div id="nc_estado" class="text-xs text-gray-500"></div>
                </form>
            </div>


            <!-- Footer -->
            <div class="p-5 border-t flex items-center justify-end gap-2">
                <button id="modalGuardarCliente"
                    class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                    <i class="fas fa-save mr-2"></i>Guardar
                </button>
                <button id="modalCancelarNC" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-200">
                    Cancelar
                </button>
            </div>
        </div>
    </div>







@endsection

@vite('resources/js/ventas/index.js')