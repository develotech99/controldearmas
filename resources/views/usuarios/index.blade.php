@extends('layouts.app')

@section('title', 'Gestión de Usuarios')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header + Filtros --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Gestión de Usuarios</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Listado de clientes registrados.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <input id="inputSearch" type="text" placeholder="Buscar nombre o email…"
                    class="w-64 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-yellow-500 focus:border-yellow-500 px-3 py-2">
                <select id="selectRoleFilter"
                    class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm focus:ring-yellow-500 focus:border-yellow-500 px-3 py-2">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ strtolower($r->nombre) }}">{{ \Illuminate\Support\Str::title($r->nombre) }}
                        </option>
                    @endforeach
                </select>
                <button id="btnClearFilters"
                    class="px-3 py-2 rounded-md border text-sm border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                    Limpiar
                </button>
                <button id="btnOpenCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700 shadow">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Usuario
                </button>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="datatableUsuarios" class="min-w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                Usuario</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                Email</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                Rol</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                Registrado</th>
                            <th
                                class="px-6 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal Crear/Editar --}}
    <div id="modalUsuario" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" data-modal-backdrop></div>
        <div class="relative mx-auto mt-10 w-full max-w-lg">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 id="modalUsuarioTitle" class="text-lg font-semibold text-gray-900 dark:text-gray-100">Crear usuario
                    </h3>
                    <button class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-modal-close>✕</button>
                </div>

                <form id="formUsuario" class="px-5 py-4 space-y-4">
                    <!-- Alerta de Validación de Duplicados -->
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>¡Atención!</strong> Antes de crear un usuario, asegúrate de que no esté registrado.
                                    <br>
                                    El <strong>Correo Electrónico</strong> y el <strong>DPI/DNI</strong> son únicos en el sistema.
                                </p>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="user_id" name="user_id">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">DPI/DNI *</label>
                            <input id="user_dpi_dni" name="user_dpi_dni" type="text" required
                                placeholder="Ingrese sin guiones"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Correo *</label>
                            <input id="email" name="email" type="email" required
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Primer nombre *</label>
                            <input id="user_primer_nombre" name="user_primer_nombre" type="text" required
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Segundo nombre</label>
                            <input id="user_segundo_nombre" name="user_segundo_nombre" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Primer apellido *</label>
                            <input id="user_primer_apellido" name="user_primer_apellido" type="text" required
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Segundo apellido</label>
                            <input id="user_segundo_apellido" name="user_segundo_apellido" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Empresa a la que pertence</label>
                            <input id="user_empresa" name="user_empresa" type="text"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Rol *</label>
                            <select id="user_rol" name="user_rol" required
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500">
                                <option value="">Selecciona…</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->id }}">{{ \Illuminate\Support\Str::title($r->nombre) }} —
                                        {{ $r->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative">
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Contraseña *</label>
                            <input id="password" name="password" type="password"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500 pr-10">
                            <button type="button" onclick="togglePassword('password', this)"
                                class="absolute right-2 top-8 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                👁️
                            </button>
                        </div>

                        <div class="relative mt-3">
                            <label class="block text-sm text-gray-700 dark:text-gray-300">Confirmar contraseña *</label>
                            <input id="password_confirmation" name="password_confirmation" type="password"
                                class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 focus:ring-yellow-500 focus:border-yellow-500 pr-10">
                            <button type="button" onclick="togglePassword('password_confirmation', this)"
                                class="absolute right-2 top-8 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                👁️
                            </button>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button"
                                class="px-4 py-2 rounded-md border text-sm border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                data-modal-close>Cancelar</button>
                            <button id="btnSubmitUsuario" type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700">
                                <svg id="iconSpinnerCreate" class="hidden animate-spin -ml-1 h-4 w-4"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0"></path>
                                </svg>
                                <span id="btnSubmitUsuarioText">Crear</span>
                            </button>
                        </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Ver --}}
    <div id="modalVerUsuario" class="fixed inset-0 z-[60] hidden">
        <div class="absolute inset-0 bg-black/50" data-modal-backdrop></div>
        <div class="relative mx-auto mt-10 w-full max-w-2xl">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Detalles del usuario</h3>
                    <button class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700" data-modal-close>✕</button>
                </div>
                <div class="px-5 py-5 space-y-5">
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 rounded-full bg-yellow-500 flex items-center justify-center">
                            <span id="verIniciales" class="text-xl font-semibold text-white">US</span>
                        </div>
                        <div>
                            <h4 id="verNombre" class="text-xl font-semibold text-gray-900 dark:text-gray-100">Usuario</h4>
                            <p id="verEmail" class="text-gray-600 dark:text-gray-400">correo@dominio.com</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Rol</div>
                            <div id="verRol" class="text-sm font-medium text-gray-900 dark:text-gray-100">—</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Fecha de registro</div>
                            <div id="verFecha" class="text-sm font-medium text-gray-900 dark:text-gray-100">—</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <tr>
        <td align="center">
            <!-- Debug temporal -->
            @php
                $logoPath = public_path('storage/controlarmasdev.png');
                echo 'Ruta: ' . $logoPath . '<br>';
                echo 'Existe: ' . (file_exists($logoPath) ? 'Sí' : 'No') . '<br>';
                if (file_exists($logoPath)) {
                    echo 'Tamaño: ' . filesize($logoPath) . ' bytes<br>';
                }
            @endphp
        </td>
    </tr>

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                btn.textContent = "🙈"; // cambia icono
            } else {
                input.type = "password";
                btn.textContent = "👁️";
            }
        }
    </script>
@endsection

@vite('resources/js/usuarios/index.js')
