import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import Swal from 'sweetalert2';

const FormFactura = document.getElementById("formFactura");
const btnGuardarFactura = document.getElementById("btnGuardarFactura");
const btnBuscarNit = document.getElementById("btnBuscarNit");
const btnAgregarItem = document.getElementById("btnAgregarItem");
const btnFiltrarFacturas = document.getElementById("btnFiltrarFacturas");
const contenedorItems = document.getElementById("contenedorItems");
const templateItem = document.getElementById("templateItem");
const busquedaVenta = document.getElementById("busquedaVenta");
const btnBuscarVenta = document.getElementById("btnBuscarVenta");
const resultadosVenta = document.getElementById("resultadosVenta");
const ventaSeleccionadaInfo = document.getElementById("ventaSeleccionadaInfo");
const btnQuitarVenta = document.getElementById("btnQuitarVenta");
const facVentaId = document.getElementById("fac_venta_id");

// CUI y dirección factura normal
const cuiInput = document.getElementById('fac_cui_receptor');
const direccionInput = document.getElementById('fac_receptor_direccion');
const btnBuscarCui = document.getElementById('btnBuscarCui');

const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

// --- helpers factura normal ---
const nombreInput = document.getElementById('fac_receptor_nombre');
const nitInput = document.getElementById('fac_nit_receptor');

// ====== ELEMENTOS FACTURA CAMBIARIA ======
const FormFacturaCambiaria = document.getElementById('formFacturaCambiaria');
const btnGuardarFacturaCambiaria = document.getElementById('btnGuardarFacturaCambiaria');
const btnAgregarItemCambiaria = document.getElementById('btnAgregarItemCambiaria');
const contenedorItemsCambiaria = document.getElementById('contenedorItemsCambiaria');
const templateItemCambiaria = document.getElementById('templateItemCambiaria');

const nitCamInput = document.getElementById('fac_cam_nit_receptor');
const cuiCamInput = document.getElementById('fac_cam_cui_receptor');
const nombreCamInput = document.getElementById('fac_cam_receptor_nombre');
const direccionCamInput = document.getElementById('fac_cam_receptor_direccion');

const btnBuscarNitCambiaria = document.getElementById('btnBuscarNitCambiaria');
const btnBuscarCuiCambiaria = document.getElementById('btnBuscarCuiCambiaria');

const subtotalFacturaCambiariaEl = document.getElementById('subtotalFacturaCambiaria');
const descuentoFacturaCambiariaEl = document.getElementById('descuentoFacturaCambiaria');
const ivaFacturaCambiariaEl = document.getElementById('ivaFacturaCambiaria');
const totalFacturaCambiariaEl = document.getElementById('totalFacturaCambiaria');

// =============================
// HELPERS GENERALES
// =============================
const toNumber = (v) => {
    if (v === null || v === undefined) return 0;
    const n = parseFloat(String(v).replace(/,/g, ''));
    return isNaN(n) ? 0 : n;
};

const q = (root, sel) => root.querySelector(sel);

const setBtnLoading = (btn, loading) => {
    if (!btn) return;
    if (loading) {
        btn.dataset._oldHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
      <span class="inline-flex items-center gap-2">
        <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
        </svg>
        Consultando...
      </span>`;
    } else {
        btn.disabled = false;
        if (btn.dataset._oldHtml) btn.innerHTML = btn.dataset._oldHtml;
        delete btn.dataset._oldHtml;
    }
};

const debounce = (fn, ms = 400) => {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
};

const isNitFormatoValido = (nit) => {
    if (!nit) return false;
    if (/^cf$/i.test(nit)) return true;
    return /^[0-9-]{3,20}$/.test(nit);
};

const isCuiFormatoValido = (cui) => {
    if (!cui) return false;
    return /^[0-9]{4,20}$/.test(cui);
};

// =============================
// MODALES (abrir/cerrar)
// =============================
const abrirModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
};

const cerrarModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

// ===== MODAL FACTURA NORMAL =====
document.getElementById("btnAbrirModalFactura")?.addEventListener("click", () => {
    abrirModal("modalFactura");

    if (contenedorItems.querySelectorAll('.item-factura').length === 0) {
        agregarItem();
    }
    recalcularTotales();
});

document.querySelectorAll('[data-modal-close="modalFactura"]').forEach(btn => {
    btn.addEventListener("click", () => {
        cerrarModal("modalFactura");
    });
});

// =============================
// FACTURA CAMBIARIA - RESET MODAL
// =============================
const resetModalFacturaCambiaria = () => {
    if (!FormFacturaCambiaria) return;

    FormFacturaCambiaria.reset();

    if (contenedorItemsCambiaria) {
        contenedorItemsCambiaria.innerHTML = '';
    }

    subtotalFacturaCambiariaEl.textContent = 'Q 0.00';
    descuentoFacturaCambiariaEl.textContent = 'Q 0.00';
    ivaFacturaCambiariaEl.textContent = 'Q 0.00';
    totalFacturaCambiariaEl.textContent = 'Q 0.00';

    if (nombreCamInput) {
        nombreCamInput.value = '';
        nombreCamInput.readOnly = true;
        nombreCamInput.classList.add('cursor-not-allowed', 'bg-gray-100');
    }
    if (direccionCamInput) {
        direccionCamInput.value = '';
        direccionCamInput.readOnly = true;
        direccionCamInput.classList.add('cursor-not-allowed', 'bg-gray-100');
    }

    // Reset venta seleccionada
    const facVentaIdCam = document.getElementById('fac_venta_id_cambiaria');
    const infoCam = document.getElementById('ventaSeleccionadaInfoCambiaria');
    const resCam = document.getElementById('resultadosVentaCambiaria');
    const busqCam = document.getElementById('busquedaVentaCambiaria');

    if (facVentaIdCam) facVentaIdCam.value = '';
    if (infoCam) infoCam.classList.add('hidden');
    if (resCam) {
        resCam.classList.add('hidden');
        resCam.innerHTML = '';
    }
    if (busqCam) busqCam.value = '';
};

// ===== MODAL FACTURA CAMBIARIA (abrir / cerrar) =====
const btnAbrirModalFacturaCambiaria = document.getElementById("btnAbrirModalFacturaCambiaria");

btnAbrirModalFacturaCambiaria?.addEventListener("click", () => {
    resetModalFacturaCambiaria();
    abrirModal("modalFacturaCambiaria");
    agregarItemCambiaria();       // <-- SOLO agrega 1 item
    recalcularTotalesCambiaria();
});

document.querySelectorAll('[data-modal-close="modalFacturaCambiaria"]').forEach(btn => {
    btn.addEventListener("click", () => {
        cerrarModal("modalFacturaCambiaria");
        resetModalFacturaCambiaria();
    });
});

// =============================
// FACTURA CAMBIARIA - BUSCAR VENTA
// =============================
const busquedaVentaCambiaria = document.getElementById('busquedaVentaCambiaria');
const btnBuscarVentaCambiaria = document.getElementById('btnBuscarVentaCambiaria');
const resultadosVentaCambiaria = document.getElementById('resultadosVentaCambiaria');
const ventaSeleccionadaInfoCambiaria = document.getElementById('ventaSeleccionadaInfoCambiaria');
const btnQuitarVentaCambiaria = document.getElementById('btnQuitarVentaCambiaria');
const facVentaIdCambiaria = document.getElementById('fac_venta_id_cambiaria');

const buscarVentaCambiaria = async () => {
    const q = busquedaVentaCambiaria.value.trim();
    if (q.length < 2) return;

    setBtnLoading(btnBuscarVentaCambiaria, true);
    resultadosVentaCambiaria.innerHTML = '';
    resultadosVentaCambiaria.classList.remove('hidden');

    try {
        const res = await fetch(`/facturacion/buscar-venta?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        if (data.codigo === 1 && data.data.length > 0) {
            data.data.forEach(venta => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-sm';
                div.innerHTML = `
                    <div class="font-bold text-blue-800">Venta #${venta.ven_id} - ${venta.ven_fecha}</div>
                    <div class="text-gray-600">${venta.cliente_nombre1} ${venta.cliente_apellido1} (${venta.cliente_nit})</div>
                    <div class="text-xs text-gray-500">Total: Q ${venta.ven_total_vendido}</div>
                `;
                div.addEventListener('click', () => seleccionarVentaCambiaria(venta));
                resultadosVentaCambiaria.appendChild(div);
            });
        } else {
            resultadosVentaCambiaria.innerHTML = '<div class="p-2 text-gray-500 text-sm">No se encontraron ventas pendientes.</div>';
        }
    } catch (err) {
        console.error(err);
        resultadosVentaCambiaria.innerHTML = '<div class="p-2 text-red-500 text-sm">Error al buscar ventas.</div>';
    } finally {
        setBtnLoading(btnBuscarVentaCambiaria, false);
    }
};

