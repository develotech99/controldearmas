import Swal from 'sweetalert2';
// import $ from 'jquery'; // Usar window.$ definido en app.js

document.addEventListener('DOMContentLoaded', function () {
    const tablaDeudas = document.getElementById('tablaDeudas');
    const modalDeuda = document.getElementById('modalDeuda');
    const formDeuda = document.getElementById('formDeuda');
    const modalPago = document.getElementById('modalPago');
    const formPago = document.getElementById('formPago');
    const modalHistorial = document.getElementById('modalHistorial');
    const tablaHistorial = document.getElementById('tablaHistorial');

    // Variables para búsqueda
    const inputNIT = document.getElementById('inputNIT');
    const btnBuscarCliente = document.getElementById('btnBuscarCliente');
    const infoCliente = document.getElementById('infoCliente');
    const nombreClienteSeleccionado = document.getElementById('nombreClienteSeleccionado');
    const btnLimpiarCliente = document.getElementById('btnLimpiarCliente');
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const divEmpresa = document.getElementById('divEmpresa');
    const selectEmpresa = document.getElementById('selectEmpresa');

    // Variables para pago
    const pagoMetodo = document.getElementById('pago_metodo');
    const divReferencia = document.getElementById('divReferencia');
    const lblReferencia = document.getElementById('lblReferencia');

    // Cargar clientes en el filtro
    cargarClientesFiltro();

    // Cargar deudas iniciales
    cargarDeudas();

    document.getElementById('btnBuscar').addEventListener('click', cargarDeudas);

    // --- Modal Nueva Deuda ---
    document.getElementById('btnNuevaDeuda').addEventListener('click', () => {
        formDeuda.reset();
        limpiarSeleccionCliente();
        modalDeuda.classList.remove('hidden');
        modalDeuda.classList.add('flex');
    });

    document.querySelectorAll('.cerrarModal').forEach(btn => {
        btn.addEventListener('click', () => {
            modalDeuda.classList.add('hidden');
            modalDeuda.classList.remove('flex');
        });
    });

    // --- Guardar Deuda ---
    formDeuda.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSubmit = formDeuda.querySelector('button[type="submit"]');
        const originalText = btnSubmit.textContent;
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Guardando...';

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
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = originalText;
        }
    });

    // --- Cargar Deudas ---
    async function cargarDeudas() {
        const btnBuscar = document.getElementById('btnBuscar');
        const originalText = btnBuscar.innerHTML;
        btnBuscar.disabled = true;
        btnBuscar.innerHTML = '<span class="animate-spin mr-2">↻</span> Buscando...';

        const clienteId = $('#filtroCliente').val();
        const estado = document.getElementById('filtroEstado').value;

        let url = `/clientes/deudas/buscar?estado=${estado}`;
        if (clienteId) url += `&cliente_id=${clienteId}`;

        try {
            const response = await fetch(url);
            const data = await response.json();
            renderTabla(data.data);
        } catch (error) {
            console.error(error);
            tablaDeudas.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-red-500">Error al cargar datos</td></tr>';
        } finally {
            btnBuscar.disabled = false;
            btnBuscar.innerHTML = originalText;
        }
    }

    function renderTabla(deudas) {
        tablaDeudas.innerHTML = '';

        if (deudas.length === 0) {
            tablaDeudas.innerHTML = '<tr><td colspan="8" class="px-6 py-4 text-center text-slate-500">No se encontraron registros</td></tr>';
            return;
        }

        deudas.forEach(deuda => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors';

            const estadoClass = deuda.estado === 'PENDIENTE'
                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';

            // Fix names
            const clienteNombre = `${deuda.cliente_nombre || ''} ${deuda.cliente_apellido || ''}`.trim();
            const empresaNombre = deuda.emp_nombre ? `<div class="text-xs text-blue-600 dark:text-blue-400 font-bold">${deuda.emp_nombre}</div>` : '';

            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">${deuda.fecha_deuda}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700 dark:text-slate-300">
                    <div class="font-medium">${clienteNombre}</div>
                    <div class="text-xs text-slate-500">NIT: ${deuda.cliente_nit || 'S/N'}</div>
                    ${empresaNombre}
                </td>
                <td class="px-6 py-4 text-sm text-slate-700 dark:text-slate-300">${deuda.descripcion || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-slate-700 dark:text-slate-300">Q${parseFloat(deuda.monto).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-medium">Q${parseFloat(deuda.monto_pagado).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-bold">Q${parseFloat(deuda.saldo_pendiente).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${estadoClass}">
                        ${deuda.estado}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end gap-2">
                        <button onclick="verHistorial(${deuda.deuda_id})" class="text-blue-600 hover:text-blue-900 dark:hover:text-blue-400" title="Ver Historial">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </button>
                        ${deuda.estado === 'PENDIENTE' ? `
                            <button onclick="abrirModalPago(${deuda.deuda_id}, ${deuda.monto}, ${deuda.saldo_pendiente})" class="text-green-600 hover:text-green-900 dark:hover:text-green-400 font-bold" title="Pagar">
                                Pagar
                            </button>
                        ` : ''}
                    </div>
                </td>
            `;
            tablaDeudas.appendChild(row);
        });
    }

    // --- Lógica de Pago (Abono) ---
    window.abrirModalPago = function (id, total, saldo) {
        document.getElementById('pago_deuda_id').value = id;
        document.getElementById('pago_total').textContent = `Q${parseFloat(total).toFixed(2)}`;
        document.getElementById('pago_saldo').textContent = `Q${parseFloat(saldo).toFixed(2)}`;
        document.getElementById('pago_monto').value = ''; // Reset
        document.getElementById('pago_monto').max = saldo; // Max is remaining balance

        // Reset form
        formPago.reset();
        pagoMetodo.value = 'EFECTIVO';
        divReferencia.classList.add('hidden');

        modalPago.classList.remove('hidden');
        modalPago.classList.add('flex');
    };

    document.querySelectorAll('.cerrarModalPago').forEach(btn => {
        btn.addEventListener('click', () => {
            modalPago.classList.add('hidden');
            modalPago.classList.remove('flex');
        });
    });

    pagoMetodo.addEventListener('change', () => {
        const metodo = pagoMetodo.value;
        if (metodo === 'EFECTIVO') {
            divReferencia.classList.add('hidden');
        } else {
            divReferencia.classList.remove('hidden');
            if (metodo === 'TARJETA') lblReferencia.textContent = 'No. Autorización';
            else if (metodo === 'CHEQUE') lblReferencia.textContent = 'No. Cheque';
            else if (metodo === 'TRANSFERENCIA') lblReferencia.textContent = 'No. Transferencia';
        }
    });

    formPago.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btnSubmit = formPago.querySelector('button[type="submit"]');
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Procesando...';

        const id = document.getElementById('pago_deuda_id').value;
        const monto = document.getElementById('pago_monto').value;
        const metodo = pagoMetodo.value;
        const referencia = document.getElementById('pago_referencia').value;
        const nota = document.getElementById('pago_nota').value;

        try {
            const response = await fetch(`/clientes/deudas/${id}/pagar`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    monto: monto,
                    metodo_pago: metodo,
                    referencia: referencia,
                    nota: nota
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('Éxito', data.message, 'success');
                modalPago.classList.add('hidden');
                modalPago.classList.remove('flex');
                cargarDeudas();
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Error al procesar el pago', 'error');
        } finally {
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Registrar Pago';
        }
    });

    // --- Historial ---
    window.verHistorial = async function (id) {
        modalHistorial.classList.remove('hidden');
        modalHistorial.classList.add('flex');
        tablaHistorial.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center">Cargando...</td></tr>';

        try {
            const response = await fetch(`/clientes/deudas/${id}/historial`); // Need to add this route
            const abonos = await response.json(); // Assuming controller returns array

            tablaHistorial.innerHTML = '';
            if (abonos.length === 0) {
                tablaHistorial.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-slate-500">No hay pagos registrados</td></tr>';
                return;
            }

            abonos.forEach(abono => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300">${new Date(abono.created_at).toLocaleDateString()}</td>
                    <td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300">${abono.metodo_pago}</td>
                    <td class="px-4 py-2 text-sm text-slate-700 dark:text-slate-300">${abono.referencia || '-'}</td>
                    <td class="px-4 py-2 text-right text-sm font-bold text-green-600">Q${parseFloat(abono.monto).toFixed(2)}</td>
                    <td class="px-4 py-2 text-sm text-slate-500">${abono.usuario || 'Sistema'}</td>
                `;
                tablaHistorial.appendChild(row);
            });
        } catch (error) {
            console.error(error);
            tablaHistorial.innerHTML = '<tr><td colspan="5" class="px-4 py-2 text-center text-red-500">Error al cargar historial</td></tr>';
        }
    };

    document.querySelectorAll('.cerrarModalHistorial').forEach(btn => {
        btn.addEventListener('click', () => {
            modalHistorial.classList.add('hidden');
            modalHistorial.classList.remove('flex');
        });
    });

    // --- Búsqueda de Cliente (NIT) ---
    btnBuscarCliente.addEventListener('click', async () => {
        const term = inputNIT.value.trim();
        if (!term) {
            Swal.fire('Atención', 'Ingrese un NIT o nombre para buscar', 'warning');
            return;
        }

        const originalIcon = btnBuscarCliente.innerHTML;
        btnBuscarCliente.disabled = true;
        btnBuscarCliente.innerHTML = '<span class="animate-spin">↻</span>';

        try {
            const response = await fetch(`/clientes/buscar?q=${term}`);
            const clientes = await response.json();

            if (clientes.length === 0) {
                Swal.fire('Info', 'No se encontraron clientes', 'info');
            } else if (clientes.length === 1) {
                seleccionarCliente(clientes[0]);
            } else {
                seleccionarCliente(clientes[0]);
                Swal.fire('Info', `Se encontraron ${clientes.length} coincidencias. Se seleccionó la primera.`, 'info');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Error al buscar cliente', 'error');
        } finally {
            btnBuscarCliente.disabled = false;
            btnBuscarCliente.innerHTML = originalIcon;
        }
    });

    btnLimpiarCliente.addEventListener('click', limpiarSeleccionCliente);

    function seleccionarCliente(cliente) {
        clienteIdHidden.value = cliente.cliente_id;
        nombreClienteSeleccionado.textContent = `${cliente.cliente_nombre1} ${cliente.cliente_apellido1} (NIT: ${cliente.cliente_nit || 'S/N'})`;

        inputNIT.classList.add('hidden');
        btnBuscarCliente.classList.add('hidden');
        infoCliente.classList.remove('hidden');

        // Manejo de empresas
        selectEmpresa.innerHTML = '<option value="">Seleccione una empresa...</option>';
        if (cliente.empresas && cliente.empresas.length > 0) {
            cliente.empresas.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.emp_id;
                option.textContent = `${emp.emp_nombre} (NIT: ${emp.emp_nit || 'S/N'})`;
                selectEmpresa.appendChild(option);
            });
            divEmpresa.classList.remove('hidden');
        } else {
            divEmpresa.classList.add('hidden');
        }
    }

    function limpiarSeleccionCliente() {
        clienteIdHidden.value = '';
        inputNIT.value = '';
        inputNIT.classList.remove('hidden');
        btnBuscarCliente.classList.remove('hidden');
        infoCliente.classList.add('hidden');
        divEmpresa.classList.add('hidden');
        selectEmpresa.innerHTML = '<option value="">Seleccione una empresa...</option>';
    }

    // Cargar solo filtro
    async function cargarClientesFiltro() {
        try {
            const response = await fetch('/clientes/buscar?q=');
            const data = await response.json();
            const filtroCliente = document.getElementById('filtroCliente');
            filtroCliente.innerHTML = '<option value="">Todos</option>';

            data.forEach(cliente => {
                const nombre = `${cliente.cliente_nombre1} ${cliente.cliente_apellido1}`;
                const option = document.createElement('option');
                option.value = cliente.cliente_id;
                option.textContent = nombre;
                filtroCliente.appendChild(option);
            });
        } catch (e) { console.error(e); }
    }
});
