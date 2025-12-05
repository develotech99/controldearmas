// BUSQUEDA CLIENTES Y REGISTRO NUEVO CLIENTE
const btnBuscarCliente = document.getElementById("btnBuscarCliente");
const tipoClienteSelect = document.getElementById("tipoCliente");
const selectorPremium = document.getElementById("selectorPremium");
const clientePremiumSelect = document.getElementById("clientePremium");
let cargandoReserva = false;
async function clientesParticulares() {
    const nit = document.getElementById("nitClientes").value.trim();
    const dpi = document.getElementById("dpiClientes").value.trim();
    const select = document.getElementById("clienteSelect");

    const params = new URLSearchParams();
    if (nit) params.append("nit", nit);
    if (dpi) params.append("dpi", dpi);

    const res = await fetch(`/api/ventas/buscar?${params.toString()}`);
    const data = await res.json();
    //('venttas desde el buscar',data);

    // Siempre limpiamos el select
    select.innerHTML = "";

    if (data.length > 0) {
        Swal.fire({
            title: "Cliente Encontrado",
            text: `Se ${data.length === 1 ? "encontró" : "encontraron"} ${data.length} cliente(s).`,
            icon: "success",
            confirmButtonText: "Aceptar",
        });

        // SOLO mostramos los resultados, sin "Seleccionar..."
        data.forEach((c) => {
            // ✅ NUEVO: Construir nombre completo del cliente
            const nombreCliente = [
                c.cliente_nombre1,
                c.cliente_nombre2,
                c.cliente_apellido1,
                c.cliente_apellido2,
            ]
                .filter(Boolean)
                .join(" ");

            // ✅ NUEVO: Si es cliente tipo 3 (empresa), mostrar nombre de empresa primero
            let nombreMostrar = '';
            if (c.cliente_tipo == 3 && c.cliente_nom_empresa) {
                // Formato: "EMPRESA XYZ - Nombre Cliente — NIT: 123"
                nombreMostrar = `Empresa: ${c.cliente_nom_empresa} - ${nombreCliente}`;
            } else {
                // Formato normal: "Nombre Cliente — NIT: 123"
                nombreMostrar = nombreCliente;
            }

            // Codificar empresas como JSON seguro para HTML
            const empresasJson = JSON.stringify(c.empresas || []).replace(/"/g, '&quot;');
            const saldoFavor = c.saldo ? parseFloat(c.saldo.saldo_monto) : 0;

            select.innerHTML += `
                <option value="${c.cliente_id}" data-empresas="${empresasJson}" data-saldo="${saldoFavor}">
                    ${nombreMostrar} — NIT: ${c.cliente_nit ?? "SN"}
                </option>`;
        });

        // Si hay un único resultado → se selecciona automáticamente
        if (data.length === 1) {
            select.value = data[0].cliente_id;
            // Disparar evento change manualmente para cargar empresas
            select.dispatchEvent(new Event('change'));
        }
    } else {
        Swal.fire({
            title: "Cliente No Encontrado",
            text: "No se encontró el cliente con los datos proporcionados.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });
        select.innerHTML = '<option value="">Cliente no encontrado</option>';
    }
}

btnBuscarCliente.addEventListener("click", function () {
    clientesParticulares();
});

// Event listener para cambio de cliente -> Cargar empresas
document.getElementById("clienteSelect").addEventListener("change", function () {
    const selectedOption = this.options[this.selectedIndex];
    const divEmpresa = document.getElementById("divEmpresaSelect");
    const selectEmpresa = document.getElementById("empresaSelect");

    // Limpiar select de empresas
    selectEmpresa.innerHTML = '<option value="">Seleccionar empresa...</option>';

    // Resetear Saldo a Favor
    const saldoContainer = document.getElementById('saldoFavorContainer');
    const checkSaldo = document.getElementById('checkSaldoFavor');
    const textoSaldo = document.getElementById('textoSaldoFavor');
    const infoRestante = document.getElementById('infoSaldoRestante');

    if (checkSaldo) checkSaldo.checked = false;
    if (infoRestante) infoRestante.classList.add('hidden');

    if (!selectedOption || !selectedOption.dataset.empresas) {
        divEmpresa.classList.add("hidden");
        if (saldoContainer) saldoContainer.classList.add('hidden');
        return;
    }

    // Manejo de Saldo a Favor
    const saldo = parseFloat(selectedOption.dataset.saldo || 0);
    if (saldo > 0 && saldoContainer) {
        saldoContainer.classList.remove('hidden');
        textoSaldo.textContent = `Q${saldo.toFixed(2)}`;
        checkSaldo.dataset.saldoDisponible = saldo;
    } else if (saldoContainer) {
        saldoContainer.classList.add('hidden');
    }

    try {
        const empresas = JSON.parse(selectedOption.dataset.empresas.replace(/&quot;/g, '"'));

        if (Array.isArray(empresas) && empresas.length > 0) {
            empresas.forEach(emp => {
                selectEmpresa.innerHTML += `<option value="${emp.emp_id}">${emp.emp_nombre} - ${emp.emp_nit || 'S/N'}</option>`;
            });
            divEmpresa.classList.remove("hidden");
        } else {
            divEmpresa.classList.add("hidden");
        }
    } catch (e) {
        console.error("Error al parsear empresas:", e);
        divEmpresa.classList.add("hidden");
    }
});

// Listener para el checkbox de Saldo a Favor
document.getElementById('checkSaldoFavor')?.addEventListener('change', function () {
    const infoRestante = document.getElementById('infoSaldoRestante');
    const montoRestante = document.getElementById('montoRestantePagar');
    const totalElement = document.getElementById('totalModal'); // Total de la venta

    if (this.checked) {
        infoRestante.classList.remove('hidden');
        // Calcular restante
        const totalVenta = parseFloat(totalElement.textContent.replace('Q', '')) || 0;
        const saldoDisponible = parseFloat(this.dataset.saldoDisponible || 0);

        let restante = totalVenta - saldoDisponible;
        if (restante < 0) restante = 0;

        montoRestante.textContent = `Q${restante.toFixed(2)}`;
    } else {
        infoRestante.classList.add('hidden');
    }

    // Si hay lógica de cuotas, actualizarla también
    if (typeof updateCuotasFromTotal === 'function') {
        updateCuotasFromTotal();
    }
});

// Event listener para el tipo de cliente
tipoClienteSelect.addEventListener("change", function () {
    const tipoSeleccionado = this.value;

    if (tipoSeleccionado === "2") {
        selectorPremium.style.display = "block";
        limpiarFormulario();
    } else {
        selectorPremium.style.display = "none";
        clientePremiumSelect.value = "";

        if (tipoSeleccionado === "1") {
            limpiarFormulario();
        }
    }

    // 👇 NUEVO: Mostrar/ocultar campos de empresa
    if (tipoSeleccionado === "3") {
        mostrarInputsEmpresa(); // muestra y habilita inputs empresa
        limpiarFormulario();
    } else {
        ocultarInputsEmpresa(); // oculta y deshabilita inputs empresa
    }

    actualizarEstado(
        `Tipo de cliente seleccionado: ${tipoSeleccionado === "1"
            ? "Normal"
            : tipoSeleccionado === "2"
                ? "Premium"
                : tipoSeleccionado === "3"
                    ? "Empresa"
                    : "Ninguno"
        }`
    );
});


// Event listener para el cliente premium seleccionado
clientePremiumSelect.addEventListener("change", function () {
    const clienteSeleccionado = this.options[this.selectedIndex];

    if (clienteSeleccionado.value !== "") {
        // Llenar los campos del formulario con los datos del cliente premium
        llenarDatosCliente(clienteSeleccionado);
        actualizarEstado(
            `Cliente premium seleccionado: ${clienteSeleccionado.textContent}`
        );
    } else {
        // Limpiar formulario si no hay cliente seleccionado
        limpiarFormulario();
        actualizarEstado("Seleccione un cliente premium");
    }
});

// Función para llenar los datos del cliente
function llenarDatosCliente(option) {
    document.getElementById("idCliente").value = option.dataset.clienteid || "";
    document.getElementById("nc_nombre1").value = option.dataset.nombre1 || "";
    document.getElementById("nc_nombre2").value = option.dataset.nombre2 || "";
    document.getElementById("nc_apellido1").value =
        option.dataset.apellido1 || "";
    document.getElementById("nc_apellido2").value =
        option.dataset.apellido2 || "";
    document.getElementById("nc_dpi").value = option.dataset.dpi || "";
}

// Función para limpiar el formulario
function limpiarFormulario() {
    document.getElementById("idCliente").value = "";
    document.getElementById("nc_nombre1").value = "";
    document.getElementById("nc_nombre2").value = "";
    document.getElementById("nc_apellido1").value = "";
    document.getElementById("nc_apellido2").value = "";
    document.getElementById("nc_dpi").value = "";
    document.getElementById("nc_nit").value = "";
    document.getElementById("nc_telefono").value = "";
    document.getElementById("nc_correo").value = "";
    document.getElementById("nc_direccion").value = "";
}


function ocultarInputsEmpresa() {
    const inputs = [
        "nombreEmpresa",
        "nc_telefono_vendedor",
        "nc_nombre_vendedor",
        "nc_ubicacion",

    ];

    inputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.value = "";              // limpia el valor
            input.classList.add("hidden"); // oculta usando Tailwind
            input.disabled = true;         // desactiva
        }
    });
    const contenedorempresa = document.getElementById("contenedorempresa");
    contenedorempresa.classList.add("hidden");
    const titulopropietario = document.getElementById("titulopropietario");
    titulopropietario.classList.add("hidden");

    // Ocultar y limpiar input de PDF
    document.getElementById("contenedor_pdf_licencia").classList.add("hidden");
    document.getElementById("nc_pdf_licencia").value = "";
}

function mostrarInputsEmpresa() {
    const inputs = [
        "nombreEmpresa",
        "nc_telefono_vendedor",
        "nc_nombre_vendedor",
        "nc_ubicacion"
    ];

    inputs.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
            input.classList.remove("hidden"); // muestra
            input.disabled = false;           // activa
        }
    });
    const contenedorempresa = document.getElementById("contenedorempresa");
    contenedorempresa.classList.remove("hidden");
    const titulopropietario = document.getElementById("titulopropietario");
    titulopropietario.classList.remove("hidden");
    document.getElementById("contenedor_pdf_licencia").classList.remove("hidden");
}






// Función para actualizar el estado
function actualizarEstado(mensaje) {
    document.getElementById("nc_estado").textContent = mensaje;
}

// GUARDAR UN NUEVO CLIENTE: NORMAL O PREMIUM
const btnNuevo = document.getElementById("btnNuevoCliente");
const modal = document.getElementById("modalNuevoCliente");
const overlay = document.getElementById("modalOverlayNC");
const btnCerrar = document.getElementById("modalCerrarNC");
const btnCancel = document.getElementById("modalCancelarNC");
const btnGuardar = document.getElementById("modalGuardarCliente");
const btnLimpiar = document.getElementById("btnLimpiarBusqueda");
const estado = document.getElementById("nc_estado");
const selectClientes = document.getElementById("cliente_particular");

// Funciones para abrir y cerrar el modal
function abrirModal() {
    modal.classList.remove("hidden");
    limpiarFormulario();
    ocultarInputsEmpresa();
    selectorPremium.style.display = "none";
    clientePremiumSelect.value = "";
    tipoClienteSelect.value = "";
}

function cerrarModal() {
    modal.classList.add("hidden");
}

btnNuevo.addEventListener("click", abrirModal);
overlay.addEventListener("click", cerrarModal);
btnCerrar.addEventListener("click", cerrarModal);
btnCancel.addEventListener("click", cerrarModal);

