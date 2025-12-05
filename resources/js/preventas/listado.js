import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive';
import Swal from 'sweetalert2';

let dataTable = null;

document.addEventListener('DOMContentLoaded', function () {
    initTable();
});

function initTable() {
    dataTable = new DataTable('#tablaPreventas', {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        ajax: {
            url: '/api/preventas/listado',
            dataSrc: 'data'
        },
        columns: [
            { data: 'prev_id' },
            { data: 'fecha' },
            { data: 'cliente' },
            {
                data: 'total',
                render: function (data) {
                    return `<span class="font-bold text-emerald-600">Q${parseFloat(data).toFixed(2)}</span>`;
                },
                className: 'text-right'
            },
            {
                data: 'estado',
                render: function (data) {
                    let color = 'bg-gray-100 text-gray-800';
                    if (data === 'PENDIENTE') color = 'bg-yellow-100 text-yellow-800';
                    if (data === 'PROCESADO') color = 'bg-green-100 text-green-800';
                    if (data === 'ANULADO') color = 'bg-red-100 text-red-800';
                    return `<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${color}">${data}</span>`;
                },
                className: 'text-center'
            },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                        <div class="flex justify-center space-x-2">
                            <button onclick="verDetalle(${row.prev_id})" 
                                    class="bg-blue-100 hover:bg-blue-200 text-blue-800 p-2 rounded-full transition duration-150" title="Ver Detalle">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="eliminar(${row.prev_id})" 
                                    class="bg-red-100 hover:bg-red-200 text-red-800 p-2 rounded-full transition duration-150" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                },
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']] // Order by ID descending
    });
}

window.verDetalle = async function (id) {
    try {
        const response = await fetch(`/api/preventas/${id}`);
        const preventa = await response.json();

        document.getElementById('detCliente').textContent = preventa.cliente ?
            `${preventa.cliente.cliente_nombre1} ${preventa.cliente.cliente_apellido1}` : 'N/A';

        if (preventa.empresa) {
            document.getElementById('detCliente').textContent += ` - ${preventa.empresa.emp_nombre}`;
        }

        document.getElementById('detFecha').textContent = new Date(preventa.prev_fecha).toLocaleDateString();
        document.getElementById('detTotal').textContent = `Q${parseFloat(preventa.prev_total).toFixed(2)}`;
        document.getElementById('detEstado').textContent = preventa.prev_estado;
        document.getElementById('detObservaciones').textContent = preventa.prev_observaciones || '-';

        const tbody = document.getElementById('tbody-productos');
        tbody.innerHTML = '';

        preventa.detalles.forEach(d => {
            const row = `
                <tr>
                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300">${d.producto ? d.producto.producto_nombre : 'Producto Eliminado'}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-300">${d.det_cantidad}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900 dark:text-gray-300">Q${parseFloat(d.det_precio_unitario).toFixed(2)}</td>
                    <td class="px-4 py-2 text-sm text-right font-bold text-gray-900 dark:text-gray-300">Q${parseFloat(d.det_subtotal).toFixed(2)}</td>
                </tr>
            `;
            tbody.innerHTML += row;
        });

        document.getElementById('modalDetalle').classList.remove('hidden');

    } catch (error) {
        console.error('Error loading details:', error);
        Swal.fire('Error', 'No se pudieron cargar los detalles', 'error');
    }
};

window.eliminar = function (id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer. La preventa será eliminada permanentemente.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`/api/preventas/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire(
                        '¡Eliminado!',
                        'La preventa ha sido eliminada.',
                        'success'
                    );
                    dataTable.ajax.reload();
                } else {
                    throw new Error(data.message);
                }

            } catch (error) {
                console.error('Error deleting:', error);
                Swal.fire('Error', 'No se pudo eliminar la preventa: ' + error.message, 'error');
            }
        }
    });
};

window.closeModal = function () {
    document.getElementById('modalDetalle').classList.add('hidden');
};
