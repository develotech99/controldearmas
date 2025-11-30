@extends('layouts.app')

@section('title', 'Ventas Reservadas')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-bookmark text-amber-500 mr-2"></i>Ventas Reservadas
        </h1>
        <a href="{{ route('ventas.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Volver a Ventas
        </a>
    </div>

    <!-- Loading State -->
    <div id="loading-reservas" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto mb-4"></div>
        <p class="text-gray-500">Cargando reservas activas...</p>
    </div>

    <!-- Empty State -->
    <div id="empty-reservas" class="hidden text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
            <i class="fas fa-inbox text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-1">No hay reservas activas</h3>
        <p class="text-gray-500">Todas las reservas han sido procesadas o canceladas.</p>
    </div>

    <!-- Grid de Reservas -->
    <div id="grid-reservas" class="hidden grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <!-- Las cards se renderizan aquí vía JS -->
    </div>
</div>

@push('scripts')
    @vite(['resources/js/ventas/reservadas.js'])
@endpush

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
@endsection