const seleccionarVentaCambiaria = (venta) => {
    // Llenar datos cliente
    nitCamInput.value = venta.cliente_nit || 'CF';
    nombreCamInput.value = `${venta.cliente_nombre1} ${venta.cliente_apellido1}`;
    direccionCamInput.value = venta.cliente_direccion || '';

    // Habilitar campos nombre/direccion por si acaso
    nombreCamInput.readOnly = false;
    nombreCamInput.classList.remove('cursor-not-allowed', 'bg-gray-100');
    direccionCamInput.readOnly = false;
    direccionCamInput.classList.remove('cursor-not-allowed', 'bg-gray-100');

    // Llenar info venta seleccionada
    facVentaIdCambiaria.value = venta.ven_id;
    document.getElementById('lblVentaIdCambiaria').textContent = venta.ven_id;
    document.getElementById('lblClienteCambiaria').textContent = `${venta.cliente_nombre1} ${venta.cliente_apellido1}`;

    ventaSeleccionadaInfoCambiaria.classList.remove('hidden');
    resultadosVentaCambiaria.classList.add('hidden');
    busquedaVentaCambiaria.value = '';

    // Llenar items
    contenedorItemsCambiaria.innerHTML = '';
    if (venta.detalles && venta.detalles.length > 0) {
        venta.detalles.forEach(det => {
            if (det.series && det.series.length > 0) {
                // Calcular descuento unitario
                const descuentoTotal = parseFloat(det.det_descuento || 0);
                const cantidadTotal = parseFloat(det.det_cantidad || 1);
                const descuentoUnitario = cantidadTotal > 0 ? (descuentoTotal / cantidadTotal) : 0;

                // Agregar una línea por cada serie
                det.series.forEach(serie => {
                    agregarItemCambiaria({
                        descripcion: `${det.producto_nombre} (Serie: ${serie})`,
                        cantidad: 1,
                        precio: det.det_precio,
                        descuento: descuentoUnitario.toFixed(2),
                        producto_id: det.det_producto_id
                    });
                });

                // Si hay cantidad sobrante sin serie
                const sobrante = cantidadTotal - det.series.length;
                if (sobrante > 0) {
                    agregarItemCambiaria({
                        descripcion: det.producto_nombre,
                        cantidad: sobrante,
                        precio: det.det_precio,
                        descuento: (descuentoUnitario * sobrante).toFixed(2),
                        producto_id: det.det_producto_id
                    });
                }
            } else {
                // Producto normal sin series
                agregarItemCambiaria({
                    descripcion: det.producto_nombre,
                    cantidad: det.det_cantidad,
                    precio: det.det_precio,
                    descuento: det.det_descuento,
                    producto_id: det.det_producto_id
                });
            }
        });
    }
    recalcularTotalesCambiaria();
};

btnBuscarVentaCambiaria?.addEventListener('click', buscarVentaCambiaria);
busquedaVentaCambiaria?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        buscarVentaCambiaria();
    }
});

// Event Listeners Globales - Limpieza de duplicados
// (Los listeners correctos ya están definidos más abajo o arriba)

// Auto-load venta from URL
const urlParams = new URLSearchParams(window.location.search);
const ventaIdParam = urlParams.get('venta_id');
const modeParam = urlParams.get('mode'); // 'normal' or 'cambiaria'

if (ventaIdParam) {
    if (modeParam === 'cambiaria') {
        // Assuming abrirModalFacturaCambiaria is a function that opens the modal and prepares it
        // If not, you might need to call resetModalFacturaCambiaria() and abrirModal("modalFacturaCambiaria")
        resetModalFacturaCambiaria();
        abrirModal("modalFacturaCambiaria");
        // Esperar un poco para asegurar que el modal esté listo
        setTimeout(() => {
            const inputBusqueda = document.getElementById('busquedaVentaCambiaria'); // Correct ID
            if (inputBusqueda) {
                inputBusqueda.value = ventaIdParam;
                buscarVentaCambiaria();
            }
        }, 300);
    } else {
        // Assuming abrirModalNuevaFactura is a function that opens the modal and prepares it
        // If not, you might need to call resetModalFactura() and abrirModal("modalFactura")
        // Note: The original code snippet provided 'abrirModalNuevaFactura' which is not defined in the given context.
        // Assuming it's equivalent to opening the 'modalFactura' and preparing it.
        // For this change, I'll use the existing 'abrirModal' for 'modalFactura'
        abrirModal("modalFactura");
        setTimeout(() => {
            // Assuming 'busquedaVenta' is the input for the normal factura modal
            const inputBusqueda = document.getElementById('busquedaVenta'); // Assuming this ID exists for normal factura
            if (inputBusqueda) {
                inputBusqueda.value = ventaIdParam;
                // Assuming 'buscarVenta' is the function for the normal factura modal
                buscarVenta();
            }
        }, 300);
    }
    // Limpiar URL
    window.history.replaceState({}, document.title, window.location.pathname);
}

