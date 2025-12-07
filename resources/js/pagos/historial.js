import DataTable from "vanilla-datatables";
import "vanilla-datatables/src/vanilla-dataTables.css";
import Swal from "sweetalert2";

// ======================= VARIABLES GLOBALES =======================
let dataTableInstance = null;
const ventaDetalles = new Map(); // Para guardar info detallada de cada venta
let metodosPagoMap = new Map();

// ======================= INICIALIZACIÓN =======================
document.addEventListener("DOMContentLoaded", async () => {
    // 1. Cargar Métodos de Pago
    await CargarMetodosPago();

    // 2. Configurar Fechas por defecto (Mes actual)
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    const inputDesde = document.getElementById("filterFechaDesde");
    const inputHasta = document.getElementById("filterFechaHasta");

    if (inputDesde) inputDesde.valueAsDate = firstDay;
    if (inputHasta) inputHasta.valueAsDate = lastDay;

    // 3. Cargar Datos Iniciales
    await CargarHistorial();

    // 4. Event Listeners Filtros
    const btnAplicar = document.getElementById("btnAplicarFiltros");
    if (btnAplicar) btnAplicar.addEventListener("click", CargarHistorial);

    const btnLimpiar = document.getElementById("btnLimpiarFiltros");
    if (btnLimpiar) btnLimpiar.addEventListener("click", LimpiarFiltros);

    // Enter en buscador
    const inputBusqueda = document.getElementById("filterBusqueda");
    if (inputBusqueda) {
        inputBusqueda.addEventListener("keyup", (e) => {
            if (e.key === "Enter") CargarHistorial();
        });
    }

    // 5. Event Listeners Modal
    document.addEventListener("click", (e) => {
        // Abrir Modal Detalle
        if (e.target.closest('.btn-ver-detalle-venta')) {
            const btn = e.target.closest('.btn-ver-detalle-venta');
            const ventaId = Number(btn.dataset.ventaId);
            mostrarDetalleCompleto(ventaId);
        }

        // Cerrar Modal
        if (e.target.closest('[data-modal-close]')) {
            cerrarModal("modalDetalleVenta");
        }
        if (e.target.hasAttribute('data-modal-backdrop')) {
            cerrarModal("modalDetalleVenta");
        }
    });
});

// ======================= FUNCIONES DE CARGA =======================

const CargarMetodosPago = async () => {
    try {
        // Fallback: Usar stats para sacar métodos
        const respStats = await fetch("/admin/pagos/stats");
        if (respStats.ok) {
            const json = await respStats.json();
            if (json.codigo === 1 && json.data.saldos) {
                const select = document.getElementById("filterMetodo");
                if (select) {
                    json.data.saldos.forEach(m => {
                        metodosPagoMap.set(m.metodo_id, m.metodo);
                        const opt = document.createElement("option");
                        opt.value = m.metodo_id;
                        opt.textContent = m.metodo;
                        select.appendChild(opt);
                    });
                }
            }
        }
    } catch (e) {
        console.error("Error cargando métodos:", e);
    }
};