async function guardarCliente() {
    const form = document.getElementById("formNuevoCliente");
    const estado = document.getElementById("nc_estado");

    // 👇 VALIDAR que cliente_tipo esté seleccionado
    const tipoCliente = document.getElementById("tipoCliente").value;
    if (!tipoCliente) {
        Swal.fire({
            title: "¡Atención!",
            text: "Debe seleccionar un tipo de cliente.",
            icon: "warning",
            confirmButtonText: "Aceptar",
        });
        return;
    }

    const formData = new FormData(form);

    // Eliminar clientePremium
    formData.delete('clientePremium');

    // Limpiar cliente_user_id si está vacío
    if (!formData.get('cliente_user_id') || formData.get('cliente_user_id') === '') {
        formData.delete('cliente_user_id');
    }

    ('====== DATOS A ENVIAR ======');
    for (let [key, value] of formData.entries()) {
        (`  ${key}: "${value}"`);
    }
    ('============================');

    estado.textContent = "Guardando cliente...";

    try {
        const response = await fetch("/api/clientes/guardar", {
            method: "POST",
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        // 👇 Obtener el texto de la respuesta primero
        const responseText = await response.text();
        ('Respuesta del servidor:', responseText);

        if (!response.ok) {
            let errorData;
            try {
                errorData = JSON.parse(responseText);
            } catch (e) {
                // Si no es JSON, es un error HTML de Laravel
                console.error('Respuesta HTML de error:', responseText);
                throw new Error(`Error ${response.status}: Revisa la consola de Laravel para más detalles`);
            }

            if (errorData.errors) {
                let mensajeError = '';
                for (let [campo, mensajes] of Object.entries(errorData.errors)) {
                    mensajeError += `${campo}: ${mensajes.join(', ')}\n`;
                }
                throw new Error(mensajeError);
            }

            throw new Error(errorData.message || "Error al guardar el cliente");
        }

        const resultado = JSON.parse(responseText);
        ('✅ Cliente guardado:', resultado);

        await Swal.fire({
            title: "¡Éxito!",
            text: "Cliente guardado correctamente.",
            icon: "success",
            confirmButtonText: "Aceptar",
        });

        estado.textContent = "Cliente guardado correctamente ✅";

        setTimeout(() => {
            cerrarModal();
            limpiarFormulario();
            estado.textContent = "";
            location.reload();
        }, 1000);

    } catch (error) {
        console.error('❌ Error completo:', error);

        Swal.fire({
            title: "¡Error!",
            text: error.message || "No se pudo guardar el cliente.",
            icon: "error",
            confirmButtonText: "Aceptar",
        });

        estado.textContent = "Error al guardar el cliente ❌";
    }
}

btnGuardar.addEventListener("click", guardarCliente);
btnLimpiar.addEventListener("click", clearInputs);

function clearInputs() {
    document.getElementById("dpiClientes").value = "";
    document.getElementById("nitClientes").value = "";
    document.getElementById("clienteSelect").innerHTML =
        '<option value="">Seleccionar...</option>'; // Limpiar el select
}

// FUNCIONES PARA FILTRAR LA BUSQUEDA DE PRODUCTOS
// Función para obtener las subcategorías de la categoría seleccionada
async function obtenerSubcategorias(categoriaId) {
    const subcategoriaSelect = document.getElementById("subcategoria");

    if (!subcategoriaSelect) return;

    try {
        const response = await fetch(
            `/api/ventas/subcategorias/${categoriaId}`
        );
        const data = await response.json();
        (data);

        subcategoriaSelect.innerHTML =
            '<option value="">Seleccionar subcategoría...</option>';
        subcategoriaSelect.disabled = false;
        // Llenar el select con las subcategorías
        data.forEach((subcategoria) => {
            subcategoriaSelect.innerHTML += `<option value="${subcategoria.subcategoria_id}">${subcategoria.subcategoria_nombre}</option>`;
        });
    } catch (error) {
        console.error("Error:", error);
        alert("Error al cargar las subcategorías");
    }
}

// Función para obtener las marcas de una subcategoría seleccionada
async function obtenerMarcas(subcategoriaId) {
    const marcaSelect = document.getElementById("marca");

    if (!marcaSelect) return;

    try {
        const response = await fetch(`/api/ventas/marcas/${subcategoriaId}`);
        const data = await response.json();
        marcaSelect.innerHTML =
            '<option value="">Seleccionar marca...</option>';
        marcaSelect.disabled = false;
        // Llenar el select con las marcas
        data.forEach((marca) => {
            marcaSelect.innerHTML += `<option value="${marca.marca_id}">${marca.marca_descripcion}</option>`;
        });
    } catch (error) {
        console.error("Error:", error);
        alert("Error al cargar las marcas");
    }
}

// Función para obtener los modelos de una marca seleccionada
async function obtenerModelos(marcaId) {
    const modeloSelect = document.getElementById("modelo");

    if (!modeloSelect) return;

    try {
        const response = await fetch(`/api/ventas/modelos/${marcaId}`);
        const data = await response.json();
        modeloSelect.innerHTML =
            '<option value="">Seleccionar modelo...</option>';
        modeloSelect.disabled = false;
        // Llenar el select con los modelos
        data.forEach((modelo) => {
            modeloSelect.innerHTML += `<option value="${modelo.modelo_id}">${modelo.modelo_descripcion}</option>`;
        });
    } catch (error) {
        console.error("Error:", error);
        alert("Error al cargar los modelos");
    }
}

// Función para obtener los calibres de un modelo seleccionado
async function obtenerCalibres(modeloId) {
    const calibreSelect = document.getElementById("calibre");

    if (!calibreSelect) return;

    try {
        const response = await fetch(`/api/ventas/calibres/${modeloId}`);
        const data = await response.json();
        calibreSelect.innerHTML =
            '<option value="">Seleccionar calibre...</option>';
        calibreSelect.disabled = false;
        // Llenar el select con los calibres
        data.forEach((calibre) => {
            calibreSelect.innerHTML += `<option value="${calibre.calibre_id}">${calibre.calibre_nombre}</option>`;
        });
    } catch (error) {
        console.error("Error:", error);
        alert("Error al cargar los calibres");
    }
}

async function buscarProductos() {
    const categoria_id = document.getElementById("categoria").value;
    const subcategoria_id = document.getElementById("subcategoria").value;
    const marca_id = document.getElementById("marca").value;
    const modelo_id = document.getElementById("modelo").value;
    const calibre_id = document.getElementById("calibre").value;

    const params = new URLSearchParams();
    if (categoria_id) params.append("categoria_id", categoria_id);
    if (subcategoria_id) params.append("subcategoria_id", subcategoria_id);
    if (marca_id) params.append("marca_id", marca_id);
    if (modelo_id) params.append("modelo_id", modelo_id);
    if (calibre_id) params.append("calibre_id", calibre_id);

    try {
        const response = await fetch(
            `/ventas/search?${params.toString()}`
        );
        const productos = await response.json();
        (productos);

        mostrarProductos(productos);
    } catch (error) {
        console.error("Error:", error);
    }
}

let productosGlobales = [];
let resultadosBusquedaData = [];
function mostrarProductos(productosData) {
    productosGlobales = productosData;

    const grid = document.getElementById("gridProductos");
    const contador = document.getElementById("contadorResultados");

    // Actualizar contador
    contador.textContent = `Mostrando ${productosData.length} producto${productosData.length === 1 ? "" : "s"
        }`;

    if (productosData.length === 0) {
        grid.innerHTML = `
      <div class="col-span-full text-center py-12 text-gray-500">
        <i class="fas fa-search text-4xl mb-4 opacity-30"></i>
        <p>No se encontraron productos</p>
      </div>`;
        return;
    }

    grid.innerHTML = productosData
        .map((producto) => {
            const stock = Number(producto.stock_cantidad_total ?? 0);
            const necesitaStock = Number(producto.producto_requiere_stock ?? 1) === 1;

            // Manejo de imagen con fallback
            let imagenSrc = producto.foto_url;

            // Si existe la imagen y NO empieza con /storage/, agregarla
            if (imagenSrc && !imagenSrc.startsWith('/storage/') && !imagenSrc.startsWith('http')) {
                imagenSrc = '/storage/' + imagenSrc;
            }
            const iniciales = producto.producto_nombre.substring(0, 2).toUpperCase();

            // Badge de stock por color - SOLO si el producto necesita stock
            let stockBadgeHtml = "";
            if (necesitaStock) {
                if (stock > 4) {
                    stockBadgeHtml = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Stock: ${stock}</span>`;
                } else if (stock > 0) {
                    stockBadgeHtml = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Stock: ${stock}</span>`;
                } else {
                    stockBadgeHtml = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Sin stock</span>`;
                }
            } else {
                // Badge para productos que NO necesitan stock (servicios, documentación, etc.)
                stockBadgeHtml = `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <i class="fas fa-infinity mr-1"></i>Disponible
                </span>`;
            }

            // Determinar si el botón debe estar deshabilitado
            const sinStock = necesitaStock && stock <= 0;
            const botonClase = sinStock
                ? "w-full bg-gray-400 text-white py-2 px-4 rounded-lg cursor-not-allowed text-sm font-medium flex items-center justify-center opacity-60"
                : "w-full bg-gray-900 text-white py-2 px-4 rounded-lg hover:bg-gray-800 transition-colors duration-200 text-sm font-medium flex items-center justify-center";

            return `
        <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
          <div class="relative h-48 bg-gray-100">
                ${imagenSrc ?
                    `<img src="${imagenSrc}" 
                        alt="${producto.producto_nombre}"
                        class="w-full h-full object-cover"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-3xl" style="display:none;">
                        ${iniciales}
                    </div>`
                    :
                    `<div class="w-full h-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-3xl">
                        ${iniciales}
                    </div>`
                }
            <!-- Badge de stock -->
            <div class="absolute top-2 right-2">
              ${stockBadgeHtml}
            </div>
          </div>
          
          <div class="p-4">
            <div class="mb-2">
              <div class="text-lg font-bold text-green-600">Q${parseFloat(
                    producto.precio_venta
                ).toFixed(2)}</div>
              ${producto.precio_venta_empresa
                    ? `
                <div class="text-sm text-blue-600">Precio empresa: Q${parseFloat(
                        producto.precio_venta_empresa
                    ).toFixed(2)}</div>
              `
                    : ""
                }
            </div>
            
            <div class="text-xs text-blue-600 font-medium mb-1">
              ${producto.marca_descripcion}
            </div>
            
            <h3 class="font-semibold text-gray-900 text-sm mb-2 line-clamp-2">
              ${producto.producto_nombre}
            </h3>
            
            <div class="text-xs text-gray-600 mb-3">
              <div>${producto.modelo_descripcion || ""} ${producto.calibre_nombre ? "- " + producto.calibre_nombre : ""
                }</div>
            </div>

            <!-- Checkbox Vender por Caja -->
            <div class="mb-3 flex items-center">
                <input type="checkbox" 
                       id="check-caja-${producto.producto_id}" 
                       class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                       ${sinStock ? 'disabled' : ''}>
                <label for="check-caja-${producto.producto_id}" class="ml-2 text-xs font-medium text-gray-900 ${sinStock ? 'text-gray-400' : ''}">
                    Vender por caja
                </label>
            </div>
            
            <button type="button"
                    data-action="agregar"
                    data-id="${producto.producto_id}"
                    ${sinStock ? 'disabled' : ''}
                    class="${botonClase}">
              <i class="fas fa-shopping-cart mr-2"></i>
              ${sinStock ? 'Sin stock' : 'Agregar'}
            </button>
          </div>
        </div>`;
        })
        .join("");
}

// 🎯 FUNCIÓN AUXILIAR: Renderizar card individual (opcional)
function renderProductoCard(producto) {
    const stock = producto.stock_cantidad_disponible || 0;
    const minimo = producto.producto_stock_minimo || 0;

    let stockClass = 'bg-green-100 text-green-800';
    let stockText = 'En stock';
    let stockIcon = 'fa-check-circle';

    if (stock <= 0) {
        stockClass = 'bg-red-100 text-red-800';
        stockText = 'Agotado';
        stockIcon = 'fa-times-circle';
    } else if (stock <= minimo) {
        stockClass = 'bg-yellow-100 text-yellow-800';
        stockText = 'Stock bajo';
        stockIcon = 'fa-exclamation-triangle';
    }

    // Determinar imagen a mostrar
    const imagenSrc = producto.foto_principal;
    const iniciales = producto.producto_nombre.substring(0, 2).toUpperCase();

    return `
        <div class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 border-b border-gray-200 dark:border-gray-600">
            <!-- Foto o Avatar -->
            <div class="flex-shrink-0">
                ${imagenSrc ?
            `<img src="${imagenSrc}" 
                          alt="${producto.producto_nombre}"
                          class="w-20 h-20 rounded-full object-cover border-2 border-blue-200"
                          onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-sm" style="display:none;">
                        ${iniciales}
                     </div>`
            :
            `<div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-sm">
                        ${iniciales}
                     </div>`
        }
            </div>
            
            <!-- Información del producto -->
            <div class="flex-grow">
                <h4 class="font-medium text-gray-900 dark:text-white">${producto.producto_nombre}</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">${producto.marca_nombre || 'N/A'}</p>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${stockClass} mt-1">
                    <i class="fas ${stockIcon} mr-1"></i>${stockText}
                </span>
            </div>
            
            <!-- Stock -->
            <div class="text-right">
                <p class="text-lg font-bold text-gray-900 dark:text-white">${stock}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">unidades</p>
            </div>
        </div>
    `;
}

const grid = document.getElementById("gridProductos");

// EVENTO PARA AGREGAR AL CARRITO EL PRODUCTO SELECCIONADO
grid.addEventListener("click", (e) => {
    const btn = e.target.closest('[data-action="agregar"]');
    if (!btn) return;
    const id = btn.dataset.id;
    agregarAlCarrito(id);
});

// Event listeners para los cambios en los selects
document.getElementById("categoria").addEventListener("change", function () {
    const categoriaId = this.value;
    limpiarSelectsPosteriores(["subcategoria", "marca", "modelo", "calibre"]);
    if (categoriaId) {
        obtenerSubcategorias(categoriaId);
        buscarProductos();
    }
});

document.getElementById("subcategoria").addEventListener("change", function () {
    const subcategoriaId = this.value;
    limpiarSelectsPosteriores(["marca", "modelo", "calibre"]);
    if (subcategoriaId) {
        obtenerMarcas(subcategoriaId);
        buscarProductos();
    }
});

document.getElementById("marca").addEventListener("change", function () {
    const marcaId = this.value;
    limpiarSelectsPosteriores(["modelo", "calibre"]);
    if (marcaId) {
        obtenerModelos(marcaId);
        buscarProductos();
    }
});

document.getElementById("modelo").addEventListener("change", function () {
    const modeloId = this.value;
    limpiarSelectsPosteriores(["calibre"]);
    if (modeloId) {
        obtenerCalibres(modeloId);
        buscarProductos();
    }
});

document.getElementById("calibre").addEventListener("change", function () {
    obtenerProductos();
    buscarProductos();
});

// Función para limpiar los selects posteriores
function limpiarSelectsPosteriores(selects) {
    selects.forEach((id) => {
        const select = document.getElementById(id);
        select.innerHTML = '<option value="">Seleccionar...</option>';
        select.disabled = true;
    });
}

let timeoutBusqueda;

document
    .getElementById("busquedaProductos")
    .addEventListener("input", function (e) {
        const busqueda = e.target.value.trim();

        clearTimeout(timeoutBusqueda);
        timeoutBusqueda = setTimeout(() => {
            buscarProductosTexto(busqueda);
        }, 200);
    });

async function buscarProductosTexto(busqueda) {
    const resultados = document.getElementById("resultadosBusqueda");

    if (busqueda.length < 2) {
        resultados.classList.add("hidden");
        return;
    }
    try {
        const response = await fetch(
            `/api/ventas/buscar-productos?busqueda=${encodeURIComponent(
                busqueda
            )}`
        );
        const productos = await response.json();

        mostrarResultados(productos);
    } catch (error) {
        console.error("Error:", error);
        resultados.innerHTML =
            '<div class="p-4 text-red-500">Error al buscar productos</div>';
        resultados.classList.remove("hidden");
    }
}

function mostrarResultados(productos) {
    resultadosBusquedaData = productos; // guarda la data reciente
    const resultados = document.getElementById("resultadosBusqueda");

    if (productos.length === 0) {
        resultados.innerHTML =
            '<div class="p-4 text-gray-500">No se encontraron productos</div>';
    } else {
        resultados.innerHTML = productos
            .map(
                (p) => `
      <div class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0"
           data-action="select-product"
           data-id="${String(p.producto_id)}">
        <div class="font-semibold">${p.producto_nombre}</div>
        <div class="text-sm text-gray-600">${p.marca_descripcion} - ${p.modelo_descripcion || ""
                    }</div>
        <div class="text-sm text-green-600 font-medium">Q${parseFloat(
                        p.precio_venta
                    ).toFixed(2)}</div>
      </div>
    `
            )
            .join("");
    }
    resultados.classList.remove("hidden");
}

// listener único
(function initResultadosDelegation() {
    const resultados = document.getElementById("resultadosBusqueda");
    if (!resultados) return;

    resultados.addEventListener("click", (e) => {
        const item = e.target.closest('[data-action="select-product"]');
        if (!item) return;

        const id = item.dataset.id;
        seleccionarProducto2(id);
    });
})();

function seleccionarProducto2(producto_id) {
    const id = String(producto_id);
    let producto = (productosGlobales || []).find(
        (p) => String(p.producto_id) === id
    );
    if (!producto) {
        producto = (resultadosBusquedaData || []).find(
            (p) => String(p.producto_id) === id
        );
    }
    if (!producto) {
        mostrarNotificacion(
            "No se pudo cargar el producto seleccionado",
            "warning"
        );
        return;
    }

    mostrarProductos([producto]); // pinta solo ese
    document.getElementById("resultadosBusqueda")?.classList.add("hidden");
    document
        .getElementById("gridProductos")
        ?.scrollIntoView({ behavior: "smooth", block: "start" });
}

let carritoProductos = [];

// Establecer fecha actual
const ahora = new Date();
const fechaHoraLocal = new Date(ahora.getTime() - (ahora.getTimezoneOffset() * 60000))
    .toISOString()
    .slice(0, 16); // Formato: YYYY-MM-DDTHH:MM

document.getElementById("fechaVenta").value = fechaHoraLocal;

// Eventos para abrir/cerrar carrito
document
    .getElementById("btnAbrirCarrito")
    .addEventListener("click", abrirCarrito);
document
    .getElementById("btnCerrarCarrito")
    .addEventListener("click", cerrarCarrito);
document
    .getElementById("overlayCarrito")
    .addEventListener("click", cerrarCarrito);

// Evento para descuento
document
    .getElementById("descuentoModal")
    .addEventListener("input", calcularTotales);

function abrirCarrito() {

    const modal = document.getElementById("modalCarrito");
    const panel = document.getElementById("panelCarrito");
    const v = validarCliente();
    const clienteId = v.clienteId;
    //('cliente desde abrir carrito',clienteId);
    cargarReservaCliente(clienteId);

    modal.classList.remove("hidden");
    setTimeout(() => {
        panel.style.width = "45%";
    }, 10);
}

function cerrarCarrito() {
    const modal = document.getElementById("modalCarrito");
    const panel = document.getElementById("panelCarrito");

    panel.style.width = "0";
    setTimeout(() => {
        modal.classList.add("hidden");
    }, 300);
}

function agregarAlCarrito(producto_id) {
    const id = String(producto_id);
    const producto = productosGlobales.find(
        (p) => String(p.producto_id) === id
    );

    if (!producto) {
        console.error("Producto no encontrado:", producto_id);
        return;
    }

    // 👇 Lógica "Vender por caja" para Munición
    const categoria = (producto.categoria_nombre || "").toLowerCase();
    // 1. Verificar si se seleccionó "Vender por caja"
    const checkCaja = document.getElementById(`check-caja-${producto_id}`);
    const venderPorCaja = checkCaja && checkCaja.checked;

    if (venderPorCaja) {
        Swal.fire({
            title: '<strong>Venta por Caja</strong>',
            html: `
                <div class="text-left">
                    <label class="flex items-center space-x-3 mb-4 cursor-pointer p-2 hover:bg-gray-50 rounded-lg border border-transparent hover:border-gray-200 transition-all">
                        <input type="checkbox" id="swal-check-caja" class="form-checkbox h-5 w-5 text-blue-600 rounded focus:ring-blue-500">
                        <span class="text-gray-700 font-medium">¿Desea vender por caja?</span>
                    </label>

                    <div id="swal-box-options" class="hidden space-y-4 pl-2 border-l-2 border-blue-100 ml-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Presentación (unidades por caja)</label>
                            <select id="swal-select-presentacion" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                                <option value="5">Caja de 5</option>
                                <option value="10">Caja de 10</option>
                                <option value="20">Caja de 20</option>
                                <option value="25">Caja de 25</option>
                                <option value="50">Caja de 50</option>
                                <option value="100">Caja de 100</option>
                                <option value="200">Caja de 200</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número de cajas</label>
                            <input type="number" id="swal-input-cajas" value="1" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                        </div>

                        <div class="bg-blue-50 p-3 rounded-md border border-blue-100">
                            <p class="text-sm text-blue-800 flex justify-between items-center">
                                <span>Total unidades a descontar:</span>
                                <strong id="swal-total-units" class="text-lg text-blue-700">0</strong>
                            </p>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-cart-plus mr-2"></i>Agregar al Carrito',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2563EB', // blue-600
            didOpen: () => {
                const check = document.getElementById('swal-check-caja');
                const options = document.getElementById('swal-box-options');
                const select = document.getElementById('swal-select-presentacion');
                const input = document.getElementById('swal-input-cajas');
                const totalDisplay = document.getElementById('swal-total-units');

                const updateTotal = () => {
                    const pres = parseInt(select.value) || 0;
                    const cajas = parseInt(input.value) || 0;
                    totalDisplay.textContent = pres * cajas;
                };

                check.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        options.classList.remove('hidden');
                        updateTotal();
                    } else {
                        options.classList.add('hidden');
                    }
                });

                select.addEventListener('change', updateTotal);
                input.addEventListener('input', updateTotal);

                // Inicializar cálculo si ya está visible (no debería, pero por seguridad)
                if (check.checked) updateTotal();
            },
            preConfirm: () => {
                const check = document.getElementById('swal-check-caja');
                if (check && check.checked) {
                    const pres = parseInt(document.getElementById('swal-select-presentacion').value);
                    const cajas = parseInt(document.getElementById('swal-input-cajas').value);
                    const total = pres * cajas;

                    if (isNaN(total) || total <= 0) {
                        Swal.showValidationMessage('La cantidad total debe ser mayor a 0');
                        return false;
                    }

                    // Validar stock
                    const stock = Number(producto.stock_cantidad_total ?? 0);
                    const necesitaStock = Number(producto.producto_requiere_stock ?? 1) === 1;

                    if (necesitaStock && total > stock) {
                        Swal.showValidationMessage(`No hay suficiente stock. Disponible: ${stock}, Solicitado: ${total}`);
                        return false;
                    }

                    return { cantidad: total };
                }
                return { cantidad: 1 };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                agregarProductoAlCarrito(producto, result.value.cantidad);
            }
        });
    } else {
        // Flujo normal para otros productos
        agregarProductoAlCarrito(producto);
    }
}