btnQuitarVentaCambiaria?.addEventListener('click', () => {
    facVentaIdCambiaria.value = '';
    ventaSeleccionadaInfoCambiaria.classList.add('hidden');
    contenedorItemsCambiaria.innerHTML = '';
    agregarItemCambiaria(); // Agregar uno vacío
    FormFacturaCambiaria.reset();
    recalcularTotalesCambiaria();
});

// =============================
// FACTURA CAMBIARIA - NIT / CUI
// =============================
const BuscarNITCambiaria = async () => {
    const nit = nitCamInput?.value?.trim() ?? '';
    if (!nit) return;

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    if (!isNitFormatoValido(nit)) {
        nitCamInput?.classList.remove('border-emerald-400');
        nitCamInput?.classList.add('border-red-400');
        Swal.fire({ icon: 'warning', title: 'NIT inválido', text: 'Escribe un NIT válido o CF.' });
        return;
    } else {
        nitCamInput?.classList.remove('border-red-400');
        nitCamInput?.classList.add('border-emerald-400');
    }

    if (/^cf$/i.test(nit)) {
        nombreCamInput.value = 'CONSUMIDOR FINAL';
        return;
    }

    setBtnLoading(btnBuscarNitCambiaria, true);
    nombreCamInput.value = 'Consultando...';

    const body = new FormData();
    body.append('nit', nit);

    try {
        const res = await fetch('/facturacion/buscarNit', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body
        });

        const data = await res.json();

        if (!res.ok || data?.codigo !== 1) {
            nombreCamInput.value = '';
            Swal.fire({
                icon: 'error',
                title: 'No se pudo consultar NIT',
                text: data?.mensaje || 'Intente nuevamente.',
            });
            return;
        }

        nombreCamInput.value = data?.nombre || 'No encontrado';
    } catch (err) {
        console.error(err);
        nombreCamInput.value = '';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'Ocurrió un error inesperado al consultar NIT',
        });
    } finally {
        setBtnLoading(btnBuscarNitCambiaria, false);
    }
};

btnBuscarNitCambiaria?.addEventListener('click', (e) => {
    e.preventDefault();
    BuscarNITCambiaria();
});

nitCamInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        BuscarNITCambiaria();
    }
});

const BuscarCUICambiaria = async () => {
    const cui = cuiCamInput?.value?.trim() ?? '';
    if (!cui) return;

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    if (!isCuiFormatoValido(cui)) {
        cuiCamInput?.classList.remove('border-emerald-400');
        cuiCamInput?.classList.add('border-red-400');
        Swal.fire({ icon: 'warning', title: 'CUI inválido', text: 'Debe ser un CUI de 13 dígitos.' });
        return;
    } else {
        cuiCamInput?.classList.remove('border-red-400');
        cuiCamInput?.classList.add('border-emerald-400');
    }

    setBtnLoading(btnBuscarCuiCambiaria, true);
    nombreCamInput.value = 'Consultando...';

    const body = new FormData();
    body.append('cui', cui);

    try {
        const res = await fetch('/facturacion/buscarCui', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body
        });

        const data = await res.json();

        if (data?.codigo === 1) {
            nombreCamInput.value = data?.nombre || '';
            if (data?.requiereManual) {
                Swal.fire({
                    icon: 'info',
                    title: 'Nombre manual',
                    text: 'El sistema FEL indica que debes ingresar el nombre manualmente.'
                });
                nombreCamInput.removeAttribute('readonly');
                nombreCamInput.classList.remove('cursor-not-allowed', 'bg-gray-100');
            }
        } else if (data?.Resultado === true) {
            const nombreFEL = data?.Nombre || '';
            if (nombreFEL && nombreFEL !== 'Ingrese nombre manualmente') {
                nombreCamInput.value = nombreFEL;
            } else {
                nombreCamInput.value = '';
                Swal.fire({
                    icon: 'info',
                    title: 'Nombre manual',
                    text: 'El sistema FEL indica que debes ingresar el nombre manualmente.'
                });
                nombreCamInput.removeAttribute('readonly');
                nombreCamInput.classList.remove('cursor-not-allowed', 'bg-gray-100');
            }
        } else {
            nombreCamInput.value = '';
            Swal.fire({
                icon: 'error',
                title: 'No se pudo consultar CUI',
                text: data?.mensaje || 'Intente nuevamente.',
            });
        }
    } catch (err) {
        console.error(err);
        nombreCamInput.value = '';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message || 'Ocurrió un error inesperado al consultar CUI',
        });
    } finally {
        setBtnLoading(btnBuscarCuiCambiaria, false);
    }
};

btnBuscarCuiCambiaria?.addEventListener('click', (e) => {
    e.preventDefault();
    BuscarCUICambiaria();
});

cuiCamInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        BuscarCUICambiaria();
    }
});

// =============================
// FACTURA CAMBIARIA - ITEMS
// =============================
const bindItemEventsCambiaria = (itemEl) => {
    const $cant = q(itemEl, '.cam-item-cantidad');
    const $prec = q(itemEl, '.cam-item-precio');
    const $desc = q(itemEl, '.cam-item-descuento');
    const $del = q(itemEl, '.btn-eliminar-item-cam');

    if (!$cant || !$prec || !$desc || !$del) {
        console.warn('Faltan elementos dentro del item cambiaria', { $cant, $prec, $desc, $del });
        return;
    }

    const onKey = () => calcularItemCambiaria(itemEl);

    $cant.addEventListener('input', onKey);
    $prec.addEventListener('input', onKey);
    $desc.addEventListener('input', onKey);

    $del.addEventListener('click', () => {
        itemEl.remove();
        recalcularTotalesCambiaria();
    });
};

