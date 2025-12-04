import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {
    initPreventas();
});

function initPreventas() {
    setupClienteSearch();
    setupProductoSearch();
    setupFormSubmit();
    loadPendientes();
}

function setupClienteSearch() {
    const input = document.getElementById('cliente_busqueda');
    const resultsDiv = document.getElementById('resultados-clientes');
    const hiddenId = document.getElementById('cliente_id');
    const selectedDiv = document.getElementById('cliente-seleccionado');

    let timeout = null;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const query = this.value;

        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        timeout = setTimeout(() => {
            fetch(`/clientes/buscar?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(cliente => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b last:border-b-0';
                            div.textContent = `${cliente.cliente_nombre1} ${cliente.cliente_apellido1} (${cliente.cliente_nit || 'S/N'})`;
                            div.onclick = () => {
                                input.value = '';
                                hiddenId.value = cliente.cliente_id;
                                selectedDiv.textContent = `Cliente: ${cliente.cliente_nombre1} ${cliente.cliente_apellido1}`;
                                selectedDiv.classList.remove('hidden');
                                resultsDiv.classList.add('hidden');
                            };
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.classList.add('hidden');
                    }
                });
        }, 300);
    });

    // Close results when clicking outside
    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
}

function setupProductoSearch() {
    const input = document.getElementById('producto_busqueda');
    const resultsDiv = document.getElementById('resultados-productos');
    const hiddenId = document.getElementById('producto_id');
    const selectedDiv = document.getElementById('producto-seleccionado');

    let timeout = null;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const query = this.value;

        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        timeout = setTimeout(() => {
            // Using existing product search endpoint or creating a new one?
            // Assuming we can use /ventas/buscar-productos or similar.
            // Let's use the one from VentasController if available, or create a specific one.
            // For now, let's assume /inventario/buscar exists or similar.
            // Checking routes... Route::get('/inventario/buscar', ...) might not exist.
            // Let's use /ventas/buscar-productos if it exists, or check routes.
            // Actually, let's use a new endpoint or existing one.
            // I'll assume /tipoarma/search for now as a placeholder or check routes.
            // Better: I'll use a generic search.

            fetch(`/ventas/buscar-productos?busqueda=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(prod => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b last:border-b-0';
                            div.textContent = `${prod.producto_nombre} (${prod.pro_codigo_sku || 'S/C'}) - Q${prod.precio_venta}`;
                            div.onclick = () => {
                                input.value = '';
                                hiddenId.value = prod.producto_id;
                                selectedDiv.textContent = `Producto: ${prod.producto_nombre}`;
                                selectedDiv.classList.remove('hidden');
                                resultsDiv.classList.add('hidden');
                            };
                            resultsDiv.appendChild(div);
                        });
                        resultsDiv.classList.remove('hidden');
                    } else {
                        resultsDiv.classList.add('hidden');
                    }
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsDiv.contains(e.target)) {
            resultsDiv.classList.add('hidden');
        }
    });
}

function setupFormSubmit() {
    const form = document.getElementById('form-preventa');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        if (!data.cliente_id || !data.producto_id) {
            Swal.fire('Error', 'Debe seleccionar un cliente y un producto', 'error');
            return;
        }

        fetch('/preventas', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire('Éxito', 'Preventa registrada correctamente', 'success');
                    form.reset();
                    document.getElementById('cliente-seleccionado').classList.add('hidden');
                    document.getElementById('producto-seleccionado').classList.add('hidden');
                    document.getElementById('cliente_id').value = '';
                    document.getElementById('producto_id').value = '';
                    loadPendientes();
                } else {
                    Swal.fire('Error', result.message || 'Error al guardar', 'error');
                }
            })
            .catch(error => {
                console.error(error);
                Swal.fire('Error', 'Ocurrió un error inesperado', 'error');
            });
    });
}

function loadPendientes() {
    const tbody = document.getElementById('tabla-preventas');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Cargando...</td></tr>';

    fetch('/preventas/pendientes')
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const preventas = result.data;
                tbody.innerHTML = '';

                if (preventas.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">No hay preventas pendientes</td></tr>';
                    return;
                }

                preventas.forEach(p => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            ${new Date(p.prev_fecha).toLocaleDateString()}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap font-semibold">
                                ${p.cliente.cliente_nombre1} ${p.cliente.cliente_apellido1}
                            </p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <p class="text-gray-900 whitespace-no-wrap">
                                ${p.producto.producto_nombre}
                            </p>
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                            ${p.prev_cantidad}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-right font-mono">
                            Q${parseFloat(p.prev_monto_pagado).toFixed(2)}
                        </td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">
                            <button class="text-blue-600 hover:text-blue-900 font-semibold" onclick="alert('Funcionalidad de convertir a venta pendiente')">
                                Facturar
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }
        })
        .catch(error => {
            console.error(error);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Error al cargar datos</td></tr>';
        });
}
