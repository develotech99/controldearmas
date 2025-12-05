import DataTable from 'datatables.net-dt';
import 'datatables.net-responsive';
import Swal from 'sweetalert2';

let dataTable = null;
let currentClienteId = null;

document.addEventListener('DOMContentLoaded', function () {
    initTable();

    document.getElementById('btnFiltrar').addEventListener('click', function () {
        loadTable();
    });

    // Search on enter
    document.getElementById('searchCliente').addEventListener('keyup', function (e) {
        if (e.key === 'Enter') {
            loadTable();
        }
    });
});

function initTable() {
    dataTable = new DataTable('#tablaEstadoCuenta', {
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        columns: [
            { data: 'nombre_completo' },
            {
                data: 'saldo_favor',
                render: function (data) {
                    return `<span class="font-bold text-emerald-600">Q${parseFloat(data).toFixed(2)}</span>`;
                },
                className: 'text-right'
            },
            {
                data: 'total_deuda',
                render: function (data) {
                    return `<span class="font-bold text-red-600">Q${parseFloat(data).toFixed(2)}</span>`;
                },
                className: 'text-right'
            },
            {
                data: 'total_pendiente',
                render: function (data) {
                    return `<span class="font-bold text-orange-600">Q${parseFloat(data).toFixed(2)}</span>`;
                },
                className: 'text-right'
            },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                        <button onclick="verDetalle(${row.cliente_id}, '${row.nombre_completo}')" 
                                class="bg-blue-100 hover:bg-blue-200 text-blue-800 font-semibold py-1 px-3 rounded text-sm transition duration-150">
                            <i class="fas fa-eye mr-1"></i> Ver Detalle
                        </button>
                    `;
                },
                className: 'text-center'
            }
        ]
    });

    loadTable();
}

async function loadTable() {
    const search = document.getElementById('searchCliente').value;

    try {
        const response = await fetch(`/api/clientes/estado-cuenta?search=${encodeURIComponent(search)}`);
        const result = await response.json();

        dataTable.clear();
        dataTable.rows.add(result.data);
        dataTable.draw();

        // Calculate Totals
        let totalSaldo = 0;
        let totalDeudas = 0;
        let totalPendiente = 0;

        result.data.forEach(c => {
            totalSaldo += parseFloat(c.saldo_favor || 0);
            totalDeudas += parseFloat(c.total_deuda || 0);
            totalPendiente += parseFloat(c.total_pendiente || 0);
        });

        // Update Cards with Animation
        animateValue("totalSaldoFavor", totalSaldo);
        animateValue("totalDeudas", totalDeudas);
        animateValue("totalPendiente", totalPendiente);

    } catch (error) {
        console.error('Error loading data:', error);
        Swal.fire('Error', 'No se pudieron cargar los datos', 'error');
    }
}

function animateValue(id, value) {
    const element = document.getElementById(id);
    if (!element) return;
    const start = 0;
    const duration = 1000;
    let startTimestamp = null;

    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const current = progress * value;
        element.textContent = `Q${current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// Make global for onclick
window.verDetalle = async function (id, nombre) {
    currentClienteId = id;
    document.getElementById('modalTitle').textContent = `Detalle de Cuenta: ${nombre}`;

    const modal = document.getElementById('modalDetalle');
    modal.classList.remove('hidden');

    // Reset tabs
    switchTab('saldo');

    // Load data
    try {
        const response = await fetch(`/api/clientes/estado-cuenta/${id}`);
        const data = await response.json();

        renderSaldo(data.historial_saldo);
        renderDeudas(data.deudas);
        renderPagos(data.ventas_credito);

    } catch (error) {
        console.error('Error loading details:', error);
        Swal.fire('Error', 'No se pudieron cargar los detalles', 'error');
    }
};

window.closeModal = function () {
    document.getElementById('modalDetalle').classList.add('hidden');
};

window.switchTab = function (tabName) {
    // Hide all contents
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));

    // Reset tab styles
    const tabs = ['saldo', 'deudas', 'pagos'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        btn.classList.remove('border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected
    document.getElementById(`content-${tabName}`).classList.remove('hidden');

    // Style selected tab
    const activeBtn = document.getElementById(`tab-${tabName}`);
    activeBtn.classList.remove('border-transparent', 'text-gray-500');
    activeBtn.classList.add('border-blue-500', 'text-blue-600');
};

function renderSaldo(historial) {
    const tbody = document.getElementById('tbody-saldo');
    tbody.innerHTML = '';

    if (!historial || historial.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No hay historial de saldo.</td></tr>';
        return;
    }

    historial.forEach(h => {
        const color = h.hist_tipo === 'ABONO' ? 'text-emerald-600' : 'text-red-600';
        const row = `
            <tr>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${new Date(h.created_at).toLocaleDateString()}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm ${color} font-medium">${h.hist_tipo}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-right font-bold">Q${parseFloat(h.hist_monto).toFixed(2)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-right">Q${parseFloat(h.hist_saldo_nuevo).toFixed(2)}</td>
                <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${h.hist_referencia || '-'} / ${h.hist_observaciones || ''}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function renderDeudas(deudas) {
    const tbody = document.getElementById('tbody-deudas');
    tbody.innerHTML = '';

    if (!deudas || deudas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-gray-500">No hay deudas registradas.</td></tr>';
        return;
    }

    deudas.forEach(d => {
        const row = `
            <tr>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-300">${new Date(d.fecha_deuda).toLocaleDateString()}</td>
                <td class="px-4 py-2 text-sm text-gray-900 dark:text-gray-300">${d.descripcion || '-'}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-right">Q${parseFloat(d.monto).toFixed(2)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-sm text-right font-bold text-red-600">Q${parseFloat(d.saldo_pendiente).toFixed(2)}</td>
                <td class="px-4 py-2 whitespace-nowrap text-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${d.estado === 'PENDIENTE' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}">
                        ${d.estado}
                    </span>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function renderPagos(ventas) {
    const container = document.getElementById('container-pagos');
    container.innerHTML = '';

    if (!ventas || ventas.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500 py-4">No hay ventas al crédito pendientes.</div>';
        return;
    }

    ventas.forEach(v => {
        const productosHtml = v.productos.map(p => `
            <div class="flex justify-between text-xs text-gray-600 ml-4">
                <span>${p.det_cantidad} x ${p.producto_nombre}</span>
                <span>Q${parseFloat(p.det_precio).toFixed(2)}</span>
            </div>
        `).join('');

        const card = `
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900">Venta #${v.ven_id}</h4>
                        <p class="text-xs text-gray-500">${new Date(v.ven_fecha).toLocaleDateString()}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-orange-600">Pendiente: Q${parseFloat(v.pago_monto_pendiente).toFixed(2)}</p>
                        <p class="text-xs text-gray-400">Total: Q${parseFloat(v.ven_total_vendido).toFixed(2)}</p>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-2 mt-2">
                    <p class="text-xs font-semibold text-gray-700 mb-1">Productos:</p>
                    ${productosHtml}
                </div>
            </div>
        `;
        container.innerHTML += card;
    });
}
