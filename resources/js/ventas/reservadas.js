document.addEventListener('DOMContentLoaded', function () {
    cargarReservas();
});

async function cargarReservas() {
    const loading = document.getElementById('loading-reservas');
    const empty = document.getElementById('empty-reservas');
    const grid = document.getElementById('grid-reservas');

    try {
        const response = await fetch('/api/reservas/activas');
        const data = await response.json();

        loading.classList.add('hidden');

        if (!data.success || !data.reservas || data.reservas.length === 0) {
            empty.classList.remove('hidden');
            grid.classList.add('hidden');
            return;
        }

        empty.classList.add('hidden');
        grid.classList.remove('hidden');
        renderReservas(data.reservas);

    } catch (error) {
        console.error('Error cargando reservas:', error);
        loading.innerHTML = `<p class="text-red-500">Error al cargar las reservas. Por favor recargue la página.</p>`;
    }
}

function renderReservas(reservas) {
    const grid = document.getElementById('grid-reservas');

    grid.innerHTML = reservas.map(reserva => {
        const itemsHtml = reserva.items.map(item => `
            <div class="flex justify-between items-center text-sm py-1 border-b border-gray-100 last:border-0">
                <span class="text-gray-600 truncate flex-1 pr-2" title="${item.nombre}">
                    ${item.cantidad}x ${item.nombre}
                </span>
                <span class="font-medium text-gray-900">Q${parseFloat(item.precio * item.cantidad).toFixed(2)}</span>
            </div>
        `).join('');

        return `
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow duration-200 overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="p-4 bg-gradient-to-r from-amber-50 to-white border-b border-amber-100">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                ${reserva.situacion}
                            </span>
                            <h3 class="text-lg font-bold text-gray-900 mt-1">${reserva.numero}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Total</p>
                            <p class="text-lg font-bold text-emerald-600">Q${parseFloat(reserva.total).toFixed(2)}</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <p><i class="far fa-calendar-alt mr-1"></i> ${new Date(reserva.fecha).toLocaleDateString()}</p>
                        <p class="truncate" title="${reserva.cliente}"><i class="far fa-user mr-1"></i> ${reserva.cliente}</p>
                    </div>
                </div>

                <!-- Body (Items preview) -->
                <div class="p-4 flex-1 bg-white">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Productos</p>
                    <div class="space-y-1 max-h-40 overflow-y-auto custom-scrollbar">
                        ${itemsHtml}
                    </div>
                </div>

                <!-- Footer (Actions) -->
                <div class="p-4 bg-gray-50 border-t border-gray-100 flex justify-end gap-2">
                    <button onclick="eliminarReserva(${reserva.id}, '${reserva.numero}')" 
                            class="px-3 py-2 bg-white border border-red-200 text-red-600 rounded-lg hover:bg-red-50 hover:border-red-300 transition-colors text-sm font-medium flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i>Eliminar
                    </button>
                </div>
            </div>
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
