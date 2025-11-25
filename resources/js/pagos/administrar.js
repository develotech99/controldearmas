// resources/js/pagos/administrar.js
import Swal from "sweetalert2";
import DataTable from "vanilla-datatables";
import "vanilla-datatables/src/vanilla-dataTables.css";





// Mapa global: venta_id => info completa de la venta
let ventaDetalles = new Map();

const CargarMisPagos = async () => {
    try {
        const baseUrl = window.URL_MIS_PAGOS || '/obtener/mispagos';
        const url = `${baseUrl}?all=1`;

        const resp = await fetch(url, { method: 'GET' });

        if (!resp.ok) {
            console.warn('MisFacturasPendientes no respondió OK. Status:', resp.status);
            return;
        }

        const ct = resp.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            console.error('Respuesta NO es JSON (probablemente HTML de error/login).');
            return;
        }

        const { codigo, data } = await resp.json();

        if (codigo !== 1 || !data) {
            console.warn('MisFacturasPendientes sin data usable', { codigo, data });
            return;
        }

        ventaDetalles.clear();

        const agregar = (lista) => {
            if (!Array.isArray(lista)) return;

            lista.forEach(v => {
                if (!v || v.venta_id == null) return;

                const ventaId = Number(v.venta_id);
                if (!ventaId) return;

                const normalizado = {
                    venta_id: ventaId,
                    fecha: v.fecha,
                    concepto: v.concepto,
                    items_count: v.items_count ?? 0,
                    monto_total: v.monto_total ?? v.total ?? 0,
                    pagado: v.pagado ?? 0,
                    pendiente: v.pendiente ?? 0,
                    estado_pago: v.estado_pago ?? v.estado ?? 'PENDIENTE',
                    observaciones: v.observaciones ?? '',

                    cliente: v.cliente || {},
                    vendedor: v.vendedor || {},
                    precios: v.precios || {},

                    pago_master: v.pago_master || {},
                    pagos_realizados: v.pagos_realizados || [],
                    cuotas_pendientes: v.cuotas_pendientes || [],
                    cuotas_disponibles: v.cuotas_disponibles ?? 0,
                    cuotas_en_revision: v.cuotas_en_revision || [],
                    marcar_como: v.marcar_como || null,
                    fecha_ultimo_pago: v.fecha_ultimo_pago || null,
                };

                // siempre usar la llave numérica
                ventaDetalles.set(ventaId, normalizado);
            });
        };

        agregar(data.pendientes);
        agregar(data.pagadas_ult4m);
        agregar(data.facturas_pendientes_all);

        console.log('ventaDetalles cargado con', ventaDetalles.size, 'ventas');
    } catch (e) {
        console.error('Error cargando MisFacturasPendientes:', e);
    }
};






/* =========================
 *  Helpers generales
 * ========================= */

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const getHeaders = () => ({
    "Content-Type": "application/json",
    "X-CSRF-TOKEN": csrfToken
});
const API = "/admin/pagos";

