document.addEventListener('DOMContentLoaded', function () {
    // Variables globales
    let carrito = [];
    let clienteSeleccionado = null;

    // Elementos del DOM - Cliente
    const inputCliente = document.getElementById('cliente_busqueda');
    const inputClienteId = document.getElementById('cliente_id');
    const divResultadosClientes = document.getElementById('resultados-clientes');
    const divClienteSeleccionado = document.getElementById('cliente-seleccionado');

    // Elementos del DOM - Productos
    const inputProducto = document.getElementById('producto_busqueda');
    const gridProductos = document.getElementById('grid-productos');
    const contadorResultados = document.getElementById('contador-resultados');

    // Elementos del DOM - Carrito
    const btnAbrirCarrito = document.getElementById('btn-abrir-carrito');
    const btnCerrarCarrito = document.getElementById('btn-cerrar-carrito');
    const modalCarrito = document.getElementById('modal-carrito');
    const panelCarrito = document.getElementById('panel-carrito');
    const overlayCarrito = document.getElementById('overlay-carrito');
    const listaCarrito = document.getElementById('lista-carrito');
    const carritoVacio = document.getElementById('carrito-vacio');
    const contadorCarrito = document.getElementById('contador-carrito');
    const spanTotal = document.getElementById('carrito-total');
    const inputMontoPagado = document.getElementById('monto_pagado');
    const btnProcesar = document.getElementById('btn-procesar');

    // Elementos del DOM - Otros
    const inputFecha = document.getElementById('fecha');
    const inputObservaciones = document.getElementById('observaciones');

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
                <div class="font-bold text-sm text-gray-800">${cliente.cliente_nombre1} ${cliente.cliente_apellido1}</div>
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
        inputCliente.value = ''; // Limpiar input para mostrar selección abajo
        divResultadosClientes.classList.add('hidden');

        divClienteSeleccionado.innerHTML = `
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-bold">${cliente.cliente_nombre1} ${cliente.cliente_apellido1}</div>
                    <div class="text-xs">NIT: ${cliente.cliente_nit || 'N/A'}</div>
                </div>
                <button type="button" class="text-red-500 hover:text-red-700" id="btn-quitar-cliente">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        divClienteSeleccionado.classList.remove('hidden');
        inputCliente.classList.add('hidden');

        document.getElementById('btn-quitar-cliente').addEventListener('click', () => {
            clienteSeleccionado = null;
            inputClienteId.value = '';
            divClienteSeleccionado.classList.add('hidden');
            inputCliente.classList.remove('hidden');
            inputCliente.focus();
        });
    }

    // --- Lógica de Búsqueda de Productos ---
    inputProducto.addEventListener('input', debounce(async (e) => {
        const query = e.target.value;
        if (query.length < 2) {
            // Si está vacío, limpiar grid o mostrar mensaje inicial
            if (query.length === 0) {
                gridProductos.innerHTML = `
                    <div class="col-span-full text-center text-gray-500 py-8">
                        <i class="fas fa-search text-4xl mb-2 opacity-30"></i>
                        <p>Busca productos para agregar a la preventa</p>
                    </div>
                `;
                contadorResultados.textContent = 'Resultados de búsqueda';
            }
            return;
        }

        try {
            const response = await fetch(`/ventas/buscar-productos?q=${query}`);
            const data = await response.json();
            renderProductos(data);
        } catch (error) {
            console.error('Error buscando productos:', error);
        }
    }, 300));

    function renderProductos(productos) {
        gridProductos.innerHTML = '';
        contadorResultados.textContent = `Mostrando ${productos.length} resultados`;

        if (productos.length === 0) {
            gridProductos.innerHTML = `
                <div class="col-span-full text-center text-gray-500 py-8">
                    <p>No se encontraron productos</p>
                </div>
            `;
            return;
        }

        productos.forEach(producto => {
            const card = document.createElement('div');
            card.className = 'bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 flex flex-col justify-between h-full';

            // Imagen (placeholder si no hay)
            const imagenUrl = producto.producto_imagen ? `/storage/${producto.producto_imagen}` : 'https://via.placeholder.com/150?text=No+Image';

            card.innerHTML = `
                <div class="mb-3">
                    <div class="h-32 w-full bg-gray-100 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                        <img src="${imagenUrl}" alt="${producto.producto_nombre}" class="h-full object-contain">
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm mb-1 line-clamp-2" title="${producto.producto_nombre}">${producto.producto_nombre}</h3>
                    <div class="text-xs text-gray-500 mb-2">Stock: ${producto.stock_cantidad_total}</div>
                    <div class="text-lg font-bold text-blue-600">Q${parseFloat(producto.producto_precio_venta).toFixed(2)}</div>
                </div>
                <button class="w-full bg-blue-50 text-blue-600 hover:bg-blue-100 font-semibold py-2 px-4 rounded-lg transition-colors text-sm flex items-center justify-center gap-2 btn-agregar-carrito">
                    <i class="fas fa-cart-plus"></i> Agregar
                </button>
            `;

            card.querySelector('.btn-agregar-carrito').addEventListener('click', () => agregarAlCarrito(producto));
            gridProductos.appendChild(card);
        });
    }

    // --- Lógica del Carrito ---
    function agregarAlCarrito(producto) {
        const index = carrito.findIndex(item => item.producto_id === producto.producto_id);

        if (index !== -1) {
            carrito[index].cantidad++;
            carrito[index].subtotal = carrito[index].cantidad * carrito[index].precio;
        } else {
            carrito.push({
                producto_id: producto.producto_id,
                nombre: producto.producto_nombre,
                cantidad: 1,
                precio: parseFloat(producto.producto_precio_venta),
                subtotal: parseFloat(producto.producto_precio_venta),
                imagen: producto.producto_imagen
            });
        }

        renderCarrito();
        abrirCarrito();
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Producto agregado',
            showConfirmButton: false,
            timer: 1500
        });
    }

    function renderCarrito() {
        listaCarrito.innerHTML = '';
        let total = 0;
        let cantidadTotal = 0;

        if (carrito.length === 0) {
            carritoVacio.classList.remove('hidden');
            contadorCarrito.textContent = '0';
            contadorCarrito.classList.add('hidden');
            spanTotal.textContent = 'Q0.00';
            return;
        }

        carritoVacio.classList.add('hidden');
        contadorCarrito.classList.remove('hidden');

        carrito.forEach((item, index) => {
            total += item.subtotal;
            cantidadTotal += item.cantidad;

            const itemDiv = document.createElement('div');
            itemDiv.className = 'flex gap-3 bg-gray-50 p-3 rounded-lg border border-gray-100';

            itemDiv.innerHTML = `
                <div class="flex-1">
                    <h4 class="font-semibold text-sm text-gray-800 line-clamp-1">${item.nombre}</h4>
                    <div class="text-xs text-gray-500 mb-2">Q${item.precio.toFixed(2)} c/u</div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 flex items-center justify-center text-xs" onclick="cambiarCantidad(${index}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="text-sm font-medium w-6 text-center">${item.cantidad}</span>
                            <button class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 flex items-center justify-center text-xs" onclick="cambiarCantidad(${index}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <div class="font-bold text-gray-800">Q${item.subtotal.toFixed(2)}</div>
                    </div>
                </div>
                <button class="text-red-400 hover:text-red-600 self-start" onclick="eliminarDelCarrito(${index})">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
            listaCarrito.appendChild(itemDiv);
        });

        contadorCarrito.textContent = cantidadTotal;
        spanTotal.textContent = `Q${total.toFixed(2)}`;
    }

    window.cambiarCantidad = function (index, delta) {
        const item = carrito[index];
        const nuevaCantidad = item.cantidad + delta;

        if (nuevaCantidad < 1) {
            eliminarDelCarrito(index);
            return;
        }

        item.cantidad = nuevaCantidad;
        item.subtotal = item.cantidad * item.precio;
        renderCarrito();
    };

    window.eliminarDelCarrito = function (index) {
        carrito.splice(index, 1);
        renderCarrito();
    };

    // --- Modal Carrito UI ---
    function abrirCarrito() {
        modalCarrito.classList.remove('hidden');
        setTimeout(() => {
            panelCarrito.classList.remove('translate-x-full');
        }, 10);
    }

    function cerrarCarrito() {
        panelCarrito.classList.add('translate-x-full');
        setTimeout(() => {
            modalCarrito.classList.add('hidden');
        }, 300);
    }

    btnAbrirCarrito.addEventListener('click', abrirCarrito);
    btnCerrarCarrito.addEventListener('click', cerrarCarrito);
    overlayCarrito.addEventListener('click', cerrarCarrito);

    // --- Procesar Preventa ---
    btnProcesar.addEventListener('click', async () => {
        if (!clienteSeleccionado) {
            Swal.fire('Error', 'Debe seleccionar un cliente', 'warning');
            return;
        }

        if (carrito.length === 0) {
            Swal.fire('Error', 'El carrito está vacío', 'warning');
            return;
        }

        const data = {
            cliente_id: inputClienteId.value,
            monto_pagado: inputMontoPagado.value,
            fecha: inputFecha.value,
            observaciones: inputObservaciones.value,
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
                Swal.fire({
                    title: '¡Éxito!',
                    text: 'Preventa registrada correctamente',
                    icon: 'success',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    location.reload(); // Recargar para limpiar todo
                });
            } else {
                Swal.fire('Error', result.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Ocurrió un error al procesar la solicitud', 'error');
        }
    });

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
});