function agregarProductoAlCarrito(producto, cantidadSolicitada = 1) {
    // ('🔵 Producto entrante:', producto);
    const id = String(producto.producto_id);
    const existente = carritoProductos.find(
        (p) => String(p.producto_id) === id
    );

    // ('productos  para ver todo ',producto)
    // Normaliza datos del producto entrante
    const stockProducto = Number(producto.stock_cantidad_total ?? 0);
    const necesitaStock = Number(producto.producto_requiere_stock ?? 1) === 1;
    const requiereSerie = Number(producto.producto_requiere_serie ?? 0) === 1;
    const seriesDisp = Array.isArray(producto.series_disponibles)
        ? producto.series_disponibles
        : [];

    // 🔹 Lotes del producto (tal cual vienen del backend)
    const lotesDelProducto = Array.isArray(producto.lotes)
        ? producto.lotes
        : [];
    const cantLotes = Number(
        producto.cantidad_lotes ?? lotesDelProducto.length ?? 0
    );
    const lotesSuma = Number(producto.lotes_cantidad_total ?? 0);

    // 🔹 PRECIOS - Extraer correctamente
    const precioVenta = Number(producto.precio_venta ?? 0);
    const precioVentaEmpresa = Number(producto.precio_venta_empresa ?? 0);

    if (existente) {
        // Solo validar stock si el producto lo necesita
        if (necesitaStock) {
            const stockItem = Number(
                existente.stock ??
                existente.stock_cantidad_total ??
                stockProducto ??
                0
            );
            if ((existente.cantidad + cantidadSolicitada) > stockItem) {
                mostrarNotificacion?.(
                    `Stock máximo disponible: ${stockItem}`,
                    "warning"
                );
                return;
            }
        }

        // (Opcional) si requiere serie, evitar superar # series disponibles
        if (requiereSerie) {
            const maxSeries = Array.isArray(existente.series_disponibles)
                ? existente.series_disponibles.length
                : seriesDisp.length;
            if ((existente.cantidad + cantidadSolicitada) > maxSeries) {
                mostrarNotificacion?.(
                    `Solo hay ${maxSeries} serie(s) disponibles para este producto.`,
                    "warning"
                );
                return;
            }
        }

        // 🔸 Si el ítem existente no tenía lotes y el producto sí, los acoplamos
        if (
            (!Array.isArray(existente.lotes) || existente.lotes.length === 0) &&
            lotesDelProducto.length > 0
        ) {
            existente.lotes = lotesDelProducto;
            existente.cantidad_lotes = cantLotes;
            existente.lotes_cantidad_total = lotesSuma;
            existente.lotesSeleccionados = existente.lotesSeleccionados ?? [];
        }

        existente.cantidad += cantidadSolicitada;
    } else {
        // Solo validar stock si el producto lo necesita
        if (necesitaStock && stockProducto < cantidadSolicitada) {
            mostrarNotificacion?.(
                "Sin stock disponible para este producto.",
                "warning"
            );
            return;
        }

        // 🔹 Precio inicial siempre es el precio_venta (normal)
        let precioInicial = precioVenta;
        let precioActivo = 'normal';

        carritoProductos.push({
            // Identidad / visual
            producto_id: producto.producto_id,
            nombre: producto.producto_nombre,
            marca: producto.marca_descripcion,
            imagen: (() => {
                let imgSrc = producto.foto_url;

                // Si no hay imagen, retornar las iniciales para el avatar
                if (!imgSrc) {
                    return null; // El template usará las iniciales del nombre
                }

                // Agregar /storage/ si no lo tiene
                if (!imgSrc.startsWith('/storage/') && !imgSrc.startsWith('http')) {
                    return '/storage/' + imgSrc;
                }

                return imgSrc;
            })(),


            // 🔹 PRECIOS CORREGIDOS - mantener valores originales
            precio_venta: precioVenta,                    // Precio normal
            precio_venta_empresa: precioVentaEmpresa,     // Precio 
            precio: precioInicial,                         // Precio que se está usando
            precio_activo: precioActivo,                   // 'normal' o 'empresa'

            // Cantidad y stock
            cantidad: cantidadSolicitada,
            stock: stockProducto,
            stock_cantidad_total: stockProducto,
            producto_requiere_stock: producto.producto_requiere_stock ?? 1,

            // Series
            producto_requiere_serie: producto.producto_requiere_serie ?? 0,
            series_disponibles: seriesDisp,
            seriesSeleccionadas: [],

            // 🔹 LOTES
            lotes: lotesDelProducto,
            cantidad_lotes: cantLotes,
            lotes_cantidad_total: lotesSuma,
            lotesSeleccionados: [],
        });
    }

    actualizarVistaCarrito();
    actualizarContadorCarrito();
    mostrarNotificacion?.(
        `${producto.producto_nombre} agregado al carrito`,
        "success"
    );
}

function cambiarCantidad(producto_id, cambio) {
    const id = String(producto_id);
    const p = carritoProductos.find((x) => String(x.producto_id) === id);
    if (!p) return;

    const stock = Number(p.stock_cantidad_total ?? 0);
    const necesitaStock = Number(p.producto_requiere_stock ?? 1) === 1;
    const nueva = (p.cantidad || 0) + cambio;

    if (nueva <= 0) return eliminarProducto(id);

    // Solo validar stock si el producto lo necesita
    if (necesitaStock && nueva > stock) {
        return mostrarNotificacion?.(
            `Stock máximo disponible: ${stock}`,
            "warning"
        );
    }

    p.cantidad = nueva;

    // Si requiere serie y ahora la cantidad supera el número de series seleccionadas,
    // invitamos a completar las series (no bloquea, pero guía).
    const requiereSerie = (p.requiere_serie ?? p.producto_requiere_serie) == 1;
    if (requiereSerie) {
        p.seriesSeleccionadas = Array.isArray(p.seriesSeleccionadas)
            ? p.seriesSeleccionadas
            : [];
        if (p.seriesSeleccionadas.length > p.cantidad) {
            // Si bajaron la cantidad, recortamos
            p.seriesSeleccionadas = p.seriesSeleccionadas.slice(0, p.cantidad);
        } else if (p.seriesSeleccionadas.length < p.cantidad) {
            // Sugerimos completar
            mostrarNotificacion?.(
                `Selecciona ${p.cantidad - p.seriesSeleccionadas.length
                } serie(s) para completar`,
                "info"
            );
        }
    }

    actualizarVistaCarrito();
}

function eliminarProducto(producto_id) {
    const id = String(producto_id);
    const p = carritoProductos.find((x) => String(x.producto_id) === id);

    carritoProductos = carritoProductos.filter(
        (x) => String(x.producto_id) !== id
    );

    if (p) mostrarNotificacion(`${p.nombre} eliminado del carrito`, "info");

    actualizarVistaCarrito();
    actualizarContadorCarrito?.();
}

function routeReservarURL() {
    return '/reservas/procesar';
}