const agregarItemCambiaria = (prefill = {}) => {
    if (!templateItemCambiaria || !contenedorItemsCambiaria) return;

    const tpl = templateItemCambiaria.content.firstElementChild;
    const nodo = tpl.cloneNode(true);

    if (prefill.descripcion) {
        q(nodo, 'input[name="det_fac_producto_desc[]"]').value = prefill.descripcion;
    }
    if (prefill.producto_id) {
        q(nodo, 'input[name="det_fac_producto_id[]"]').value = prefill.producto_id;
    }
    if (typeof prefill.cantidad !== 'undefined') q(nodo, '.cam-item-cantidad').value = prefill.cantidad;
    if (typeof prefill.precio !== 'undefined') q(nodo, '.cam-item-precio').value = prefill.precio;
    if (typeof prefill.descuento !== 'undefined') q(nodo, '.cam-item-descuento').value = prefill.descuento;

    contenedorItemsCambiaria.appendChild(nodo);
    bindItemEventsCambiaria(nodo);
    calcularItemCambiaria(nodo);
};



btnAgregarItemCambiaria?.addEventListener('click', () => agregarItemCambiaria());

const calcularItemCambiaria = (itemEl) => {
    const $cant = itemEl.querySelector('.cam-item-cantidad');
    const $prec = itemEl.querySelector('.cam-item-precio');
    const $desc = itemEl.querySelector('.cam-item-descuento');
    const $total = itemEl.querySelector('.cam-item-total');

    if (!$cant || !$prec || !$desc || !$total) {
        console.warn('Faltan inputs en item cambiaria (calcularItemCambiaria)');
        return;
    }

    const cantidad = Math.max(0, toNumber($cant.value));
    const precio = Math.max(0, toNumber($prec.value));
    const descuento = Math.max(0, toNumber($desc.value));

    let importeBruto = (cantidad * precio) - descuento;
    if (importeBruto < 0) importeBruto = 0;

    $total.value = importeBruto.toFixed(2);

    recalcularTotalesCambiaria();
};

const recalcularTotalesCambiaria = () => {
    const items = contenedorItemsCambiaria?.querySelectorAll('.item-factura-cambiaria') || [];

    let subtotalNeto = 0;
    let ivaAcum = 0;
    let descuentoAcum = 0;

    items.forEach((item) => {
        const $cant = item.querySelector('.cam-item-cantidad');
        const $prec = item.querySelector('.cam-item-precio');
        const $desc = item.querySelector('.cam-item-descuento');

        if (!$cant || !$prec || !$desc) return;

        const cant = Math.max(0, toNumber($cant.value));
        const prec = Math.max(0, toNumber($prec.value));
        const desc = Math.max(0, toNumber($desc.value));

        const bruto = Math.max(0, (cant * prec) - desc);

        // Match backend rounding logic (2 decimals per item)
        const base = parseFloat((bruto / 1.12).toFixed(2));
        const iva = parseFloat((bruto - base).toFixed(2));

        subtotalNeto += base;
        ivaAcum += iva;
        descuentoAcum += desc;
    });

    const totalVenta = subtotalNeto + ivaAcum;

    subtotalFacturaCambiariaEl.textContent = `Q ${subtotalNeto.toFixed(2)}`;
    descuentoFacturaCambiariaEl.textContent = `Q ${descuentoAcum.toFixed(2)}`;
    ivaFacturaCambiariaEl.textContent = `Q ${ivaAcum.toFixed(2)}`;
    totalFacturaCambiariaEl.textContent = `Q ${totalVenta.toFixed(2)}`;
};