const fmtQ = (n) =>
    "Q " +
    Number(n || 0).toLocaleString("es-GT", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const swalLoadingOpen = (title = "Procesando...") =>
    Swal.fire({
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => Swal.showLoading(),
    });

const swalLoadingClose = () => Swal.close();

const debounce = (fn, ms = 350) => {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
};

const setTxt = (id, txt) => {
    const el = document.getElementById(id);
    if (el) el.textContent = txt;
};

const abrirModal = (id) => document.getElementById(id)?.classList.remove("hidden");
const cerrarModal = (id) => document.getElementById(id)?.classList.add("hidden");


/* =========================
 *  Estado simple
 * ========================= */
let validarState = { ps: null, venta: null, debia: 0, hizo: 0 };
let uploadTmp = null;

/* =========================
 *  DataTables (vanilla-datatables)
 * ========================= */
let dtPendientes = null;
let dtMovimientos = null;
let dtPreview = null;

const labelsES = {
    placeholder: "Buscar...",
    perPage: "{select} registros por página",
    noRows: "No se encontraron registros",
    info: "Mostrando {start} a {end} de {rows} registros",
};

const initTablaPendientes = () => {
    if (dtPendientes) dtPendientes.destroy();
    dtPendientes = new DataTable("#tablaPendientes", {
        searchable: false,
        sortable: true,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: labelsES,
        data: {
            headings: [
                "Fecha",
                "Venta",
                "Cliente",
                "Concepto",
                "Debía",
                "Depositado",
                "Diferencia",
                "Comprobante",
                "Acciones",
            ],
            data: [],
        },
    });
};

const initTablaMovimientos = () => {
    if (dtMovimientos) {
        try {
            dtMovimientos.destroy();
        } catch (_) { }
        dtMovimientos = null;
    }

    dtMovimientos = new DataTable("#tablaMovimientos", {
        searchable: false,
        sortable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: labelsES,
        data: {
            headings: ["Fecha", "Tipo", "Descripción", "Referencia", "Método", "Monto", "Estado", "Acciones"],
            data: [],
        },
    });
};

const initTablaPreview = () => {
    if (dtPreview) dtPreview.destroy();
    dtPreview = new DataTable("#tablaPrevia", {
        searchable: false,
        sortable: true,
        fixedHeight: true,
        perPage: 25,
        perPageSelect: [10, 25, 50, 100],
        labels: labelsES,
        data: {
            headings: ["Fecha", "Descripción", "Referencia", "Monto", "Detectado"],
            data: [],
        },
    });

    document.querySelectorAll("#tablaPrevia thead th:nth-child(4)")
        .forEach(th => th.classList.add("text-right"));
};

/* =========================
 *  Stats / Dashboard
 *  Espera: { codigo, mensaje, detalle, data:{ saldo_total_gtq, saldos, pendientes, ultima_carga } }
 * ========================= */

const CargarStats = async () => {
    try {
        swalLoadingOpen("Cargando estadísticas...");
        const url = `${API}/dashboard-stats`;
        const resp = await fetch(url, { method: "GET" });
        const { codigo, mensaje, data } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            const {
                saldo_total_gtq = 0,
                pendientes = 0,
                ultima_carga = null,
            } = data || {};

            setTxt("saldoCajaTotalGTQ", fmtQ(saldo_total_gtq));
            setTxt("contadorPendientes", String(pendientes));
            setTxt(
                "ultimaCargaEstado",
                ultima_carga ? new Date(ultima_carga).toLocaleString() : "—"
            );
        } else {
            console.error(mensaje);
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Error stats:", e);
    }
};

/* =========================
 *  Pendientes
 *  Espera: { codigo, mensaje, data:[...] }
 * ========================= */
const renderPendientes = (rows = []) => {
    const tabla = document.getElementById("tablaPendientes");
    const empty = document.getElementById("emptyPendientes");

    // Si no hay filas: destruir DT, ocultar la tabla y mostrar vacío
    if (!rows.length) {
        if (dtPendientes) { try { dtPendientes.destroy(); } catch (_) { } dtPendientes = null; }
        // Limpiar cuerpo por si quedó algo
        document.querySelector("#tablaPendientes tbody")?.replaceChildren();
        tabla?.classList.add("hidden");
        empty?.classList.remove("hidden");
        return;
    }

    // Sí hay filas: asegurarnos de mostrar la tabla y ocultar el vacío
    tabla?.classList.remove("hidden");
    empty?.classList.add("hidden");

    const isSmall = window.matchMedia('(max-width: 768px)').matches;

    const data = rows.map((r) => {
        const fecha = r.fecha ? new Date(r.fecha).toLocaleDateString() : "—";
        const venta = r.venta_id ? `#${r.venta_id}` : "—";
        const cliente = r.cliente || "—";

        const ventaTotal = Number(r.venta_total || 0);
        const pendienteVenta = Number(r.pendiente_venta || 0);
        const debiaEnvio = Number(r.debia_envio || 0);
        const cuotasSel = Number(r.cuotas_seleccionadas || 0);
        const cuotasTotal = Number(r.cuotas_total_venta || 0);

        const concepto = r.concepto || "—";
        const debia = fmtQ(r.debia);
        const deposito = fmtQ(r.depositado);
        const difNum = Number(r.diferencia || 0);
        const difCls = difNum === 0 ? "text-emerald-600" : difNum > 0 ? "text-amber-600" : "text-rose-600";
        const dif = `<span class="${difCls}">${fmtQ(difNum)}</span>`;

        const cuotasInfo = (cuotasSel || cuotasTotal)
            ? `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 text-indigo-700">
           Cuotas: ${cuotasSel || 0}${cuotasTotal ? ` / ${cuotasTotal}` : ""}
         </span>`
            : "";

        const detalleHtml = `
      <div class="space-y-1">
        <div class="font-medium">${concepto}</div>
        <div class="text-[12px] text-gray-500 flex flex-wrap gap-2">
          <span>Venta: <b>${fmtQ(ventaTotal)}</b></span>
          <span>Pend. venta: <b>${fmtQ(pendienteVenta)}</b></span>
          ${debiaEnvio ? `<span>Debía (envío): <b>${fmtQ(debiaEnvio)}</b></span>` : ""}
          ${cuotasInfo}
        </div>
      </div>
    `;

        const comp = r.imagen
            ? `<button class="btn-ver-comp text-blue-600 hover:underline"
           data-img="${r.imagen}"
           data-ref="${r.referencia || ""}"
           data-fecha="${r.fecha || ""}"
           data-monto="${r.depositado || 0}">
           Ver
         </button>`
            : "—";

        const acciones = `
      <div class="flex justify-center">
        <button class="btn-validar bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-3 py-1.5 rounded-lg"
          data-ps="${r.ps_id}" data-venta="${r.venta_id}"
          data-debia="${r.debia}" data-hizo="${r.depositado}">
          Validar
        </button>
      </div>
    `;

        if (isSmall) {
            const cabecera = `
        <div class="text-[12px] text-gray-500">${fecha} • Venta <b>${venta}</b></div>
        <div class="text-[13px] font-medium">${cliente}</div>
      `;
            const totales = `
        <div class="mt-2 text-[12px] text-gray-600 flex flex-wrap gap-x-4 gap-y-1">
          <span>Debía: <b>${debia}</b></span>
          <span>Depósito: <b>${deposito}</b></span>
          <span>Dif.: <b class="${difCls}">${fmtQ(difNum)}</b></span>
          <span>Comprobante: ${comp}</span>
        </div>
      `;
            return [cabecera + detalleHtml + totales, acciones];
        }

        return [fecha, venta, cliente, detalleHtml, debia, deposito, dif, comp, acciones];
    });

    if (dtPendientes) { try { dtPendientes.destroy(); } catch (_) { } dtPendientes = null; }

    const headingsDesktop = ["Fecha", "Venta", "Cliente", "Detalle", "Debía", "Depositado", "Diferencia", "Comprobante", "Acciones"];
    const headingsMobile = ["Factura / Cliente / Detalle", "Acciones"];

    dtPendientes = new DataTable("#tablaPendientes", {
        searchable: false,
        sortable: true,
        fixedHeight: false,             // <<< evita scroll forzado con pocas/ninguna fila
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: labelsES,
        data: { headings: isSmall ? headingsMobile : headingsDesktop, data },
    });
};


const BuscarPendientes = async () => {
    try {
        swalLoadingOpen("Cargando pendientes...");
        const q = document.getElementById("buscarFactura")?.value?.trim() || "";
        const estado = document.getElementById("filtroEstado")?.value || "";
        const url = new URL(`${API}/pendientes`, window.location.origin);
        if (q) url.searchParams.set("q", q);
        if (estado) url.searchParams.set("estado", estado);

        const resp = await fetch(url, { method: "GET" });
        const { codigo, mensaje, data } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            renderPendientes(data || []);
        } else {
            console.error(mensaje);
            renderPendientes([]);
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Error pendientes:", e);
        renderPendientes([]);
    }
};

document.addEventListener("click", (e) => {
    // Ver comprobante
    if (e.target.closest(".btn-ver-comp")) {
        const btn = e.target.closest(".btn-ver-comp");
        const img = decodeURIComponent(btn.dataset.img || "");
        const ref = btn.dataset.ref || "—";
        const fecha = btn.dataset.fecha
            ? new Date(btn.dataset.fecha).toLocaleString()
            : "—";
        const monto = Number(btn.dataset.monto || 0);

        const src = img;
        const imgEl = document.getElementById("imgComprobante");
        const aEl = document.getElementById("btnDescargarComprobante");
        if (imgEl) imgEl.src = src;
        if (aEl) aEl.href = src;

        setTxt("refComprobante", ref);
        setTxt("fechaComprobante", fecha);
        setTxt("montoComprobante", fmtQ(monto));
        abrirModal("modalComprobante");
    }

    // Validar
    if (e.target.closest(".btn-validar")) {
        const btn = e.target.closest(".btn-validar");
        validarState = {
            ps: Number(btn.dataset.ps),
            venta: Number(btn.dataset.venta),
            debia: Number(btn.dataset.debia || 0),
            hizo: Number(btn.dataset.hizo || 0),
        };
        setTxt("mvVenta", `#${validarState.venta}`);
        setTxt("mvDebia", fmtQ(validarState.debia));
        setTxt("mvHizo", fmtQ(validarState.hizo));
        setTxt("mvDif", fmtQ(validarState.hizo - validarState.debia));
        setTxt("mvMetodo", "—");
        abrirModal("modalValidar");
    }

    if (
        e.target.closest("[data-modal-close], [data-close-modal]") ||
        e.target.closest("[data-modal-backdrop]") ||
        (e.target.classList?.contains("bg-black/50") && e.target.closest(".fixed.inset-0.z-50"))
    ) {
        cerrarModal("modalValidar");
        cerrarModal("modalEgreso");
        cerrarModal("modalComprobante");
        cerrarModal("modalDetalleVenta");
        cerrarModal("modalIngreso");
    }


});


/* =========================
 *  Aprobar / Rechazar
 *  Espera: { codigo:1, mensaje }
 * ========================= */
const Aprobar = async () => {
    if (!validarState.ps) return;
    try {
        swalLoadingOpen("Aprobando pago...");
        const url = `${API}/aprobar`;
        const body = JSON.stringify({
            ps_id: validarState.ps,
            observaciones: document.getElementById("mvObs")?.value || "",
        });
        const resp = await fetch(url, {
            method: "POST",
            headers: getHeaders(),
            body,
        });
        const { codigo, mensaje } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            await Swal.fire("¡Éxito!", mensaje || "Pago aprobado", "success");
            cerrarModal("modalValidar");
            await CargarStats();
            await BuscarPendientes();
            await CargarMovimientos();
        } else {
            await Swal.fire("Error", mensaje || "No se pudo aprobar", "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Aprobar error:", e);
    }
};

const Rechazar = async () => {
    if (!validarState.ps) return;
    const motivo = document.getElementById("mvObs")?.value || "";
    if (!motivo || motivo.length < 5) {
        return Swal.fire("Atención", "Indica el motivo (mín 5 caracteres).", "info");
    }
    try {
        swalLoadingOpen("Rechazando pago...");
        const resp = await fetch(`${API}/rechazar`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify({ ps_id: validarState.ps, motivo }),
        });
        const { codigo, mensaje } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            await Swal.fire("¡Éxito!", mensaje || "Pago rechazado", "success");
            cerrarModal("modalValidar");
            await CargarStats();
            await BuscarPendientes();
        } else {
            await Swal.fire("Error", mensaje || "No se pudo rechazar", "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Rechazar error:", e);
    }
};