async function procesarReserva() {
    const v = validarCliente();
    const btn = document.getElementById('btnReservar');
    if (!v.valido) {
        Swal?.fire?.('Falta cliente', v.error, 'warning');
        return; // corta aquí
    }
    const clienteId = v.clienteId;
    ('clienteId detectado ->', clienteId);

    try {
        btn && (btn.disabled = true);

        // === 1) Lee datos base de la UI (ajusta selectores si difieren) ===
        // const clienteId = Number(document.getElementById('cliente_id')?.value || 0);
        const fechaReserva = document.getElementById('fecha_reserva')?.value || new Date().toISOString().slice(0, 10);
        const diasVigencia = Number(document.getElementById('dias_vigencia')?.value || 7);
        const observaciones = (document.getElementById('observaciones')?.value || '').trim();

        // Obtener empresa seleccionada si existe
        const empresaSelect = document.getElementById("empresaSelect");
        const empresaId = (empresaSelect && !empresaSelect.closest('.hidden')) ? empresaSelect.value : null;


        if (!Array.isArray(carritoProductos) || carritoProductos.length === 0) {
            throw new Error('El carrito está vacío.');
        }

        // Si tienes una función que calcula totales, úsala. Si no, calculo mínimo:
        const totales = (typeof getTotales === 'function') ? getTotales() : null;
        const subtotal = Number(totales?.subtotal ?? carritoProductos.reduce((a, p) => a + (Number(p.precio || 0) * Number(p.cantidad || 0)), 0));
        const descuento_porcentaje = Number(totales?.descuento_porcentaje ?? 0);
        const descuento_monto = Number(totales?.descuento_monto ?? 0);
        const total = Number(totales?.total ?? (subtotal - descuento_monto));

        // === 2) Construye productos como exige tu validador ===
        const productos = carritoProductos.map(p => {
            const cantidad = Number(p.cantidad || 0);
            const precioUnitario = Number(p.precio || 0);

            const requiereSerie = Number(p.producto_requiere_serie ?? 0) === 1 ? 1 : 0;
            const seriesSeleccionadas = Array.isArray(p.seriesSeleccionadas) ? p.seriesSeleccionadas : [];

            const tieneLotes = Array.isArray(p.lotes) && p.lotes.length > 0;
            const lotesSeleccionados = Array.isArray(p.lotesSeleccionados)
                ? p.lotesSeleccionados.map(it => ({
                    lote_id: Number(it.lote_id),
                    cantidad: Number(it.cantidad || 0),
                }))
                : [];

            return {
                producto_id: Number(p.producto_id),
                cantidad: cantidad,
                precio_unitario: precioUnitario,
                subtotal_producto: Number((precioUnitario * cantidad).toFixed(2)),
                requiere_serie: requiereSerie,
                producto_requiere_stock: Number(p.producto_requiere_stock ?? 1) === 1 ? 1 : 0,
                series_seleccionadas: requiereSerie ? seriesSeleccionadas : [],
                tiene_lotes: !!tieneLotes,
                lotes_seleccionados: tieneLotes ? lotesSeleccionados : []
            };
        });

        // === 3) Payload final ===
        const payload = {
            cliente_id: clienteId,
            fecha_reserva: fechaReserva,
            subtotal,
            descuento_porcentaje,
            descuento_monto,
            total,
            productos,
            observaciones,
            dias_vigencia: diasVigencia,
            dias_vigencia: diasVigencia,
            empresa_id: empresaId // <-- Corregido: ID de empresa
        };

        // === 4) POST a Laravel ===
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const resp = await fetch(routeReservarURL(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        const data = await resp.json();

        if (!resp.ok) {
            // 419/422/etc
            const msg = data?.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data?.message || 'No se pudo completar la reserva.');
            throw new Error(msg);
        }

        Swal.fire({
            icon: 'success',
            title: 'Reserva creada',
            html: `No. <b>${data.numero_reserva}</b> (vigencia ${data.vigencia_dias} días)`,
            confirmButtonText: 'Aceptar',
            confirmButtonColor: '#10b981'
        }).then(() => {
            // 🔄 recarga completa de la página
            window.location.reload();
        });


    } catch (e) {
        console.error(e);
        Swal?.fire?.('Error', String(e.message || e), 'error');
    } finally {
        btn && (btn.disabled = false);
    }
}


// ============================================
// VARIABLES GLOBALES PARA RESERVAS
// ============================================
let reservaActualCliente = null;

// ============================================
// FUNCIÓN PARA CARGAR RESERVA DEL CLIENTE
// ============================================
// 🔁 Cargar todas las reservas del cliente y renderizarlas
async function cargarReservaCliente(clienteId) {
    if (!clienteId) {
        console.warn('No hay cliente seleccionado');
        limpiarVistaReserva?.();
        return;
    }

    try {
        const resp = await fetch(`/api/reservas/cliente/${clienteId}`, {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });

        const text = await resp.text();
        if (!resp.ok) {
            console.error('Respuesta no OK:', text);
            limpiarVistaReserva?.();
            Swal?.fire?.('Error', `No se pudo cargar reservas (HTTP ${resp.status}).`, 'error');
            return;
        }

        let data;
        try { data = JSON.parse(text); }
        catch {
            console.error('Respuesta no JSON:', text);
            limpiarVistaReserva?.();
            Swal?.fire?.('Error', 'La respuesta del servidor no es JSON.', 'error');
            return;
        }

        // ('datos de la(s) reserva(s) por cliente', data);

        // ✅ La API nueva devuelve: { success, count, reservas: [...] }
        if (data.success && Array.isArray(data.reservas) && data.reservas.length > 0) {
            mostrarReservasCliente(data.reservas);
        } else {
            limpiarVistaReserva?.();
        }
    } catch (error) {
        console.error('Error al cargar reservas:', error);
        limpiarVistaReserva?.();
        Swal?.fire?.('Error', 'Error de red consultando reservas.', 'error');
    }
}


// ============================================
// LIMPIAR VISTA DE RESERVA (sin productos)
// ============================================
function limpiarVistaReserva() {
    const containerReserva = document.getElementById('productosCarritoReseva');
    const carritoVacioReserva = document.getElementById('carritoVacioReserva');

    if (containerReserva) {
        containerReserva.innerHTML = '';
    }

    if (carritoVacioReserva) {
        carritoVacioReserva.classList.remove('hidden');
    }

    reservaActualCliente = null;
}

// ============================================
// MOSTRAR RESERVA EXISTENTE EN LA UI
// ============================================
// Renderiza una lista de reservas con su botón "Agregar al Carrito"
function mostrarReservasCliente(reservas) {
    const container = document.getElementById('productosCarritoReserva');
    const vacio = document.getElementById('carritoVacioReserva');
    if (!container) return;

    vacio?.classList.add('hidden');

    // Guardamos una copia para usar por índice (evita meter JSON gigante en data-*)
    window._reservasClienteCache = reservas;

    const html = reservas.map((res, idx) => {
        // res.items es el arreglo de productos de ESA reserva
        const items = Array.isArray(res.items) ? res.items : [];

        const card = `
  <!-- Banner de una reserva -->
  <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border-l-4 border-amber-500 rounded-lg p-4 mb-4 shadow-sm">
    <div class="flex items-start gap-3">
      <div class="flex-shrink-0">
        <i class="fas fa-exclamation-circle text-amber-600 text-2xl"></i>
      </div>
      <div class="flex-1 min-w-0">
        <h4 class="text-sm font-bold text-amber-900 mb-2">
          ${res.numero} — ${res.situacion ?? 'RESERVADA'}
        </h4>
        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
          <div><span class="text-gray-600">Fecha:</span> <span class="ml-1 font-semibold text-gray-800">${res.fecha}</span></div>
          <div><span class="text-gray-600">Total:</span> <span class="ml-1 font-bold text-emerald-600">Q${Number(res.total || 0).toFixed(2)}</span></div>
          <div class="col-span-2"><span class="text-gray-600">Vendedor:</span> <span class="ml-1 font-semibold text-gray-800">${res.vendedor || ''}</span></div>
        </div>
        <div class="flex gap-2 items-center">
          <button type="button"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-all duration-200 flex items-center gap-1.5 btnCargarReserva"
                  data-index="${idx}">
            <i class="fas fa-shopping-cart"></i>
            Agregar al Carrito
          </button>

          <!-- Botón pequeñito para borrar reserva -->
          <button type="button"
                  class="p-1.5 rounded-full border border-red-200 bg-red-50 hover:bg-red-100 text-red-600 text-xs flex items-center justify-center btnBorrarReserva"
                  data-index="${idx}"
                  title="Eliminar esta reserva">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Lista de productos de la reserva -->
  <div class="space-y-2 mb-6">
    ${items.map((item) => {
            const necesitaStock = Number(item.producto_requiere_stock ?? 1) === 1;
            const requiereSerie = Number(item.producto_requiere_serie ?? 0) === 1;
            const seriesSel = Array.isArray(item.seriesSeleccionadas) ? item.seriesSeleccionadas : [];
            const tieneSuficientesSeries = !requiereSerie || seriesSel.length === Number(item.cantidad || 0);

            const badgeSerie = (necesitaStock && requiereSerie)
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium ${tieneSuficientesSeries ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700"}">
             <i class="fas fa-barcode mr-1"></i>
             Serie: ${seriesSel.length}/${item.cantidad}
           </span>`
                : "";

            const hasLotes = Array.isArray(item.lotes) && item.lotes.length > 0;
            const asignadoLotes = Array.isArray(item.lotesSeleccionados)
                ? item.lotesSeleccionados.reduce((acc, it) => acc + Number(it.cantidad || 0), 0)
                : 0;

            const badgeLotes = (necesitaStock && hasLotes)
                ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium ${asignadoLotes === Number(item.cantidad || 0) ? "bg-emerald-100 text-emerald-700" : "bg-amber-100 text-amber-700"}">
             <i class="fas fa-layer-group mr-1"></i>
             Lotes: ${asignadoLotes}/${item.cantidad}
           </span>`
                : "";

            return `
        <div class="bg-white border border-gray-200 rounded-lg p-3 hover:shadow-md transition-shadow duration-200">
          <div class="flex items-start gap-3">
            <div class="relative flex-shrink-0" style="width: 64px; height: 64px;">
              ${item.imagen
                    ? `<img src="${item.imagen}" alt="${item.nombre}" class="w-full h-full object-cover rounded-lg border border-gray-200" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                     <div style="display:none; width:64px; height:64px;" class="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold rounded-lg text-lg">
                       ${String(item.nombre || 'PR').substring(0, 2).toUpperCase()}
                     </div>`
                    : `<div style="width:64px; height:64px;" class="bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold rounded-lg text-lg">
                       ${String(item.nombre || 'PR').substring(0, 2).toUpperCase()}
                     </div>`
                }
              <div class="absolute bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-lg border-2 border-white" style="top:-8px; right:-8px;">
                ${item.cantidad}x
              </div>
            </div>

            <div class="flex-1 min-w-0">
              <h5 class="font-semibold text-sm text-gray-900 truncate mb-1">${item.nombre}</h5>
              ${item.marca ? `<p class="text-xs text-gray-500 flex items-center gap-1 mb-2"><i class="fas fa-industry text-[9px]"></i> ${item.marca}</p>` : ''}

              <div class="flex flex-wrap items-center gap-1.5 mb-2">
                ${necesitaStock ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                   <i class="fas fa-box mr-1"></i> Stock: ${item.stock_cantidad_total || 0}
                 </span>` : ''}
                ${badgeSerie}
                ${badgeLotes}
              </div>

              <div class="flex items-center justify-between">
                <div class="text-xs text-gray-600">
                  <span>Precio unitario:</span>
                  <span class="ml-1 font-semibold text-gray-900">Q${Number(item.precio || 0).toFixed(2)}</span>
                </div>
                <div class="text-right">
                  <div class="text-xs text-gray-500">Total</div>
                  <div class="font-bold text-emerald-600">Q${(Number(item.precio || 0) * Number(item.cantidad || 0)).toFixed(2)}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
        }).join('')}
  </div>
`;

        return card;
    }).join('');

    container.innerHTML = html;

    // ========================================
    // EVENT LISTENERS DESPUÉS DE RENDERIZAR
    // ========================================

    // ✅ Botones "Agregar al Carrito"
    container.querySelectorAll('.btnCargarReserva').forEach(btn => {
        btn.addEventListener('click', async function () {
            const idx = Number(this.dataset.index);
            const reserva = window._reservasClienteCache?.[idx];
            if (!reserva) return;

            try {
                await cargarReservaEnCarrito(reserva.items);
                return;
            } catch (e) {
                console.error(e);
                Swal?.fire?.('Error', 'No se pudo cargar la reserva.', 'error');
            }
        });
    });

    // ✅ Botones "Eliminar Reserva"
    container.querySelectorAll('.btnBorrarReserva').forEach(btn => {
        btn.addEventListener('click', async function () {
            const idx = Number(this.dataset.index);
            const reserva = window._reservasClienteCache?.[idx];

            if (!reserva) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontró la información de la reserva'
                });
                return;
            }

            // Confirmación
            const result = await Swal.fire({
                icon: 'warning',
                title: '¿Eliminar reserva?',
                html: `
          <div class="text-left px-4">
            <p class="mb-3 text-gray-700">Esta acción cancelará la venta y revertirá todo el stock (series, lotes, inventario).</p>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 space-y-1">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Reserva:</span>
                <span class="font-semibold text-gray-900">${reserva.numero}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total:</span>
                <span class="font-semibold text-emerald-600">Q${Number(reserva.total || 0).toFixed(2)}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Situación:</span>
                <span class="font-semibold text-amber-600">${reserva.situacion || 'RESERVADA'}</span>
              </div>
            </div>
          </div>
        `,
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                confirmButtonColor: '#ef4444',
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#6b7280',
                width: '500px'
            });

            if (!result.isConfirmed) return;

            // Mostrar loading
            Swal.fire({
                title: 'Cancelando reserva...',
                html: '<div class="text-sm text-gray-600 mt-2">Revirtiendo stock y series...</div>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
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
                        reserva_id: reserva.ven_id,
                        motivo: 'Eliminación de reserva desde el carrito del cliente'
                    })
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error al cancelar la venta');
                }

                // Éxito
                await Swal.fire({
                    icon: 'success',
                    title: '¡Reserva Eliminada!',
                    html: `
            <div class="text-center">
              <p class="text-gray-700 mb-2">La reserva ha sido cancelada exitosamente</p>
              <div class="text-sm text-gray-600">
                <p>✓ Stock revertido</p>
                <p>✓ Series liberadas</p>
                <p>✓ Lotes actualizados</p>
              </div>
            </div>
          `,
                    timer: 3000,
                    showConfirmButton: false
                });

                // Remover del cache local
                window._reservasClienteCache.splice(idx, 1);
                window.reload;

                // Recargar UI con las reservas actualizadas
                const v = validarCliente();
                if (v.valido && v.clienteId) {
                    cargarReservaCliente(v.clienteId);
                } else {
                    // Si no hay cliente válido, limpiar la vista
                    container.innerHTML = '';
                    vacio?.classList.remove('hidden');
                }

            } catch (error) {
                console.error('Error al cancelar reserva:', error);

                Swal.fire({
                    icon: 'error',
                    title: 'Error al Cancelar',
                    html: `
            <div class="text-left px-4">
              <p class="text-gray-700 mb-2">No se pudo cancelar la reserva:</p>
              <p class="text-sm text-red-600 bg-red-50 p-3 rounded border border-red-200">
                ${error.message || 'Error desconocido'}
              </p>
            </div>
          `,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
    });
}

// ===== util: diferencia reserva - carrito (y series restantes) =====
function prepararCargaDesdeReserva(itemsReserva, carrito) {
    const cargables = [];

    itemsReserva.forEach(res => {
        const enCarrito = carrito.find(p => p.producto_id === res.producto_id);

        const cantReserva = Number(res.cantidad || 0);
        const cantCarrito = Number(enCarrito?.cantidad || 0);

        // series de la reserva vienen en seriesSeleccionadas
        const seriesReserva = Array.isArray(res.seriesSeleccionadas) ? res.seriesSeleccionadas : [];
        const seriesCarrito = Array.isArray(enCarrito?.seriesSeleccionadas) ? enCarrito.seriesSeleccionadas : [];

        // series faltantes = reserva - ya en carrito
        const setCarrito = new Set(seriesCarrito);
        const seriesFaltantes = seriesReserva.filter(s => !setCarrito.has(s));

        let cantidadFaltante;
        if (Number(res.producto_requiere_serie ?? 0) === 1) {
            cantidadFaltante = Math.max(Math.min(seriesFaltantes.length, cantReserva - cantCarrito), 0);
        } else {
            cantidadFaltante = Math.max(cantReserva - cantCarrito, 0);
        }

        if (cantidadFaltante > 0) {
            cargables.push({
                ...res,
                cantidad_reserva: cantReserva,
                cantidad_en_carrito: cantCarrito,
                cantidad_faltante: cantidadFaltante,
                series_disponibles: seriesFaltantes
            });
        }
    });

    return cargables;
}

// ===== modal: elegir cantidades y series =====
function mostrarModalSeleccionReserva(itemsCargables) {
    const htmlItems = itemsCargables.map((item, index) => {
        const requiereSerie = Number(item.producto_requiere_serie ?? 0) === 1;
        const seriesDisponibles = Array.isArray(item.series_disponibles) ? item.series_disponibles : [];

        return `
      <div class="border border-gray-200 rounded-lg p-4 mb-3 bg-white" data-item-index="${index}">
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0" style="width:48px;height:48px;">
            ${item.imagen
                ? `<img src="${item.imagen}" alt="${item.nombre}" class="w-full h-full object-cover rounded-lg border border-gray-200">`
                : `<div class="w-full h-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold rounded-lg text-sm">
                     ${String(item.nombre || 'PR').substring(0, 2).toUpperCase()}
                   </div>`
            }
          </div>

          <div class="flex-1">
            <h5 class="font-semibold text-sm text-gray-900 mb-1">${item.nombre}</h5>
            <p class="text-xs text-gray-600 mb-2">
              En reserva: <b>${item.cantidad_reserva}</b> · En carrito: <b>${item.cantidad_en_carrito}</b> ·
              Faltante: <b class="text-blue-700">${item.cantidad_faltante}</b>
              ${item.marca ? ` | Marca: ${item.marca}` : ''}
            </p>

            <div class="flex items-center gap-2 mb-2">
              <label class="text-xs font-medium text-gray-700">Cantidad a cargar:</label>
              <input type="number"
                     min="0"
                     max="${item.cantidad_faltante}"
                     value="${item.cantidad_faltante}"
                     data-item-index="${index}"
                     readonly
                     class="cantidad-cargar w-24 px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <span class="text-[11px] text-gray-500"></span>
            </div>

            ${(requiereSerie && seriesDisponibles.length > 0) ? `
              <div class="mt-2">
                <label class="text-xs font-medium text-gray-700 mb-1 block">Series a cargar ahora:</label>
                <div class="max-h-32 overflow-y-auto border border-gray-200 rounded-lg p-2 bg-gray-50 space-y-1">
                  ${seriesDisponibles.map((serie, i) => `
                      <label class="flex items-center gap-2 text-xs hover:bg-white px-2 py-1 rounded cursor-pointer">
                        <input type="checkbox"
                               value="${serie}"
                               data-item-index="${index}"
                               class="serie-checkbox rounded text-blue-600 focus:ring-blue-500"
                               ${i < item.cantidad_faltante ? 'checked' : ''}>
                        <span class="font-mono text-gray-700">${serie}</span>
                      </label>
                    `).join('')
                }
                </div>
              </div>` :
                (requiereSerie ? `<div class="text-[11px] text-amber-700">No hay series restantes por cargar.</div>` : '')
            }
          </div>
        </div>
      </div>
    `;
    }).join('');

    return Swal.fire({
        title: 'Cargar desde reserva',
        html: `
      <div class="text-left">
        <p class="text-sm text-gray-600 mb-4">
          Elige cuánto cargar ahora y, si aplica, qué series incluir.
        </p>
        <div class="max-h-96 overflow-y-auto">${htmlItems}</div>
      </div>
    `,
        width: '700px',
        showCancelButton: true,
        confirmButtonText: 'Agregar al carrito',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',

        didOpen: () => {
            const popup = Swal.getPopup();

            // Ajusta checks al cambiar cantidad
            popup.querySelectorAll('.cantidad-cargar').forEach(inp => {
                inp.addEventListener('input', function () {
                    const idx = this.dataset.itemIndex;
                    const max = parseInt(this.getAttribute('max') || '0', 10);
                    let val = parseInt(this.value || '0', 10);
                    if (Number.isNaN(val) || val < 0) val = 0;
                    if (val > max) val = max;
                    this.value = String(val);

                    const checks = popup.querySelectorAll(`.serie-checkbox[data-item-index="${idx}"]`);
                    checks.forEach((cb, i) => { cb.checked = i < val; });
                });
            });

            // No permitir más series que la cantidad elegida
            popup.querySelectorAll('.serie-checkbox').forEach(cb => {
                cb.addEventListener('change', function () {
                    const idx = this.dataset.itemIndex;
                    const qty = parseInt(popup.querySelector(`.cantidad-cargar[data-item-index="${idx}"]`)?.value || '0', 10);
                    const checks = popup.querySelectorAll(`.serie-checkbox[data-item-index="${idx}"]`);
                    const count = Array.from(checks).filter(x => x.checked).length;
                    if (count > qty) {
                        this.checked = false;
                        Swal.showValidationMessage(`No puedes seleccionar más de ${qty} serie(s).`);
                    }
                });
            });
        },

        preConfirm: () => {
            const popup = Swal.getPopup();
            const seleccion = [];
            let errorMsg = null;

            itemsCargables.forEach((item, index) => {
                const qtyEl = popup.querySelector(`.cantidad-cargar[data-item-index="${index}"]`);
                const cant = parseInt(qtyEl?.value || '0', 10);

                if (cant > 0) {
                    const requiereSerie = Number(item.producto_requiere_serie ?? 0) === 1;
                    let seriesSel = [];

                    if (requiereSerie) {
                        const checks = popup.querySelectorAll(`.serie-checkbox[data-item-index="${index}"]`);
                        seriesSel = Array.from(checks).filter(x => x.checked).map(x => x.value);


                        if (seriesSel.length === 0) {
                            errorMsg = `${item.nombre}: selecciona al menos 1 serie.`;
                            return;
                        }
                        if (seriesSel.length > cant) {
                            errorMsg = `${item.nombre}: no puedes seleccionar más de ${cant} serie(s).`;
                            return;
                        }
                    }

                    seleccion.push({
                        ...item,

                        cantidad_a_cargar: requiereSerie ? seriesSel.length : cant,
                        series_a_cargar: seriesSel
                    });
                }
            });

            if (errorMsg) { Swal.showValidationMessage(errorMsg); return false; }
            if (seleccion.length === 0) { Swal.showValidationMessage('No elegiste nada para cargar.'); return false; }
            return seleccion;
        }

    });
}

async function aplicarCargaReservaSeleccion(seleccion) {
    // Activar flag para evitar recarga de reserva
    cargandoReserva = true;

    // Obtener IDs de productos para consultar stock actual
    const productosIds = seleccion.map(s => s.producto_id);

    // 🆕 Colección para guardar todas las series que se están cargando
    const seriesSeleccionadasSet = new Set();

    try {
        // Consultar stock actual de los productos
        const response = await fetch('/api/productos/stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ productos_ids: productosIds })
        });

        const stockData = await response.json();
        ('📦 Stocks actualizados:', stockData);

        // Crear un mapa de stock actualizado
        const stockMap = {};
        if (stockData.success && Array.isArray(stockData.stocks)) {
            stockData.stocks.forEach(s => {
                stockMap[s.producto_id] = {
                    stock_total: Number(s.stock_total || 0),
                    stock_reservado: Number(s.stock_reservado || 0),
                    stock_disponible: Number(s.stock_disponible || 0)
                };
            });
        }

        // Agregar productos al carrito con stock actualizado
        seleccion.forEach(sel => {
            const existe = carritoProductos.find(p => p.producto_id === sel.producto_id);
            const stockInfo = stockMap[sel.producto_id];

            // ⭐ Cantidad que se está cargando ahora
            const cantidadCargando = Number(sel.cantidad_a_cargar || 0);

            // ⭐ FÓRMULA: (stock_total - stock_reservado) + cantidad_cargando
            let stockFinal;
            if (stockInfo) {
                stockFinal = (stockInfo.stock_total - stockInfo.stock_reservado) + cantidadCargando;
            } else {
                stockFinal = Number(sel.stock_cantidad_total ?? 0) + cantidadCargando;
            }

            // 🆕 Acumular series a cargar para luego mandarlas al backend
            if (Array.isArray(sel.series_a_cargar) && sel.series_a_cargar.length > 0) {
                sel.series_a_cargar.forEach(serieId => seriesSeleccionadasSet.add(serieId));
            }

            if (existe) {
                // ========================================
                // PRODUCTO YA EXISTE EN EL CARRITO
                // ========================================

                // Sumar cantidad
                existe.cantidad = Number(existe.cantidad || 0) + cantidadCargando;

                // ⭐ ACTUALIZAR STOCK con la fórmula
                if (stockInfo) {
                    existe.stock_cantidad_total = (stockInfo.stock_total - stockInfo.stock_reservado) + cantidadCargando;
                } else {
                    existe.stock_cantidad_total = Number(existe.stock_cantidad_total || 0) + cantidadCargando;
                }

                // Merge series (evitar duplicados)
                if (Array.isArray(sel.series_a_cargar) && sel.series_a_cargar.length > 0) {
                    const prev = Array.isArray(existe.seriesSeleccionadas) ? existe.seriesSeleccionadas : [];
                    existe.seriesSeleccionadas = Array.from(new Set([...prev, ...sel.series_a_cargar]));
                }

                // Actualizar series disponibles si vienen en la selección
                if (Array.isArray(sel.series_disponibles) && sel.series_disponibles.length > 0) {
                    const prevDisp = Array.isArray(existe.series_disponibles) ? existe.series_disponibles : [];
                    existe.series_disponibles = Array.from(new Set([...prevDisp, ...sel.series_disponibles]));
                }

                // Merge lotes seleccionados
                if (Array.isArray(sel.lotesSeleccionados) && sel.lotesSeleccionados.length > 0) {
                    const prevLotes = Array.isArray(existe.lotesSeleccionados) ? existe.lotesSeleccionados : [];

                    // Agregar o sumar cantidades de lotes
                    sel.lotesSeleccionados.forEach(nuevoLote => {
                        const loteExistente = prevLotes.find(l => l.lote_id === nuevoLote.lote_id);
                        if (loteExistente) {
                            loteExistente.cantidad = Number(loteExistente.cantidad || 0) + Number(nuevoLote.cantidad || 0);
                        } else {
                            prevLotes.push({
                                lote_id: nuevoLote.lote_id,
                                cantidad: Number(nuevoLote.cantidad || 0),
                                lote_codigo: nuevoLote.lote_codigo || '',
                                lote_fecha_vencimiento: nuevoLote.lote_fecha_vencimiento || null
                            });
                        }
                    });

                    existe.lotesSeleccionados = prevLotes;
                }

                // Actualizar lotes disponibles si vienen
                if (Array.isArray(sel.lotes) && sel.lotes.length > 0) {
                    existe.lotes = sel.lotes;
                }

            } else {
                // ========================================
                // PRODUCTO NUEVO EN EL CARRITO
                // ========================================

                // 🔧 NORMALIZAR ESTRUCTURA IGUAL QUE EN actualizarVistaCarrito
                const nuevoProducto = {
                    // IDs y básicos
                    producto_id: sel.producto_id,
                    nombre: sel.nombre || sel.producto_nombre || 'Producto',
                    marca: sel.marca || sel.marca_descripcion || '',
                    imagen: sel.imagen || null,

                    // Cantidad
                    cantidad: cantidadCargando,

                    // Precios - estructura completa
                    precio_venta: Number(sel.precio_venta ?? sel.precio ?? 0),
                    precio_venta_empresa: Number(sel.precio_venta_empresa || 0),
                    precio_activo: sel.precio_activo || 'normal',
                    precio_personalizado: sel.precio_personalizado ?? null,

                    // 🎯 Calcular precio según tipo activo
                    precio: (() => {
                        if (sel.precio_activo === 'personalizado' && sel.precio_personalizado !== null) {
                            return Number(sel.precio_personalizado);
                        } else if (sel.precio_activo === 'empresa') {
                            return Number(sel.precio_venta_empresa || 0);
                        } else {
                            return Number(sel.precio_venta ?? sel.precio ?? 0);
                        }
                    })(),

                    // Stock
                    stock_cantidad_total: stockFinal,

                    // Control de stock y series
                    producto_requiere_stock: Number(sel.producto_requiere_stock ?? 1),
                    producto_requiere_serie: Number(sel.producto_requiere_serie ?? 0),

                    // Series
                    seriesSeleccionadas: Array.isArray(sel.series_a_cargar) ? sel.series_a_cargar : [],
                    series_disponibles: Array.isArray(sel.series_disponibles) ? sel.series_disponibles : [],

                    // Lotes
                    lotes: Array.isArray(sel.lotes) ? sel.lotes : [],
                    lotesSeleccionados: Array.isArray(sel.lotesSeleccionados)
                        ? sel.lotesSeleccionados.map(lote => ({
                            lote_id: lote.lote_id,
                            cantidad: Number(lote.cantidad || 0),
                            lote_codigo: lote.lote_codigo || '',
                            lote_fecha_vencimiento: lote.lote_fecha_vencimiento || null
                        }))
                        : []
                };

                carritoProductos.push(nuevoProducto);
            }
        });

        // 🆕 Enviar las series al backend para marcar serie_estado = 'disponible'
        const seriesSeleccionadas = Array.from(seriesSeleccionadasSet);
        ('series seleccionadas', seriesSeleccionadas)
        if (seriesSeleccionadas.length > 0) {
            //       try {
            //       const resp = await fetch('/ventas/marcar-disponibles', {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            //     },
            //     body: JSON.stringify({ seriesSeleccionadas })
            // });

            // // Leer texto porque si hay error HTML o JSON malformado, igual lo vemos
            // const data = await resp.text();

            // ("📡 RESPUESTA DESDE AGREGAR AL CARRITO:", resp.status, data);

            //       } catch (e) {
            //         console.error('⚠️ Error al actualizar estado de series:', e);
            //       }
        }

        // Actualizar vista del carrito
        actualizarVistaCarrito();

        Swal.fire({
            icon: 'success',
            title: '¡Productos Agregados!',
            text: `Se agregaron ${seleccion.length} producto(s) al carrito desde la reserva`,
            timer: 2000,
            showConfirmButton: false
        });

    } catch (error) {
        console.error('❌ Error al obtener stock:', error);

        // Si falla la consulta de stock, usar el stock de la reserva
        seleccion.forEach(sel => {
            const existe = carritoProductos.find(p => p.producto_id === sel.producto_id);
            const cantidadCargando = Number(sel.cantidad_a_cargar || 0);

            if (existe) {
                // Actualizar producto existente
                existe.cantidad = Number(existe.cantidad || 0) + cantidadCargando;
                existe.stock_cantidad_total = Number(existe.stock_cantidad_total || 0) + cantidadCargando;

                if (Array.isArray(sel.series_a_cargar) && sel.series_a_cargar.length > 0) {
                    const prev = Array.isArray(existe.seriesSeleccionadas) ? existe.seriesSeleccionadas : [];
                    existe.seriesSeleccionadas = Array.from(new Set([...prev, ...sel.series_a_cargar]));
                }

                if (Array.isArray(sel.lotesSeleccionados) && sel.lotesSeleccionados.length > 0) {
                    const prevLotes = Array.isArray(existe.lotesSeleccionados) ? existe.lotesSeleccionados : [];
                    sel.lotesSeleccionados.forEach(nuevoLote => {
                        const loteExistente = prevLotes.find(l => l.lote_id === nuevoLote.lote_id);
                        if (loteExistente) {
                            loteExistente.cantidad = Number(loteExistente.cantidad || 0) + Number(nuevoLote.cantidad || 0);
                        } else {
                            prevLotes.push({
                                lote_id: nuevoLote.lote_id,
                                cantidad: Number(nuevoLote.cantidad || 0),
                                lote_codigo: nuevoLote.lote_codigo || '',
                                lote_fecha_vencimiento: nuevoLote.lote_fecha_vencimiento || null
                            });
                        }
                    });
                    existe.lotesSeleccionados = prevLotes;
                }

            } else {
                // Crear producto nuevo (fallback sin stock actualizado)
                carritoProductos.push({
                    producto_id: sel.producto_id,
                    nombre: sel.nombre || sel.producto_nombre || 'Producto',
                    marca: sel.marca || sel.marca_descripcion || '',
                    imagen: sel.imagen || null,
                    cantidad: cantidadCargando,

                    precio_venta: Number(sel.precio_venta ?? sel.precio ?? 0),
                    precio_venta_empresa: Number(sel.precio_venta_empresa || 0),
                    precio_activo: sel.precio_activo || 'normal',
                    precio_personalizado: sel.precio_personalizado ?? null,

                    precio: (() => {
                        if (sel.precio_activo === 'personalizado' && sel.precio_personalizado !== null) {
                            return Number(sel.precio_personalizado);
                        } else if (sel.precio_activo === 'empresa') {
                            return Number(sel.precio_venta_empresa || 0);
                        } else {
                            return Number(sel.precio_venta ?? sel.precio ?? 0);
                        }
                    })(),

                    stock_cantidad_total: Number(sel.stock_cantidad_total ?? 0) + cantidadCargando,

                    producto_requiere_stock: Number(sel.producto_requiere_stock ?? 1),
                    producto_requiere_serie: Number(sel.producto_requiere_serie ?? 0),

                    seriesSeleccionadas: Array.isArray(sel.series_a_cargar) ? sel.series_a_cargar : [],
                    series_disponibles: Array.isArray(sel.series_disponibles) ? sel.series_disponibles : [],

                    lotes: Array.isArray(sel.lotes) ? sel.lotes : [],
                    lotesSeleccionados: Array.isArray(sel.lotesSeleccionados)
                        ? sel.lotesSeleccionados.map(lote => ({
                            lote_id: lote.lote_id,
                            cantidad: Number(lote.cantidad || 0),
                            lote_codigo: lote.lote_codigo || '',
                            lote_fecha_vencimiento: lote.lote_fecha_vencimiento || null
                        }))
                        : []
                });
            }
        });

        actualizarVistaCarrito();

        Swal.fire({
            icon: 'warning',
            title: 'Productos Agregados',
            text: 'No se pudo actualizar el stock del servidor. Verifica la disponibilidad.',
            timer: 3000,
            showConfirmButton: false
        });
    } finally {
        // Desactivar flag
        setTimeout(() => {
            cargandoReserva = false;
        }, 100);
    }
}


// ========================================
// FUNCIÓN DE DEPURACIÓN OPCIONAL
// ========================================
function verificarEstructuraCarrito() {
    ('🔍 Verificando estructura del carrito:');

    carritoProductos.forEach((p, index) => {
        const errores = [];

        // Campos requeridos
        if (!p.producto_id) errores.push('❌ Falta producto_id');
        if (typeof p.cantidad !== 'number') errores.push('❌ cantidad no es número');
        if (typeof p.precio !== 'number') errores.push('❌ precio no es número');
        if (typeof p.producto_requiere_stock !== 'number') errores.push('❌ producto_requiere_stock no es número');
        if (typeof p.producto_requiere_serie !== 'number') errores.push('❌ producto_requiere_serie no es número');

        // Arrays
        if (!Array.isArray(p.seriesSeleccionadas)) errores.push('❌ seriesSeleccionadas no es array');
        if (!Array.isArray(p.lotesSeleccionados)) errores.push('❌ lotesSeleccionados no es array');

        // Validación de series
        if (p.producto_requiere_serie === 1 && p.seriesSeleccionadas.length !== p.cantidad) {
            errores.push(`⚠️ Requiere ${p.cantidad} serie(s), tiene ${p.seriesSeleccionadas.length}`);
        }

        // Validación de lotes
        if (Array.isArray(p.lotes) && p.lotes.length > 0) {
            const totalLotes = p.lotesSeleccionados.reduce((sum, l) => sum + Number(l.cantidad || 0), 0);
            if (totalLotes > 0 && totalLotes !== p.cantidad) {
                errores.push(`⚠️ Cantidad en lotes (${totalLotes}) != cantidad producto (${p.cantidad})`);
            }
        }

        if (errores.length > 0) {
            (`\n📦 Producto ${index + 1}: ${p.nombre}`);
            errores.forEach(e => (e));
        } else {
            (`\n✅ Producto ${index + 1}: ${p.nombre} - Estructura correcta`);
        }
    });

    return carritoProductos.length;
}

// ===== NUEVA versión de cargarReservaEnCarrito(items): respeta tu listener =====
async function cargarReservaEnCarrito(items) {

    ('intentar abrir modal')
    if (!Array.isArray(items) || items.length === 0) {
        Swal?.fire?.('Reserva vacía', 'No hay productos para cargar.', 'info');
        return;
    }

    // 1) calcular lo que falta (reserva - carrito)
    const cargables = prepararCargaDesdeReserva(items, carritoProductos);
    if (!cargables.length) {
        Swal?.fire?.('Sin cambios', 'Tienes que vender o reservar primero.', 'info');
        return;
    }

    // 2) pedir al usuario cantidades/series
    const result = await mostrarModalSeleccionReserva(cargables);

    // 3) aplicar
    if (result.isConfirmed && result.value) {
        aplicarCargaReservaSeleccion(result.value);
    }
}





function actualizarVistaCarrito() {
    const container = document.getElementById("productosCarrito");
    const carritoVacio = document.getElementById("carritoVacio");
    const v = validarCliente();
    const clienteId = v.clienteId;
    ('cliente desde el actualizar', clienteId);


    if (!container) return;

    if (!Array.isArray(carritoProductos)) carritoProductos = [];
    (carritoProductos);

    // 🔧 Normalizar carrito en caliente
    carritoProductos = carritoProductos.map((p) => {
        if (!p || typeof p !== "object") return p;

        // nombre / marca
        p.nombre = p.nombre ?? p.producto_nombre ?? p.descripcion ?? "Producto";
        p.marca = p.marca ?? p.marca_descripcion ?? "";

        // imagen
        // Construir imagen igual que en mostrarProductos
        // imagen - ya viene procesada, solo validar que exista
        if (!p.imagen) p.imagen = null;

        // precios - NO sobrescribir si ya existen
        if (!p.precio_venta) p.precio_venta = Number(p.precio ?? 0);
        if (!p.precio_venta_empresa) p.precio_venta_empresa = Number(p.precio_venta_empresa ?? 0);

        // Convertir a números
        p.precio_venta = Number(p.precio_venta);
        p.precio_venta_empresa = Number(p.precio_venta_empresa);

        // Precio activo (el que se está usando)
        if (!p.precio_activo) {
            p.precio_activo = 'normal';
        }

        // Precio personalizado
        if (p.precio_personalizado === undefined) {
            p.precio_personalizado = null;
        }

        // Precio final según selección
        if (p.precio_activo === 'personalizado' && p.precio_personalizado !== null) {
            p.precio = Number(p.precio_personalizado);
        } else if (p.precio_activo === 'empresa') {
            p.precio = p.precio_venta_empresa;
        } else {
            p.precio = p.precio_venta;
        }

        // cantidad
        p.cantidad = Number(p.cantidad ?? 1);

        // stock
        p.stock_cantidad_total = Number(
            p.stock_cantidad_total ?? p.stockDisponible ?? p.stock ?? 0
        );

        // producto necesita stock (1=sí, 0=no)
        p.producto_requiere_stock = Number(p.producto_requiere_stock ?? 1);

        // requiere serie
        p.producto_requiere_serie = Number(
            p.producto_requiere_serie ?? p.requiere_serie ?? 0
        );

        // series disponibles
        if (!Array.isArray(p.series_disponibles)) {
            p.series_disponibles = Array.isArray(p.seriesDisponibles)
                ? p.seriesDisponibles
                : [];
        }

        // series seleccionadas
        if (!Array.isArray(p.seriesSeleccionadas)) {
            p.seriesSeleccionadas = [];
        }

        // LOTES
        if (!Array.isArray(p.lotes)) p.lotes = [];
        if (!Array.isArray(p.lotesSeleccionados)) p.lotesSeleccionados = [];

        return p;
    });

    if (carritoProductos.length === 0) {
        carritoVacio?.classList.remove("hidden");
        container.innerHTML = "";
        calcularTotales?.();
        return;
    }

    carritoVacio?.classList.add("hidden");

    const htmlTarjetas = carritoProductos.map((p) => {
        const stock = Number(p.stock_cantidad_total ?? 0);
        const necesitaStock = Number(p.producto_requiere_stock ?? 1) === 1;
        const requiereSerie = Number(p.producto_requiere_serie ?? 0) === 1;
        const seriesSel = Array.isArray(p.seriesSeleccionadas)
            ? p.seriesSeleccionadas
            : [];
        const seriesDisponibles = Array.isArray(p.series_disponibles)
            ? p.series_disponibles
            : [];
        const tieneSuficientesSeries =
            !requiereSerie || seriesSel.length === Number(p.cantidad || 0);

        // Badge de serie - SOLO si necesita stock
        const badgeSerie = (necesitaStock && requiereSerie)
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
                       ${tieneSuficientesSeries
                ? "bg-emerald-100 text-emerald-700"
                : "bg-amber-100 text-amber-700"
            }">
                    <i class="fas fa-barcode mr-1"></i>
                    Serie: ${seriesSel.length}/${p.cantidad}
               </span>`
            : "";

        const hasLotes = Array.isArray(p.lotes) && p.lotes.length > 0;
        const asignadoLotes = Array.isArray(p.lotesSeleccionados)
            ? p.lotesSeleccionados.reduce(
                (acc, it) => acc + Number(it.cantidad || 0),
                0
            )
            : 0;

        // Badge de lotes - SOLO si necesita stock
        const badgeLotes = (necesitaStock && hasLotes)
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium
         ${asignadoLotes === Number(p.cantidad || 0)
                ? "bg-emerald-100 text-emerald-700"
                : "bg-amber-100 text-amber-700"
            }">
       <i class="fas fa-layer-group mr-1"></i>
       Lotes: ${asignadoLotes}/${p.cantidad}
     </span>`
            : "";

        // Selector de precio con opción personalizada
        const tienePrecioEmpresa = p.precio_venta_empresa > 0 && p.precio_venta_empresa !== p.precio_venta;
        const selectorPrecio = `
                <div class="mt-1.5 space-y-2">
                    <div class="flex items-center gap-1.5 text-[10px] flex-wrap">
                        <span class="text-gray-500 font-medium">Precio:</span>
                        <button type="button"
                                data-action="cambiar-precio"
                                data-id="${String(p.producto_id)}"
                                data-tipo="normal"
                                class="px-2 py-1 rounded-md font-medium transition-all duration-200 border
                                ${p.precio_activo === 'normal'
                ? 'bg-blue-600 text-white shadow-sm border-blue-600'
                : 'bg-gray-200 text-gray-500 border-gray-300 hover:bg-gray-300'}">
                            Precio Individual (Q${p.precio_venta.toFixed(2)})
                        </button>
                        ${tienePrecioEmpresa ? `
                        <button type="button"
                                data-action="cambiar-precio"
                                data-id="${String(p.producto_id)}"
                                data-tipo="empresa"
                                class="px-2 py-1 rounded-md font-medium transition-all duration-200 border
                                ${p.precio_activo === 'empresa'
                    ? 'bg-blue-600 text-white shadow-sm border-blue-600'
                    : 'bg-gray-200 text-gray-500 border-gray-300 hover:bg-gray-300'}">
                            Precio Empresa (Q${p.precio_venta_empresa.toFixed(2)})
                        </button>
                        ` : ''}

                        <button type="button"
                                data-action="cambiar-precio"
                                data-id="${String(p.producto_id)}"
                                data-tipo="personalizado"
                                class="px-2 py-1 rounded-md font-medium transition-all duration-200 border flex items-center gap-1
                                ${p.precio_activo === 'personalizado'
                ? 'bg-blue-600 text-white shadow-sm border-blue-600'
                : 'bg-gray-200 text-gray-500 border-gray-300 hover:bg-gray-300'}">
                            <i class="fas fa-edit text-[10px]"></i>
                            Personalizado
                        </button>
                        
                    </div>
                    
                    ${p.precio_activo === 'personalizado' ? `
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] text-gray-600 font-medium">Precio Custom:</span>
                        <div class="flex items-center gap-1 bg-white border border-purple-300 rounded-md px-2 py-1 focus-within:ring-2 focus-within:ring-purple-400">
                            <span class="text-xs text-gray-500">Q</span>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   value="${p.precio_personalizado ?? ''}"
                                   placeholder="0.00"
                                   data-action="precio-personalizado"
                                   data-id="${String(p.producto_id)}"
                                   class="w-20 text-sm font-semibold text-gray shadow-sm bg-white-600 border-blue-600 focus:outline-none focus:ring-0 p-0">
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;

        return `
        <div class="group bg-white p-4 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-300 flex items-start gap-4">
          <!-- Imagen -->
          <div class="relative flex-shrink-0" style="width: 96px; height: 96px;">
            ${p.imagen ?
                `<img src="${p.imagen}"
                     alt="${p.nombre}"
                     class="w-full h-full object-cover rounded-lg border-2 border-gray-200"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                 <div style="display:none; width: 96px; height: 96px;" 
                      class="absolute inset-0 bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold rounded-lg text-2xl">
                    ${p.nombre.substring(0, 2).toUpperCase()}
                 </div>`
                :
                `<div style="width: 96px; height: 96px;" 
                     class="bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold rounded-lg text-2xl">
                    ${p.nombre.substring(0, 2).toUpperCase()}
                 </div>`
            }
            
            <!-- Badge de cantidad -->
            <div class="absolute z-10 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-extrabold px-3 py-1.5 rounded-full shadow-xl border-3 border-white" 
                 style="top: -12px; right: -12px;">
                ${p.cantidad}x
            </div>
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start mb-2">
              <div class="flex-1 min-w-0 mr-2">
                <h4 class="font-semibold text-sm text-gray-900 truncate">${p.nombre}</h4>
                ${p.marca ? `<p class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                    <i class="fas fa-industry text-[9px]"></i>
                    ${p.marca}
                </p>` : ''}
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                  ${necesitaStock ? `
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 border border-gray-300">
                    <i class="fas fa-box mr-1"></i>
                    Stock: ${stock}
                  </span>
                  ` : ''}
                  ${badgeSerie}
                  ${badgeLotes}
                </div>
              </div>
              <button type="button"
                      data-action="eliminar"
                      data-id="${String(p.producto_id)}"
                      class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-1.5 rounded-lg transition-all duration-200">
                <i class="fas fa-trash-alt text-xs"></i>
              </button>
            </div>

            <!-- Controles de cantidad -->
            <div class="flex items-center gap-2 mb-2">
                <div class="flex items-center gap-1 bg-gray-50 rounded-lg p-1 border border-gray-200">
                    <button type="button"
                            data-action="disminuir"
                            data-id="${String(p.producto_id)}"
                            class="bg-white hover:bg-gray-100 text-gray-700 w-7 h-7 rounded-md flex items-center justify-center text-sm shadow-sm border border-gray-200 transition-all duration-200 hover:scale-105 active:scale-95">
                      <i class="fas fa-minus text-xs"></i>
                    </button>

                    <span class="font-bold text-sm w-10 text-center text-gray-900">${p.cantidad}</span>

                    <button type="button"
                            data-action="aumentar"
                            data-id="${String(p.producto_id)}"
                            class="bg-blue-600 hover:bg-blue-700 text-white w-7 h-7 rounded-md flex items-center justify-center text-sm shadow-sm transition-all duration-200 hover:scale-105 active:scale-95">
                      <i class="fas fa-plus text-xs font-bold"></i>
                    </button>
                </div>

                ${(necesitaStock && requiereSerie && seriesDisponibles.length > 0)
                ? `
                  <button type="button"
                          data-action="series"
                          data-id="${String(p.producto_id)}"
                          class="px-3 py-1.5 rounded-lg text-xs font-medium border-2 border-blue-200 bg-white hover:bg-blue-50 hover:border-blue-300 text-blue-700 transition-all duration-200 flex items-center gap-1.5">
                    <i class="fas fa-barcode"></i>
                    Series
                  </button>
                `
                : ``
            }

                ${(necesitaStock && hasLotes)
                ? `
                    <button type="button"
                            data-action="lotes"
                            data-id="${String(p.producto_id)}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border-2 border-purple-200 bg-white hover:bg-purple-50 hover:border-purple-300 text-purple-700 transition-all duration-200 flex items-center gap-1.5">
                        <i class="fas fa-layer-group"></i>
                        Lotes
                    </button>
                    `
                : ``
            }
            
            ${(() => {
                // 👇 Verificar si es munición para mostrar botón de editar cajas
                const original = productosGlobales.find(pg => String(pg.producto_id) === String(p.producto_id));
                const cat = (original?.categoria_nombre || "").toLowerCase();
                const esMunicion = cat.includes("municion") || cat.includes("munición");

                if (esMunicion) {
                    return `
                    <button type="button"
                            data-action="editar-cajas"
                            data-id="${String(p.producto_id)}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium border-2 border-orange-200 bg-white hover:bg-orange-50 hover:border-orange-300 text-orange-700 transition-all duration-200 flex items-center gap-1.5">
                        <i class="fas fa-boxes"></i>
                        Editar Cajas
                    </button>
                    `;
                }
                return "";
            })()}
            </div>

            <!-- Selector de precio -->
            ${selectorPrecio}

            <!-- Precio total -->
            <div class="mt-2 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-green-50 border border-emerald-200 rounded-lg p-2">
            <div class="text-xs text-gray-600">
                <span class="font-medium">Precio unitario:</span>
                <span class="ml-1 font-semibold text-emerald-700 precio-unitario-valor">Q${p.precio.toFixed(2)}</span>
                ${p.precio_activo === 'empresa' ? '<span class="ml-1 text-[10px] text-purple-600 font-medium">(Especial)</span>' : ''}
                ${p.precio_activo === 'personalizado' ? '<span class="ml-1 text-[10px] text-purple-600 font-medium">(Personalizado)</span>' : ''}
            </div>
            <div class="text-right">
                <div class="text-xs text-gray-500 font-medium">Total</div>
                <div class="font-bold text-emerald-600 text-lg total-producto-valor">
                Q${(p.precio * p.cantidad).toFixed(2)}
                </div>
            </div>
            </div>
          </div>
        </div>`;
    })
        .join("");


    const puedeReservar = carritoProductos.every(p => {
        const necesitaStock = Number(p.producto_requiere_stock ?? 1) === 1;
        const requiereSerie = Number(p.producto_requiere_serie ?? 0) === 1;
        const cant = Number(p.cantidad || 0);

        const seriesOK = !necesitaStock || !requiereSerie || (Array.isArray(p.seriesSeleccionadas) && p.seriesSeleccionadas.length === cant);

        const hasLotes = Array.isArray(p.lotes) && p.lotes.length > 0;
        const asignadoLotes = Array.isArray(p.lotesSeleccionados)
            ? p.lotesSeleccionados.reduce((acc, it) => acc + Number(it.cantidad || 0), 0)
            : 0;
        const lotesOK = !necesitaStock || !hasLotes || asignadoLotes === cant;

        // Si requiere stock, valida stock total también
        const stockOK = !necesitaStock || Number(p.stock_cantidad_total ?? 0) >= cant;

        return seriesOK && lotesOK && stockOK;
    });


    const htmlAcciones = `
  <div class="mt-4 flex flex-col sm:flex-row items-center gap-2 sm:justify-end">
    <button type="button"
            data-action="reservar"
            class="px-4 py-2 rounded-lg text-sm font-semibold transition
                   ${puedeReservar
            ? 'bg-blue-600 text-white hover:bg-blue-700'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed'}"
            ${puedeReservar ? '' : 'disabled'}>
      <i class="fas fa-clipboard-check mr-1"></i> Reservar
    </button>
  </div>
`;

    // 4) Pintar tarjetas + botón
    container.innerHTML = htmlTarjetas + htmlAcciones;
    // En la sección "Precio total", modifica esta parte:



    // Event listener para precio personalizado - REEMPLAZA el que tienes:
    container.querySelectorAll('[data-action="precio-personalizado"]').forEach(input => {
        input.addEventListener('input', (e) => {
            const id = e.currentTarget.dataset.id;
            const valor = parseFloat(e.currentTarget.value) || 0;

            const producto = carritoProductos.find(p => String(p.producto_id) === id);
            if (producto) {
                producto.precio_personalizado = valor;
                producto.precio = valor;

                // Actualizar solo el precio unitario sin recargar todo
                const precioUnitarioSpan = e.currentTarget.closest('.group').querySelector('.precio-unitario-valor');
                if (precioUnitarioSpan) {
                    precioUnitarioSpan.textContent = `Q${valor.toFixed(2)}`;
                }

                // Actualizar el total del producto
                const totalProductoDiv = e.currentTarget.closest('.group').querySelector('.total-producto-valor');
                if (totalProductoDiv) {
                    totalProductoDiv.textContent = `Q${(valor * producto.cantidad).toFixed(2)}`;
                }

                calcularTotales?.();
            }
        });
    });

    // Event listener para cambio de precio
    container.querySelectorAll('[data-action="cambiar-precio"]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const id = e.currentTarget.dataset.id;
            const tipo = e.currentTarget.dataset.tipo;

            const producto = carritoProductos.find(p => String(p.producto_id) === id);
            if (producto) {
                producto.precio_activo = tipo;

                if (tipo === 'personalizado') {
                    // Si no hay precio personalizado previo, usar el precio actual como base
                    if (producto.precio_personalizado === null) {
                        producto.precio_personalizado = producto.precio;
                    }
                    producto.precio = Number(producto.precio_personalizado);
                } else if (tipo === 'empresa') {
                    producto.precio = producto.precio_venta_empresa;
                } else {
                    producto.precio = producto.precio_venta;
                }

                actualizarVistaCarrito();
            }
        });
    });

    // Botón Reservar




    calcularTotales?.();
}

