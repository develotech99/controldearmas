import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive';
import Swal from 'sweetalert2';

console.log('Ventas Reservadas JS loaded - Executing immediately');

const fechaInicioInput = document.getElementById('fecha_inicio');
const fechaFinInput = document.getElementById('fecha_fin');
const inputBuscar = document.getElementById('search');
const btnFiltrar = document.querySelector('button[onclick="cargarReservas()"]');
const loadingDiv = document.getElementById('loading-reservas');
const emptyDiv = document.getElementById('empty-reservas');
const gridDiv = document.getElementById('grid-reservas');
const tbody = document.getElementById('tbody-reservas');

let dataTable = null;

// Define function globally
window.cargarReservas = async function () {
    console.log('Function cargarReservas called');

    if (!loadingDiv) {
        console.error('CRITICAL: Elements not found in DOM');
        return;
    }

    // Show loading, hide others
    loadingDiv.classList.remove('hidden');
    emptyDiv.classList.add('hidden');
    gridDiv.classList.add('hidden');

    const params = new URLSearchParams();
    if (fechaInicioInput && fechaInicioInput.value) params.append('fecha_inicio', fechaInicioInput.value);
    if (fechaFinInput && fechaFinInput.value) params.append('fecha_fin', fechaFinInput.value);
    if (inputBuscar && inputBuscar.value) params.append('busqueda', inputBuscar.value);

    console.log('Fetching from:', `/api/ventas/reservas/listar?${params.toString()}`);

    try {
        const response = await fetch(`/api/ventas/reservas/listar?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const reservas = await response.json();
        console.log('Reservas cargadas:', reservas);

        renderTable(reservas);

    } catch (error) {
        console.error('Error cargando reservas:', error);
        loadingDiv.classList.add('hidden');
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar las reservas. Por favor intente de nuevo.'
        });
    }
};

function renderTable(reservas) {
    loadingDiv.classList.add('hidden');

    if (reservas.length === 0) {
        emptyDiv.classList.remove('hidden');
        gridDiv.classList.add('hidden');
        return;
    }

    emptyDiv.classList.add('hidden');
    gridDiv.classList.remove('hidden');

    // Destroy existing DataTable if it exists
    if (dataTable) {
        dataTable.destroy();
    }

    tbody.innerHTML = reservas.map(reserva => {
        const productosHtml = reserva.detalles.map(d => {
            let html = `<div>${d.det_cantidad} x ${d.producto_nombre}</div>`;
            if (d.series && d.series.length > 0) {
                html += `<div class="text-xs text-gray-500 ml-2">Series: ${d.series.join(', ')}</div>`;
            }
            return html;
        }).join('');

        return `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${reserva.ven_no_reserva || 'N/A'}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        ${new Date(reserva.ven_fecha).toLocaleDateString()}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        ${reserva.cliente_nom_empresa ? reserva.cliente_nom_empresa + ' - ' : ''}
                        ${reserva.cliente_nombre1} ${reserva.cliente_apellido1}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        NIT: ${reserva.cliente_nit || 'CF'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-white">
                        ${reserva.user_primer_nombre} ${reserva.user_primer_apellido}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        ${productosHtml}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                        Q${parseFloat(reserva.ven_total_vendido).toFixed(2)}
                    </div>
                    <div class="text-xs text-gray-500">
                        ${reserva.metpago_descripcion || 'N/A'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="cancelarReserva(${reserva.ven_id})" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 font-bold">
                        Cancelar Reserva
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // Initialize DataTable
    dataTable = new DataTable('#grid-reservas table', {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']] // Order by first column (Reserva/Fecha) descending
    });
}

window.cancelarReserva = async function (id) {
    const result = await Swal.fire({
        title: '¿Cancelar Reserva?',
        text: "Esta acción liberará el stock y las series reservadas. ¿Estás seguro?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No, mantener'
    });

    if (result.isConfirmed) {
        try {
            Swal.fire({
                title: 'Cancelando...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const response = await fetch('/api/ventas/reservas/cancelar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ id: id, motivo: 'Cancelación manual desde módulo Reservas' })
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire(
                    '¡Cancelada!',
                    'La reserva ha sido cancelada correctamente.',
                    'success'
                );
                cargarReservas(); // Refresh table
            } else {
                throw new Error(data.message || 'Error desconocido');
            }

        } catch (error) {
            console.error('Error cancelando reserva:', error);
            Swal.fire(
                'Error',
                'No se pudo cancelar la reserva: ' + error.message,
                'error'
            );
        }
    }
};

// Override the onclick from HTML to use our JS function
if (btnFiltrar) {
    btnFiltrar.onclick = function (e) {
        e.preventDefault();
        cargarReservas();
    };
} else {
    console.warn('Button btnFiltrar not found');
}

// Initial load
console.log('Triggering initial load...');
cargarReservas();
