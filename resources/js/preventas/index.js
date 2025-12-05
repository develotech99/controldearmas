document.addEventListener('DOMContentLoaded', function () {
    // Variables globales
    let carrito = [];
    let clienteSeleccionado = null;
    let productoSeleccionado = null;

    // Elementos del DOM
    const inputCliente = document.getElementById('cliente_busqueda');
    const inputClienteId = document.getElementById('cliente_id');
    const divResultadosClientes = document.getElementById('resultados-clientes');
    const divClienteSeleccionado = document.getElementById('cliente-seleccionado');

    const inputProducto = document.getElementById('producto_busqueda');
    const inputProductoId = document.getElementById('producto_id');
    const inputProductoPrecio = document.getElementById('producto_precio');
    const divResultadosProductos = document.getElementById('resultados-productos');
    const divProductoSeleccionado = document.getElementById('producto-seleccionado');
    const inputCantidad = document.getElementById('cantidad');
    const btnAgregar = document.getElementById('btn-agregar');

    const tbodyCarrito = document.getElementById('carrito-body');
    const tfootTotal = document.getElementById('carrito-total');
    const divCarritoEmpty = document.getElementById('carrito-empty');

    const formPreventa = document.getElementById('form-preventa');
    const tablaPreventas = document.getElementById('tabla-preventas-body');

    // --- Lógica de Búsqueda de Clientes ---
    inputCliente.addEventListener('input', debounce(async (e) => {
        const query = e.target.value;
        if (query.length < 2) {
            divResultadosClientes.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`/clientes/buscar?q=${query}`);
            const data = await response.json();
            mostrarResultadosClientes(data);
        } catch (error) {
            console.error('Error buscando clientes:', error);
        }
    }, 300));

    function mostrarResultadosClientes(clientes) {
        divResultadosClientes.innerHTML = '';
        if (clientes.length === 0) {
            divResultadosClientes.classList.add('hidden');
            return;
        }

        clientes.forEach(cliente => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b last:border-b-0';
            div.innerHTML = `
                <div class="font-bold text-sm">${cliente.cliente_nombre1} ${cliente.cliente_apellido1}</div>
                <div class="text-xs text-gray-500">NIT: ${cliente.cliente_nit || 'N/A'}</div>
            `;
            div.addEventListener('click', () => seleccionarCliente(cliente));
            divResultadosClientes.appendChild(div);
        });
        divResultadosClientes.classList.remove('hidden');
    }

    function seleccionarCliente(cliente) {
        clienteSeleccionado = cliente;
        inputClienteId.value = cliente.cliente_id;
        inputCliente.value = `${cliente.cliente_nombre1} ${cliente.cliente_apellido1}`;
        divClienteSeleccionado.textContent = `Cliente seleccionado: ${cliente.cliente_nombre1} ${cliente.cliente_apellido1} (NIT: ${cliente.cliente_nit || 'N/A'})`;
        divClienteSeleccionado.classList.remove('hidden');
        divResultadosClientes.classList.add('hidden');
    }

    // --- Lógica de Búsqueda de Productos ---
    inputProducto.addEventListener('input', debounce(async (e) => {
        const query = e.target.value;
        if (query.length < 2) {
            divResultadosProductos.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`/ventas/buscar-productos?q=${query}`);
            const data = await response.json();
            mostrarResultadosProductos(data);
        } catch (error) {
            console.error('Error buscando productos:', error);
        }
    }, 300));

    function mostrarResultadosProductos(productos) {
        divResultadosProductos.innerHTML = '';
        if (productos.length === 0) {
            divResultadosProductos.classList.add('hidden');
            return;
        }

        productos.forEach(producto => {
            const div = document.createElement('div');
            div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b last:border-b-0';
            div.innerHTML = `
                <div class="font-bold text-sm">${producto.producto_nombre}</div>
                <div class="text-xs text-gray-500">Precio: Q${parseFloat(producto.producto_precio_venta).toFixed(2)}</div>
            `;
            div.addEventListener('click', () => seleccionarProducto(producto));
            divResultadosProductos.appendChild(div);
        });
        divResultadosProductos.classList.remove('hidden');
    }

    function seleccionarProducto(producto) {
        productoSeleccionado = producto;
        inputProductoId.value = producto.producto_id;
        inputProductoPrecio.value = producto.producto_precio_venta;
        inputProducto.value = producto.producto_nombre;
        divProductoSeleccionado.textContent = `Producto: ${producto.producto_nombre} - Precio: Q${parseFloat(producto.producto_precio_venta).toFixed(2)}`;
        divProductoSeleccionado.classList.remove('hidden');
        divResultadosProductos.classList.add('hidden');
        inputCantidad.focus();
    }

    // --- Lógica del Carrito ---
    btnAgregar.addEventListener('click', () => {
        if (!productoSeleccionado) {
            Swal.fire('Error', 'Seleccione un producto primero', 'warning');
            return;
        }

        const cantidad = parseInt(inputCantidad.value);
        if (isNaN(cantidad) || cantidad < 1) {
            Swal.fire('Error', 'Ingrese una cantidad válida', 'warning');
            return;
        }

        const precio = parseFloat(productoSeleccionado.producto_precio_venta);

        // Verificar si ya existe en el carrito
        const index = carrito.findIndex(item => item.producto_id === productoSeleccionado.producto_id);

        if (index !== -1) {
            carrito[index].cantidad += cantidad;
            carrito[index].subtotal = carrito[index].cantidad * precio;
        } else {
            carrito.push({
                producto_id: productoSeleccionado.producto_id,
                nombre: productoSeleccionado.producto_nombre,
                cantidad: cantidad,
                precio: precio,
                subtotal: cantidad * precio
            });
        }

        renderCarrito();
        limpiarSeleccionProducto();
    });

    function limpiarSeleccionProducto() {
        productoSeleccionado = null;
        inputProducto.value = '';
        inputProductoId.value = '';
        inputProductoPrecio.value = '';
        inputCantidad.value = '1';
        divProductoSeleccionado.classList.add('hidden');
        divResultadosProductos.classList.add('hidden');
    }

    function renderCarrito() {
        tbodyCarrito.innerHTML = '';
        let total = 0;

        if (carrito.length === 0) {
            divCarritoEmpty.classList.remove('hidden');
            tfootTotal.textContent = 'Q0.00';
            return;
        }

        divCarritoEmpty.classList.add('hidden');

        carrito.forEach((item, index) => {
            total += item.subtotal;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-3 py-2 text-sm text-gray-700">
                    <div class="font-medium">${item.nombre}</div>
                    <div class="text-xs text-gray-500">Q${item.precio.toFixed(2)} c/u</div>
                </td>
                <td class="px-3 py-2 text-center text-sm text-gray-700">${item.cantidad}</td>
                <td class="px-3 py-2 text-right text-sm font-bold text-gray-700">Q${item.subtotal.toFixed(2)}</td>
                <td class="px-3 py-2 text-center">
                    <button type="button" class="text-red-500 hover:text-red-700" onclick="eliminarDelCarrito(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            `;
            tbodyCarrito.appendChild(row);
        });

        tfootTotal.textContent = `Q${total.toFixed(2)}`;
    }

    window.eliminarDelCarrito = function (index) {
        carrito.splice(index, 1);
        renderCarrito();
    };


    // --- Envío del Formulario ---
    formPreventa.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!inputClienteId.value) {
            Swal.fire('Error', 'Seleccione un cliente', 'warning');
            return;
        }

        if (carrito.length === 0) {
            Swal.fire('Error', 'Agregue al menos un producto al carrito', 'warning');
            return;
        }

        const formData = new FormData(formPreventa);
        const data = {
            cliente_id: formData.get('cliente_id'),
            monto_pagado: formData.get('monto_pagado'),
            fecha: formData.get('fecha'),
            observaciones: formData.get('observaciones'),
            productos: carrito
        };

        try {
            const response = await fetch('/preventas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                Swal.fire('Éxito', 'Preventa registrada correctamente', 'success');
                formPreventa.reset();
                carrito = [];
                renderCarrito();
                divClienteSeleccionado.classList.add('hidden');
                clienteSeleccionado = null;
                cargarPreventasPendientes();
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
        }
    });

    // --- Cargar Preventas Pendientes ---
    async function cargarPreventasPendientes() {
        try {
            const response = await fetch('/preventas/pendientes');
            const result = await response.json();

            if (result.success) {
                renderTablaPreventas(result.data);
            }
        } catch (error) {
            console.error('Error cargando preventas:', error);
        }
    }

    function renderTablaPreventas(preventas) {
        tablaPreventas.innerHTML = '';
        preventas.forEach(prev => {
            // Calcular total si no viene (aunque ahora debería venir)
            const total = prev.prev_total || 0;
            const productosStr = prev.detalles ? prev.detalles.map(d => `${d.det_cantidad}x ${d.producto?.producto_nombre}`).join(', ') : 'Sin detalles';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${new Date(prev.prev_fecha).toLocaleDateString()}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${prev.cliente?.cliente_nombre1} ${prev.cliente?.cliente_apellido1}</td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="max-w-xs truncate" title="${productosStr}">${productosStr}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">Q${parseFloat(total).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-bold">Q${parseFloat(prev.prev_monto_pagado).toFixed(2)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="text-blue-600 hover:text-blue-900 mr-2" onclick="facturarPreventa(${prev.prev_id})">Facturar</button>
                </td>
            `;
            tablaPreventas.appendChild(row);
        });
    }

    // Utils
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Init
    cargarPreventasPendientes();
});