document.getElementById("btnAprobar")?.addEventListener("click", Aprobar);
document.getElementById("btnRechazar")?.addEventListener("click", Rechazar);

/* =========================
 *  Movimientos
 *  Espera: { codigo:1, data:{ data:[...], total } } o { codigo:1, data:[...] }
 * ========================= */
const rangoMes = () => {
    const val = document.getElementById("filtroMes")?.value || "";
    const pad = (n) => String(n).padStart(2, "0");
    if (val) {
        const [y, m] = val.split("-").map(Number);
        const f = new Date(y, m - 1, 1);
        const l = new Date(y, m, 0);
        return {
            from: `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())}`,
            to: `${l.getFullYear()}-${pad(l.getMonth() + 1)}-${pad(l.getDate())}`,
        };
    }
    const n = new Date();
    const f = new Date(n.getFullYear(), n.getMonth(), 1);
    const l = new Date(n.getFullYear(), n.getMonth() + 1, 0);
    return {
        from: `${f.getFullYear()}-${pad(f.getMonth() + 1)}-${pad(f.getDate())}`,
        to: `${l.getFullYear()}-${pad(l.getMonth() + 1)}-${pad(l.getDate())}`,
    };
};





// ======================= CARGAR MOVIMIENTOS =======================
const CargarMovimientos = async () => {
    try {
        swalLoadingOpen("Cargando movimientos...");

        const metodoId = document.getElementById("filtroMetodo")?.value || "";
        const { from, to } = rangoMes();

        const url = new URL(`${API}/movimientos`, window.location.origin);
        url.searchParams.set("from", from);
        url.searchParams.set("to", to);
        if (metodoId) url.searchParams.set("metodo_id", metodoId);

        const resp = await fetch(url, { method: "GET" });
        const { codigo, mensaje, data } = await resp.json();

        swalLoadingClose();

        if (codigo !== 1) {
            console.error(mensaje);
            renderMovimientos([]);
            setTxt("totalMovimientosMes", fmtQ(0));
            return;
        }

        const rows = Array.isArray(data?.movimientos)
            ? data.movimientos
            : Array.isArray(data)
                ? data
                : [];

        const total = Number(data?.total ?? 0);

        // ==== construir mapa de ventas (solo ACTIVAS) ====
        ventaDetalles = new Map();

        const ventas = Array.isArray(data?.ventas) ? data.ventas : [];
        ventas.forEach(v => {
            const id = Number(v.ven_id || v.id || v.venta_id || 0);
            if (!id) return;

            // si el backend manda el estado de la venta, filtramos aquí
            const situacion = (v.ven_situacion || v.situacion || v.estado || "").toUpperCase();
            if (situacion && situacion !== "ACTIVA") return; // ❗ solo guardamos activas

            // normalizamos estructura para usar en renderMovimientos
            ventaDetalles.set(id, {
                id,
                situacion,
                cliente: {
                    nombre: v.cliente_nombre,
                    empresa: v.cliente_empresa,
                    nit: v.cliente_nit,
                    telefono: v.cliente_telefono,
                },
                vendedor: {
                    nombre: v.vendedor_nombre,
                },
                precios: {
                    individual: v.precio_individual,
                    empresa: v.precio_empresa,
                    aplicado: v.precio_aplicado,
                },
                concepto: v.concepto,
                items_count: v.items_count,
                monto_total: v.pago_monto_total ?? v.monto_total,
                pagado: v.pago_monto_pagado ?? v.pagado,
                pendiente: v.pago_monto_pendiente ?? v.pendiente,
                pagos_realizados: v.pagos_realizados || [],
                cuotas_pendientes: v.cuotas_pendientes || [],
            });
        });

        console.log("ventaDetalles cargado con", ventaDetalles.size, "ventas ACTIVAS");

        renderMovimientos(rows);
        setTxt("totalMovimientosMes", fmtQ(total));
    } catch (e) {
        swalLoadingClose();
        console.error("Error movs:", e);
        renderMovimientos([]);
        setTxt("totalMovimientosMes", fmtQ(0));
    }
};
const renderMovimientos = (rows = []) => {
    // 1) Filtrar: quitar ventas cuyo detalle NO está activo / no existe en ventaDetalles
    const rowsFiltradas = rows.filter((r) => {
        const tipo = r.cja_tipo || "";

        // Para todo lo que no sea VENTA (DEPÓSITO, EGRESO, AJUSTE, etc.) se muestra siempre
        if (tipo !== "VENTA") return true;

        const ventaId = Number(r.venta_id || 0);
        if (!ventaId) return true; // por si hay movimientos VENTA sin referencia

        const venta = ventaDetalles.get(ventaId) || null;
        if (!venta) {
            // No hay detalle en ventaDetalles => venta no activa o no encontrada => NO mostrar fila
            return false;
        }

        const situacionVenta = (venta.situacion || venta.ven_situacion || "").toUpperCase();
        // Solo mostramos filas cuyo detalle tenga venta ACTIVA (o sin campo de situación)
        return !situacionVenta || situacionVenta === "ACTIVA";
    });

    // 2) Construir las columnas solo para las filas que pasaron el filtro
    const data = rowsFiltradas.map((r) => {
        const fecha = r.cja_fecha
            ? new Date(r.cja_fecha).toLocaleString("es-GT")
            : "—";

        const tipo = r.cja_tipo || "—";
        const ref = r.cja_no_referencia || "—";
        const metodo = r.metodo || "—";
        const descripcion = r.cja_observaciones || "";
        const esIn = ["VENTA", "DEPOSITO", "AJUSTE_POS"].includes(tipo);
        const est = r.cja_situacion || "—";

        const ventaId = Number(r.venta_id || 0);
        const venta = ventaId ? (ventaDetalles.get(ventaId) || null) : null;

        const situacionVenta = (venta?.situacion || venta?.ven_situacion || "").toUpperCase();
        const ventaActiva = !!venta && (!situacionVenta || situacionVenta === "ACTIVA");

        console.log(
            `mov cja_id=${r.cja_id} ref=${ref} venta_id=${ventaId} tieneDetalle=${ventaActiva ? "SI" : "NO"}`
        );

        const cliente = ventaActiva ? (venta?.cliente || {}) : {};
        const vendedor = ventaActiva ? (venta?.vendedor || {}) : {};
        const precios = ventaActiva ? (venta?.precios || {}) : {};
        const pagosRealizados = ventaActiva ? (venta?.pagos_realizados || []) : [];
        const cuotasPendientes = ventaActiva ? (venta?.cuotas_pendientes || []) : [];

        // ===== Col 1: Fecha / Movimiento
        const descLimpia = descripcion.trim().toLowerCase();
        const mostrarDescripcion = descripcion && descLimpia !== "venta registrada";

        let ventaBadge = "";
        if (ventaActiva && ref && ref.startsWith("VENTA-")) {
            const num = ref.split("-")[1] || "";
            if (num) {
                ventaBadge = `
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700">
                        Venta #${num}
                    </span>
                `;
            }
        }

        // solo mostramos VENTA-XX si la venta es activa
        let refHtml = "";
        if (ventaActiva) {
            refHtml = `<span class="text-gray-400">${ref}</span>`;
        }

        const col1 = `
            <div class="space-y-1 text-[12px]">
                <div class="font-semibold text-gray-800">${fecha}</div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2 py-0.5 rounded-full font-semibold bg-gray-100 text-gray-700 uppercase">
                        ${tipo}
                    </span>
                    ${ventaBadge}
                    <span class="text-gray-500">${metodo}</span>
                    ${refHtml}
                </div>
                ${mostrarDescripcion
                    ? `<div class="text-gray-700">${descripcion}</div>`
                    : ""
                }
            </div>
        `;

        // ===== Col 2: Cliente / Productos
        let col2 = `<div class="text-[12px] text-gray-400">—</div>`;

        if (ventaActiva) {
            col2 = `
                <div class="space-y-2 text-[12px]">
                    <div>
                        <div class="font-semibold text-blue-700">
                            ${cliente.nombre || "Sin cliente"}
                        </div>
                        <div class="text-gray-500">
                            ${cliente.empresa && cliente.empresa !== "Sin Empresa" ? cliente.empresa : ""}
                            ${cliente.nit && cliente.nit !== "—" ? `${cliente.empresa ? " · " : ""}NIT: ${cliente.nit}` : ""}
                            ${cliente.telefono && cliente.telefono !== "—" ? `${(cliente.empresa || cliente.nit) ? " · " : ""}Tel: ${cliente.telefono}` : ""}
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-amber-700">
                            Productos (${venta.items_count || 0})
                        </div>
                        <div class="text-gray-800 line-clamp-2">
                            ${venta.concepto || "Sin productos"}
                        </div>
                    </div>

                    ${vendedor && vendedor.nombre
                        ? `<div class="text-gray-500">
                                Vendedor: ${vendedor.nombre}
                           </div>`
                        : ""
                    }
                </div>
            `;
        }

        // ===== Col 3: Precios / Resumen / Cuotas / Historial
        let col3 = `<div class="text-[12px] text-gray-400">—</div>`;

        if (ventaActiva) {
            const ultimosPagos = pagosRealizados.slice(-2);
            const extraPagos = pagosRealizados.length - ultimosPagos.length;

            const cuotasMostrar = cuotasPendientes.slice(0, 2);
            const extraCuotas = cuotasPendientes.length - cuotasMostrar.length;

            const cuotasHtml = cuotasPendientes.length ? `
                <div class="mt-1 space-y-1">
                    <div class="text-[11px] font-semibold text-gray-700">
                        Cuotas pendientes (${cuotasPendientes.length})
                    </div>
                    ${cuotasMostrar.map(c => `
                        <div class="flex justify-between items-center p-1.5 bg-gray-50 rounded">
                            <span class="text-[11px]">
                                #${c.numero}
                                ${c.en_revision
                                    ? '<span class="ml-1 px-1.5 py-0.5 text-[10px] rounded bg-amber-100 text-amber-800">En revisión</span>'
                                    : ''
                                }
                            </span>
                            <span class="text-[11px] font-semibold">${fmtQ(c.monto)}</span>
                            <span class="text-[10px] text-gray-500">${c.vence}</span>
                        </div>
                    `).join("")}
                    ${extraCuotas > 0
                        ? `<div class="text-[10px] text-gray-500">+${extraCuotas} cuota(s) más...</div>`
                        : ""
                    }
                </div>
            ` : "";

            const historialHtml = pagosRealizados.length ? `
                <div class="mt-1 space-y-1">
                    <div class="text-[11px] font-semibold text-gray-700">
                        Historial de pagos (${pagosRealizados.length})
                    </div>
                    ${ultimosPagos.map(p => `
                        <div class="flex justify-between items-center p-1.5 bg-green-50 rounded">
                            <span class="text-[11px]">${p.fecha}</span>
                            <span class="text-[11px] font-semibold text-green-700">
                                ${fmtQ(p.monto)}
                            </span>
                            <span class="text-[10px] text-gray-500">
                                ${p.metodo || ""}
                            </span>
                        </div>
                    `).join("")}
                    ${extraPagos > 0
                        ? `<div class="text-[10px] text-gray-500">+${extraPagos} pago(s) más...</div>`
                        : ""
                    }
                </div>
            ` : "";

            col3 = `
                <div class="space-y-2 text-[12px]">
                    <div class="bg-gray-50 rounded-lg p-2 grid grid-cols-2 gap-2">
                        <div>
                            <div class="text-[11px] font-semibold text-green-700">
                                Precios
                            </div>
                            <div class="text-[11px]">Individual: ${fmtQ(precios.individual || 0)}</div>
                            <div class="text-[11px]">Empresa: ${fmtQ(precios.empresa || 0)}</div>
                            ${precios.aplicado
                                ? `<div class="mt-1 text-[11px] text-emerald-700 font-semibold">
                                        Aplicado: ${fmtQ(precios.aplicado)}
                                   </div>`
                                : ""
                            }
                        </div>
                        <div>
                            <div class="text-[11px] font-semibold text-purple-700">
                                Resumen
                            </div>
                            <div class="text-[11px]">
                                Total: <span class="font-semibold">
                                    ${fmtQ(venta.monto_total || 0)}
                                </span>
                            </div>
                            <div class="text-[11px] text-emerald-700">
                                Pagado: <span class="font-semibold">
                                    ${fmtQ(venta.pagado || 0)}
                                </span>
                            </div>
                            <div class="text-[11px] text-rose-600">
                                Pendiente: <span class="font-semibold">
                                    ${fmtQ(venta.pendiente || 0)}
                                </span>
                            </div>
                        </div>
                    </div>

                    ${cuotasHtml}
                    ${historialHtml}
                </div>
            `;
        }

        // ===== Col 4: monto / estado / acciones
        const montoHtml = `
            <div class="text-right">
                <div class="font-semibold ${esIn ? "text-emerald-600" : "text-rose-600"}">
                    ${fmtQ(r.cja_monto)}
                </div>
                <div class="text-[11px] text-gray-500 uppercase tracking-wide">
                    ${esIn ? "INGRESO" : "EGRESO"}
                </div>
            </div>
        `;

        const estadoHtml =
            est === "PENDIENTE"
                ? `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700">PENDIENTE</span>`
                : est === "ACTIVO"
                    ? `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700">VALIDADO</span>`
                    : est === "ANULADA"
                        ? `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700">RECHAZADO</span>`
                        : `<span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700">${est}</span>`;

        const acciones =
            est === "PENDIENTE"
                ? `
            <div class="flex justify-end gap-1 mt-2">
                <button class="btn-validar-mov bg-emerald-600 hover:bg-emerald-700 text-white text-xs px-2 py-1 rounded"
                    data-id="${r.cja_id}"
                    data-tipo="${tipo}"
                    data-monto="${r.cja_monto}"
                    data-ref="${ref}"
                    data-descripcion="${descripcion}">
                    Validar
                </button>
                <button class="btn-rechazar-mov bg-rose-600 hover:bg-rose-700 text-white text-xs px-2 py-1 rounded"
                    data-id="${r.cja_id}"
                    data-tipo="${tipo}"
                    data-monto="${r.cja_monto}"
                    data-ref="${ref}"
                    data-descripcion="${descripcion}">
                    Rechazar
                </button>
            </div>
        `
                : "";

        const col4 = `
            <div class="space-y-2 text-[12px] text-right">
                ${montoHtml}
                ${estadoHtml}
                ${acciones}
            </div>
        `;

        return [col1, col2, col3, col4];
    });

    if (dtMovimientos) {
        try { dtMovimientos.destroy(); } catch (e) { console.warn("Error al destruir DataTable:", e); }
        dtMovimientos = null;
    }

    dtMovimientos = new DataTable("#tablaMovimientos", {
        searchable: true,
        sortable: true,
        fixedHeight: false,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: labelsES,
        data: {
            headings: [
                "Fecha / Movimiento",
                "Cliente / Productos",
                "Precios / Resumen",
                "Monto / Estado / Acciones",
            ],
            data,
        },
    });

    console.log("DataTable inicializado con", data.length, "filas");
};
