(function initCarritoDelegation() {
    const container = document.getElementById("productosCarrito");
    if (!container) return;

    container.addEventListener("click", (e) => {
        const btn = e.target.closest("[data-action]");
        if (!btn) return;

        const action = btn.dataset.action;
        if (action === "reservar") {
            e.preventDefault();
            e.stopPropagation();
            procesarReserva();
            return;
        }
        const id = btn.dataset.id;
        if (!id) return;

        switch (action) {
            case "eliminar":
                eliminarProducto(id);
                break;
            case "disminuir":
                cambiarCantidad(id, -1);
                break;
            case "aumentar":
                cambiarCantidad(id, 1);
                break;
            case "series":
                seleccionarSeries(id); // <<< NUEVO
                break;
            case "lotes":
                seleccionarLotes(id);
                break;
            case "editar-cajas":
                editarCajasMunicion(id);
                break;
        }
    });
})();
function calcularTotales() {
    const subtotal = carritoProductos.reduce(
        (sum, p) => sum + p.precio * p.cantidad,
        0
    );

    // ✅ MODIFICADO: Contar tenencias por serie
    const totalTenencia = carritoProductos.reduce((sum, p) => {
        if (p.seriesConTenencia) {
            const numTenencias = Object.keys(p.seriesConTenencia).length;
            return sum + (numTenencias * 60);
        }
        return sum;
    }, 0);

    const descuento = parseFloat(document.getElementById("descuentoModal").value) || 0;
    const descuentoMonto = subtotal * (descuento / 100);
    const total = subtotal - descuentoMonto + totalTenencia;

    document.getElementById("subtotalModal").textContent = `Q${subtotal.toFixed(2)}`;

    const tenenciaEl = document.getElementById("tenenciaModal");
    if (tenenciaEl) {
        tenenciaEl.textContent = `Q${totalTenencia.toFixed(2)}`;
        const tenenciaRow = tenenciaEl.closest('.tenencia-row');
        if (tenenciaRow) {
            tenenciaRow.classList.toggle('hidden', totalTenencia === 0);
        }
    }

    document.getElementById("totalModal").textContent = `Q${total.toFixed(2)}`;
}
function actualizarContadorCarrito() {
    const contador = carritoProductos.reduce((sum, p) => sum + p.cantidad, 0);
    const badge = document.getElementById("contadorCarrito");

    badge.textContent = contador;

    // Animar el badge cuando se agrega algo
    if (contador > 0) {
        badge.classList.remove("hidden");
        badge.style.transform = "scale(1.2)";
        setTimeout(() => {
            badge.style.transform = "scale(1)";
        }, 200);
    } else {
        badge.classList.add("hidden");
    }
}