// ====== SUBMIT FACTURA CAMBIARIA ======
FormFacturaCambiaria?.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    const items = contenedorItemsCambiaria?.querySelectorAll('.item-factura-cambiaria') || [];
    if (items.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Sin items', text: 'Agrega al menos un producto/servicio.' });
        return;
    }

    let totalFactura = 0;
    items.forEach((item) => {
        const $total = item.querySelector('.cam-item-total');
        if ($total) totalFactura += toNumber($total.value);
    });

    if (totalFactura <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Importes inválidos',
            text: 'Verifica cantidades, precios y descuentos.'
        });
        return;
    }

    // ✅ NUEVA validación de "abono": usamos la fecha de vencimiento
    const fechaVencInput = FormFacturaCambiaria.querySelector('input[name="fac_cam_fecha_vencimiento"]');
    const fechaVenc = fechaVencInput?.value || '';

    if (!fechaVenc) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin fecha de vencimiento',
            text: 'Debes ingresar la fecha de vencimiento del crédito.'
        });
        fechaVencInput?.focus();
        return;
    }

    setBtnLoading(btnGuardarFacturaCambiaria, true);

    try {
        const formData = new FormData(FormFacturaCambiaria);

        const res = await fetch('/facturacion/certificar-cambiaria', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: formData
        });



        const data = await res.json();


        if (!res.ok || data?.codigo !== 1) {
            throw new Error(data?.detalle || data?.mensaje || `Error ${res.status}`);
        }

        const result = await Swal.fire({
            icon: 'success',
            title: '¡Factura cambiaria certificada!',
            html: `
                <div style="text-align:left">
                    <p><b>UUID:</b> ${data.data.uuid}</p>
                    <p><b>Serie:</b> ${data.data.serie}</p>
                    <p><b>Número:</b> ${data.data.numero}</p>
                    <p><b>Total:</b> Q ${Number(data.data.total).toFixed(2)}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '📄 Ver Factura Cambiaria',
            cancelButtonText: 'Cerrar',
            reverseButtons: true
        });

        if (result.isConfirmed && data.data.fac_id) {
            window.open(`/facturacion/${data.data.fac_id}/vista-cambiaria`, '_blank');
        }

        cerrarModal('modalFacturaCambiaria');
        FormFacturaCambiaria.reset();
        contenedorItemsCambiaria.innerHTML = '';
        document.getElementById('subtotalFacturaCambiaria').textContent = 'Q 0.00';
        document.getElementById('descuentoFacturaCambiaria').textContent = 'Q 0.00';
        document.getElementById('ivaFacturaCambiaria').textContent = 'Q 0.00';
        document.getElementById('totalFacturaCambiaria').textContent = 'Q 0.00';

    } catch (err) {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'No se pudo certificar',
            text: err.message || 'Error desconocido'
        });
    } finally {
        setBtnLoading(btnGuardarFacturaCambiaria, false);
    }
});


// =============================
// FACTURA NORMAL - NIT / CUI
// =============================
let isSearching = false;
let isSearchingCui = false;

const BuscarNIT = async (ev) => {
    if (ev && typeof ev.preventDefault === 'function') ev.preventDefault();

    const nit = nitInput?.value?.trim() ?? '';
    if (!nit) return;

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    if (!isNitFormatoValido(nit)) {
        nitInput?.classList.remove('border-emerald-400');
        nitInput?.classList.add('border-red-400');
        Swal.fire({ icon: 'warning', title: 'NIT inválido', text: 'Escribe un NIT válido o CF.' });
        return;
    } else {
        nitInput?.classList.remove('border-red-400');
        nitInput?.classList.add('border-emerald-400');
    }

    if (/^cf$/i.test(nit)) {
        nombreInput.value = 'CONSUMIDOR FINAL';
        return;
    }

    if (isSearching) return;
    isSearching = true;
    setBtnLoading(btnBuscarNit, true);
    nombreInput.value = 'Consultando...';

    const body = new FormData();
    body.append('nit', nit);

    const url = '/facturacion/buscarNit';
    const config = {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body
    };

    try {
        const peticion = await fetch(url, config);
        if (!peticion.ok) {
            const txt = await peticion.text();
            throw new Error(`Error ${peticion.status}: ${txt}`);
        }

        const respuesta = await peticion.json();

        if (respuesta?.codigo === 1) {
            nombreInput.value = respuesta?.nombre || 'No encontrado';
        } else {
            nombreInput.value = '';
            Swal.fire({
                icon: 'error',
                title: 'No se pudo consultar',
                text: respuesta?.mensaje || 'Intente nuevamente.',
            });
        }
    } catch (error) {
        console.error(error);
        nombreInput.value = '';
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error inesperado',
        });
    } finally {
        setBtnLoading(btnBuscarNit, false);
        isSearching = false;
    }
};

const BuscarCUI = async (ev) => {
    if (ev && typeof ev.preventDefault === 'function') ev.preventDefault();

    const cui = cuiInput?.value?.trim() ?? '';
    if (!cui) return;

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    if (!isCuiFormatoValido(cui)) {
        cuiInput?.classList.remove('border-emerald-400');
        cuiInput?.classList.add('border-red-400');
        Swal.fire({ icon: 'warning', title: 'CUI inválido', text: 'Escribe un CUI válido.' });
        return;
    } else {
        cuiInput?.classList.remove('border-red-400');
        cuiInput?.classList.add('border-emerald-400');
    }

    if (isSearchingCui) return;
    isSearchingCui = true;
    setBtnLoading(btnBuscarCui, true);

    if (nombreInput) nombreInput.value = 'Consultando...';
    if (direccionInput) direccionInput.value = '';

    const body = new FormData();
    body.append('cui', cui);

    const url = '/facturacion/buscarCui';
    const config = {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
        },
        body
    };

    try {
        const peticion = await fetch(url, config);
        if (!peticion.ok) {
            const txt = await peticion.text();
            throw new Error(`Error ${peticion.status}: ${txt}`);
        }

        const respuesta = await peticion.json();

        if (respuesta?.codigo === 1) {
            if (nombreInput) {
                nombreInput.value = respuesta?.nombre || 'No encontrado';
            }
            if (direccionInput) {
                direccionInput.value = respuesta?.direccion || '';
            }
        } else {
            if (nombreInput) nombreInput.value = '';
            if (direccionInput) direccionInput.value = '';

            Swal.fire({
                icon: 'error',
                title: 'No se pudo consultar',
                text: respuesta?.mensaje || 'Intente nuevamente.',
            });
        }
    } catch (error) {
        console.error(error);
        if (nombreInput) nombreInput.value = '';
        if (direccionInput) direccionInput.value = '';

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error inesperado',
        });
    } finally {
        setBtnLoading(btnBuscarCui, false);
        isSearchingCui = false;
    }
};

btnBuscarNit?.addEventListener('click', BuscarNIT);

nitInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        BuscarNIT(e);
    }
});

btnBuscarCui?.addEventListener('click', BuscarCUI);

cuiInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        BuscarCUI(e);
    }
});

cuiInput?.addEventListener('input', debounce(() => {
    const cui = cuiInput.value.trim();
    if (!cui) {
        cuiInput.classList.remove('border-emerald-400', 'border-red-400');
        return;
    }
    if (isCuiFormatoValido(cui)) {
        cuiInput.classList.remove('border-red-400');
        cuiInput.classList.add('border-emerald-400');
    } else {
        cuiInput.classList.remove('border-emerald-400');
        cuiInput.classList.add('border-red-400');
    }
}, 300));

nitInput?.addEventListener('input', debounce(() => {
    const nit = nitInput.value.trim();
    if (!nit) {
        nitInput.classList.remove('border-emerald-400', 'border-red-400');
        nombreInput.value = '';
        return;
    }
    if (isNitFormatoValido(nit)) {
        nitInput.classList.remove('border-red-400');
        nitInput.classList.add('border-emerald-400');
    } else {
        nitInput.classList.remove('border-emerald-400');
        nitInput.classList.add('border-red-400');
    }
}, 300));

// =============================
// FACTURA NORMAL - ITEMS
// =============================
const calcularItem = (itemEl) => {
    const $cant = q(itemEl, '.item-cantidad');
    const $prec = q(itemEl, '.item-precio');
    const $desc = q(itemEl, '.item-descuento');
    const $total = q(itemEl, '.item-total');

    const cantidad = Math.max(0, toNumber($cant.value));
    const precio = Math.max(0, toNumber($prec.value));
    const descuento = Math.max(0, toNumber($desc.value));

    let importeBruto = (cantidad * precio) - descuento;
    if (importeBruto < 0) importeBruto = 0;

    $total.value = importeBruto.toFixed(2);

    recalcularTotales();
};

const recalcularTotales = () => {
    const items = contenedorItems.querySelectorAll('.item-factura');

    let subtotalNeto = 0;
    let ivaAcum = 0;
    let descuentoAcum = 0;

    items.forEach((item) => {
        const cant = Math.max(0, toNumber(q(item, '.item-cantidad').value));
        const prec = Math.max(0, toNumber(q(item, '.item-precio').value));
        const desc = Math.max(0, toNumber(q(item, '.item-descuento').value));

        const bruto = Math.max(0, (cant * prec) - desc);

        // Match backend rounding logic (2 decimals per item)
        const base = parseFloat((bruto / 1.12).toFixed(2));
        const iva = parseFloat((bruto - base).toFixed(2));

        subtotalNeto += base;
        ivaAcum += iva;
        descuentoAcum += desc;
    });

    const totalVenta = subtotalNeto + ivaAcum;

    document.getElementById('subtotalFactura').textContent = `Q ${subtotalNeto.toFixed(2)}`;
    document.getElementById('descuentoFactura').textContent = `Q ${descuentoAcum.toFixed(2)}`;
    document.getElementById('ivaFactura').textContent = `Q ${ivaAcum.toFixed(2)}`;
    document.getElementById('totalFactura').textContent = `Q ${totalVenta.toFixed(2)}`;
};

const bindItemEvents = (itemEl) => {
    const onKey = () => calcularItem(itemEl);

    q(itemEl, '.item-cantidad').addEventListener('input', onKey);
    q(itemEl, '.item-precio').addEventListener('input', onKey);
    q(itemEl, '.item-descuento').addEventListener('input', onKey);

    q(itemEl, '.btn-eliminar-item').addEventListener('click', () => {
        itemEl.remove();
        recalcularTotales();
        reindexItems();
    });
};

const reindexItems = () => {
    const items = contenedorItems.querySelectorAll('.item-factura');
    items.forEach((item, index) => {
        // Update series inputs names to match index
        const seriesInputs = item.querySelectorAll('.input-serie-id');
        seriesInputs.forEach(input => {
            input.name = `det_fac_series[${index}][]`;
        });
    });
};

const agregarItem = (prefill = {}) => {
    const tpl = templateItem?.content?.firstElementChild;
    if (!tpl) return;

    const nodo = tpl.cloneNode(true);

    if (prefill.descripcion) q(nodo, 'input[name="det_fac_producto_desc[]"]').value = prefill.descripcion;
    if (prefill.producto_id) {
        let input = q(nodo, 'input[name="det_fac_producto_id[]"]');
        if (!input) {
            // If template doesn't have it, create it (though it should)
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'det_fac_producto_id[]';
            nodo.appendChild(input);
        }
        input.value = prefill.producto_id;
    }

    // Partial Billing Fields
    if (prefill.detalle_venta_id) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'det_fac_detalle_venta_id[]';
        input.value = prefill.detalle_venta_id;
        nodo.appendChild(input);
    }

    // Series
    if (prefill.series_ids && Array.isArray(prefill.series_ids)) {
        prefill.series_ids.forEach(serieId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.className = 'input-serie-id';
            // Name will be set by reindexItems
            input.value = serieId;
            nodo.appendChild(input);
        });
    }

    if (typeof prefill.cantidad !== 'undefined') q(nodo, '.item-cantidad').value = prefill.cantidad;
    if (typeof prefill.precio !== 'undefined') q(nodo, '.item-precio').value = prefill.precio;
    if (typeof prefill.descuento !== 'undefined') q(nodo, '.item-descuento').value = prefill.descuento;

    contenedorItems.appendChild(nodo);

    bindItemEvents(nodo);
    calcularItem(nodo);
    reindexItems();
};

const seleccionarVenta = (venta) => {
    // Llenar datos cliente
    nitInput.value = venta.cliente_nit || 'CF';
    nombreInput.value = `${venta.cliente_nombre1} ${venta.cliente_apellido1}`;
    document.getElementById('fac_receptor_direccion').value = venta.cliente_direccion || '';

    // Llenar info venta seleccionada
    facVentaId.value = venta.ven_id;
    document.getElementById('lblVentaId').textContent = venta.ven_id;
    document.getElementById('lblCliente').textContent = `${venta.cliente_nombre1} ${venta.cliente_apellido1}`;

    ventaSeleccionadaInfo.classList.remove('hidden');
    resultadosVenta.classList.add('hidden');
    busquedaVenta.value = '';

    // Llenar items
    contenedorItems.innerHTML = '';
    if (venta.detalles && venta.detalles.length > 0) {
        venta.detalles.forEach(det => {
            if (det.series && det.series.length > 0) {
                // Calcular descuento unitario
                const descuentoTotal = parseFloat(det.det_descuento || 0);
                const cantidadTotal = parseFloat(det.det_cantidad || 1);
                const descuentoUnitario = cantidadTotal > 0 ? (descuentoTotal / cantidadTotal) : 0;

                // Agregar una línea por cada serie
                det.series.forEach(serie => {
                    // Check if serie is object or string (backward compatibility)
                    const serieNumero = serie.numero || serie;
                    const serieId = serie.id || null; // If string, we might not have ID here unless passed differently

                    agregarItem({
                        descripcion: `${det.producto_nombre} (Serie: ${serieNumero})`,
                        cantidad: 1,
                        precio: det.det_precio,
                        descuento: descuentoUnitario.toFixed(2),
                        producto_id: det.det_producto_id,
                        detalle_venta_id: det.det_id,
                        series_ids: serieId ? [serieId] : []
                    });
                });

                // Si hay cantidad sobrante sin serie (should not happen for serialized items if data is correct)
                const sobrante = cantidadTotal - det.series.length;
                if (sobrante > 0) {
                    agregarItem({
                        descripcion: det.producto_nombre,
                        cantidad: sobrante,
                        precio: det.det_precio,
                        descuento: (descuentoUnitario * sobrante).toFixed(2),
                        producto_id: det.det_producto_id,
                        detalle_venta_id: det.det_id
                    });
                }
            } else {
                // Producto normal sin series
                agregarItem({
                    descripcion: det.producto_nombre,
                    cantidad: det.det_cantidad,
                    precio: det.det_precio,
                    descuento: det.det_descuento,
                    producto_id: det.det_producto_id,
                    detalle_venta_id: det.det_id
                });
            }
        });
    }
    recalcularTotales();
};

const buscarVenta = async () => {
    const ventaId = busquedaVenta?.value?.trim();
    if (!ventaId) {
        Swal.fire({ icon: 'warning', title: 'Ingrese ID de venta', text: 'Por favor ingrese el ID de la venta a buscar.' });
        return;
    }

    setBtnLoading(btnBuscarVenta, true);

    try {
        const res = await fetch(`/api/ventas/${ventaId}`, {
            headers: { 'X-CSRF-TOKEN': token }
        });
        const json = await res.json();

        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Venta no encontrada');
        }

        seleccionarVenta(json.data);
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'No se pudo buscar la venta' });
    } finally {
        setBtnLoading(btnBuscarVenta, false);
    }
};


btnBuscarVenta?.addEventListener('click', buscarVenta);
busquedaVenta?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        buscarVenta();
    }
});

btnQuitarVenta?.addEventListener('click', () => {
    facVentaId.value = '';
    ventaSeleccionadaInfo.classList.add('hidden');
    contenedorItems.innerHTML = '';
    agregarItem(); // Agregar uno vacío
    FormFactura.reset();
    recalcularTotales();
});

// ===== SUBMIT: CERTIFICAR FACTURA =====
FormFactura?.addEventListener('submit', async (e) => {
    const items = contenedorItems.querySelectorAll('.item-factura');
    if (items.length === 0) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Sin items', text: 'Agrega al menos un producto/servicio.' });
        return;
    }
    let totalFactura = 0;
    items.forEach((item) => { totalFactura += toNumber(q(item, '.item-total').value); });
    if (totalFactura <= 0) {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Importes inválidos', text: 'Verifica cantidades, precios y descuentos.' });
        return;
    }

    e.preventDefault();

    if (!token) {
        Swal.fire({ icon: 'error', title: 'CSRF no encontrado', text: 'No se encontró el token CSRF.' });
        return;
    }

    const btn = btnGuardarFactura;
    setBtnLoading(btn, true);

    try {
        const formData = new FormData(FormFactura);

        const res = await fetch('/facturacion/certificar', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
            body: formData
        });

        const data = await res.json();

        if (!res.ok || data?.codigo !== 1) {
            throw new Error(data?.detalle || data?.mensaje || `Error ${res.status}`);
        }

        const result = await Swal.fire({
            icon: 'success',
            title: '¡Factura certificada!',
            html: `
                <div style="text-align:left">
                <p><b>UUID:</b> ${data.data.uuid}</p>
                <p><b>Serie:</b> ${data.data.serie}</p>
                <p><b>Número:</b> ${data.data.numero}</p>
                <p><b>Total:</b> Q ${Number(data.data.total).toFixed(2)}</p>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: '📄 Imprimir Factura',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            window.open(`/facturacion/${data.data.fac_id}/vista`, '_blank');
        }

        cerrarModal('modalFactura');
        FormFactura.reset();
        contenedorItems.innerHTML = '';
        document.getElementById('subtotalFactura').textContent = 'Q 0.00';
        document.getElementById('descuentoFactura').textContent = 'Q 0.00';
        document.getElementById('ivaFactura').textContent = 'Q 0.00';
        document.getElementById('totalFactura').textContent = 'Q 0.00';

        if (window.tablaFacturas) {
            window.tablaFacturas.ajax.reload(null, false);
        }

    } catch (err) {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'No se pudo certificar', text: err.message || 'Error desconocido' });
    } finally {
        setBtnLoading(btn, false);
    }
});

