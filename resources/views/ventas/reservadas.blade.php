@extends('layouts.app')

@section('title', 'Ventas Reservadas')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-6">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-gray-100 sm:text-3xl sm:truncate">
                Ventas Reservadas
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Gestión de ventas en estado de reserva
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <a href="{{ route('ventas.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="fas fa-arrow-left mr-2"></i>
                Volver a Ventas
            </a>
        </div>
    </div>

    <!-- Content -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        
        <!-- Loading State -->
        <div id="loading-reservas" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-500">Cargando reservas...</p>
        </div>

        <!-- Empty State -->
        <div id="empty-reservas" class="hidden p-12 text-center">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <i class="fas fa-inbox text-4xl"></i>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No hay reservas activas</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Todas las ventas están procesadas o canceladas.</p>
        </div>

        <!-- Table -->
        <div id="grid-reservas" class="hidden overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Reserva
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Vendedor
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Productos
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="tbody-reservas" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <!-- Rows will be inserted here by JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    @vite(['resources/js/ventas/reservadas.js'])
@endpush
@endsection