function mostrarNotificacion(mensaje, tipo = "success") {
    const notificacion = document.createElement("div");
    notificacion.className = `
        fixed top-4 left-1/2 
        -translate-x-1/2 -translate-y-5
        opacity-0
        px-6 py-3 rounded-lg shadow-lg z-50 
        transition-all duration-300 transform
        ${tipo === "success"
            ? "bg-green-500 text-white"
            : tipo === "warning"
                ? "bg-yellow-500 text-white"
                : tipo === "info"
                    ? "bg-blue-500 text-white"
                    : "bg-red-500 text-white"
        }
    `;

    notificacion.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${tipo === "success"
            ? "check"
            : tipo === "warning"
                ? "exclamation-triangle"
                : "info-circle"
        } mr-2"></i>
            ${mensaje}
        </div>
    `;

    document.body.appendChild(notificacion);

    // Mostrar con animación
    setTimeout(() => {
        notificacion.classList.remove("-translate-y-5", "opacity-0");
        notificacion.classList.add("translate-y-0", "opacity-100");
    }, 10);

    // Ocultar después de 3 segundos
    setTimeout(() => {
        notificacion.classList.remove("translate-y-0", "opacity-100");
        notificacion.classList.add("-translate-y-5", "opacity-0");
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 3000);
}

// Inicializar contador al cargar la página
document.addEventListener("DOMContentLoaded", function () {
    actualizarContadorCarrito();
    calcularTotales();
});
async function seleccionarSeries(producto_id) {
    const id = String(producto_id);
    const p = carritoProductos.find((x) => String(x.producto_id) === id);
    if (!p) return;

    if ((p.requiere_serie ?? p.producto_requiere_serie) != 1) {
        return mostrarNotificacion?.("Este producto no requiere series.", "info");
    }

    const cantidad = Number(p.cantidad || 0);
    const disponibles = Array.isArray(p.series_disponibles) ? p.series_disponibles : [];
    const yaSel = new Set(Array.isArray(p.seriesSeleccionadas) ? p.seriesSeleccionadas : []);

    // ✅ NUEVO: Verificar tenencias ya seleccionadas
    const tenenciasExistentes = p.seriesConTenencia || {};

    // ✅ MODIFICADO: Armar HTML con checkbox de tenencia por serie
    const opciones = disponibles
        .map((s) => {
            const serie = s.serie_numero_serie ?? s.numero ?? String(s);
            const checked = yaSel.has(serie) ? "checked" : "";
            const tenenciaChecked = tenenciasExistentes[serie] ? "checked" : "";

            return `
          <div class="flex items-center gap-3 py-2 px-2 hover:bg-gray-50 rounded border-b">
            <label class="flex items-center gap-2 flex-1">
              <input type="checkbox" class="serie-opt w-4 h-4" value="${serie}" ${checked}>
              <span class="font-mono font-semibold">${serie}</span>
            </label>
            <label class="flex items-center gap-2 text-sm text-blue-700 ${checked ? '' : 'opacity-30 pointer-events-none'}">
              <input type="checkbox" class="tenencia-opt w-4 h-4" data-serie="${serie}" ${tenenciaChecked} ${checked ? '' : 'disabled'}>
              <span class="whitespace-nowrap">+Q60 tenencia</span>
            </label>
          </div>`;
        })
        .join("");

    const { value: resultado } = await Swal.fire({
        title: `Selecciona ${cantidad} serie(s)`,
        html: `
          <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded">
            <div class="text-sm font-semibold text-blue-900 mb-1">
              <i class="fas fa-info-circle mr-1"></i>
              Puedes cobrar tenencia individual por cada serie
            </div>
            <div class="text-lg font-bold text-blue-700" id="tenenciaInfo">
              Total tenencia: Q0.00
            </div>
          </div>
          
          <div class="text-left max-h-80 overflow-auto border rounded p-2 bg-gray-50">
            ${opciones || '<div class="text-sm text-gray-500 text-center py-4">Sin series disponibles.</div>'}
          </div>
          
          <div class="mt-3 flex justify-between text-sm px-2">
            <span class="text-gray-600">Seleccionadas:</span>
            <span class="font-bold"><span id="selCount">${yaSel.size}</span> / ${cantidad}</span>
          </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        didOpen: () => {
            const checks = Swal.getHtmlContainer().querySelectorAll(".serie-opt");
            const tenenciaChecks = Swal.getHtmlContainer().querySelectorAll(".tenencia-opt");
            const selCount = Swal.getHtmlContainer().querySelector("#selCount");
            const tenenciaInfo = Swal.getHtmlContainer().querySelector("#tenenciaInfo");

            // ✅ NUEVO: Función para actualizar info de tenencia
            function actualizarTenencia() {
                const numTenencias = Array.from(tenenciaChecks).filter(c => c.checked && !c.disabled).length;
                const monto = numTenencias * 60;
                tenenciaInfo.textContent = `Total tenencia: Q${monto.toFixed(2)}`;
            }

            // ✅ NUEVO: Al marcar/desmarcar una serie, habilitar/deshabilitar su checkbox de tenencia
            checks.forEach((chk) => {
                chk.addEventListener("change", () => {
                    const serie = chk.value;
                    const tenenciaChk = Swal.getHtmlContainer().querySelector(`.tenencia-opt[data-serie="${serie}"]`);
                    const row = chk.closest('div');

                    if (chk.checked) {
                        // Habilitar checkbox de tenencia
                        tenenciaChk.disabled = false;
                        row.querySelector('label:last-child').classList.remove('opacity-30', 'pointer-events-none');
                    } else {
                        // Deshabilitar y desmarcar checkbox de tenencia
                        tenenciaChk.disabled = true;
                        tenenciaChk.checked = false;
                        row.querySelector('label:last-child').classList.add('opacity-30', 'pointer-events-none');
                    }

                    const current = Array.from(checks).filter((c) => c.checked).length;
                    selCount.textContent = current;
                    actualizarTenencia();

                    // No permitir seleccionar más que la cantidad requerida
                    if (current > cantidad) {
                        chk.checked = false;
                        const tenenciaChk = Swal.getHtmlContainer().querySelector(`.tenencia-opt[data-serie="${serie}"]`);
                        tenenciaChk.disabled = true;
                        tenenciaChk.checked = false;
                        row.querySelector('label:last-child').classList.add('opacity-30', 'pointer-events-none');

                        selCount.textContent = Array.from(checks).filter((c) => c.checked).length;
                        actualizarTenencia();
                        mostrarNotificacion?.(`Solo puedes seleccionar ${cantidad} serie(s).`, "warning");
                    }
                });
            });

            // ✅ NUEVO: Al marcar/desmarcar checkbox de tenencia, actualizar total
            tenenciaChecks.forEach((tenChk) => {
                tenChk.addEventListener("change", actualizarTenencia);
            });

            actualizarTenencia(); // Inicializar
        },
        preConfirm: () => {
            const checks = Swal.getHtmlContainer().querySelectorAll(".serie-opt");
            const tenenciaChecks = Swal.getHtmlContainer().querySelectorAll(".tenencia-opt");

            const seleccionadas = Array.from(checks)
                .filter((c) => c.checked)
                .map((c) => c.value);

            // ✅ NUEVO: Crear objeto con tenencias por serie
            const tenenciasPorSerie = {};
            Array.from(tenenciaChecks).forEach((tc) => {
                if (tc.checked && !tc.disabled) {
                    tenenciasPorSerie[tc.dataset.serie] = true;
                }
            });

            if (seleccionadas.length !== cantidad) {
                Swal.showValidationMessage(`Debes seleccionar exactamente ${cantidad} serie(s).`);
                return false;
            }

            return {
                series: seleccionadas,
                tenencias: tenenciasPorSerie
            };
        },
    });

    if (resultado) {
        p.seriesSeleccionadas = resultado.series;
        p.seriesConTenencia = resultado.tenencias; // ✅ NUEVO: Guardar tenencias por serie

        // ✅ NUEVO: Contar cuántas tenencias se cobraron
        const numTenencias = Object.keys(resultado.tenencias).length;

        actualizarVistaCarrito();
        calcularTotales();

        const msgTenencia = numTenencias > 0 ? ` (+Q${numTenencias * 60} tenencia en ${numTenencias} serie${numTenencias > 1 ? 's' : ''})` : '';
        mostrarNotificacion?.(`Series actualizadas${msgTenencia}`, "success");
    }
}