// =============================
// DATATABLE FACTURAS
// =============================
const elTabla = document.getElementById('tablaFacturas');
const fmtQ = (n) => `Q ${Number(n || 0).toFixed(2)}`;
const fmtFecha = (s) => {
    if (!s) return '—';
    const d = new Date(s);
    return d.toLocaleDateString('es-GT') + ' ' + d.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' });
};

const estadoBadge = (estado) => {
    if (estado === 'CERTIFICADO') {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">✓ Certificado</span>';
    }
    if (estado === 'ANULADO') {
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✕ Anulado</span>';
    }
    return `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">${estado}</span>`;
};

const ES_LANG = {
    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
};

if (elTabla) {
    window.tablaFacturas = new DataTable(elTabla, {
        ajax: {
            url: '/facturacion/obtener-facturas',
            dataSrc: 'data',
            data: (d) => {
                const fi = document.getElementById('filtroFechaInicio')?.value;
                const ff = document.getElementById('filtroFechaFin')?.value;
                if (fi) d.fecha_inicio = fi;
                if (ff) d.fecha_fin = ff;
                return d;
            }
        },
        columns: [
            { title: 'UUID', data: 'fac_uuid', className: 'text-xs font-mono' },
            {
                title: 'Documento',
                data: null,
                render: (d, t, row) => `${row.fac_serie || ''}-${row.fac_numero || ''}`,
                className: 'font-semibold'
            },
            { title: 'Cliente', data: 'fac_receptor_nombre', className: 'max-w-xs truncate' },
            { title: 'Estado', data: 'fac_estado', render: (d) => estadoBadge(d), className: 'text-center' },
            { title: 'Total', data: 'fac_total', render: (d) => fmtQ(d), className: 'text-right font-semibold' },
            { title: 'Moneda', data: 'fac_moneda', className: 'text-center' },
            { title: 'Fecha Emisión', data: 'fac_fecha_emision', render: (d) => fmtFecha(d), className: 'text-sm' },
            { title: 'Certificado', data: 'fac_fecha_certificacion', render: (d) => fmtFecha(d), className: 'text-sm' },
            {
                title: 'Acciones',
                data: null,
                orderable: false,
                searchable: false,
                render: (d, t, row) => {

                    const puedeAnular = row.fac_estado === 'CERTIFICADO';

                    // Elegir ruta correcta según tipo de factura
                    const urlVista =
                        row.fac_tipo_documento === 'FCAM'
                            ? `/facturacion/${row.fac_id}/vista-cambiaria`
                            : `/facturacion/${row.fac_id}/vista`;

                    return `
        <div class="flex flex-nowrap gap-2">

            <!-- Botón imprimir -->
            <a href="${urlVista}" target="_blank"
                class="px-3 py-1 rounded bg-sky-600 hover:bg-sky-700 text-white text-xs font-medium transition inline-flex items-center gap-1 whitespace-nowrap"
                title="Imprimir">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
            </a>

            ${puedeAnular ? `
            <button type="button"
                class="btn-anular px-3 py-1 rounded bg-red-600 hover:bg-red-700 text-white text-xs font-medium transition inline-flex items-center gap-1 whitespace-nowrap"
                data-anular="${row.fac_uuid}" data-id="${row.fac_id}"
                title="Anular">

                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
            ` : ''}
        </div>
    `;
                }

            }
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ordering: false,
        searching: false,
        scrollX: true,
        autoWidth: false,
        language: ES_LANG
    });
}

btnFiltrarFacturas?.addEventListener('click', () => {
    if (window.tablaFacturas) {
        Swal.fire({
            title: 'Cargando...',
            text: 'Filtrando facturas',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        window.tablaFacturas.ajax.reload(() => {
            Swal.close();
        });
    }
});

// ===== CONSULTAR DTE =====
const btnConsultarDte = document.getElementById('btnConsultarDte');
const uuidConsulta = document.getElementById('uuid_consulta');
const resultadoConsultaDte = document.getElementById('resultadoConsultaDte');

const templateResultadoDte = document.getElementById('templateResultadoDte') || (() => {
    const temp = document.createElement('template');
    temp.innerHTML = `
        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-semibold text-gray-800">Resultado de la Consulta</h4>
                <div class="flex gap-2">
                    <span class="px-2 py-1 rounded-full text-xs font-medium" data-estado-badge></span>
                    <button type="button" class="p-1 text-gray-400 hover:text-gray-600 transition" data-limpiar-consulta
                        title="Limpiar consulta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <span class="text-gray-600">UUID:</span>
                    <span class="font-mono text-gray-800 text-xs" data-uuid></span>
                </div>
                <div>
                    <span class="text-gray-600">Documento:</span>
                    <span class="font-semibold" data-documento></span>
                </div>
                <div>
                    <span class="text-gray-600">Fecha Certificación:</span>
                    <span data-fecha-certificacion></span>
                </div>
                <div>
                    <span class="text-gray-600">Estado:</span>
                    <span data-estado></span>
                </div>
            </div>
        </div>
    `;
    return temp;
})();

const consultarDte = async (uuid, contenedorResultado) => {
    if (!uuid.trim()) {
        Swal.fire({ icon: 'warning', title: 'UUID requerido', text: 'Por favor ingresa un UUID válido' });
        return;
    }

    const uuidPattern = /^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}$/;
    if (!uuidPattern.test(uuid)) {
        Swal.fire({ icon: 'warning', title: 'UUID inválido', text: 'El formato del UUID no es correcto' });
        return;
    }

    setBtnLoading(btnConsultarDte, true);

    contenedorResultado.innerHTML = `
        <div class="bg-white rounded-lg border border-gray-200 p-8 shadow-sm">
            <div class="flex flex-col items-center justify-center space-y-3">
                <svg class="w-8 h-8 animate-spin text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <p class="text-gray-600 font-medium">Consultando DTE...</p>
                <p class="text-gray-500 text-sm">Buscando en el sistema FEL</p>
            </div>
        </div>
    `;
    contenedorResultado.classList.remove('hidden');

    try {
        const response = await fetch(`/facturacion/consultar-dte/${uuid}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            }
        });

        const data = await response.json();

        if (!response.ok || data.codigo !== 1) {
            throw new Error(data.mensaje || 'Error al consultar el DTE');
        }

        mostrarResultadoDte(data.data, contenedorResultado);
    } catch (error) {
        console.error('Error consultando DTE:', error);

        contenedorResultado.innerHTML = `
            <div class="bg-white rounded-lg border border-red-200 p-6 shadow-sm">
                <div class="flex items-center space-x-3 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="font-semibold">Error en la consulta</h4>
                        <p class="text-sm text-gray-600 mt-1">${error.message || 'No se pudo consultar el DTE'}</p>
                    </div>
                </div>
            </div>
        `;
        contenedorResultado.classList.remove('hidden');

        Swal.fire({
            icon: 'error',
            title: 'Error en consulta',
            text: error.message || 'No se pudo consultar el DTE'
        });
    } finally {
        setBtnLoading(btnConsultarDte, false);
    }
};

const mostrarResultadoDte = (datos, contenedor) => {
    const template = templateResultadoDte.content.cloneNode(true);

    template.querySelector('[data-uuid]').textContent = datos.UUID || datos.uuid;
    template.querySelector('[data-documento]').textContent = `${datos.Serie || datos.serie}-${datos.Numero || datos.numero}`;
    template.querySelector('[data-fecha-certificacion]').textContent = datos.FechaHoraCertificacion || datos.fechaHoraCertificacion;

    const estado = datos.estado_local || 'Desconocido';
    template.querySelector('[data-estado]').textContent = estado;

    const badge = template.querySelector('[data-estado-badge]');
    badge.textContent = estado;

    let badgeClass = 'bg-gray-100 text-gray-800';
    if (estado === 'CERTIFICADO') {
        badgeClass = 'bg-emerald-100 text-emerald-800';
    } else if (estado === 'ANULADO') {
        badgeClass = 'bg-red-100 text-red-800';
    }

    badge.className = `px-2 py-1 rounded-full text-xs font-medium ${badgeClass}`;

    if (estado === 'ANULADO' && datos.fecha_anulacion) {
        const infoAnulacion = document.createElement('div');
        infoAnulacion.className = 'col-span-2 bg-red-50 border border-red-200 rounded p-3 mt-2';
        infoAnulacion.innerHTML = `
            <p class="text-sm text-red-800 font-semibold mb-1">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Documento Anulado
            </p>
            <p class="text-xs text-red-700">Fecha de anulación: ${datos.fecha_anulacion}</p>
            ${datos.motivo_anulacion ? `<p class="text-xs text-red-700 mt-1">Motivo: ${datos.motivo_anulacion}</p>` : ''}
        `;
        template.querySelector('.grid').appendChild(infoAnulacion);
    }

    const btnLimpiar = template.querySelector('[data-limpiar-consulta]');
    btnLimpiar?.addEventListener('click', () => {
        contenedor.innerHTML = '';
        contenedor.classList.add('hidden');
        uuidConsulta.value = '';
    });

    contenedor.innerHTML = '';
    contenedor.appendChild(template);
    contenedor.classList.remove('hidden');
};

btnConsultarDte?.addEventListener('click', () => {
    consultarDte(uuidConsulta.value, resultadoConsultaDte);
});

uuidConsulta?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        consultarDte(uuidConsulta.value, resultadoConsultaDte);
    }
});

// ===== ANULAR FACTURA =====
document.addEventListener('click', async (e) => {
    if (e.target.closest('.btn-anular')) {
        const btn = e.target.closest('.btn-anular');
        const uuid = btn.dataset.anular;
        const id = btn.dataset.id;

        const result = await Swal.fire({
            title: '¿Anular Factura?',
            text: `Esta acción anulará la factura ${uuid}. ¿Estás seguro?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            try {
                setBtnLoading(btn, true);

                const response = await fetch(`/facturacion/anular/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.codigo === 1) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Factura anulada',
                        text: 'La factura ha sido anulada exitosamente'
                    });

                    if (window.tablaFacturas) {
                        window.tablaFacturas.ajax.reload(null, false);
                    }
                } else {
                    throw new Error(data.mensaje || 'Error al anular la factura');
                }
            } catch (error) {
                console.error('Error anulando factura:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al anular',
                    text: error.message || 'No se pudo anular la factura'
                });
            } finally {
                setBtnLoading(btn, false);
            }
        }
    }
});

// ==========================================
// EXPORTS PARA USO EN OTROS MÓDULOS (REPORTES)
// ==========================================
window.abrirModal = abrirModal;
window.cerrarModal = cerrarModal;
window.seleccionarVenta = seleccionarVenta;
window.seleccionarVentaCambiaria = seleccionarVentaCambiaria;
window.buscarVenta = buscarVenta;
window.buscarVentaCambiaria = buscarVentaCambiaria;
window.resetModalFacturaCambiaria = resetModalFacturaCambiaria;
window.recalcularTotales = recalcularTotales;
window.agregarItem = agregarItem;
// window.agregarItemCambiaria = agregarItemCambiaria; 
// window.recalcularTotalesCambiaria = recalcularTotalesCambiaria;