const CargarHistorial = async () => {
    const from = document.getElementById("filterFechaDesde")?.value || "";
    const to = document.getElementById("filterFechaHasta")?.value || "";
    const metodoId = document.getElementById("filterMetodo")?.value || "";
    const tipo = document.getElementById("filterTipo")?.value || "";
    const situacion = document.getElementById("filterSituacion")?.value || "";
    const q = document.getElementById("filterBusqueda")?.value || "";

    const btn = document.getElementById("btnAplicarFiltros");
    let originalText = "";
    if (btn) {
        originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
        btn.disabled = true;
    }

    try {
        const params = new URLSearchParams({
            from, to, metodo_id: metodoId, tipo, situacion, q
        });

        const url = `/pagos/movimientos?${params.toString()}`;
        const resp = await fetch(url);

        if (!resp.ok) throw new Error("Error en la petición");

        const json = await resp.json();
        if (json.codigo === 1) {
            renderTabla(json.data.movimientos);
            const statTotal = document.getElementById("statTotalIngresos");
            if (statTotal) statTotal.textContent = fmtQ(json.data.total);
        } else {
            Swal.fire("Error", json.mensaje || "No se pudieron cargar los datos", "error");
        }

    } catch (e) {
        console.error(e);
        Swal.fire("Error", "Ocurrió un error al cargar el historial", "error");
    } finally {
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
};

const LimpiarFiltros = () => {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    const iDesde = document.getElementById("filterFechaDesde");
    const iHasta = document.getElementById("filterFechaHasta");
    if (iDesde) iDesde.valueAsDate = firstDay;
    if (iHasta) iHasta.valueAsDate = lastDay;

    const iMetodo = document.getElementById("filterMetodo");
    if (iMetodo) iMetodo.value = "";

    const iTipo = document.getElementById("filterTipo");
    if (iTipo) iTipo.value = "";

    const iSit = document.getElementById("filterSituacion");
    if (iSit) iSit.value = "";

    const iBus = document.getElementById("filterBusqueda");
    if (iBus) iBus.value = "";

    CargarHistorial();
};

// ======================= RENDERIZADO =======================

const renderTabla = (rows) => {
    // Destruir instancia previa
    if (dataTableInstance) {
        dataTableInstance.destroy();
        dataTableInstance = null;
    }

    const tbody = document.querySelector("#tablaHistorial tbody");
    if (!tbody) return;

    tbody.innerHTML = "";
    ventaDetalles.clear(); // Limpiar cache de detalles

    // Pre-procesar datos para el mapa de detalles
    rows.forEach(r => {
        if (r.cja_id_venta) {
            ventaDetalles.set(Number(r.cja_id_venta), {
                venta_id: r.cja_id_venta,
                fecha: r.cja_fecha,
                cliente: r.cliente || {},
                vendedor: r.vendedor || {},
                concepto: r.productos ? r.productos.concepto : (r.cja_observaciones || "—"),
                items_count: r.productos ? r.productos.items_count : 0,
                monto_total: r.venta_total || 0,
            });
        }
    });

    const newRows = rows.map(r => {
        const fecha = new Date(r.cja_fecha).toLocaleString('es-GT', {
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit'
        });

        // Tipo Badge
        let tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-gray-100 text-gray-600">${r.cja_tipo}</span>`;
        if (r.cja_tipo === 'VENTA') tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-700">VENTA</span>`;
        if (r.cja_tipo === 'DEPOSITO') tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-blue-100 text-blue-700">DEPÓSITO</span>`;
        if (r.cja_tipo === 'EGRESO') tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-rose-100 text-rose-700">EGRESO</span>`;
        if (r.cja_tipo === 'AJUSTE_POS') tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-indigo-100 text-indigo-700">AJUSTE POS</span>`;
        if (r.cja_tipo === 'AJUSTE_NEG') tipoBadge = `<span class="px-2 py-1 rounded text-xs font-bold bg-orange-100 text-orange-700">AJUSTE NEG</span>`;

        // Descripción
        let desc = r.cja_observaciones || "—";
        if (r.cliente) {
            desc = `
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900">${r.cliente.nombre}</span>
                    <span class="text-xs text-gray-500">${r.cliente.empresa || ""}</span>
                    <span class="text-xs text-gray-400 italic mt-0.5">${r.productos ? r.productos.concepto : desc}</span>
                </div>
            `;
        }

        // Método
        let metodo = r.metodo || "—";

        // Monto
        const esIngreso = ["VENTA", "DEPOSITO", "AJUSTE_POS", "PAGO_DEUDA"].includes(r.cja_tipo);
        const montoClass = esIngreso ? "text-emerald-600" : "text-rose-600";
        const monto = `<span class="font-bold ${montoClass}">${fmtQ(r.cja_monto)}</span>`;

        // Estado
        const est = r.cja_situacion || "—";
        let estadoBadge = `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">${est}</span>`;
        if (est === 'ACTIVO') estadoBadge = `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">ACTIVO</span>`;
        if (est === 'ANULADO') estadoBadge = `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">ANULADO</span>`;
        if (est === 'EN_TIENDA') estadoBadge = `<span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800">EN TIENDA</span>`;

        // Acciones
        let acciones = "";
        if (r.cja_id_venta) {
            acciones = `
                <button class="btn-ver-detalle-venta text-blue-600 hover:text-blue-900 transition-colors p-2 rounded-full hover:bg-blue-50"
                    data-venta-id="${r.cja_id_venta}" title="Ver Detalle Completo">
                    <i class="fas fa-eye"></i>
                </button>
            `;
        }

        return [
            fecha,
            tipoBadge,
            desc,
            r.cja_no_referencia || "—",
            metodo,
            monto,
            estadoBadge,
            acciones
        ];
    });

    const tableEl = document.getElementById("tablaHistorial");
    if (tableEl) {
        dataTableInstance = new DataTable(tableEl, {
            data: {
                headings: ["Fecha", "Tipo", "Descripción / Cliente", "Ref.", "Método", "Monto", "Estado", "Acciones"],
                data: newRows
            },
            perPage: 10,
            perPageSelect: [10, 25, 50, 100],
            labels: {
                placeholder: "Filtrar en tabla...",
                perPage: "{select} registros",
                noRows: "No se encontraron registros",
                info: "{start}-{end} de {rows}",
            },
            searchable: false,
        });
    }
};

// ======================= MEGA GUI LOGIC =======================

const mostrarDetalleCompleto = async (ventaId) => {
    // 1. Mostrar modal con loading o datos parciales
    abrirModal("modalDetalleVenta");

    // Resetear campos
    setTxt("mdvVenta", `#${ventaId}`);
    setTxt("mdvCliente", "Cargando...");

    try {
        // 2. Fetch detalle completo de la venta
        const resp = await fetch(`/api/ventas/${ventaId}`);
        if (!resp.ok) throw new Error("Error cargando detalle de venta");

        const json = await resp.json();
        if (!json.data) throw new Error("No data");

        const venta = json.data;

        // 3. Popular Modal

        // Header
        setTxt("mdvVenta", `#${venta.ven_id}`);
        setTxt("mdvFecha", venta.ven_fecha ? new Date(venta.ven_fecha).toLocaleString() : "—");

        const estadoBadge = document.getElementById("mdvEstadoBadge");
        if (estadoBadge) {
            const st = (venta.ven_situacion || "").toUpperCase();
            estadoBadge.textContent = st;
            let cls = "bg-gray-700 text-white";
            if (st === 'ACTIVA' || st === 'AUTORIZADA') cls = "bg-emerald-600 text-white";
            if (st === 'PENDIENTE') cls = "bg-yellow-500 text-white";
            if (st === 'ANULADA') cls = "bg-rose-600 text-white";
            if (st === 'COMPLETADA' || st === 'FACTURADA') cls = "bg-blue-600 text-white";
            estadoBadge.className = `px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider ${cls}`;
        }

        // Cliente
        setTxt("mdvCliente", venta.cliente ? venta.cliente.nombre : "Consumidor Final");
        setTxt("mdvNit", venta.cliente && venta.cliente.nit ? `NIT: ${venta.cliente.nit}` : "NIT: CF");
        setTxt("mdvVendedor", venta.vendedor ? venta.vendedor.nombre : "—");

        // Productos
        const tbodyProd = document.getElementById("mdvTablaProductos");
        if (tbodyProd) {
            tbodyProd.innerHTML = "";
            let totalVenta = 0;

            if (venta.detalles && venta.detalles.length > 0) {
                venta.detalles.forEach(d => {
                    const subtotal = Number(d.det_subtotal || 0);
                    totalVenta += subtotal;
                    tbodyProd.innerHTML += `
                        <tr>
                            <td class="px-5 py-3 text-gray-700">
                                <div class="font-medium">${d.producto_nombre || "Item"}</div>
                            </td>
                            <td class="px-5 py-3 text-center text-gray-600">${d.det_cantidad}</td>
                            <td class="px-5 py-3 text-right text-gray-600">${fmtQ(d.det_precio_unitario)}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-900">${fmtQ(subtotal)}</td>
                        </tr>
                    `;
                });
                setTxt("mdvItemsCount", `${venta.detalles.length} items`);
            } else {
                tbodyProd.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-gray-500">Sin detalles</td></tr>`;
            }
            setTxt("mdvTotalVenta", fmtQ(totalVenta));
        }

        // Financiero
        const total = Number(venta.ven_total_vendido || 0);
        let pagado = 0;
        let pagosList = [];

        if (venta.pagos && Array.isArray(venta.pagos)) {
            pagosList = venta.pagos;
            pagado = pagosList.reduce((acc, p) => acc + Number(p.monto || 0), 0);
        } else if (venta.pago_master) {
            pagado = Number(venta.pago_master.pago_monto_pagado || 0);
        }

        const pendiente = Math.max(0, total - pagado);

        setTxt("mdvPagado", fmtQ(pagado));
        setTxt("mdvPendiente", fmtQ(pendiente));

        const pct = total > 0 ? Math.min(100, (pagado / total) * 100) : 0;
        setTxt("mdvProgresoTexto", `${pct.toFixed(0)}%`);
        const barra = document.getElementById("mdvBarraProgreso");
        if (barra) barra.style.width = `${pct}%`;

        // Historial Pagos
        const listaPagos = document.getElementById("mdvListaPagos");
        if (listaPagos) {
            if (pagosList.length === 0) {
                listaPagos.innerHTML = `<div class="p-5 text-center text-gray-500 text-sm">No hay pagos registrados.</div>`;
            } else {
                listaPagos.innerHTML = pagosList.map(p => `
                    <div class="p-4 flex justify-between items-center hover:bg-gray-50 transition-colors">
                        <div>
                            <p class="font-medium text-gray-900 text-sm">${p.metodo || "Pago"}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                ${p.fecha ? new Date(p.fecha).toLocaleDateString() : "—"} • Ref: ${p.referencia || "—"}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-emerald-600 text-sm">${fmtQ(p.monto)}</p>
                            ${p.comprobante ? `
                                <a href="/storage/${p.comprobante}" target="_blank" class="text-[10px] text-blue-600 hover:underline flex items-center justify-end gap-1 mt-1">
                                    <i class="fas fa-paperclip"></i> Ver
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `).join("");
            }
        }

        // Facturación
        const infoFac = document.getElementById("mdvInfoFactura");
        if (infoFac) {
            const st = (venta.ven_situacion || "").toUpperCase();
            if (st === 'FACTURADA' || st === 'COMPLETADA') {
                infoFac.innerHTML = `
                    <div class="bg-green-50 rounded-lg p-3 border border-green-100">
                        <div class="flex items-center justify-center mb-2">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <p class="text-green-800 font-medium text-sm">Venta Facturada</p>
                        <button class="mt-2 text-xs bg-white border border-green-200 text-green-700 px-3 py-1.5 rounded hover:bg-green-50 transition-colors shadow-sm">
                            Ver Factura
                        </button>
                    </div>
                `;
            } else {
                infoFac.innerHTML = `
                    <div class="text-center py-2">
                        <p class="text-gray-400 text-sm mb-2">Pendiente de facturación</p>
                        <span class="inline-block px-3 py-1 bg-gray-100 text-gray-500 text-xs rounded-full">
                            Sin Factura
                        </span>
                    </div>
                `;
            }
        }

        // Botón Ir
        const btnIr = document.getElementById("btnIrVenta");
        if (btnIr) btnIr.href = `/ventas?buscar=${venta.ven_id}`;

    } catch (e) {
        console.error(e);
        Swal.fire("Error", "No se pudo cargar el detalle completo de la venta.", "error");
    }
};

// ======================= UTILIDADES =======================

const abrirModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.remove("hidden");
};

const cerrarModal = (id) => {
    const el = document.getElementById(id);
    if (el) el.classList.add("hidden");
};

const setTxt = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
};

const fmtQ = (val) => {
    return "Q " + Number(val).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