/* =========================
 *  Confirmar validación/rechazo de movimiento
 * ========================= */
const ConfirmarValidacionMovimiento = async (cjaId, tipo, monto, referencia, descripcion, accion) => {
    const accionTexto = accion === 'validar' ? 'validar' : 'rechazar';
    const icono = accion === 'validar' ? 'question' : 'warning';
    const textoConfirmacion = accion === 'validar'
        ? `¿Estás seguro de validar este movimiento?<br><br>
           <strong>Tipo:</strong> ${tipo}<br>
           <strong>Descripción:</strong> ${descripcion}<br>
           <strong>Referencia:</strong> ${referencia}<br>
           <strong>Monto:</strong> ${fmtQ(monto)}<br><br>
           Esta acción actualizará el saldo de caja.`
        : `¿Estás seguro de rechazar este movimiento?<br><br>
           <strong>Tipo:</strong> ${tipo}<br>
           <strong>Descripción:</strong> ${descripcion}<br>
           <strong>Referencia:</strong> ${referencia}<br>
           <strong>Monto:</strong> ${fmtQ(monto)}<br><br>
           Este movimiento será marcado como rechazado.`;

    const { isConfirmed } = await Swal.fire({
        title: `¿${accion === 'validar' ? 'Validar' : 'Rechazar'} Movimiento?`,
        html: textoConfirmacion,
        icon: icono,
        showCancelButton: true,
        confirmButtonText: `Sí, ${accionTexto}`,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: accion === 'validar' ? '#10b981' : '#ef4444'
    });

    if (isConfirmed) {
        await ValidarMovimiento(cjaId, accion);
    }
};