async function seleccionarLotes(producto_id) {
    const id = String(producto_id);
    const p = carritoProductos.find((x) => String(x.producto_id) === id);
    if (!p) return;

    const cantidadNecesaria = Number(p.cantidad || 0);
    if (cantidadNecesaria < 1) {
        return mostrarNotificacion?.(
            "La cantidad del producto debe ser al menos 1.",
            "info"
        );
    }

    const lotes = Array.isArray(p.lotes) ? p.lotes : [];
    if (lotes.length === 0) {
        return mostrarNotificacion?.(
            "Este producto no tiene lotes disponibles.",
            "info"
        );
    }

    // Prefill con lo que ya seleccionó
    const preSel = new Map(
        (p.lotesSeleccionados || []).map((it) => [
            Number(it.lote_id),
            Number(it.cantidad),
        ])
    );

    // Construir HTML: cada lote con checkbox y input cantidad
    const opciones = lotes
        .map((l) => {
            const loteId = Number(l.lote_id ?? l.id);
            const loteCodigo = l.lote_codigo ?? `Lote ${loteId}`;
            const max = Number(l.lote_cantidad_total ?? 0);
            const pre = preSel.get(loteId) || 0;
            const checked = pre > 0 ? "checked" : "";
            const disabled = pre > 0 ? "" : "disabled";
            return `
      <div class="flex items-center justify-between gap-3 py-1">
        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" class="lote-check" data-lote-id="${loteId}" data-max="${max}" ${checked}>
          <span class="font-mono">${loteCodigo}</span>
          <span class="text-xs text-gray-500">(disp: ${max})</span>
        </label>
        <input type="number" min="1" max="${max}" value="${pre || ""}" 
               class="lote-input w-20 px-2 py-1 border rounded text-sm text-right" 
               data-lote-id="${loteId}" ${disabled}>
      </div>`;
        })
        .join("");

    const { value: asignaciones } = await Swal.fire({
        title: `Seleccione ${cantidadNecesaria} unidad(es) de los lotes disponibles`,
        html: `
      <div class="text-left max-h-72 overflow-auto px-1">
        ${opciones}
      </div>
      <div class="mt-3 text-xs text-gray-600">
        Asignadas: <span id="lotSum">0</span> / ${cantidadNecesaria}
      </div>
    `,
        showCancelButton: true,
        confirmButtonText: "Guardar",
        cancelButtonText: "Cancelar",
        didOpen: () => {
            const container = Swal.getHtmlContainer();
            const checks = container.querySelectorAll(".lote-check");
            const inputs = container.querySelectorAll(".lote-input");
            const lotSum = container.querySelector("#lotSum");

            const getSum = () =>
                Array.from(inputs).reduce((acc, inp) => {
                    const v = Number(inp.value || 0);
                    return acc + (!inp.disabled && v > 0 ? v : 0);
                }, 0);

            const recalc = () => {
                lotSum.textContent = getSum();
            };

            checks.forEach((chk) => {
                const loteId = Number(chk.dataset.loteId);
                const inp = container.querySelector(
                    `.lote-input[data-lote-id="${loteId}"]`
                );
                chk.addEventListener("change", () => {
                    if (chk.checked) {
                        // Al marcar, intentamos poner 1 sin exceder el total ni el max del lote
                        const current = getSum();
                        const maxLote = Number(chk.dataset.max || 0);
                        if (current >= cantidadNecesaria) {
                            // No hay espacio para más unidades
                            chk.checked = false;
                            mostrarNotificacion?.(
                                `No puedes superar ${cantidadNecesaria} unidad(es) en total.`,
                                "warning"
                            );
                            return;
                        }
                        inp.disabled = false;
                        let val = Number(inp.value || 0);
                        if (val < 1) val = 1;
                        if (val > maxLote) val = maxLote;
                        // Cap al restante disponible
                        const restante = cantidadNecesaria - current;
                        if (val > restante) val = restante;
                        inp.value = val;
                    } else {
                        inp.value = "";
                        inp.disabled = true;
                    }
                    recalc();
                });
            });

            inputs.forEach((inp) => {
                inp.addEventListener("input", () => {
                    const max = Number(inp.getAttribute("max") || 0);
                    // suma de todos los demás inputs activos
                    const othersSum = Array.from(inputs).reduce((acc, el) => {
                        if (el === inp) return acc;
                        const v = Number(el.value || 0);
                        return acc + (!el.disabled && v > 0 ? v : 0);
                    }, 0);

                    let val = Number(inp.value || 0);
                    if (val < 1) val = 1;
                    if (val > max) val = max; // no superar cantidad del lote

                    const restante = cantidadNecesaria - othersSum;
                    if (val > restante) {
                        val = Math.max(restante, 0); // no exceder el total requerido
                    }

                    // si restante es 0, desmarcamos este lote
                    if (val <= 0) {
                        inp.value = "";
                        const loteId = inp.dataset.loteId;
                        const chk = container.querySelector(
                            `.lote-check[data-lote-id="${loteId}"]`
                        );
                        if (chk) chk.checked = false;
                        inp.disabled = true;
                    } else {
                        inp.value = val;
                    }
                    recalc();
                });
            });

            // Calcular suma inicial (prefill)
            recalc();
        },
        preConfirm: () => {
            const container = Swal.getHtmlContainer();
            const checks = container.querySelectorAll(".lote-check");
            const inputs = container.querySelectorAll(".lote-input");

            const asign = [];
            let sum = 0;

            checks.forEach((chk) => {
                const loteId = Number(chk.dataset.loteId);
                const max = Number(chk.dataset.max);
                const inp = container.querySelector(
                    `.lote-input[data-lote-id="${loteId}"]`
                );
                if (chk.checked) {
                    const cant = Number(inp.value || 0);
                    if (cant < 1) {
                        Swal.showValidationMessage(
                            "Hay lotes seleccionados sin cantidad válida."
                        );
                    }
                    if (cant > max) {
                        Swal.showValidationMessage(
                            `No puedes tomar más de ${max} del lote ${loteId}.`
                        );
                    }
                    asign.push({
                        lote_id: loteId,
                        cantidad: cant,
                        lote_codigo:
                            lotes.find((ll) => Number(ll.lote_id) === loteId)
                                ?.lote_codigo ?? "",
                    });
                    sum += cant;
                }
            });

            if (asign.length === 0) {
                Swal.showValidationMessage("Selecciona al menos un lote.");
                return false;
            }

            if (sum !== cantidadNecesaria) {
                Swal.showValidationMessage(
                    `Debes seleccionar exactamente ${cantidadNecesaria} unidad(es) de los lotes disponibles. Actualmente: ${sum}.`
                );
                return false;
            }

            return asign;
        },
    });

    if (asignaciones) {
        p.lotesSeleccionados = asignaciones; // [{lote_id, cantidad, lote_codigo}]
        actualizarVistaCarrito();
        mostrarNotificacion?.("Lotes asignados.", "success");
    }
}

// LOGICA PARA EL METODO DE PAGO

// Utils
const moneyToNumber = (s) => Number(String(s).replace(/[^\d.-]/g, "")) || 0;
const getTotalVenta = () =>
    moneyToNumber(document.getElementById("totalModal")?.textContent || 0);

// Reparto exacto a 2 decimales (distribuye el redondeo)
function repartirEnCuotas(total, n) {
    const cent = Math.round(total * 100);
    const base = Math.floor(cent / n);
    let resto = cent - base * n;
    const res = Array.from(
        { length: n },
        (_, i) => (base + (i < resto ? 1 : 0)) / 100
    );
    return res;
}

// Render de cuotas
function renderCuotas(montos) {
    const lista = document.getElementById("cuotasLista");
    lista.innerHTML = montos
        .map(
            (m, i) => `
    <div class="flex items-center justify-between gap-3">
      <div class="text-sm text-gray-700">Cuota ${i + 1}</div>
      <input type="number" step="0.01" min="0" value="${m.toFixed(2)}"
             class="cuota-input w-28 px-2 py-1 border rounded text-right text-sm" data-index="${i}">
    </div>
  `
        )
        .join("");

    // listeners de edición
    lista.querySelectorAll(".cuota-input").forEach((inp) => {
        inp.addEventListener("input", validarCuotas);
    });

    validarCuotas();
}


// Valida suma = total y habilita/deshabilita botón
function validarCuotas() {
    const errores = [];
    const total = getTotalVenta();

    // Tomar abono y limitarlo entre 0 y total, pero sin vaciar cuotas
    const abonoInp = document.getElementById("abonoInicial");
    let abono = Number(abonoInp?.value || 0);

    // Si el abono es mayor que el total, lo limitamos al total, pero no vaciamos las cuotas
    if (abono < 0) abono = 0;
    if (abono > total) abono = total;
    if (abonoInp) abonoInp.value = abono.toFixed(2);

    // Suma de cuotas
    const inputs = Array.from(
        document.querySelectorAll("#cuotasLista .cuota-input")
    );
    const sumCuotas = inputs.reduce(
        (acc, el) => acc + (Number(el.value) || 0),
        0
    );

    // Validación: abono + cuotas = total
    const sumTotal = +(sumCuotas + abono).toFixed(2);
    const diff = +(sumTotal - total).toFixed(2);

    // (opcional) mostrar saldo si tienes #saldoCuotas en el HTML
    const saldoEl = document.getElementById("saldoCuotas");
    if (saldoEl) saldoEl.textContent = `Q${(total - abono).toFixed(2)}`;

    const msg = document.getElementById("cuotasMensaje");

    if (Math.abs(diff) < 0.01) {
        msg.textContent = "OK: Abono + cuotas = total.";
        msg.className = "text-xs mt-1 text-green-600";
    } else {
        msg.textContent = `Abono + cuotas (Q${sumTotal.toFixed(
            2
        )}) debe ser igual al total (Q${total.toFixed(
            2
        )})`;
        msg.className = "text-xs mt-1 text-red-600";
        errores.push("La suma de las cuotas y el abono debe ser igual al total.");
    }

    return errores;  // Devolvemos el arreglo de errores
}



document.getElementById("abonoInicial")?.addEventListener("input", () => {
    const total = getTotalVenta();
    const abonoInp = document.getElementById("abonoInicial");
    let v = Number(abonoInp.value || 0);
    if (v < 0) v = 0;
    if (v > total) v = total;
    abonoInp.value = v.toFixed(2);
    updateCuotasFromTotal();
});

// Evento para método de pago
document.querySelectorAll('input[name="metodoPago"]').forEach((radio) => {
    radio.addEventListener("change", function () {
        const val = String(this.value);
        const autorizacionContainer = document.getElementById(
            "autorizacionContainer"
        );
        const cuotasContainer = document.getElementById("cuotasContainer");
        const numeroAut = document.getElementById("numeroAutorizacion");

        if (["2", "3", "4", "5"].includes(val)) {
            // Mostrar autorización
            cuotasContainer.classList.add("hidden");
            autorizacionContainer.classList.remove("hidden");
            // Validación rápida: no permitir procesar si falta autorización
            numeroAut.removeEventListener("input", checkAuth);
            numeroAut.addEventListener("input", checkAuth);
            checkAuth();
        } else if (val === "6") {
            // Pagos: mostrar cuotas, ocultar autorización y repartir
            autorizacionContainer.classList.add("hidden");
            numeroAut.value = "";
            cuotasContainer.classList.remove("hidden");
            document.getElementById("abonoInicial").value = "";
            document.querySelectorAll(".cuota-input").forEach((input) => {
                input.value = ""; // Vaciar cada input de cuotas
            });
            // Inicializar cuotas según total
            updateCuotasFromTotal();
        } else {
            // Otro método
            autorizacionContainer.classList.add("hidden");
            numeroAut.value = "";
            document.getElementById("cuotasContainer").classList.add("hidden");
            // deja el estado del botón a cargo de calcularTotales()
        }

        calcularTotales?.(); // sigue tu flujo
    });
});

// Controles de cuotas
document
    .getElementById("cuotasRepartir")
    ?.addEventListener("click", updateCuotasFromTotal);
document
    .getElementById("cuotasNumero")
    ?.addEventListener("input", updateCuotasFromTotal);

// Si cambia descuento, vuelve a repartir (y validar)
document.getElementById("descuentoModal")?.addEventListener("input", () => {
    calcularTotales?.();
    // repartimos otra vez (igualitario)
    updateCuotasFromTotal();
});

// Observa cambios en el total (por cambios de carrito, descuentos, etc.)
const totalEl = document.getElementById("totalModal");
if (totalEl) {
    const obs = new MutationObserver(() => {
        updateCuotasFromTotal();
    });
    obs.observe(totalEl, {
        childList: true,
        characterData: true,
        subtree: true,
    });
}

document.querySelectorAll('input[name="metodoAbono"]').forEach((radio) => {
    radio.addEventListener("change", function () {
        const autorizacionContainer = document.getElementById(
            "autorizacionContainer"
        );

        if (this.value === "transferencia" || this.value === "cheque") {
            // Mostrar contenedor de autorización cuando se seleccione Transferencia
            autorizacionContainer.classList.remove("hidden");
        } else {
            // Ocultar contenedor de autorización cuando se seleccione Efectivo
            autorizacionContainer.classList.add("hidden");
        }
    });
});




























































// ============================================
// VALIDACIÓN COMPLETA PARA PROCESAR VENTA
// ============================================


const btnProcesarventa = document.getElementById("procesarVentaModal");

btnProcesarventa.addEventListener("click", validarVenta);

function validarVenta() {
    const errores = [];
    const avisos = [];

    // 1. VALIDAR CLIENTE
    const cliente = validarCliente();
    if (!cliente || !cliente.valido) {
        errores.push(cliente?.error || "Error validando cliente");
    }

    // 2. VALIDAR PRODUCTOS EN CARRITO
    const productos = validarProductosCarrito();
    if (!productos.valido) {
        errores.push(...productos.errores);
    }
    if (productos.avisos && productos.avisos.length > 0) {
        avisos.push(...productos.avisos);
    }

    // 3. VALIDAR MÉTODO DE PAGO
    const metodoPago = validarMetodoPago();
    if (!metodoPago.valido) {
        errores.push(...metodoPago.errores);
    }

    // 4. VALIDAR FECHAS Y DATOS GENERALES
    const datosGenerales = validarDatosGenerales();
    if (!datosGenerales.valido) {
        errores.push(...datosGenerales.errores);
    }


    // Mostrar errores si los hay
    if (errores.length > 0) {
        const erroresHtml = errores.map(error =>
            `<li class="text-sm text-red-700 mb-1">• ${error}</li>`
        ).join('');

        Swal.fire({
            title: 'Faltan datos por completar',
            html: `
                <div class="text-left">
                    <p class="text-sm text-gray-600 mb-3">Complete los siguientes datos para proceder:</p>
                    <ul class="max-h-60 overflow-auto">
                        ${erroresHtml}
                    </ul>
                </div>
            `,
            icon: 'warning',
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#f59e0b'
        });
        return;
    }

    // Si todo está válido, procesar
    procesarVentaFinal();

    return {
        // valido: todoValido,
        errores,
        avisos,
    };
}

// ============================================
// VALIDACIONES ESPECÍFICAS
// ============================================

function validarProductosCarrito() {
    const errores = [];
    const avisos = [];

    // Verificar que hay productos
    if (!Array.isArray(carritoProductos) || carritoProductos.length === 0) {
        errores.push("El carrito está vacío. Agregue al menos un producto.");
        return { valido: false, errores, avisos };
    }

    // Validar cada producto
    carritoProductos.forEach((producto, index) => {
        const nombre = producto.nombre || `Producto ${index + 1}`;
        const cantidad = Number(producto.cantidad || 0);
        const stock = Number(producto.stock_cantidad_total || 0);
        const necesitaStock = Number(producto.producto_requiere_stock ?? 1) === 1;
        const requiereSerie = Number(producto.producto_requiere_serie || 0) === 1;
        const tieneLotes = Array.isArray(producto.lotes) && producto.lotes.length > 0;

        // Validar cantidad básica (siempre)
        if (cantidad <= 0) {
            errores.push(`${nombre}: La cantidad debe ser mayor a 0`);
        }

        // Solo validar stock, series y lotes si el producto necesita stock
        if (necesitaStock) {
            // Validar cantidad vs stock
            if (cantidad > stock) {
                errores.push(
                    `${nombre}: Cantidad solicitada (${cantidad}) supera el stock disponible (${stock})`
                );
            }

            // Validar series si las requiere
            if (requiereSerie) {
                const seriesSeleccionadas = Array.isArray(producto.seriesSeleccionadas)
                    ? producto.seriesSeleccionadas
                    : [];

                if (seriesSeleccionadas.length === 0) {
                    errores.push(
                        `${nombre}: Debe seleccionar las series (requiere ${cantidad} serie(s))`
                    );
                } else if (seriesSeleccionadas.length !== cantidad) {
                    errores.push(
                        `${nombre}: Debe seleccionar exactamente ${cantidad} serie(s). Actualmente: ${seriesSeleccionadas.length}`
                    );
                }
            }

            // Validar lotes si los tiene
            if (tieneLotes) {
                const lotesSeleccionados = Array.isArray(producto.lotesSeleccionados)
                    ? producto.lotesSeleccionados
                    : [];

                const totalAsignadoLotes = lotesSeleccionados.reduce(
                    (sum, lote) => sum + Number(lote.cantidad || 0),
                    0
                );

                if (totalAsignadoLotes === 0) {
                    errores.push(
                        `${nombre}: Debe asignar los productos a los lotes disponibles`
                    );
                } else if (totalAsignadoLotes !== cantidad) {
                    errores.push(
                        `${nombre}: La cantidad asignada en lotes (${totalAsignadoLotes}) debe coincidir con la cantidad del producto (${cantidad})`
                    );
                }

                // Verificar que no exceda la cantidad disponible en cada lote
                lotesSeleccionados.forEach((loteSeleccionado) => {
                    const loteOriginal = producto.lotes.find(
                        (l) => Number(l.lote_id) === Number(loteSeleccionado.lote_id)
                    );
                    if (loteOriginal) {
                        const maxLote = Number(loteOriginal.lote_cantidad_total || 0);
                        const cantidadAsignada = Number(loteSeleccionado.cantidad || 0);
                        if (cantidadAsignada > maxLote) {
                            errores.push(
                                `${nombre}: Cantidad asignada al lote ${loteOriginal.lote_codigo} (${cantidadAsignada}) supera lo disponible (${maxLote})`
                            );
                        }
                    }
                });
            }
        }

        // Validar precios (siempre, para todos los productos)
        if (!producto.precio || producto.precio <= 0) {
            errores.push(`${nombre}: Precio inválido`);
        }
    });

    return {
        valido: errores.length === 0,
        errores,
        avisos,
    };
}

