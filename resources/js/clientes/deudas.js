import Swal from 'sweetalert2';
import Swal from 'sweetalert2';
// import $ from 'jquery'; // Usar window.$ definido en app.js
import 'select2';
import 'select2';
import 'select2/dist/css/select2.css';

document.addEventListener('DOMContentLoaded', function () {
    const tablaDeudas = document.getElementById('tablaDeudas');
    const modalDeuda = document.getElementById('modalDeuda');
    const formDeuda = document.getElementById('formDeuda');

    // Inicializar Select2 para búsqueda de clientes
    $('#selectCliente').select2({
        placeholder: 'Buscar cliente por nombre o NIT',
        ajax: {
            url: '/clientes/buscar',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term // término de búsqueda
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(cliente => ({
                        id: cliente.cliente_id,
                        text: `${cliente.cliente_nombre} ${cliente.cliente_apellido} - NIT: ${cliente.cliente_nit || 'S/N'}`
                    }))
                };
            },
            cache: true
        },
        dropdownParent: $('#modalDeuda') // Importante para que funcione dentro del modal
    });

    // Cargar clientes para el filtro (opcional, o usar select2 también)
    $('#filtroCliente').select2({
        placeholder: 'Filtrar por cliente',
        allowClear: true,
        ajax: {
            url: '/clientes/buscar',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { q: params.term };
            },
            processResults: function (data) {
                return {
                    results: data.map(cliente => ({
                        id: cliente.cliente_id,
                        text: `${cliente.cliente_nombre} ${cliente.cliente_apellido}`
                    }))
                };
            }
        }
    });

    // Cargar deudas
    cargarDeudas();

    document.getElementById('btnBuscar').addEventListener('click', cargarDeudas);

    // Modal Logic
    document.getElementById('btnNuevaDeuda').addEventListener('click', () => {
        formDeuda.reset();
        $('#selectCliente').val(null).trigger('change');
        modalDeuda.classList.remove('hidden');
        modalDeuda.classList.add('flex');
    });

    document.querySelectorAll('.cerrarModal').forEach(btn => {
        btn.addEventListener('click', () => {
            modalDeuda.classList.add('hidden');
            modalDeuda.classList.remove('flex');
        });
    });

    // Guardar Deuda
    formDeuda.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(formDeuda);

        try {
            const response = await fetch('/clientes/deudas', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Éxito', data.message, 'success');
                modalDeuda.classList.add('hidden');
                modalDeuda.classList.remove('flex');
                cargarDeudas();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Ocurrió un error al guardar', 'error');
        }
    });

    // Función Cargar Deudas
    async function cargarDeudas() {
        const clienteId = $('#filtroCliente').val();
        const estado = document.getElementById('filtroEstado').value;

        let url = `/clientes/deudas/buscar?estado=${estado}`;
        if (clienteId) url += `&cliente_id=${clienteId}`;

        try {
            const response = await fetch(url);
            const data = await response.json();

            renderTabla(data.data); // Asumiendo paginación de Laravel
        } catch (error) {
            console.error(error);
        }
    }

    function renderTabla(deudas) {
        tablaDeudas.innerHTML = '';

        if (deudas.length === 0) {
            tablaDeudas.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-slate-500">No se encontraron registros</td></tr>';
            return;
        }

        deudas.forEach(deuda => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors';

            const estadoClass = deuda.estado === 'PENDIENTE'
                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">${deuda.fecha_deuda}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                    <div class="font-medium">${deuda.cliente_nombre} ${deuda.cliente_apellido}</div>
                    <div class="text-xs text-slate-500">NIT: ${deuda.cliente_nit || 'S/N'}</div>
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300">${deuda.descripcion || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-slate-700 dark:text-slate-300">Q${parseFloat(deuda.monto).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${estadoClass}">
                        ${deuda.estado}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    ${deuda.estado === 'PENDIENTE' ? `
                        <button onclick="pagarDeuda(${deuda.deuda_id}, ${deuda.monto})" class="text-green-600 hover:text-green-900 dark:hover:text-green-400 font-bold">
                            Pagar
                        </button>
                    ` : '<span class="text-slate-400">Pagado</span>'}
                </td>
            `;
            tablaDeudas.appendChild(row);
        });
    }

    // Exponer función pagarDeuda al scope global
    window.pagarDeuda = async function (id, monto) {
        const { value: metodoPago } = await Swal.fire({
            title: 'Registrar Pago',
            text: `¿Confirmar pago de Q${parseFloat(monto).toFixed(2)}?`,
            input: 'select',
            inputOptions: {
                'EFECTIVO': 'Efectivo',
                'TARJETA': 'Tarjeta',
                'TRANSFERENCIA': 'Transferencia',
                'CHEQUE': 'Cheque'
            },
            inputPlaceholder: 'Seleccione método de pago',
            showCancelButton: true,
            confirmButtonText: 'Pagar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return 'Debe seleccionar un método de pago';
                }
            }
        });

        if (metodoPago) {
            try {
                const response = await fetch(`/clientes/deudas/${id}/pagar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ metodo_pago: metodoPago })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('Pagado', data.message, 'success');
                    cargarDeudas();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('Error', 'No se pudo procesar el pago', 'error');
            }
        }
    };
});
