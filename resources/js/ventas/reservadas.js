

async function cargarReservas() {
    const loading = document.getElementById('loading-reservas');
    const empty = document.getElementById('empty-reservas');
    const grid = document.getElementById('grid-reservas');
    const tbody = document.getElementById('tbody-reservas');

    // Get filter values
    const fechaInicio = document.getElementById('fecha_inicio')?.value || '';
    const fechaFin = document.getElementById('fecha_fin')?.value || '';
    const search = document.getElementById('search')?.value || '';

    // Build query string
    const params = new URLSearchParams();
    if (fechaInicio) params.append('fecha_inicio', fechaInicio);
    if (fechaFin) params.append('fecha_fin', fechaFin);
    if (search) params.append('search', search);

    try {
        if (loading) loading.classList.remove('hidden');
        if (empty) empty.classList.add('hidden');
        if (grid) grid.classList.add('hidden');
        if (tbody) tbody.innerHTML = '';

        const response = await fetch(`/reservas/activas?${params.toString()}`);
        const data = await response.json();

        if (loading) loading.classList.add('hidden');

        if (!data.success || !data.reservas || data.reservas.length === 0) {
            if (empty) empty.classList.remove('hidden');
            if (grid) grid.classList.add('hidden');
            return;
        }

        if (empty) empty.classList.add('hidden');
        if (grid) grid.classList.remove('hidden');
        renderReservas(data.reservas);

    } catch (error) {
        console.error('Error cargando reservas:', error);
        if (loading) loading.innerHTML = `<p class="text-red-500">Error al cargar las reservas. Por favor recargue la página.</p>`;
    }
}

function renderReservas(reservas) {
    const tbody = document.getElementById('tbody-reservas');

    tbody.innerHTML = reservas.map(reserva => {
        const itemsSummary = reserva.items.map(item =>
            `<div class="text-sm text-gray-500 dark:text-gray-400">
                ${item.cantidad}x ${item.nombre}
             </div>`
        ).join('');

        return `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${reserva.numero}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">${new Date(reserva.fecha).toLocaleDateString()}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-gray-100">${reserva.cliente}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">${reserva.empresa || ''}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-gray-100">${reserva.vendedor || 'N/A'}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="max-h-20 overflow-y-auto custom-scrollbar">
                        ${itemsSummary}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">Q${parseFloat(reserva.total).toFixed(2)}</div>
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        ${reserva.situacion}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="eliminarReserva(${reserva.id}, '${reserva.numero}')" 
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-bold">
                        Eliminar
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

window.eliminarReserva = async function (id, numero) {
    const result = await Swal.fire({
        title: '¿Eliminar Reserva?',
        html: `
            <p class="mb-2">Estás a punto de eliminar la reserva <strong>${numero}</strong>.</p>
            <p class="text-sm text-gray-500">Esta acción liberará el stock reservado y no se puede deshacer.</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    // Loading
    Swal.fire({
        title: 'Procesando...',
        text: 'Liberando stock y cancelando reserva',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const response = await fetch('/ventas/cancelarReserva', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                reserva_id: id,
                motivo: 'Eliminación manual desde vista de reservas'
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Error al cancelar la reserva');
        }

        await Swal.fire({
            icon: 'success',
            title: 'Reserva Eliminada',
            text: 'La reserva ha sido cancelada y el stock liberado.',
            timer: 2000,
            showConfirmButton: false
        });

        // Recargar lista
        cargarReservas();

    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo cancelar la reserva'
        });
    }
};

// Expose to window for HTML onclick events
window.cargarReservas = cargarReservas;

// Initial load
cargarReservas();