/* =========================
 *  Validar Movimiento de Caja
 * ========================= */
const ValidarMovimiento = async (cjaId, accion = 'validar') => {
    try {
        swalLoadingOpen(`${accion === 'validar' ? 'Validando' : 'Rechazando'} movimiento...`);

        const resp = await fetch(`${API}/movimientos/${cjaId}/${accion}`, {
            method: 'POST',
            headers: getHeaders(),
            body: JSON.stringify({})
        });

        const { codigo, mensaje } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            await Swal.fire("¡Éxito!", mensaje || `Movimiento ${accion === 'validar' ? 'validado' : 'rechazado'}`, "success");

            // Recargar todo para actualizar saldos
            await CargarStats();
            await CargarMovimientos();
            await BuscarPendientes();
        } else {
            await Swal.fire("Error", mensaje || `No se pudo ${accion} el movimiento`, "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error(`Error ${accion} movimiento:`, e);
        Swal.fire("Error", "Ocurrió un error inesperado", "error");
    }
};

// Agregar esto en el event listener general
document.addEventListener("click", (e) => {
    if (e.target.closest('.btn-validar-mov')) {
        const btn = e.target.closest('.btn-validar-mov');
        const cjaId = btn.dataset.id;
        const tipo = btn.dataset.tipo;
        const monto = btn.dataset.monto;
        const ref = btn.dataset.ref;
        const descripcion = btn.dataset.descripcion || "—";

        ConfirmarValidacionMovimiento(cjaId, tipo, monto, ref, descripcion, 'validar');
    }

    // Rechazar movimiento de caja
    if (e.target.closest('.btn-rechazar-mov')) {
        const btn = e.target.closest('.btn-rechazar-mov');
        const cjaId = btn.dataset.id;
        const tipo = btn.dataset.tipo;
        const monto = btn.dataset.monto;
        const ref = btn.dataset.ref;
        const descripcion = btn.dataset.descripcion || "—";

        ConfirmarValidacionMovimiento(cjaId, tipo, monto, ref, descripcion, 'rechazar');
    }
});



document.getElementById("btnFiltrarMovs")?.addEventListener("click", CargarMovimientos);

/* =========================
 *  Egresos
 *  Espera: { codigo:1, mensaje }
 * ========================= */
const AbrirEgreso = () => abrirModal("modalEgreso");
document.getElementById("btnAbrirEgreso")?.addEventListener("click", AbrirEgreso);

document.getElementById("btnGuardarEgreso")?.addEventListener("click", async (e) => {
    e.preventDefault();

    // Validación mínima (método, monto, motivo)
    const egMetodo = document.getElementById("egMetodo")?.value;
    const egMonto = document.getElementById("egMonto")?.value;
    const egMotivo = document.getElementById("egMotivo")?.value;
    if (!egMetodo || !egMonto || !egMotivo) {
        return Swal.fire("Campos vacíos", "Completa método, monto y motivo.", "info");
    }

    try {
        swalLoadingOpen("Guardando egreso...");
        const form = document.getElementById("formEgreso");
        const fd = new FormData(form);
        fd.append("_token", csrfToken);
        const resp = await fetch(`${API}/egresos`, { method: "POST", body: fd });
        const { codigo, mensaje } = await resp.json();
        swalLoadingClose();

        if (codigo === 1) {
            await Swal.fire("¡Éxito!", mensaje || "Egreso registrado", "success");
            cerrarModal("modalEgreso");
            form.reset();
            await CargarStats();
            await CargarMovimientos();
        } else {
            await Swal.fire("Error", mensaje || "No se pudo registrar", "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Egreso error:", e);
    }
});

/* =========================
 *  Upload Estado de Cuenta
 *  Preview: { codigo:1, data:{ path, headers, rows } }
 *  Procesar: { codigo:1, mensaje }
 * ========================= */
const zone = document.getElementById("uploadZone");
const inputFile = document.getElementById("archivoMovimientos");
const btnPreview = document.getElementById("btnVistaPrevia");
const btnProcesar = document.getElementById("btnProcesar");
const btnLimpiar = document.getElementById("btnLimpiar");
const fileInfo = document.getElementById("fileInfo");
const uploadContent = document.getElementById("uploadContent");
const fileName = document.getElementById("fileName");
const fileSize = document.getElementById("fileSize");
const bancoOrigen = document.getElementById("bancoOrigen");

const enableUploadActions = (ok) => {
    if (btnPreview) btnPreview.disabled = !ok;
    if (btnProcesar) btnProcesar.disabled = !ok || !uploadTmp;
};

const resetUpload = () => {
    if (inputFile) inputFile.value = "";
    uploadTmp = null;
    fileInfo?.classList.add("hidden");
    uploadContent?.classList.remove("hidden");
    enableUploadActions(false);

    const vista = document.getElementById("vistaPrevia");
    if (vista) vista.classList.add("hidden");

    if (dtPreview) {
        try { dtPreview.destroy(); } catch (_) { }
        dtPreview = null;
    }

    setTxt("totalMovimientos", "0");
};

const onFileSelected = () => {
    const f = inputFile?.files?.[0];
    if (!f) return;
    if (fileName) fileName.textContent = f.name;
    if (fileSize) fileSize.textContent = `${(f.size / (1024 * 1024)).toFixed(2)} MB`;
    fileInfo?.classList.remove("hidden");
    uploadContent?.classList.add("hidden");
    enableUploadActions(true);
};

zone?.addEventListener("click", () => inputFile?.click());
zone?.addEventListener("dragover", (e) => {
    e.preventDefault();
    zone.classList.add("dragover");
});
zone?.addEventListener("dragleave", () => zone.classList.remove("dragover"));
zone?.addEventListener("drop", (e) => {
    e.preventDefault();
    zone.classList.remove("dragover");
    if (e.dataTransfer.files.length) {
        inputFile.files = e.dataTransfer.files;
        onFileSelected();
    }
});
inputFile?.addEventListener("change", onFileSelected);

btnPreview?.addEventListener("click", async () => {
    const f = inputFile?.files?.[0];
    if (!f) return Swal.fire("Archivo faltante", "Selecciona un archivo.", "info");

    try {
        swalLoadingOpen("Subiendo archivo...");
        const fd = new FormData();
        fd.append("archivo", f);
        if (bancoOrigen?.value) fd.append("banco_id", bancoOrigen.value);
        fd.append("_token", csrfToken);

        const resp = await fetch(`${API}/movs/upload`, { method: "POST", body: fd });
        const { codigo, mensaje, data } = await resp.json();
        swalLoadingClose();

        if (codigo !== 1 || !data?.path) {
            return Swal.fire("Error", mensaje || "No se pudo previsualizar", "error");
        }

        uploadTmp = data;

        const vista = document.getElementById("vistaPrevia");
        if (vista) {
            vista.classList.remove("hidden");
            void vista.offsetHeight;
            // o: await new Promise(requestAnimationFrame);
        }

        const rows = (data.rows || []).map((r) => [
            r.fecha || "—",
            r.descripcion || "—",
            r.referencia || "—",
            r.monto ?? 0,
            r.detectado ?? "—",
        ]);

        if (dtPreview) {
            try { dtPreview.destroy(); } catch (_) { }
            dtPreview = null;
        }

        dtPreview = new DataTable("#tablaPrevia", {
            searchable: false,
            sortable: true,
            perPage: 25,
            perPageSelect: [10, 25, 50, 100],
            labels: labelsES,
            data: {
                headings: ["Fecha", "Descripción", "Referencia", "Monto", "Detectado"],
                data: rows,
            },
            columns: [
                { select: 0, type: "string" },
                { select: 1, type: "string" },
                { select: 2, type: "string" },
                {
                    select: 3,
                    type: "number",
                    render: (val) =>
                        `<div class="text-right tabular-nums font-medium">${fmtQ(Number(val || 0))}</div>`,
                },
                { select: 4, type: "string" },
            ],
        });

        setTimeout(() => {
            dtPreview?.page(1);
            dtPreview?.refresh(); // asegura recalculo de anchos
            document.querySelectorAll("#tablaPrevia thead th:nth-child(4)")
                .forEach((th) => th.classList.add("text-right"));
        }, 0);

        setTxt("totalMovimientos", String(rows.length));
        enableUploadActions(true);
    } catch (e) {
        swalLoadingClose();
        console.error("Preview error:", e);
    }
});


btnProcesar?.addEventListener("click", async () => {
    if (!uploadTmp?.path) {
        return Swal.fire("Primero la vista previa", "Genera la vista previa.", "info");
    }
    try {
        swalLoadingOpen("Procesando archivo...");
        const fi = document.getElementById("fechaInicio")?.value || "";
        const ff = document.getElementById("fechaFin")?.value || "";
        const body = JSON.stringify({
            archivo_path: uploadTmp.path,
            banco_id: bancoOrigen?.value ? Number(bancoOrigen.value) : undefined,
            fecha_inicio: fi || undefined,
            fecha_fin: ff || undefined
        });

        const resp = await fetch(`${API}/movs/procesar`, {
            method: "POST",
            headers: getHeaders(),
            body,
        });
        const { codigo, mensaje, data } = await resp.json();
        swalLoadingClose();

        if (codigo !== 1) {
            return Swal.fire("Error", mensaje || "No se pudo procesar", "error");
        }

        const ecId = data?.ec_id;
        if (!ecId) {
            return Swal.fire("Error", "No se obtuvo el control (ec_id)", "error");
        }

        await ConciliarAutomatico(ecId, { auto_aprobar: true, tolerancia: 1.00 });

        await CargarStats();
        await CargarMovimientos();
        await BuscarPendientes();

    } catch (e) {
        swalLoadingClose();
        console.error("Procesar error:", e);
    }
});


const ConciliarAutomatico = async (ecId) => {
    try {
        swalLoadingOpen("Conciliando pagos...");
        const resp = await fetch(`${API}/conciliar`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify({ ec_id: ecId }) // sin auto_aprobar: primero revisamos
        });
        const { codigo, data, mensaje } = await resp.json();
        swalLoadingClose();

        if (codigo !== 1) {
            return Swal.fire("Error", mensaje || "Falló la conciliación", "error");
        }

        const { coincidencias = [], revision = [], sin_match = [] } = data;

        // Si no hay coincidencias, actualiza panel y avisa
        if (!coincidencias.length) {
            const seccion = document.getElementById("seccionConciliacion");
            const matchesDiv = document.getElementById("matchesList");
            const noMatchDiv = document.getElementById("noMatchList");
            if (seccion) seccion.classList.remove("hidden");
            matchesDiv.innerHTML = '<p class="text-sm text-gray-500">No hubo coincidencias.</p>';
            noMatchDiv.innerHTML = sin_match.length
                ? `<h4 class="font-semibold text-gray-700 mb-2">Sin coincidencia (${sin_match.length})</h4>`
                + sin_match.map(n => `
              <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                <div class="flex justify-between">
                  <div>
                    <p class="font-semibold text-sm">Venta #${n.venta_id}</p>
                    <p class="text-xs text-gray-600">Ref cliente: ${n.ps_referencia || "—"}</p>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-gray-700">${fmtQ(n.ps_monto || 0)}</p>
                    <span class="text-xs px-2 py-1 bg-gray-600 text-white rounded">SIN MATCH</span>
                  </div>
                </div>
              </div>`).join("")
                : "";
            return Swal.fire("Sin coincidencias", "No se detectaron pagos para validar.", "info");
        }

        // Mostrar detalle antes de validar
        const filas = coincidencias.map((c) => {
            const ps = c._ps_row || {};
            const debiaEnvio = Number(ps.ps_monto_total_cuotas_front ?? 0);
            const pendienteVenta = Number(ps.pago_monto_pendiente ?? 0);
            const debia = debiaEnvio > 0 ? debiaEnvio : pendienteVenta;
            const depositado = Number(ps.ps_monto_comprobante ?? c.banco_monto ?? 0);
            const dif = depositado - debia;
            const difCls = dif === 0 ? "text-emerald-700" : dif > 0 ? "text-amber-700" : "text-rose-700";
            return `
        <tr class="border-b">
          <td class="py-2 pr-3 text-sm">#${c.venta_id}</td>
          <td class="py-2 pr-3 text-sm">${ps.ps_referencia || c.banco_ref || "—"}</td>
          <td class="py-2 pr-3 text-sm">${c.banco_fecha || "—"}</td>
          <td class="py-2 pr-3 text-sm text-right">${fmtQ(debia)}</td>
          <td class="py-2 pr-3 text-sm text-right">${fmtQ(depositado)}</td>
          <td class="py-2 pr-3 text-sm text-right ${difCls} font-semibold">${fmtQ(dif)}</td>
        </tr>`;
        }).join("");

        const html = `
      <div class="text-left">
        <p class="mb-3">Se detectaron <b>${coincidencias.length}</b> coincidencia(s). Revisa el detalle:</p>
        <div class="overflow-x-auto border rounded-lg">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="py-2 px-3 text-left">Venta</th>
                <th class="py-2 px-3 text-left">Ref.</th>
                <th class="py-2 px-3 text-left">Fecha banco</th>
                <th class="py-2 px-3 text-right">Debía</th>
                <th class="py-2 px-3 text-right">Depositado</th>
                <th class="py-2 px-3 text-right">Diferencia</th>
              </tr>
            </thead>
            <tbody>${filas}</tbody>
          </table>
        </div>
        <p class="mt-3">¿Deseas <b>validarlas</b> y registrar los pagos automáticamente?</p>
      </div>`;

        const { isConfirmed } = await Swal.fire({
            title: "Coincidencias encontradas",
            html,
            icon: "question",
            width: 800,
            showCancelButton: true,
            confirmButtonText: "Sí, validar ahora",
            cancelButtonText: "No, revisar después"
        });

        if (!isConfirmed) {
            return; // el admin decidió no validar aún
        }

        // Aprobar una por una y verificar resultado
        swalLoadingOpen("Validando coincidencias...");
        const resultados = [];
        for (const c of coincidencias) {
            try {
                const r = await fetch(`${API}/aprobar`, {
                    method: "POST",
                    headers: getHeaders(),
                    body: JSON.stringify({
                        ps_id: c.ps_id,
                        observaciones: `Validado por conciliación (Ref: ${c.banco_ref || ""})`
                    })
                });
                const js = await r.json().catch(() => ({}));
                resultados.push({
                    venta_id: c.venta_id,
                    ps_id: c.ps_id,
                    ok: js?.codigo === 1,
                    msg: js?.mensaje || js?.detalle || "Sin detalle"
                });
            } catch (err) {
                resultados.push({
                    venta_id: c.venta_id,
                    ps_id: c.ps_id,
                    ok: false,
                    msg: "Error de red o servidor"
                });
            }
        }
        swalLoadingClose();

        const okCount = resultados.filter(x => x.ok).length;
        const fail = resultados.filter(x => !x.ok);

        if (fail.length) {
            const lista = fail.map(f => `Venta #${f.venta_id} (ps ${f.ps_id}): ${f.msg}`).join("<br>");
            await Swal.fire({
                icon: "warning",
                title: "Validación parcial",
                html: `Aprobados: <b>${okCount}</b> · Fallidos: <b>${fail.length}</b><br><br>${lista}`,
                confirmButtonText: "Entendido"
            });
        } else {
            await Swal.fire("¡Listo!", `Se validaron ${okCount} coincidencia(s).`, "success");
        }

        // refrescar
        await CargarStats();
        await CargarMovimientos();
        await BuscarPendientes();

    } catch (e) {
        swalLoadingClose();
        console.error("Conciliar error:", e);
        Swal.fire("Error", "Ocurrió un error inesperado durante la conciliación.", "error");
    }
};


btnLimpiar?.addEventListener("click", resetUpload);


/* =========================
 *  Ingresos/Egresos
 * ========================= */
const AbrirIngreso = () => abrirModal("modalIngreso");

document.getElementById("btnAbrirIngreso")?.addEventListener("click", AbrirIngreso);
document.getElementById("btnAbrirEgreso")?.addEventListener("click", AbrirEgreso);

// Guardar INGRESO - VERSIÓN SIMPLIFICADA
document.getElementById("btnGuardarIngreso")?.addEventListener("click", async (e) => {
    e.preventDefault();

    const ingMonto = parseFloat(document.getElementById("ingMonto")?.value || 0);
    const ingConcepto = document.getElementById("ingConcepto")?.value;

    if (!ingMonto || !ingConcepto) {
        return Swal.fire("Campos vacíos", "Completa monto y concepto.", "info");
    }

    try {
        swalLoadingOpen("Guardando ingreso...");

        // OBJETO SIMPLE SIN metodo_id
        const data = {
            monto: ingMonto,
            concepto: ingConcepto,
            fecha: document.getElementById("ingFecha")?.value || null,
            referencia: document.getElementById("ingReferencia")?.value || null,
        };

        const resp = await fetch(`${API}/ingresos`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify(data)
        });

        const result = await resp.json();
        swalLoadingClose();

        if (result.codigo === 1) {
            await Swal.fire("¡Éxito!", result.mensaje || "Ingreso registrado", "success");
            cerrarModal("modalIngreso");
            document.getElementById("formIngreso").reset();
            await CargarStats();
            await CargarMovimientos();
        } else {
            await Swal.fire("Error", result.mensaje || "No se pudo registrar", "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Ingreso error:", e);
        Swal.fire("Error", "Ocurrió un error inesperado", "error");
    }
});

// Guardar EGRESO - VERSIÓN SIMPLIFICADA
document.getElementById("btnGuardarEgreso")?.addEventListener("click", async (e) => {
    e.preventDefault();

    const egMonto = parseFloat(document.getElementById("egMonto")?.value || 0);
    const egMotivo = document.getElementById("egMotivo")?.value;

    if (!egMonto || !egMotivo) {
        return Swal.fire("Campos vacíos", "Completa monto y motivo.", "info");
    }

    try {
        swalLoadingOpen("Guardando egreso...");

        // OBJETO SIMPLE SIN metodo_id
        const data = {
            monto: egMonto,
            motivo: egMotivo,
            fecha: document.getElementById("egFecha")?.value || null,
            referencia: document.getElementById("egReferencia")?.value || null,
        };

        const resp = await fetch(`${API}/egresos`, {
            method: "POST",
            headers: getHeaders(),
            body: JSON.stringify(data)
        });

        const result = await resp.json();
        swalLoadingClose();

        if (result.codigo === 1) {
            await Swal.fire("¡Éxito!", result.mensaje || "Egreso registrado", "success");
            cerrarModal("modalEgreso");
            document.getElementById("formEgreso").reset();
            await CargarStats();
            await CargarMovimientos();
        } else {
            await Swal.fire("Error", result.mensaje || "No se pudo registrar", "error");
        }
    } catch (e) {
        swalLoadingClose();
        console.error("Egreso error:", e);
        Swal.fire("Error", "Ocurrió un error inesperado", "error");
    }
});

/* =========================
 *  Filtros y refresco
 * ========================= */
document.getElementById("buscarFactura")?.addEventListener("input", debounce(BuscarPendientes, 350));
document.getElementById("filtroEstado")?.addEventListener("change", BuscarPendientes);

document.getElementById("btnRefrescar")?.addEventListener("click", async () => {
    await CargarStats();
    await BuscarPendientes();
    await CargarMovimientos();
    await Swal.fire({
        title: "Actualizado",
        text: "Datos refrescados",
        icon: "success",
        timer: 1200,
        showConfirmButton: false,
    });
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        ["modalValidar", "modalEgreso", "modalComprobante", "modalDetalleVenta"].forEach(cerrarModal);
    }
});

/* =========================
 *  Init
 * ========================= */
const Init = async () => {
    initTablaPendientes();
    initTablaMovimientos();
    initTablaPreview();
    await CargarStats();
    await BuscarPendientes();

    // 🔹 Primero cargar detalle de ventas
    await CargarMisPagos();

    // 🔹 Luego movimientos, que ya usarán ventaDetalles
    await CargarMovimientos();
};

document.addEventListener("DOMContentLoaded", Init);
