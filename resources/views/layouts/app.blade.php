<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CONTROL DE ARMAS') }} - Sistema de Inventario</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" href="{{ asset('images/controlarmasdev.png') }}" type="image/png">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        // Check for dark mode preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }

        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        }

        function closeMobileMenu() {
            const sidebar = document.getElementById('mobile-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    </script>
</head>

<body class="font-sans antialiased bg-slate-50 dark:bg-gray-900 dark:text-gray-100 h-full">
    <div class="min-h-screen flex">
        <!-- Sidebar Overlay (Mobile) -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"
            onclick="closeMobileMenu()"></div>

        <!-- Sidebar -->
        <div id="mobile-sidebar"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-800 dark:bg-gray-800 transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:flex-shrink-0">
            @include('layouts.navigation')
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-slate-200 dark:border-gray-700 lg:hidden">
                <div class="px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between items-center py-4">
                        <!-- Mobile Menu Button -->
                        <button onclick="toggleMobileMenu()"
                            class="p-2 rounded-md text-slate-600 dark:text-gray-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <!-- Mobile Logo -->
                            <div class="w-8 h-8 bg-slate-800 dark:bg-gray-900 rounded-lg flex items-center justify-center">
                                <img src="{{ asset('images/controlarmasdev.png') }}" alt="Logo" class="w-6 h-6">
                            </div>
                            <div class="flex flex-col">
                                <span class="text-lg font-semibold text-slate-800 dark:text-white leading-none">CONTROL DE ARMAS</span>
                                <span class="text-[0.6rem] text-slate-500 dark:text-gray-400 font-medium tracking-wider uppercase">by Develotech</span>
                            </div>

                        <div class="w-10"></div> <!-- Spacer for centering -->
                    </div>
                </div>
            </header>

            <!-- Page Heading (Desktop) -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-slate-200 dark:border-gray-700 hidden lg:block">
                    <div class="px-6 py-4">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-2 sm:p-4 lg:p-6">
                @yield('content')
                
                <footer class="mt-auto py-4 text-center text-xs text-slate-500 dark:text-gray-500">
                    © {{ date('Y') }} Control de Armas <a href="https://www.develotechgt.com/" target="_blank" class="hover:text-slate-700 dark:hover:text-gray-300 transition-colors">by Develotech</a>
                </footer>
            </main>
        </div>
    </div>

    <!-- Modal Manual de Sistema Global -->
    <div id="modalManual" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalManualBackdrop"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full">
                
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-4 py-3 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-white flex items-center gap-2" id="modal-title">
                        <i class="fas fa-book-reader"></i> Manual de Usuario y Guía del Sistema
                    </h3>
                    <button type="button" class="text-white hover:text-gray-200 focus:outline-none" id="btn-cerrar-manual">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4 bg-white dark:bg-gray-800">
                    <div class="flex flex-col md:flex-row gap-6 h-[600px]">
                        <!-- Sidebar Navigation -->
                        <div class="w-full md:w-1/4 border-r border-gray-200 dark:border-gray-700 pr-4 overflow-y-auto">
                            <nav class="space-y-1" id="manual-nav">
                                <button data-step="1" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                    1. Inventario
                                </button>
                                <button data-step="2" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    2. Ventas
                                </button>
                                <button data-step="3" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    3. Preventas
                                </button>
                                <button data-step="4" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    4. Autorización
                                </button>
                                <button data-step="5" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    5. Facturación
                                </button>
                                <button data-step="6" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    6. Clientes
                                </button>
                                <button data-step="7" class="w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
                                    7. Pagos y Historial
                                </button>
                            </nav>
                        </div>

                        <!-- Content Area -->
                        <div class="w-full md:w-3/4 overflow-y-auto pr-2" id="manual-content">
                            <!-- Step 1: Inventario -->
                            <div data-content="1" class="space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Gestión de Inventario</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        El módulo de inventario permite administrar todo el catálogo de productos, incluyendo armas, municiones y accesorios.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                        <h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2"><i class="fas fa-info-circle mr-2"></i>Conceptos Clave</h5>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-blue-700 dark:text-blue-200">
                                            <li><strong>Stock:</strong> Cantidad física disponible.</li>
                                            <li><strong>Series:</strong> Identificadores únicos para armas.</li>
                                            <li><strong>Lotes:</strong> Agrupación para municiones/accesorios.</li>
                                        </ul>
                                    </div>
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-100 dark:border-yellow-800">
                                        <h5 class="font-semibold text-yellow-800 dark:text-yellow-300 mb-2"><i class="fas fa-exclamation-triangle mr-2"></i>¿Qué pasa si...?</h5>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-yellow-700 dark:text-yellow-200">
                                            <li><strong>Stock llega a 0:</strong> El producto no aparecerá en búsquedas de venta.</li>
                                            <li><strong>Elimino un producto:</strong> Solo se permite si no tiene ventas históricas.</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                    <h5 class="font-semibold text-gray-900 dark:text-white mb-3">Guía Rápida</h5>
                                    <ol class="list-decimal list-inside space-y-2 text-gray-600 dark:text-gray-300 text-sm">
                                        <li>Para agregar, usa el botón <strong>"Agregar Producto"</strong>.</li>
                                        <li>Sube imágenes en formato JPG/PNG (Max 2MB).</li>
                                        <li>Usa el buscador para filtrar por código, nombre o categoría.</li>
                                    </ol>
                                </div>
                            </div>

                            <!-- Step 2: Ventas -->
                            <div data-content="2" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Punto de Venta</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Realiza ventas directas a clientes finales o empresas.
                                    </p>
                                </div>

                                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-100 dark:border-green-800">
                                    <h5 class="font-semibold text-green-800 dark:text-green-300 mb-2"><i class="fas fa-check-circle mr-2"></i>Proceso de Venta</h5>
                                    <ol class="list-decimal list-inside space-y-2 text-sm text-green-700 dark:text-green-200">
                                        <li><strong>Buscar Cliente:</strong> Por NIT o Nombre. Si no existe, créalo ahí mismo.</li>
                                        <li><strong>Dirección de Facturación:</strong> El sistema selecciona automáticamente la dirección según el tipo de cliente (Empresa o Individual), pero puedes editarla manualmente si es necesario.</li>
                                        <li><strong>Agregar Productos:</strong> Busca por nombre o código.</li>
                                        <li><strong>Seleccionar Series/Lotes:</strong> Obligatorio para armas y municiones controladas.</li>
                                        <li><strong>Pago:</strong> Selecciona método (Efectivo, Tarjeta, Cheque, Mixto).</li>
                                    </ol>
                                </div>

                                <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-100 dark:border-purple-800">
                                    <h5 class="font-semibold text-purple-800 dark:text-purple-300 mb-2"><i class="fas fa-wallet mr-2"></i>Pagos Mixtos y Saldo a Favor</h5>
                                    <p class="text-sm text-purple-700 dark:text-purple-200 mb-2">
                                        Si un cliente tiene saldo a favor (por una devolución anterior), puedes usarlo:
                                    </p>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-purple-700 dark:text-purple-200">
                                        <li>Marca la casilla <strong>"Usar Saldo a Favor"</strong> en el modal de pago.</li>
                                        <li>El sistema descontará el saldo y pedirá el resto en otro método de pago.</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Step 3: Preventas -->
                            <div data-content="3" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Preventas y Cotizaciones</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Genera cotizaciones formales sin comprometer el stock inmediatamente.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg border border-indigo-100 dark:border-indigo-800">
                                        <h5 class="font-semibold text-indigo-800 dark:text-indigo-300 mb-2">Diferencias con Venta</h5>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-indigo-700 dark:text-indigo-200">
                                            <li>No descuenta stock al crearse.</li>
                                            <li>No genera factura fiscal (DTE).</li>
                                            <li>Se puede editar libremente.</li>
                                        </ul>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Conversión</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Usa el botón <strong>"Convertir a Venta"</strong> cuando el cliente confirme. Esto moverá los datos al módulo de Ventas para facturación.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Autorización -->
                            <div data-content="4" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Autorización de Ventas</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Paso crítico donde se confirma la salida del producto y se decide la facturación.
                                    </p>
                                </div>

                                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-100 dark:border-amber-800">
                                    <h5 class="font-semibold text-amber-800 dark:text-amber-300 mb-2"><i class="fas fa-shield-alt mr-2"></i>Tipos de Autorización</h5>
                                    <div class="space-y-3">
                                        <div>
                                            <strong class="text-amber-900 dark:text-amber-100">1. Autorizar y Facturar:</strong>
                                            <p class="text-sm text-amber-700 dark:text-amber-200">
                                                Descuenta stock, finaliza la venta y te lleva a emitir la factura FEL inmediatamente.
                                            </p>
                                        </div>
                                        <div>
                                            <strong class="text-amber-900 dark:text-amber-100">2. Autorizar sin Facturar:</strong>
                                            <p class="text-sm text-amber-700 dark:text-amber-200">
                                                Descuenta stock y marca la venta como "Autorizada", pero deja la factura pendiente para después. Útil para entregas rápidas donde la factura se hace luego.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 5: Facturación -->
                            <div data-content="5" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Facturación FEL</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Emisión de Documentos Tributarios Electrónicos certificados por SAT.
                                    </p>
                                </div>

                                <div class="bg-teal-50 dark:bg-teal-900/20 p-4 rounded-lg border border-teal-100 dark:border-teal-800">
                                    <h5 class="font-semibold text-teal-800 dark:text-teal-300 mb-2"><i class="fas fa-file-invoice mr-2"></i>Certificación</h5>
                                    <p class="text-sm text-teal-700 dark:text-teal-200 mb-2">
                                        Al hacer clic en <strong>"Certificar"</strong>, el sistema conecta con el certificador (Digifact/Infile).
                                    </p>
                                    <ul class="list-disc list-inside space-y-1 text-sm text-teal-700 dark:text-teal-200">
                                        <li>Si es exitoso, obtienes el UUID y la Serie.</li>
                                        <li>Si falla, revisa el mensaje de error (generalmente NIT inválido o servicio caído).</li>
                                    </ul>
                                </div>

                                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg border border-red-100 dark:border-red-800">
                                    <h5 class="font-semibold text-red-800 dark:text-red-300 mb-2"><i class="fas fa-ban mr-2"></i>Anulación</h5>
                                    <p class="text-sm text-red-700 dark:text-red-200">
                                        <strong>Anular Factura:</strong> Cancela el DTE en SAT. La venta vuelve a estado "Editable" pero el stock NO se devuelve automáticamente (por seguridad). Debes gestionar la devolución manualmente si aplica.
                                    </p>
                                </div>
                            </div>

                            <!-- Step 6: Clientes -->
                            <div data-content="6" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Gestión de Clientes</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Administra la base de datos de clientes y sus estados de cuenta.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                                        <h5 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Clientes Morosos</h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            Registra deudas manuales o automáticas. Un cliente con deudas pendientes puede ser bloqueado para nuevas ventas a crédito.
                                        </p>
                                    </div>
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                        <h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2">Estado de Cuenta</h5>
                                        <p class="text-sm text-blue-700 dark:text-blue-200">
                                            Vista detallada de todas las transacciones del cliente: compras, pagos, abonos y saldo actual.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 7: Pagos y Historial -->
                            <div data-content="7" class="hidden space-y-6">
                                <div>
                                    <h4 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Pagos y Historial</h4>
                                    <p class="text-gray-600 dark:text-gray-300">
                                        Gestiona tus pagos pendientes, visualiza el historial y descarga facturas.
                                    </p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                                        <h5 class="font-semibold text-blue-800 dark:text-blue-300 mb-2"><i class="fas fa-history mr-2"></i>Historial de Pagos</h5>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-blue-700 dark:text-blue-200">
                                            <li>Consulta todas tus compras y pagos realizados.</li>
                                            <li><strong>Ver Factura:</strong> Si la venta ya fue facturada, aparecerá un botón verde "Ver Factura" para abrir el PDF.</li>
                                            <li><strong>Estado:</strong> Verifica si tus pagos han sido validados por administración.</li>
                                        </ul>
                                    </div>
                                    <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg border border-orange-100 dark:border-orange-800">
                                        <h5 class="font-semibold text-orange-800 dark:text-orange-300 mb-2"><i class="fas fa-upload mr-2"></i>Mis Pagos (Carga de Boletas)</h5>
                                        <p class="text-sm text-orange-700 dark:text-orange-200 mb-2">
                                            Si tienes pagos pendientes (crédito), usa esta sección para subir tus boletas de depósito.
                                        </p>
                                        <ul class="list-disc list-inside space-y-1 text-sm text-orange-700 dark:text-orange-200">
                                            <li>Sube la foto de la boleta o transferencia.</li>
                                            <li>El pago quedará en revisión hasta que un administrador lo valide.</li>
                                            <li><strong>Corrección:</strong> Si te equivocaste y el pago NO ha sido validado, puedes anularlo y subirlo de nuevo. Si ya fue validado, contacta a administración.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" id="btn-entendido-manual">
                        Entendido
                    </button>
                </div>
            </div>
        </div>
    </div>

    @yield('scripts')
</body>

</html>