function validarCliente() {
    const clienteSelect = document.getElementById("clienteSelect");
    const clienteId = clienteSelect?.value;

    if (!clienteId || clienteId === "") {
        return {
            valido: false,
            error: "Debe seleccionar un cliente",
        };
    }

    return {
        valido: true,
        clienteId: clienteId
    };
}

function validarMetodoPago() {
    const errores = [];
    const metodoPago = document.querySelector('input[name="metodoPago"]:checked')?.value;
    const numeroAutorizacion = document.getElementById("numeroAutorizacion")?.value.trim() || '';
    const selecBanco = document.getElementById("selectBanco")?.value.trim() || '';

    if (!metodoPago) {
        errores.push("Debe seleccionar un método de pago");
        return { valido: false, errores };
    }

    // Definir 'tipoMetodo' antes del switch
    const tipoMetodo = {
        2: "tarjeta de crédito",
        3: "tarjeta de débito",
        4: "transferencia",
        5: "cheque",
    };

    // Validaciones específicas por método de pago
    switch (metodoPago) {
        case "1": // Efectivo
            // No requiere validaciones adicionales
            break;

        case "2": // Tarjeta de crédito
        case "3": // Tarjeta de débito
        case "4": // Transferencia
        case "5": // Cheque
            if (!selecBanco || selecBanco === "") {
                errores.push(
                    `Debe seleccionar el tipo de banco para ${tipoMetodo[metodoPago]}`
                );
            }

            if (!numeroAutorizacion) {
                errores.push(
                    `Debe ingresar el número de autorización para ${tipoMetodo[metodoPago]}`
                );
            }
            break;

        case "6": // Pagos/Cuotas
            // Verificar si el abono es por transferencia
            const esTransferencia = document.querySelector(
                'input[name="metodoAbono"][value="transferencia"]:checked'
            );

            if (esTransferencia) {
                if (!selecBanco || selecBanco === "") {
                    errores.push(
                        `Debe seleccionar el tipo de banco para el abono por transferencia`
                    );
                }

                if (!numeroAutorizacion) {
                    errores.push(
                        `Debe ingresar el número de autorización de la transferencia`
                    );
                }
            }

            const erroresCuotas = validarCuotas();

            if (erroresCuotas.length > 0) {
                errores.push(...erroresCuotas);
            }

            break;

        default:
            errores.push("Método de pago no válido");
    }

    return {
        valido: errores.length === 0,
        errores,
    };
}


function validarDatosGenerales() {
    const errores = [];

    // Validar fecha
    const fecha = document.getElementById("fechaVenta")?.value;
    if (!fecha) {
        errores.push("Debe seleccionar una fecha para la venta");
    }

    // Validar que el total sea mayor a 0
    const total = getTotalVenta();
    if (total <= 0) {
        errores.push("El total de la venta debe ser mayor a 0");
    }

    return {
        valido: errores.length === 0,
        errores
    };
}

// Repartir según total y cantidad actual
function updateCuotasFromTotal() {
    const cont = document.getElementById("cuotasContainer");
    if (!cont || cont.classList.contains("hidden")) return; // solo si método 6

    const total = getTotalVenta();

    // Deducción de Saldo a Favor
    const checkSaldo = document.getElementById('checkSaldoFavor');
    let saldoFavor = 0;
    if (checkSaldo && checkSaldo.checked) {
        saldoFavor = parseFloat(checkSaldo.dataset.saldoDisponible || 0);
        // No podemos usar más saldo del total
        if (saldoFavor > total) saldoFavor = total;
    }

    // Restar abono del total (después de restar saldo a favor)
    const abono = Math.min(
        total - saldoFavor,
        Math.max(0, Number(document.getElementById("abonoInicial")?.value || 0))
    );
    const saldo = Math.max(0, total - saldoFavor - abono);

    // Si hay abono o saldo favor, permite 1 cuota; si no, mínimo 2
    const minCuotas = (abono > 0 || saldoFavor > 0) ? 1 : 2;
    const n = Math.max(minCuotas, Math.min(36, Number(document.getElementById("cuotasNumero")?.value || 2)));

    // (opcional) mostrar saldo si tienes #saldoCuotas en el HTML
    const saldoEl = document.getElementById("saldoCuotas");
    if (saldoEl) saldoEl.textContent = `Q${saldo.toFixed(2)}`;

    // Repartir SOLO el saldo
    renderCuotas(repartirEnCuotas(saldo, n));
}

// ============================================
// FUNCIÓN PARA PROCESAR LA VENTA FINAL
// ============================================

async function procesarVentaFinal() {
    try {
        // Mostrar loading
        Swal.fire({
            title: 'Procesando venta...',
            text: 'Por favor espere',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // 1. DATOS GENERALES DE LA VENTA
        const clienteId = document.getElementById("clienteSelect").value;
        const fechaVenta = document.getElementById("fechaVenta").value;
        const metodoPago = document.querySelector('input[name="metodoPago"]:checked').value;
        const descuento = parseFloat(document.getElementById("descuentoModal").value) || 0;

        // Obtener empresa seleccionada si existe
        const empresaSelect = document.getElementById("empresaSelect");
        const empresaId = (empresaSelect && !empresaSelect.closest('.hidden')) ? empresaSelect.value : null;

        // Calcular totales
        const subtotal = carritoProductos.reduce((sum, p) => sum + (p.precio * p.cantidad), 0);
        // ('carritosproducotos',carritoProductos)


        // ✅ NUEVO: Calcular tenencia
        const totalTenencia = carritoProductos.reduce((sum, p) => {
            if (p.seriesConTenencia) {
                const numTenencias = Object.keys(p.seriesConTenencia).length;
                return sum + (numTenencias * 60);
            }
            return sum;
        }, 0);

        const descuentoMonto = subtotal * (descuento / 100);
        const total = subtotal - descuentoMonto + totalTenencia; // ✅ SUMAR TENENCIA

        // Saldo a Favor
        const checkSaldo = document.getElementById('checkSaldoFavor');
        let saldoFavorUsado = 0;
        if (checkSaldo && checkSaldo.checked) {
            const disponible = parseFloat(checkSaldo.dataset.saldoDisponible || 0);
            saldoFavorUsado = Math.min(disponible, total);
        }

        // 2. DATOS DE VENTA
        const datosVenta = {
            // Información general
            cliente_id: clienteId,
            fecha_venta: fechaVenta,
            subtotal: subtotal.toFixed(2),
            descuento_porcentaje: descuento,
            descuento_monto: descuentoMonto.toFixed(2),
            total: total.toFixed(2),
            empresa_id: empresaId,

            // Saldo a favor
            saldo_favor_usado: saldoFavorUsado.toFixed(2),

            // Método de pago
            metodo_pago: metodoPago,

            // Productos del carrito
            productos: carritoProductos.map(producto => ({
                producto_id: producto.producto_id,
                cantidad: producto.cantidad,
                precio_unitario: producto.precio,
                subtotal_producto: (producto.precio * producto.cantidad).toFixed(2),

                // Indica si el producto requiere validación de stock
                producto_requiere_stock: producto.producto_requiere_stock,

                // Series si las requiere
                requiere_serie: producto.producto_requiere_serie || 0,
                series_seleccionadas: producto.seriesSeleccionadas || [],

                // Lotes si los tiene
                tiene_lotes: (Array.isArray(producto.lotes) && producto.lotes.length > 0),
                lotes_seleccionados: producto.lotesSeleccionados || [],
                series_con_tenencia: producto.seriesConTenencia || {}  // ✅ NUEVO
            }))
        };

        // 3. DATOS ESPECÍFICOS SEGÚN MÉTODO DE PAGO
        switch (metodoPago) {
            case "1": // Efectivo
                datosVenta.pago = {
                    tipo: "efectivo",
                    monto: total.toFixed(2)
                };
                break;

            case "2": // Tarjeta de crédito
            case "3": // Tarjeta de débito  
            case "4": // Transferencia
            case "5": // Cheque
                const numeroAutorizacion = document.getElementById("numeroAutorizacion").value.trim();
                const bancoId = document.getElementById("selectBanco").value;

                datosVenta.pago = {
                    tipo: metodoPago === "2" ? "tarjeta_credito" :
                        metodoPago === "3" ? "tarjeta_debito" :
                            metodoPago === "4" ? "transferencia" : "cheque",
                    monto: total.toFixed(2),
                    numero_autorizacion: numeroAutorizacion,
                    banco_id: bancoId
                };
                break;

            case "6": // Pagos/Cuotas
                const abonoInicial = parseFloat(document.getElementById("abonoInicial").value) || 0;
                const metodoAbono = document.querySelector('input[name="metodoAbono"]:checked')?.value || "efectivo";

                // Recopilar cuotas
                const cuotasInputs = document.querySelectorAll("#cuotasLista .cuota-input");
                const cuotas = Array.from(cuotasInputs).map((input, index) => ({
                    numero_cuota: index + 1,
                    monto: parseFloat(input.value) || 0,
                    fecha_vencimiento: null // Se calculará en el backend
                }));

                datosVenta.pago = {
                    tipo: "cuotas",
                    abono_inicial: abonoInicial.toFixed(2),
                    metodo_abono: metodoAbono,
                    total_cuotas: cuotas.reduce((sum, c) => sum + c.monto, 0).toFixed(2),
                    cantidad_cuotas: cuotas.length,
                    cuotas: cuotas
                };

                // Si el abono es por transferencia, agregar datos bancarios
                if (metodoAbono === "transferencia") {
                    const numeroAutorizacionAbono = document.getElementById("numeroAutorizacion").value.trim();
                    const bancoIdAbono = document.getElementById("selectBanco").value;

                    datosVenta.pago.numero_autorizacion_abono = numeroAutorizacionAbono;
                    datosVenta.pago.banco_id_abono = bancoIdAbono;
                }
                // Si el abono es por transferencia, agregar datos bancarios
                if (metodoAbono === "cheque") {
                    const numeroAutorizacionAbono = document.getElementById("numeroAutorizacion").value.trim();
                    const bancoIdAbono = document.getElementById("selectBanco").value;

                    datosVenta.pago.numero_autorizacion_abono = numeroAutorizacionAbono;
                    datosVenta.pago.banco_id_abono = bancoIdAbono;
                }
                break;

        }

        ('Datos de venta a enviar:', datosVenta);

        // 4. ENVIAR AL CONTROLADOR
        const response = await fetch('/api/ventas/procesar-venta', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            body: JSON.stringify(datosVenta)
        });

        const resultado = await response.json();
        ('resultado proceso de venta: ', resultado);


        if (response.ok && resultado.success) {
            // Éxito
            await Swal.fire({
                title: '¡Venta procesada!',
                text: `Venta registrada exitosamente. Folio: ${resultado.folio || 'N/A'}`,
                icon: 'success',
                confirmButtonText: 'Continuar'
            });

            // Limpiar formulario
            limpiarFormularioVenta();
            buscarProductos();

            // Opcional: imprimir ticket o redirigir
            if (resultado.venta_id) {
                const imprimirTicket = await Swal.fire({
                    title: '¿Desea imprimir el comprobante?',
                    text: "Podrás imprimirlo después desde el historial",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fas fa-print"></i> Sí, imprimir',
                    cancelButtonText: 'No, gracias',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                });

                if (imprimirTicket.isConfirmed) {
                    window.open(`/reportes/ventas/${resultado.venta_id}/imprimir`, '_blank');
                }
            }

        } else {
            // Error del servidor
            throw new Error(resultado.message || 'Error procesando la venta');
        }

    } catch (error) {
        console.error('Error procesando venta:', error);

        await Swal.fire({
            title: 'Error',
            text: error.message || 'Ocurrió un error al procesar la venta',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    }
}

function limpiarFormularioVenta() {
    // Limpiar carrito
    carritoProductos = [];
    actualizarVistaCarrito();
    actualizarContadorCarrito();

    // Resetear cliente
    document.getElementById("clienteSelect").value = "";

    // Resetear método de pago
    document.querySelectorAll('input[name="metodoPago"]').forEach(radio => {
        radio.checked = false;
    });

    // Ocultar contenedores específicos
    document.getElementById("autorizacionContainer").classList.add("hidden");
    document.getElementById("cuotasContainer").classList.add("hidden");

    // Limpiar campos
    document.getElementById("numeroAutorizacion").value = "";
    document.getElementById("selectBanco").value = "";
    document.getElementById("selectBanco").value = "";
    document.getElementById("abonoInicial").value = "";
    document.getElementById("descuentoModal").value = "";

    // Limpiar y ocultar empresa
    document.getElementById("empresaSelect").innerHTML = '<option value="">Seleccionar empresa...</option>';
    document.getElementById("divEmpresaSelect").classList.add("hidden");

    // Limpiar cuotas
    document.getElementById("cuotasLista").innerHTML = "";

    // Cerrar modal de carrito
    cerrarCarrito();

    // Resetear fecha a actual
    const ahora = new Date();
    const fechaHoraLocal = new Date(ahora.getTime() - (ahora.getTimezoneOffset() * 60000))
        .toISOString()
        .slice(0, 16);
    document.getElementById("fechaVenta").value = fechaHoraLocal;

    // Recalcular totales
    calcularTotales();
}




document.addEventListener('DOMContentLoaded', function () {
    const checkbox = document.getElementById('checkRequiereDocumentacion');
    const modal = document.getElementById('modalDocumentacion');
    const btnCerrarModal = document.getElementById('btnCerrarModalDocumentacion'); // si usas ID

    // Mostrar/ocultar modal al marcar el checkbox
    checkbox.addEventListener('change', function () {
        if (this.checked) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            cerrarModalDocumentacion(); // También cerrar si desmarcan
        }
    });

    // Si el botón de cerrar tiene ID o clase, usa esto:
    if (btnCerrarModal) {
        btnCerrarModal.addEventListener('click', cerrarModalDocumentacion);
    }

    // Opcional: cerrar al hacer clic fuera del contenido
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            cerrarModalDocumentacion();
        }
    });
});

// Función para cerrar el modal
function cerrarModalDocumentacion() {
    const modal = document.getElementById('modalDocumentacion');
    const checkbox = document.getElementById('checkRequiereDocumentacion');
    document.getElementById("tipoDocumentoSelect").value = '';
    document.getElementById("numeroDocumentoInput").value = '';

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    checkbox.checked = false;
}

// Función para agregar documento
function agregarDocumentoVenta() {
    const tipo = document.getElementById("tipoDocumentoSelect").value;
    const numero = document.getElementById("numeroDocumentoInput").value;

    if (!tipo || !numero) {
        alert("Por favor completa todos los campos.");
        return;
    }

    // Aquí puedes enviar a un array, backend o agregar a una lista visual
    ("Documento agregado:", tipo, numero);

    cerrarModalDocumentacion();
}

function editarCajasMunicion(producto_id) {
    const id = String(producto_id);
    const producto = carritoProductos.find((p) => String(p.producto_id) === id);
    const original = productosGlobales.find((p) => String(p.producto_id) === id);

    if (!producto || !original) return;

    Swal.fire({
        title: '<strong>Editar Cajas de Munición</strong>',
        html: `
            <div class="text-left">
                <div class="mb-4 text-sm text-gray-600">
                    Producto: <b>${producto.nombre}</b><br>
                    Stock disponible: <b>${original.stock_cantidad_total}</b>
                </div>

                <div class="space-y-4 pl-2 border-l-2 border-orange-100 ml-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Presentación (unidades por caja)</label>
                        <select id="swal-edit-presentacion" class="w-full border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                            <option value="5">Caja de 5</option>
                            <option value="10">Caja de 10</option>
                            <option value="20">Caja de 20</option>
                            <option value="25">Caja de 25</option>
                            <option value="50">Caja de 50</option>
                            <option value="100">Caja de 100</option>
                            <option value="200">Caja de 200</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número de cajas</label>
                        <input type="number" id="swal-edit-cajas" value="1" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200">
                    </div>

                    <div class="bg-orange-50 p-3 rounded-md border border-orange-100">
                        <p class="text-sm text-orange-800 flex justify-between items-center">
                            <span>Total unidades:</span>
                            <strong id="swal-edit-total" class="text-lg text-orange-700">0</strong>
                        </p>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Actualizar Cantidad',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f97316', // orange-500
        didOpen: () => {
            const select = document.getElementById('swal-edit-presentacion');
            const input = document.getElementById('swal-edit-cajas');
            const totalDisplay = document.getElementById('swal-edit-total');

            // Intentar adivinar la presentación actual si es posible (opcional)
            // Por ahora default a 1 caja y calculamos

            const updateTotal = () => {
                const pres = parseInt(select.value) || 0;
                const cajas = parseInt(input.value) || 0;
                totalDisplay.textContent = pres * cajas;
            };

            select.addEventListener('change', updateTotal);
            input.addEventListener('input', updateTotal);

            updateTotal();
        },
        preConfirm: () => {
            const pres = parseInt(document.getElementById('swal-edit-presentacion').value);
            const cajas = parseInt(document.getElementById('swal-edit-cajas').value);
            const total = pres * cajas;

            if (isNaN(total) || total <= 0) {
                Swal.showValidationMessage('La cantidad total debe ser mayor a 0');
                return false;
            }

            // Validar stock
            const stock = Number(original.stock_cantidad_total ?? 0);
            const necesitaStock = Number(original.producto_requiere_stock ?? 1) === 1;

            if (necesitaStock && total > stock) {
                Swal.showValidationMessage(`No hay suficiente stock. Disponible: ${stock}, Solicitado: ${total}`);
                return false;
            }

            return { cantidad: total };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Actualizar cantidad en el carrito
            producto.cantidad = result.value.cantidad;

            // Si requiere series, limpiar selección si la cantidad cambia (para obligar a re-seleccionar)
            if (producto.producto_requiere_serie === 1) {
                if (producto.seriesSeleccionadas.length > producto.cantidad) {
                    producto.seriesSeleccionadas = []; // O recortar
                    mostrarNotificacion("Cantidad cambiada. Por favor verifique las series.", "info");
                }
            }

            actualizarVistaCarrito();
            actualizarContadorCarrito();
            mostrarNotificacion("Cantidad actualizada correctamente", "success");
        }
    });
}
