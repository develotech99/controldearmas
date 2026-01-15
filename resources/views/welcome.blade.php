<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Inventario - Control de Armas by Develotech</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Check for dark mode preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="h-full font-sans antialiased bg-slate-100 dark:bg-gray-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo y Título -->
            <div class="text-center">
                <div class="mx-auto w-20 h-20 bg-slate-800 dark:bg-gray-700 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 4h.01M9 12h.01M9 16h.01M13 12h.01M13 16h.01M13 8h.01M17 12h.01M17 16h.01"></path>
                    </svg>
                </div>
                
                <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">
                    CONTROL DE ARMAS
                </h1>
                <p class="text-lg text-slate-600 dark:text-gray-400 mb-8">
                    Sistema de Control de Inventario
                </p>
            </div>

            <!-- Acceso al Sistema -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-slate-200 dark:border-gray-700 p-8">
                <div class="text-center mb-6">
                    <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-2">
                        Acceso para Empleados
                    </h2>
                    <p class="text-slate-600 dark:text-gray-400 text-sm">
                        Ingresa con tus credenciales para acceder al sistema
                    </p>
                </div>

                <div class="space-y-4">
                    <a href="{{ route('login') }}" 
                       class="w-full flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg text-white bg-slate-800 dark:bg-gray-600 hover:bg-slate-700 dark:hover:bg-gray-500 transition-colors duration-200">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Iniciar Sesión
                    </a>

       
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="bg-slate-50 dark:bg-gray-800 rounded-xl p-6 border border-slate-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-slate-800 dark:text-white mb-3">Funciones del Sistema:</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                        <span class="text-slate-600 dark:text-gray-400">Control de Stock</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        <span class="text-slate-600 dark:text-gray-400">Registro de Ventas</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                        <span class="text-slate-600 dark:text-gray-400">Reportes Legales</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                        <span class="text-slate-600 dark:text-gray-400">Alertas</span>
                    </div>
                </div>
            </div>

            <!-- Footer Simple -->
            <div class="text-center">
                <p class="text-xs text-slate-500 dark:text-gray-500">
                    © {{ date('Y') }} Control de Armas by Develotech - Sistema Interno
                </p>
            </div>
        </div>
    </div>
</body>
</html>