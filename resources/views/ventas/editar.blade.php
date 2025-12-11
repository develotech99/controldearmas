@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Editar Venta #{{ $venta->ven_id }}</h1>
        <a href="{{ route('ventas.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i> Volver
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-500">Cliente</label>
                <div class="text-lg font-semibold text-gray-800">
                    {{ $venta->cliente_nombre1 }} {{ $venta->cliente_apellido1 }}
                </div>
                <div class="text-sm text-gray-600">NIT: {{ $venta->cliente_nit }}</div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Estado</label>
                <span class="px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                    {{ $venta->ven_situacion }}
                </span>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-500">Fecha</label>
                <div class="text-gray-800">{{ $venta->ven_fecha }}</div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b bg-gray-50">
            <h2 class="font-semibold text-gray-700">Detalle de Productos</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Series Asignadas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($detalles as $det)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $det->producto_nombre }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $det->det_cantidad }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($det->series && count($det->series) > 0)
                            <div class="flex flex-col gap-2">
                                @foreach($det->series as $serie)
                                    <div class="flex items-center justify-between bg-gray-50 p-2 rounded border">
                                        <span class="text-sm font-mono">{{ $serie->serie_numero }}</span>
                                        <button onclick="cambiarSerie({{ $det->det_id }}, {{ $serie->serie_id }}, '{{ $serie->serie_numero }}', {{ $det->det_producto_id }})" 
                                            class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                            Cambiar
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <span class="text-xs text-gray-500">Sin series / No aplica</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <!-- Future actions -->
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Cambiar Serie -->
<div id="modalCambiarSerie" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
        <h3 class="text-lg font-bold mb-4">Cambiar Serie</h3>
        <p class="text-sm text-gray-600 mb-4">Serie actual: <span id="lblSerieActual" class="font-mono font-bold"></span></p>
        
        <input type="hidden" id="inputDetalleId">
        <input type="hidden" id="inputSerieIdActual">
        <input type="hidden" id="inputProductoId">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Serie</label>
            <select id="selectNuevaSerie" class="w-full border rounded-lg p-2">
                <option value="">Cargando series disponibles...</option>
            </select>
        </div>

        <div class="flex justify-end gap-3">
            <button onclick="cerrarModalSerie()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancelar</button>
            <button onclick="guardarCambioSerie()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Guardar Cambio</button>
        </div>
    </div>
</div>

<script>
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const ventaId = {{ $venta->ven_id }};

    function cerrarModalSerie() {
        document.getElementById('modalCambiarSerie').classList.add('hidden');
        document.getElementById('modalCambiarSerie').classList.remove('flex');
    }

    async function cambiarSerie(detalleId, serieId, serieNumero, productoId) {
        document.getElementById('inputDetalleId').value = detalleId;
        document.getElementById('inputSerieIdActual').value = serieId;
        document.getElementById('inputProductoId').value = productoId;
        document.getElementById('lblSerieActual').textContent = serieNumero;

        const select = document.getElementById('selectNuevaSerie');
        select.innerHTML = '<option value="">Cargando...</option>';

        const modal = document.getElementById('modalCambiarSerie');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Fetch available series for this product
        try {
            // Updated route based on web.php: /inventario/productos/{id}/series-disponibles
            const res = await fetch(`/inventario/productos/${productoId}/series-disponibles`);
            const data = await res.json();
            
            if (data.success) {
                select.innerHTML = '<option value="">Seleccione nueva serie...</option>';
                data.data.forEach(s => {
                    select.innerHTML += `<option value="${s.serie_id}">${s.serie_numero_serie}</option>`;
                });
            } else {
                 select.innerHTML = '<option value="">Error al cargar series</option>';
                 console.error(data.message);
            }

        } catch (e) {
            console.error(e);
            select.innerHTML = '<option value="">Error de conexión</option>';
        }
    }

    async function guardarCambioSerie() {
        const detalleId = document.getElementById('inputDetalleId').value;
        const oldSerieId = document.getElementById('inputSerieIdActual').value;
        const newSerieId = document.getElementById('selectNuevaSerie').value;
        const productoId = document.getElementById('inputProductoId').value;

        if (!newSerieId) {
            alert('Seleccione una nueva serie');
            return;
        }

        try {
            // Updated route to match VentasController::updateEditableSale
            const res = await fetch('/ventas/update-editable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({
                    ven_id: ventaId,
                    cambios: [{
                        det_id: detalleId,
                        producto_id: productoId,
                        old_serie_id: oldSerieId,
                        new_serie_id: newSerieId
                    }]
                })
            });

            const data = await res.json();

            if (data.success) {
                alert('Serie cambiada exitosamente');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Error desconocido'));
            }
        } catch (e) {
            console.error(e);
            alert('Error al guardar cambio');
        }
    }
</script>
@endsection
