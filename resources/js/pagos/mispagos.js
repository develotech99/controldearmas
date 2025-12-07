// DataTables v2 + Tailwind, sin jQuery
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import Swal from 'sweetalert2';
import { createWorker } from 'tesseract.js';
import PaymentAnalyzer from './Analizador';



const REQUIRE_COMPROBANTE = false;

/* ==== Utils ==== */
const fmtQ = n =>
    'Q ' + Number(n ?? 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const badge = (estado) => {
    const s = String(estado || '').toUpperCase();
    const cls = {
        PENDIENTE: 'bg-yellow-100 text-yellow-800',
        VENCIDA: 'bg-red-100 text-red-800',
        COMPLETADO: 'bg-green-100 text-green-800',
        PARCIAL: 'bg-blue-100 text-blue-800',
        EN_REVISION: 'bg-amber-100 text-amber-800'
    }[s] || 'bg-gray-100 text-gray-800';
    return `<span class="px-2 py-1 text-xs font-semibold rounded ${cls}">${estado ?? 'N/D'}</span>`;
};


const BANKS = { '1': 'Banrural', '2': 'Banco Industrial', '3': 'BANTRAB' };

const detectBanco = (text) => {
    const t = (text || '').toLowerCase().replace(/\s+/g, ' ');
    if (/(banrural|banco de desarrollo rural|b\s*\.?\s*rural)/.test(t)) return { id: '1', nombre: 'Banrural' };
    if (/(banco industrial|bi en l[ií]nea|b\s*\.?\s*industrial|\bbi\b(?!.*rural))/.test(t)) return { id: '2', nombre: 'Banco Industrial' };
    if (/(bantrab|banco de los trabajadores)/.test(t)) return { id: '3', nombre: 'BANTRAB' };
    return { id: null, nombre: '' };
};


const ES_LANG = {
    processing: "Procesando...", search: "Buscar:", lengthMenu: "Mostrar _MENU_ registros",
    info: "Mostrando _START_ a _END_ de _TOTAL_ registros", infoEmpty: "Mostrando 0 a 0 de 0 registros",
    infoFiltered: "(filtrado de _MAX_ registros totales)", loadingRecords: "Cargando...",
    zeroRecords: "No se encontraron registros", emptyTable: "Sin datos disponibles",
    paginate: { first: "Primero", previous: "Anterior", next: "Siguiente", last: "Último" },
    aria: { sortAscending: ": activar para ordenar ascendente", sortDescending: ": activar para ordenar descendente" }
};


const ventaIndex = new Map();
let currentStep = 1;
let selectedCuotas = [];

const showSuccess = (title, text) => Swal.fire({ icon: 'success', title, text, confirmButtonColor: '#3B82F6' });
const showError = (title, text) => Swal.fire({ icon: 'error', title, text, confirmButtonColor: '#EF4444' });
const showWarning = (title, text) => Swal.fire({ icon: 'warning', title, text, confirmButtonColor: '#F59E0B' });
const showLoading = (title) => Swal.fire({ title, allowOutsideClick: false, didOpen: () => Swal.showLoading() });
const confirmAction = async (title, text) => {
    const r = await Swal.fire({ title, text, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar', confirmButtonColor: '#3B82F6', cancelButtonColor: '#6B7280' });
    return r.isConfirmed;
};

const datatable = new DataTable('#tablaFacturas', {
    data: [],
    pageLength: 10,
    responsive: true,
    language: ES_LANG,
    order: [[1, 'desc']], // Ordenar por fecha descendente
    columns: [
        {
            title: 'Factura #',
            data: 'venta_id',
            render: (d) => `<span class="font-bold text-blue-600">#${d}</span>`
        },
        {
            title: 'Fecha',
            data: 'fecha',
            render: (d, type) => {
                if (type === 'sort' || type === 'type') {
                    return new Date(d).getTime();
                }
                const fecha = new Date(d);
                return `<span class="text-sm text-gray-700">${fecha.toLocaleDateString('es-GT')}</span>`;
            }
        },
        {
            title: 'Cliente',
            data: null,
            render: (d, t, row) => {
                const cliente = row.cliente || {};
                let html = `<div class="text-sm">
                    <div class="font-semibold text-gray-900">${cliente.nombre || 'N/A'}</div>`;

                // Si es cliente empresa (tipo 3), mostrar empresa
                if (cliente.tipo === 3 && cliente.empresa && cliente.empresa !== 'Sin Empresa') {
                    html += `<div class="text-xs text-gray-600 mt-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-blue-800">
                            <i class="fas fa-building text-xs mr-1"></i>
                            ${cliente.empresa}
                        </span>
                    </div>`;
                }

                // NIT si existe
                if (cliente.nit && cliente.nit !== '—') {
                    html += `<div class="text-xs text-gray-500 mt-0.5">NIT: ${cliente.nit}</div>`;
                }

                html += `</div>`;
                return html;
            }
        },
        {
            title: 'Vendedor',
            data: null,
            render: (d, t, row) => {
                const vendedor = row.vendedor || {};
                return `<div class="text-sm">
                    <div class="text-gray-700">${vendedor.nombre || 'N/A'}</div>
                </div>`;
            }
        },
        {
            title: 'Productos',
            data: 'concepto',
            render: (d, t, row) => {
                const concepto = d || 'Sin productos';
                const itemsCount = row.items_count || 0;
                return `<div class="text-xs">
                    <div class="text-gray-700 max-w-xs truncate font-medium" title="${concepto}">
                        ${concepto}
                    </div>
                    <div class="text-gray-500 mt-0.5">${itemsCount} artículo${itemsCount !== 1 ? 's' : ''}</div>
                </div>`;
            }
        },
        {
            title: 'Precio Aplicado',
            data: null,
            render: (d, t, row) => {
                const precios = row.precios || {};
                const cliente = row.cliente || {};

                let html = `<div class="text-xs">`;

                if (cliente.tipo === 3) {
                    // Cliente empresa - mostrar precio empresa
                    html += `<div class="font-semibold text-blue-600">${fmtQ(precios.empresa || 0)}</div>`;
                    html += `<div class="text-gray-500">Precio Empresa</div>`;
                } else {
                    // Cliente individual - mostrar precio individual
                    html += `<div class="font-semibold text-gray-900">${fmtQ(precios.individual || 0)}</div>`;
                    html += `<div class="text-gray-500">Precio Individual</div>`;
                }

                // Precio real aplicado si es diferente
                if (precios.aplicado && precios.aplicado !== precios.individual && precios.aplicado !== precios.empresa) {
                    html += `<div class="text-green-600 font-medium mt-1">${fmtQ(precios.aplicado)}</div>`;
                    html += `<div class="text-gray-500 text-xs">Precio Real</div>`;
                }

                html += `</div>`;
                return html;
            }
        },
        {
            title: 'Total Venta',
            data: 'monto_total',
            render: (d) => `<span class="font-bold text-gray-900 text-sm">${fmtQ(d)}</span>`
        },
        {
            title: 'Pagado',
            data: 'pagado',
            render: (d) => `<span class="text-green-600 font-semibold text-sm">${fmtQ(d)}</span>`
        },
        {
            title: 'Pendiente',
            data: 'pendiente',
            render: (d) => `<span class="text-red-600 font-semibold text-sm">${fmtQ(d)}</span>`
        },
        {
            title: 'Estado',
            data: 'estado_pago',
            render: (d) => badge(d)
        },
        {
            title: 'Acciones',
            data: null,
            orderable: false,
            searchable: false,
            render: (_d, _t, row) => {
                let extraBtns = '';

                // Buscar comprobante en pagos realizados si no hay en revisión
                let comprobanteUrl = row.comprobante_revision ? `/storage/${row.comprobante_revision}` : null;

                if (!comprobanteUrl && Array.isArray(row.pagos_realizados)) {
                    const pagoConComprobante = row.pagos_realizados.find(p => p.comprobante);
                    if (pagoConComprobante) {
                        comprobanteUrl = `/storage/${pagoConComprobante.comprobante}`;
                    }
                }

                if (comprobanteUrl) {
                    extraBtns += `
                        <a href="${comprobanteUrl}" target="_blank" 
                           class="bg-purple-100 hover:bg-purple-200 text-purple-700 px-3 py-1 rounded text-sm transition-colors flex items-center"
                           title="Ver Boleta Cargada">
                            <i class="fas fa-file-invoice mr-1"></i>Boleta
                        </a>`;
                } else {
                    // Botón para subir boleta si NO existe (para cualquier tipo de pago)
                    // Se usa la misma lógica de modal de pago pero solo para subir comprobante
                    extraBtns += `
                        <button class="btn-subir-boleta bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded text-sm transition-colors flex items-center"
                                data-venta="${row.venta_id}"
                                title="Adjuntar Boleta">
                            <i class="fas fa-upload mr-1"></i>Adjuntar
                        </button>`;
                }

                if (Number(row.pendiente) > 0) {
                    const totalPend = Array.isArray(row.cuotas_pendientes) ? row.cuotas_pendientes.length : 0;
                    const bloquearSolo = (Array.isArray(row.cuotas_en_revision) ? row.cuotas_en_revision.length : 0);
                    const disponibles = Number(row.cuotas_disponibles ?? (totalPend - bloquearSolo));
                    const canPay = disponibles > 0;

                    const dis = canPay ? '' : 'disabled opacity-50 cursor-not-allowed';
                    const btnClass = canPay ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-gray-300 text-gray-600';
                    const title = canPay
                        ? `Pagar (${disponibles}/${totalPend} disponibles)`
                        : 'No hay cuotas disponibles (algunas en revisión)';

                    return `
                        <div class="flex gap-2 items-center">
                          ${bloquearSolo ? '<span class="px-2 py-1 text-xs font-semibold rounded bg-amber-100 text-amber-800"><i class="fas fa-clock mr-1"></i>En revisión</span>' : ''}
                          ${extraBtns}
                          <button class="btn-pagar ${btnClass} px-3 py-1 rounded text-sm font-medium ${dis}" 
                                  data-venta="${row.venta_id}" 
                                  title="${title}">
                            <i class="fas fa-money-bill-wave mr-1"></i>Pagar
                          </button>
                          <button class="btn-detalle bg-gray-200 hover:bg-gray-300 text-gray-900 px-3 py-1 rounded text-sm" 
                                  data-venta="${row.venta_id}">
                            <i class="fas fa-eye mr-1"></i>Ver
                          </button>
                        </div>`;
                }
                return `
                    <div class="flex gap-2">
                      <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>PAGADA
                      </span>
                      ${extraBtns}
                      <button class="btn-detalle bg-gray-200 hover:bg-gray-300 text-gray-900 px-3 py-1 rounded text-sm" 
                              data-venta="${row.venta_id}">
                        <i class="fas fa-eye mr-1"></i>Ver
                      </button>
                    </div>`;
            }
        }
    ],
    // Configuración responsiva mejorada
    columnDefs: [
        {
            targets: [4, 5], // Productos y Precio
            className: 'min-w-[150px]'
        },
        {
            targets: [6, 7, 8], // Totales
            className: 'text-right'
        }
    ]
});


// Función para mostrar detalle completo de la venta
const mostrarDetalleVenta = async (ventaId) => {
    console.log('mostrarDetalleVenta llamada con ventaId =', ventaId);
    console.log('venta en el mapa =', ventaIndex.get(Number(ventaId)));
    const venta = ventaIndex.get(Number(ventaId));
    if (!venta) {
        showError('Error', 'No se encontró la información de la venta');
        return;
    }

    const cliente = venta.cliente || {};
    const vendedor = venta.vendedor || {};
    const precios = venta.precios || {};
    const pagoMaster = venta.pago_master || {};
    const pagosRealizados = venta.pagos_realizados || [];
    const cuotasPendientes = venta.cuotas_pendientes || [];

    let cuotasHTML = '';
    if (cuotasPendientes.length > 0) {
        cuotasHTML = `
            <div class="mt-4">
                <h4 class="font-semibold text-gray-800 mb-2">Cuotas Pendientes:</h4>
                <div class="space-y-2">
                    ${cuotasPendientes.map(c => `
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span class="text-sm">Cuota #${c.numero} ${c.en_revision ? '<span class="ml-2 px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800">En revisión</span>' : ''}</span>
                            <span class="font-semibold">${fmtQ(c.monto)}</span>
                            <span class="text-xs text-gray-600">${c.vence}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    let historialHTML = '';
    if (pagosRealizados.length > 0) {
        historialHTML = `
            <div class="mt-4">
                <h4 class="font-semibold text-gray-800 mb-2">Historial de Pagos:</h4>
                <div class="space-y-2">
                    ${pagosRealizados.map(p => `
                        <div class="flex justify-between items-center p-2 bg-green-50 rounded">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">${p.fecha}</span>
                                <span class="text-xs text-gray-600">${p.metodo}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-green-600">${fmtQ(p.monto)}</span>
                                ${p.comprobante ? `
                                    <a href="/storage/${p.comprobante}" target="_blank" 
                                       class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded hover:bg-blue-200 transition-colors"
                                       title="Ver Boleta">
                                        <i class="fas fa-image mr-1"></i>Boleta
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    await Swal.fire({
        title: `Detalle de Venta #${ventaId}`,
        html: `
            <div class="text-left space-y-4">
                <!-- Cliente -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-user mr-2"></i>Cliente
                    </h4>
                    <p class="text-sm"><strong>Nombre:</strong> ${cliente.nombre || 'N/A'}</p>
                    ${cliente.tipo === 3 && cliente.empresa !== 'Sin Empresa' ? `<p class="text-sm"><strong>Empresa:</strong> ${cliente.empresa}</p>` : ''}
                    ${cliente.nit !== '—' ? `<p class="text-sm"><strong>NIT:</strong> ${cliente.nit}</p>` : ''}
                    ${cliente.telefono !== '—' ? `<p class="text-sm"><strong>Teléfono:</strong> ${cliente.telefono}</p>` : ''}
                </div>

                <!-- Vendedor -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-gray-800 mb-2">
                        <i class="fas fa-user-tie mr-2"></i>Vendedor
                    </h4>
                    <p class="text-sm">${vendedor.nombre || 'N/A'}</p>
                </div>

                <!-- Productos -->
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-yellow-800 mb-2">
                        <i class="fas fa-box mr-2"></i>Productos (${venta.items_count || 0})
                    </h4>
                    <p class="text-sm">${venta.concepto || 'Sin productos'}</p>
                </div>

                <!-- Precios -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-green-800 mb-2">
                        <i class="fas fa-tags mr-2"></i>Precios
                    </h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <strong>Individual:</strong> ${fmtQ(precios.individual || 0)}
                        </div>
                        <div>
                            <strong>Empresa:</strong> ${fmtQ(precios.empresa || 0)}
                        </div>
                        ${precios.aplicado ? `
                            <div class="col-span-2">
                                <strong>Aplicado:</strong> <span class="text-green-600 font-bold">${fmtQ(precios.aplicado)}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>

                <!-- Totales -->
                <div class="bg-purple-50 p-4 rounded-lg">
                    <h4 class="font-semibold text-purple-800 mb-2">
                        <i class="fas fa-calculator mr-2"></i>Resumen Financiero
                    </h4>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Total Venta:</span>
                            <span class="font-bold">${fmtQ(venta.monto_total || 0)}</span>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span>Pagado:</span>
                            <span class="font-bold">${fmtQ(venta.pagado || 0)}</span>
                        </div>
                        <div class="flex justify-between text-red-600">
                            <span>Pendiente:</span>
                            <span class="font-bold">${fmtQ(venta.pendiente || 0)}</span>
                        </div>
                    </div>
                </div>

                ${cuotasHTML}
                ${historialHTML}
            </div>
        `,
        width: '600px',
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#3B82F6',
        customClass: {
            popup: 'rounded-lg'
        }
    });
};

// Actualizar el event listener de la tabla
document.getElementById('tablaFacturas')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button');
    if (!btn) return;

    if (btn.classList.contains('btn-pagar')) {
        const ventaId = btn.dataset.venta;
        const venta = ventaIndex.get(Number(ventaId));
        if (!venta) return;

        const disponibles = (venta.cuotas_pendientes || []).filter(c => !c.en_revision);
        if (disponibles.length === 0) {
            showWarning('Sin cuotas disponibles', 'Todas las cuotas de esta venta tienen comprobante en revisión.');
            return;
        }

        renderCuotas(venta);
        btnSubir && (btnSubir.dataset.venta = ventaId);
        openModal();
    }

    if (btn.classList.contains('btn-subir-boleta')) {
        const ventaId = btn.dataset.venta;
        const venta = ventaIndex.get(Number(ventaId));
        if (!venta) return;

        // Limpiar lista de cuotas porque es subida directa
        listDiv.innerHTML = '';
        totalSel.textContent = 'Q 0.00';
        selectedCuotas = [];

        // Configurar modal para solo subida
        btnSubir && (btnSubir.dataset.venta = ventaId);
        btnSubir && (btnSubir.dataset.modo = 'solo_boleta'); // Flag para saber que es solo boleta

        openModal();
        // Saltar directamente al paso 2 (subir foto)
        showStep(2);
    }

    if (btn.classList.contains('btn-detalle')) {
        const ventaId = btn.dataset.venta;
        mostrarDetalleVenta(ventaId);
    }
});

const GetFacturas = async () => {
    try {
        showLoading('Cargando facturas...');
        const res = await fetch('/pagos/obtener/mispagos');
        const json = await res.json();
        if (json.codigo !== 1) { Swal.close(); showWarning('Advertencia', json.mensaje || 'No se pudieron cargar las facturas'); return; }

        const pendientes = (json.data?.pendientes ?? []).map(f => ({
            ...f,
            estado: f.estado_pago || 'PENDIENTE'
        }));

        const pagadas4m = (json.data?.pagadas_ult4m ?? []).map(f => ({
            ...f, pendiente: 0, estado: 'COMPLETADO'
        }));

        const rows = [...pendientes, ...pagadas4m];

        ventaIndex.clear();
        rows.forEach(r => ventaIndex.set(r.venta_id, r));

        // 👇 NUEVO: Poblar filtro de clientes
        const clientesMap = new Map();
        rows.forEach(r => {
            if (r.cliente && r.cliente.id) {
                clientesMap.set(r.cliente.id, r.cliente.nombre || 'Sin Nombre');
            }
        });

        const filtroSelect = document.getElementById('filtroCliente');
        if (filtroSelect) {
            const currentVal = filtroSelect.value;
            filtroSelect.innerHTML = '<option value="">Todos los clientes</option>';

            // Ordenar alfabéticamente
            const clientesOrdenados = Array.from(clientesMap.entries()).sort((a, b) => a[1].localeCompare(b[1]));

            clientesOrdenados.forEach(([id, nombre]) => {
                filtroSelect.innerHTML += `<option value="${id}">${nombre}</option>`;
            });
            filtroSelect.value = currentVal; // Restaurar selección si existe
        }

        updateStats(rows);
        datatable.clear();
        datatable.rows.add(rows).draw();
        Swal.close();

        // 👇 Listener para filtrado
        if (filtroSelect) {
            filtroSelect.onchange = () => {
                const val = filtroSelect.value;
                if (!val) {
                    datatable.clear().rows.add(rows).draw();
                    updateStats(rows);
                } else {
                    const filtered = rows.filter(r => String(r.cliente?.id) === val);
                    datatable.clear().rows.add(filtered).draw();
                    updateStats(filtered);
                }
            };
        }

    } catch (e) { console.warn(e); Swal.close(); showError('Error', 'Ocurrió un error al cargar las facturas'); }
};

const updateStats = (data) => {
    const $ = (id) => document.getElementById(id);
    $('totalFacturas') && ($('totalFacturas').textContent = data.length);
    $('facturasPendientes') && ($('facturasPendientes').textContent = data.filter(r => r.pendiente > 0).length);
    $('pagosCompletados') && ($('pagosCompletados').textContent = data.filter(r => r.estado === 'COMPLETADO').length);
    $('pagosParciales') && ($('pagosParciales').textContent = data.filter(r => String(r.estado || '').toUpperCase() === 'PARCIAL').length);
};

GetFacturas();

const bancoSelectTop = document.getElementById('bancoSelectTop');
const modal = document.getElementById('modalPago');
const listDiv = document.getElementById('cuotasList');
const totalSel = document.getElementById('totalSeleccionado');
const btnSubir = document.getElementById('btnSubirPago');
const btnClose = document.getElementById('btnCancelarPago');
const inputComp = document.getElementById('inputComprobante');
const paymentAnalyzer = new PaymentAnalyzer();

const step1 = document.getElementById('step1');
const step2 = document.getElementById('step2');
const step3 = document.getElementById('step3');

const btnContinuarPaso2 = document.getElementById('btnContinuarPaso2');
const btnContinuarPaso3 = document.getElementById('btnContinuarPaso3');
const btnVolverPaso1 = document.getElementById('btnVolverPaso1');
const btnVolverPaso2 = document.getElementById('btnVolverPaso2');

const btnOcr = document.getElementById('btnOcr');
const btnEditarManual = document.getElementById('btnEditarManual');
const btnEnviarPago = document.getElementById('btnEnviarPago');
const ocrPreview = document.getElementById('ocrPreview');
const ocrRows = document.getElementById('ocrRows');
const formDatos = document.getElementById('datosPagoForm');
const formWrap = document.getElementById('formWrap');
const uploadZone = document.getElementById('uploadZone');
const uploadContent = document.getElementById('uploadContent');
const previewContent = document.getElementById('previewContent');
const imagePreview = document.getElementById('imagePreview');

const resumenCuotas = document.getElementById('resumenCuotas');
const resumenDatos = document.getElementById('resumenDatos');
const totalFinal = document.getElementById('totalFinal');

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';


let currentBlob = null;
let parsed = { fecha: '', monto: '', referencia: '', concepto: '', banco_id: null, banco_nombre: '' };
let ocrWorker = null;


function normalizeAmount(raw) {
    let s = String(raw || '').replace(/[^\d.,]/g, '').trim();
    if (!s) return '';

    const dotCount = (s.match(/\./g) || []).length;
    const commaCount = (s.match(/,/g) || []).length;

    if (dotCount && commaCount) {
        const lastDot = s.lastIndexOf('.');
        const lastComma = s.lastIndexOf(',');
        const decSep = lastDot > lastComma ? '.' : ',';
        const thouSep = decSep === '.' ? ',' : '.';
        s = s.replace(new RegExp('\\' + thouSep, 'g'), '');
        if (decSep === ',') s = s.replace(',', '.');
        return s;
    }

    if (commaCount) {
        const lastComma = s.lastIndexOf(',');
        const decimals = s.length - lastComma - 1;
        if (decimals === 2) {
            s = s.replace(/\./g, ''); // puntos como miles
            s = s.replace(/,/g, '.'); // coma decimal -> punto
            return s;
        }
        return s.replace(/,/g, '');
    }

    if (dotCount) {
        const lastDot = s.lastIndexOf('.');
        const decimals = s.length - lastDot - 1;

        if (dotCount > 1 && decimals === 2) {

            return s.slice(0, lastDot).replace(/\./g, '') + '.' + s.slice(lastDot + 1);
        }

        if (decimals === 2) return s;

        return s.replace(/\./g, '');
    }
    return s;
}


function extractDatetimeLocal(text) {
    if (!text) return '';

    console.log('Extrayendo fecha de:', text);

    let t = String(text).toLowerCase()
        .replace(/\s+/g, ' ')
        .replace(/a\.\s*m\.?/gi, 'am')
        .replace(/p\.\s*m\.?/gi, 'pm')
        .trim();

    t = t.replace(/\bag0\b/gi, 'ago');

    t = t
        .replace(/(\d)[oO]+(?=\d)/g, (m, d1) => d1 + '0')
        .replace(/(\d)0(?=\d)/g, '$10');


    t = t.replace(/\b([12][0oO]\d{2})\b/g, (s) => s.replace(/[oO]/g, '0'));


    t = t.replace(/(\d{1,2})\s*[\/\-.]\s*ag0\s*[\/\-.]/gi, '$1/ago/');

    console.log('Texto normalizado y corregido OCR:', t);

    console.log('Texto normalizado y corregido OCR:', t);


    const meses = {
        ene: '01', enero: '01',
        feb: '02', febrero: '02',
        mar: '03', marzo: '03',
        abr: '04', abril: '04',
        may: '05', mayo: '05',
        jun: '06', junio: '06',
        jul: '07', julio: '07',
        ago: '08', agosto: '08',  // IMPORTANTE: incluye "ago"
        sep: '09', sept: '09', septiembre: '09',
        oct: '10', octubre: '10',
        nov: '11', noviembre: '11',
        dic: '12', diciembre: '12'
    };

    let match = /(\d{1,2})\s*[\/\-.]\s*([a-záéíóú]{3,10})\s*[\/\-.]\s*([0-9oO]{2,4})\s*[-–—]\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(am|pm)?/i.exec(t);

    if (match) {
        let [, dd, mmStr, yyyy, hh = '00', mi = '00', ss = '00', ampm = ''] = match;


        yyyy = String(yyyy).replace(/[oO]/g, '0');

        if (yyyy.length === 2) yyyy = (Number(yyyy) > 50 ? '19' : '20') + yyyy;

        const mesKey = mmStr.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const mm = meses[mesKey];

        if (!mm) {
            console.warn('Mes no reconocido:', mmStr, 'normalizado:', mesKey);
            return '';
        }

        let H = Number(hh);
        if (ampm) {
            const isPM = ampm.toLowerCase().includes('pm');
            if (H === 12 && !isPM) H = 0;        // 12:xx AM = 00:xx
            if (H < 12 && isPM) H += 12;         // X:xx PM = (X+12):xx
        }

        const pad = n => String(n).padStart(2, '0');
        const resultado = `${yyyy}-${pad(mm)}-${pad(dd)}T${pad(H)}:${pad(mi)}`;

        console.log('Fecha extraída (formato texto):', resultado);
        return resultado;
    }


    match = /(\d{1,2})\s*[\/\-.]\s*([a-záéíóú]{3,10})\s*[\/\-.]\s*(\d{2,4})\s+(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(am|pm)?/i.exec(t);

    if (match) {
        let [, dd, mmStr, yyyy, hh = '00', mi = '00', ss = '00', ampm = ''] = match;

        console.log('Match formato texto sin guión:', { dd, mmStr, yyyy, hh, mi, ss, ampm });

        if (yyyy.length === 2) yyyy = (Number(yyyy) > 50 ? '19' : '20') + yyyy;

        const mesKey = mmStr.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const mm = meses[mesKey];

        if (!mm) {
            console.warn('Mes no reconocido:', mmStr);
            return '';
        }

        let H = Number(hh);
        if (ampm) {
            const isPM = ampm.toLowerCase().includes('pm');
            if (H === 12 && !isPM) H = 0;
            if (H < 12 && isPM) H += 12;
        }

        const pad = n => String(n).padStart(2, '0');
        const resultado = `${yyyy}-${pad(mm)}-${pad(dd)}T${pad(H)}:${pad(mi)}`;

        console.log('Fecha extraída (sin guión):', resultado);
        return resultado;
    }

    // PATRÓN 3: dd/mm/yyyy HH:mm (formato numérico)
    match = /(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2,4})(?:\s*[-–]?\s*(\d{1,2}):(\d{2})(?::(\d{2}))?\s*(am|pm)?)?/i.exec(t);

    if (match) {
        let [, dd, mm, yyyy, hh = '00', mi = '00', ss = '00', ampm = ''] = match;

        console.log('Match formato numérico:', { dd, mm, yyyy, hh, mi, ss, ampm });

        if (yyyy.length === 2) yyyy = (Number(yyyy) > 50 ? '19' : '20') + yyyy;

        let H = Number(hh);
        if (ampm) {
            const isPM = ampm.toLowerCase().includes('pm');
            if (H === 12 && !isPM) H = 0;
            if (H < 12 && isPM) H += 12;
        }

        const pad = n => String(n).padStart(2, '0');
        const resultado = `${yyyy}-${pad(mm)}-${pad(dd)}T${pad(H)}:${pad(mi)}`;

        console.log('Fecha extraída (numérica):', resultado);
        return resultado;
    }

    console.warn('No se pudo extraer fecha del texto:', t);
    return '';
}

/* ==== CONFIGURACIÓN TESSERACT MEJORADA ==== */
const initOCRWorker = async () => {
    if (!ocrWorker) {
        console.log('Inicializando worker OCR...');
        ocrWorker = await createWorker('spa', 1, {
            logger: m => {
                if (m.status === 'recognizing text') {
                    console.log(`OCR Progress: ${(m.progress * 100).toFixed(1)}%`);
                }
            }
        });

        await ocrWorker.setParameters({
            'tessedit_char_whitelist': '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzáéíóúñ/.-: ',
            'tessedit_pageseg_mode': '6',
            'preserve_interword_spaces': '1'
        });

        console.log('Worker OCR inicializado');
    }
    return ocrWorker;
};

const runOCR = async (blob) => {
    try {
        const worker = await initOCRWorker();
        const { data } = await worker.recognize(blob);

        console.log('Texto OCR crudo:', data.text);
        console.log('Confianza promedio:', data.confidence);

        return data.text || '';

    } catch (error) {
        console.error('Error en OCR:', error);
        throw error;
    }
};

/* ==== PARSER MEJORADO ==== */
function parseVoucher(textRaw) {
    console.log('=== INICIO PARSING COMPROBANTE ===');
    console.log('Texto original:', textRaw);

    const t = (textRaw || '').replace(/\s+/g, ' ').trim();
    console.log('Texto normalizado:', t);

    // Extraer fecha con función mejorada
    const fechaNorm = extractDatetimeLocal(t);
    console.log('Fecha extraída:', fechaNorm);

    const montoPatterns = [
        /(GTQ|Q)\s*([0-9][0-9.,]+)/i,
        /([0-9][0-9.,]+)\s*(GTQ|Q)/i,
        /monto[:\s]*([0-9][0-9.,]+)/i,
        /total[:\s]*([0-9][0-9.,]+)/i,
        /\b([0-9]{1,3}(?:[,.][0-9]{3})*(?:[,.][0-9]{2})?)\b/g
    ];

    let monto = '';
    for (const pattern of montoPatterns) {
        const m = pattern.exec(t);
        if (m) {
            const candidato = m[2] || m[1];
            const normalizado = normalizeAmount(candidato);
            if (normalizado && Number(normalizado) > 0) {
                monto = normalizado;
                console.log('Monto encontrado:', monto, 'de:', candidato);
                break;
            }
        }
    }

    const refPatterns = [
        /(?:ref(?:erencia)?|aut(?:orizaci[oó]n)?|n[úu]mero|comprobante)[:\s#]*([0-9A-Z\-]{6,})/i,
        /autorización[:\s]*([0-9]+)/i,
        /\b([0-9]{7,})\b/
    ];

    let referencia = '';
    for (const pattern of refPatterns) {
        const refMatch = pattern.exec(t);
        if (refMatch) {
            referencia = refMatch[1];
            console.log('Referencia encontrada:', referencia);
            break;
        }
    }

    // Extraer concepto
    const conceptoMatch = /(concepto|detalle|servicio|descripci[oó]n|por)[:\-]?\s*([^\d\n]{3,120}?)(?=(\sref|\saut|\sfecha|\sQ\b|\smonto|$))/i.exec(t);
    const concepto = conceptoMatch ? conceptoMatch[2].trim() : '';
    console.log('Concepto extraído:', concepto);

    // Detectar banco
    let banco_id = (bancoSelectTop && bancoSelectTop.value) ? String(bancoSelectTop.value) : null;
    let banco_nombre = banco_id ? (BANKS[banco_id] || '') : '';

    if (!banco_id) {
        const banco = detectBanco(t);
        banco_id = banco.id;
        banco_nombre = banco.nombre || '';
        if (banco_id && bancoSelectTop) {
            bancoSelectTop.value = banco_id;
        }
        console.log('Banco detectado:', banco_nombre, 'ID:', banco_id);
    }

    // Actualizar objeto parsed
    parsed = {
        ...parsed,
        fecha: fechaNorm || '',
        monto: monto || '',
        referencia,
        concepto,
        banco_id,
        banco_nombre
    };


    if (selectedCuotas.length > 0) {
        const analysis = paymentAnalyzer.analyzeDataQuality(parsed, selectedCuotas);
        displayAnalysisResults(analysis);
    }


    fillFormFromParsed();
}

function displayAnalysisResults(analysis) {

    const reportHTML = paymentAnalyzer.generateAnalysisReport(analysis);

    const ocrPreview = document.getElementById('ocrPreview');
    let analysisContainer = document.getElementById('analysisContainer');

    if (!analysisContainer) {
        analysisContainer = document.createElement('div');
        analysisContainer.id = 'analysisContainer';
        analysisContainer.className = 'mt-4';

        if (ocrPreview && ocrPreview.parentNode) {
            ocrPreview.parentNode.insertBefore(analysisContainer, ocrPreview.nextSibling);
        }
    }

    analysisContainer.innerHTML = reportHTML;

    if (analysis.overall_confidence < paymentAnalyzer.confidence.MEDIUM) {
        setTimeout(() => {
            Swal.fire({
                icon: 'warning',
                title: 'Comprobante requiere revisión',
                html: `
                    <div class="text-left">
                        <p class="mb-3">El análisis detectó algunos problemas:</p>
                        <ul class="text-sm space-y-1 mb-4">
                            ${analysis.conclusions.map(c => `<li class="text-gray-700">${c}</li>`).join('')}
                        </ul>
                        <p class="text-blue-600 font-medium">Recomendación: Revise los datos manualmente</p>
                    </div>
                `,
                confirmButtonText: 'Revisar Datos',
                confirmButtonColor: '#3B82F6',
                showCancelButton: true,
                cancelButtonText: 'Continuar Así'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir automáticamente el formulario manual
                    const formWrap = document.getElementById('formWrap');
                    const btnEditarManual = document.getElementById('btnEditarManual');

                    if (formWrap && formWrap.classList.contains('hidden')) {
                        formWrap.classList.remove('hidden');
                        if (btnEditarManual) {
                            btnEditarManual.textContent = 'Ocultar Formulario';
                        }
                        updateStep2Buttons();
                    }
                }
            });
        }, 1000);
    } else if (analysis.overall_confidence >= paymentAnalyzer.confidence.HIGH) {
        // Mostrar mensaje de éxito para alta confianza
        setTimeout(() => {
            Swal.fire({
                icon: 'success',
                title: 'Comprobante validado exitosamente',
                text: 'Todos los datos se extrajeron correctamente',
                timer: 2000,
                showConfirmButton: false
            });
        }, 1000);
    }
}

function fillFormFromParsed() {
    if (!formDatos) return;
    formDatos.elements['fecha'] && (formDatos.elements['fecha'].value = parsed.fecha || '');
    formDatos.elements['monto'] && (formDatos.elements['monto'].value = parsed.monto || '');
    formDatos.elements['referencia'] && (formDatos.elements['referencia'].value = parsed.referencia || '');
    formDatos.elements['concepto'] && (formDatos.elements['concepto'].value = parsed.concepto || '');
}

function renderOCRTable() {
    if (!ocrRows) return;
    ocrRows.innerHTML = '';

    const fields = [
        { label: 'Banco Destino', value: parsed.banco_nombre || '—' },
        { label: 'Fecha y Hora', value: parsed.fecha || '—' },
        { label: 'Monto', value: parsed.monto ? fmtQ(parsed.monto) : '—' },
        { label: 'Referencia', value: parsed.referencia || '—' },
        { label: 'Concepto', value: parsed.concepto || '—' }
    ];

    fields.forEach(f => {
        const div = document.createElement('div');
        div.className = 'flex justify-between items-center py-2 border-b border-green-200 last:border-b-0';
        div.innerHTML = `<span class="font-medium text-green-700">${f.label}:</span><span class="text-green-800">${f.value}</span>`;
        ocrRows.appendChild(div);
    });
}


const setBank = (id) => {
    const val = String(id || '');
    parsed.banco_id = val || null;
    parsed.banco_nombre = BANKS[val] || '';
    if (bancoSelectTop && bancoSelectTop.value !== val) bancoSelectTop.value = val;
    if (formDatos?.elements['banco_id'] && formDatos.elements['banco_id'].value !== val) formDatos.elements['banco_id'].value = val;
    updateStep2Buttons();
};
bancoSelectTop?.addEventListener('change', (e) => setBank(e.target.value));

/* ==== Pasos ==== */
const updateStepIndicators = (step) => {
    document.querySelectorAll('.step-indicator').forEach((indicator, idx) => {
        const circle = indicator.querySelector('div');
        const text = indicator.querySelector('span');
        if (idx + 1 <= step) {
            circle.className = 'w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold';
            text.className = 'text-sm font-medium text-blue-600';
            indicator.classList.add('active');
        }
        else {
            circle.className = 'w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-semibold';
            text.className = 'text-sm font-medium text-gray-500';
            indicator.classList.remove('active');
        }
    });
};

const showStep = (step) => {
    currentStep = step;
    step1?.classList.add('hidden');
    step2?.classList.add('hidden');
    step3?.classList.add('hidden');
    if (step === 1) step1?.classList.remove('hidden');
    if (step === 2) step2?.classList.remove('hidden');
    if (step === 3) { step3?.classList.remove('hidden'); fillResumenData(); }
    updateStepIndicators(step);
};

const openModal = () => {
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        showStep(1);
        setBank('');
    }
};

const closeModal = () => {
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    btnSubir?.removeAttribute('data-venta');
    inputComp && (inputComp.value = '');
    listDiv && (listDiv.innerHTML = '');
    totalSel && (totalSel.textContent = 'Q 0.00');
    bancoSelectTop && (bancoSelectTop.value = '');
    setBank('');
    selectedCuotas = [];
    currentStep = 1;
    currentBlob = null;
    parsed = { fecha: '', monto: '', referencia: '', concepto: '', banco_id: null, banco_nombre: '' };
    ocrRows && (ocrRows.innerHTML = '');
    ocrPreview?.classList.add('hidden');
    formWrap?.classList.add('hidden');
    btnOcr?.setAttribute('disabled', 'disabled');
    btnContinuarPaso2?.setAttribute('disabled', 'disabled');
    btnContinuarPaso3?.setAttribute('disabled', 'disabled');
    uploadContent?.classList.remove('hidden');
    previewContent?.classList.add('hidden');
};

/* ==== Listado cuotas ==== */
const calcTotalSeleccionado = () => {
    const checks = listDiv?.querySelectorAll('input.cuota-check:checked') ?? [];
    selectedCuotas = [];
    let sum = 0;
    checks.forEach(ch => {
        const d = {
            id: Number(ch.dataset.id),
            numero: ch.dataset.numero,
            monto: Number(ch.dataset.monto),
            vence: ch.dataset.vence
        };
        selectedCuotas.push(d);
        sum += d.monto;
    });
    totalSel && (totalSel.textContent = fmtQ(sum));
    btnContinuarPaso2 && (selectedCuotas.length > 0 ? btnContinuarPaso2.removeAttribute('disabled') : btnContinuarPaso2.setAttribute('disabled', 'disabled'));
    return sum;
};

const renderCuotas = (venta) => {
    if (!listDiv) return;
    listDiv.innerHTML = '';

    const cuotas = Array.isArray(venta.cuotas_pendientes) ? venta.cuotas_pendientes : [];
    if (!cuotas.length) {
        listDiv.innerHTML = `<div class="p-4 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-center">No hay cuotas pendientes para esta factura.</div>`;
        btnContinuarPaso2 && btnContinuarPaso2.setAttribute('disabled', 'disabled');
        return;
    }

    const disponibles = cuotas.filter(c => !c.en_revision);
    if (disponibles.length === 0) {
        listDiv.innerHTML = `
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-center">
            Todas las cuotas pendientes de esta venta tienen comprobante en revisión.
        </div>`;
        btnContinuarPaso2 && btnContinuarPaso2.setAttribute('disabled', 'disabled');
        return;
    }

    cuotas.forEach(c => {
        const enRev = !!c.en_revision;

        const row = document.createElement('div');
        row.className = 'cuota-card border-2 border-gray-200 rounded-xl p-4 bg-white shadow-sm';
        row.innerHTML = `
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <input type="checkbox" class="cuota-check w-5 h-5 accent-blue-600" ${enRev ? 'disabled' : ''} data-id="${c.cuota_id}" data-numero="${c.numero}" data-monto="${c.monto}" data-vence="${c.vence}">
          <div>
            <div class="text-lg font-semibold text-gray-800">Cuota #${c.numero} ${enRev ? '<span class="ml-2 px-2 py-0.5 text-xs rounded bg-amber-100 text-amber-800 align-middle">EN REVISIÓN</span>' : ''}</div>
            <div class="text-sm text-gray-600">Vence: ${c.vence} • Estado: ${c.estado}</div>
          </div>
        </div>
        <div class="text-right">
          <div class="text-xl font-bold text-blue-600">${fmtQ(c.monto)}</div>
          <button class="btn-pagar-una ${enRev ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700 text-white'} px-4 py-2 rounded-lg text-sm font-medium mt-2" data-id="${c.cuota_id}" data-monto="${c.monto}" ${enRev ? 'disabled title="Comprobante en revisión"' : ''}>Pagar Solo Esta</button>
        </div>
      </div>`;

        listDiv.appendChild(row);

        if (!enRev) {
            const checkbox = row.querySelector('input.cuota-check');
            checkbox.addEventListener('change', () => {
                checkbox.checked ? row.classList.add('selected') : row.classList.remove('selected');
                calcTotalSeleccionado();
            });
            row.addEventListener('click', (e) => {
                if (!e.target.matches('button, input')) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        }
    });

    calcTotalSeleccionado();
};



/* ==== Upload y OCR ==== */
const handleFileUpload = (file) => {
    if (!file) return;
    if (!file.type.startsWith('image/')) {
        showError('Error', 'Por favor selecciona un archivo de imagen válido');
        return;
    }
    currentBlob = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        if (imagePreview) {
            imagePreview.src = e.target.result;
            uploadContent?.classList.add('hidden');
            previewContent?.classList.remove('hidden');
        }
    };
    reader.readAsDataURL(file);
    btnOcr?.removeAttribute('disabled');
    updateStep2Buttons();
};

const updateStep2Buttons = () => {
    if (!btnContinuarPaso3) return;

    const bankSelected =
        (bancoSelectTop && String(bancoSelectTop.value || '').trim()) ||
        (parsed && String(parsed.banco_id || '').trim());

    // Lectura segura del formulario
    const getVal = (name) =>
        (formDatos?.elements?.[name]?.value ?? '').toString().trim();

    // ¿El usuario escribió ALGO manualmente?
    const hasManualData = !!(
        getVal('monto') ||
        getVal('fecha') ||
        getVal('referencia') ||
        getVal('concepto')
    );

    const hasParsedData = !!(
        (parsed?.monto ?? '') ||
        (parsed?.fecha ?? '') ||
        (parsed?.referencia ?? '') ||
        (parsed?.concepto ?? '')
    );

    const canContinue = !!bankSelected && (hasManualData || hasParsedData);
    if (canContinue) {
        btnContinuarPaso3.removeAttribute('disabled');
    } else {
        btnContinuarPaso3.setAttribute('disabled', 'disabled');
    }
};

/* ==== Event Listeners Upload ==== */
uploadZone?.addEventListener('click', () => inputComp?.click());
uploadZone?.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});
uploadZone?.addEventListener('dragleave', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
});
uploadZone?.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    const files = e.dataTransfer.files;
    if (files.length > 0) handleFileUpload(files[0]);
});
inputComp?.addEventListener('change', () => {
    const f = inputComp.files?.[0];
    if (f) handleFileUpload(f);
});

/* ==== OCR Event Listeners ==== */
btnOcr?.addEventListener('click', async () => {
    if (!currentBlob) {
        showError('Error', 'Adjunta una imagen del comprobante o ingresa datos manualmente');
        return;
    }

    btnOcr.setAttribute('disabled', 'disabled');
    const originalText = btnOcr.textContent;
    btnOcr.textContent = 'Procesando con IA...';
    showLoading('Procesando comprobante con IA...');

    try {
        const text = await runOCR(currentBlob);
        parseVoucher(text);
        renderOCRTable();
        ocrPreview?.classList.remove('hidden');

        const faltaBanco = !((bancoSelectTop && bancoSelectTop.value) || parsed.banco_id);
        const faltaFecha = !parsed.fecha;
        const faltaMonto = !parsed.monto;
        const faltaRef = !parsed.referencia || String(parsed.referencia).length < 6;

        if (faltaBanco || faltaFecha || faltaMonto || faltaRef) {
            formWrap?.classList.remove('hidden');
            btnEditarManual && (btnEditarManual.textContent = 'Ocultar Formulario');
        }

        updateStep2Buttons();
        Swal.close();
        showSuccess('¡Datos extraídos!', 'Los datos del comprobante se han procesado correctamente');

    } catch (e) {
        console.error(e);
        Swal.close();
        showError('Error de procesamiento', 'No se pudo procesar el comprobante con IA. Puedes ingresar los datos manualmente.');
        formWrap?.classList.remove('hidden');
        btnEditarManual && (btnEditarManual.textContent = 'Ocultar Formulario');
        updateStep2Buttons();
    } finally {
        btnOcr.textContent = originalText;
        btnOcr.removeAttribute('disabled');
    }
});

btnEditarManual?.addEventListener('click', () => {
    if (!formWrap) return;
    if (formWrap.classList.contains('hidden')) {
        fillFormFromParsed();
        formWrap.classList.remove('hidden');
        btnEditarManual.textContent = 'Ocultar Formulario';
    }
    else {
        formWrap.classList.add('hidden');
        btnEditarManual.textContent = 'Ingresar Manualmente';
    }
    updateStep2Buttons();
});

/* ==== Resumen ==== */
const fillResumenData = () => {
    if (resumenCuotas) {
        resumenCuotas.innerHTML = '';
        selectedCuotas.forEach(c => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-1';
            div.innerHTML = `<span class="text-sm">Cuota #${c.numero} - Vence: ${c.vence}</span><span class="font-semibold">${fmtQ(c.monto)}</span>`;
            resumenCuotas.appendChild(div);
        });
    }
    totalFinal && (totalFinal.textContent = fmtQ(selectedCuotas.reduce((s, c) => s + c.monto, 0)));
    const d = getFinalData();
    if (resumenDatos) {
        const fields = [
            { label: 'Banco', value: d.banco_nombre || '—' },
            { label: 'Fecha', value: d.fecha || '—' },
            { label: 'Monto Comprobante', value: d.monto ? fmtQ(d.monto) : '—' },
            { label: 'Referencia', value: d.referencia || '—' },
            { label: 'Concepto', value: d.concepto || '—' }
        ];
        resumenDatos.innerHTML = '';
        fields.forEach(f => {
            const div = document.createElement('div');
            div.className = 'flex justify-between items-center py-1 text-sm';
            div.innerHTML = `<span class="text-green-700">${f.label}:</span><span class="text-green-800 font-medium">${f.value}</span>`;
            resumenDatos.appendChild(div);
        });
    }
};

/* ==== Navegación ==== */
btnContinuarPaso2?.addEventListener('click', () => {
    if (!selectedCuotas.length) {
        showWarning('Selección requerida', 'Selecciona al menos una cuota');
        return;
    }
    showStep(2);
});

btnVolverPaso1?.addEventListener('click', () => showStep(1));

btnContinuarPaso3?.addEventListener('click', () => {
    const finalData = getFinalData();
    const errors = [];
    const dtLocalPattern = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/;

    if (!finalData.banco_id) errors.push('• Debe seleccionar el banco destino');
    if (!finalData.monto || Number(finalData.monto) <= 0) errors.push('• El monto es requerido');
    if (!finalData.referencia || String(finalData.referencia).length < 6) errors.push('• La referencia debe tener al menos 6 caracteres');
    if (!finalData.fecha || !dtLocalPattern.test(finalData.fecha)) errors.push('• La fecha debe tener formato válido (YYYY-MM-DDTHH:MM)');

    if (errors.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos incompletos',
            html: `<div class="text-left bg-yellow-50 p-3 rounded-lg">${errors.join('<br>')}</div>`,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#F59E0B'
        });
        formWrap?.classList.remove('hidden');
        btnEditarManual && (btnEditarManual.textContent = 'Ocultar Formulario');
        return;
    }
    showStep(3);
});

btnVolverPaso2?.addEventListener('click', () => showStep(2));

document.getElementById('tablaFacturas')?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button');
    if (!btn) return;

    if (btn.classList.contains('btn-pagar')) {
        const ventaId = btn.dataset.venta;
        const venta = ventaIndex.get(Number(ventaId));
        if (!venta) return;

        const disponibles = (venta.cuotas_pendientes || []).filter(c => !c.en_revision);
        if (disponibles.length === 0) {
            showWarning('Sin cuotas disponibles', 'Todas las cuotas de esta venta tienen comprobante en revisión.');
            return;
        }

        renderCuotas(venta);
        btnSubir && (btnSubir.dataset.venta = ventaId);
        openModal();
    }

    if (btn.classList.contains('btn-detalle')) {
        console.log('Detalle factura', btn.dataset.venta);
    }
});



listDiv?.addEventListener('click', (ev) => {
    const b = ev.target.closest('button.btn-pagar-una');
    if (!b) return;
    ev.stopPropagation();

    const cuotaId = Number(b.dataset.id);
    const monto = Number(b.dataset.monto);

    Swal.fire({
        title: 'Pago individual',
        html: `<div class="text-left">
            <p class="mb-4">¿Deseas pagar solo esta cuota?</p>
            <div class="bg-blue-50 p-4 rounded-lg"><p><strong>Cuota ID:</strong> ${cuotaId}</p><p><strong>Monto:</strong> ${fmtQ(monto)}</p></div>
            <p class="mt-4 text-sm text-gray-600">Puedes subir comprobante o ingresar datos manualmente.</p>
          </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#3B82F6'
    }).then(r => {
        if (r.isConfirmed) {
            document.querySelectorAll('input.cuota-check:checked').forEach(ch => {
                ch.checked = false;
                ch.closest('.cuota-card')?.classList.remove('selected');
            });
            const checkbox = document.querySelector(`input.cuota-check[data-id="${cuotaId}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.closest('.cuota-card')?.classList.add('selected');
                calcTotalSeleccionado();
                showStep(2);
            }
        }
    });
});

btnClose?.addEventListener('click', async () => {
    const hasChanges = selectedCuotas.length || currentBlob || currentStep > 1;
    if (hasChanges) {
        const ok = await confirmAction('¿Cerrar sin guardar?', 'Se perderán los cambios');
        if (!ok) return;
    }
    closeModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal?.classList.contains('hidden')) {
        btnClose?.click();
    }
});

/* ==== Envío final ==== */
btnEnviarPago?.addEventListener('click', async () => {
    if (!selectedCuotas.length) {
        showError('Error', 'No hay cuotas seleccionadas');
        return;
    }

    const finalData = getFinalData();
    if (!finalData.monto || Number(finalData.monto) <= 0) {
        showError('Error', 'El monto del comprobante es requerido');
        return;
    }

    const ventaId = btnSubir?.dataset?.venta || '';
    const totalCuotas = selectedCuotas.reduce((sum, c) => sum + c.monto, 0);

    const confirmed = await Swal.fire({
        title: '¿Confirmar el pago?',
        html: `<div class="text-left">
            <div class="mb-4">
              <h6 class="font-semibold text-gray-800 mb-2">Resumen del pago:</h6>
              <div class="bg-blue-50 p-4 rounded-lg space-y-2">
                <p><strong>Cuotas seleccionadas:</strong> ${selectedCuotas.length}</p>
                <p><strong>Total cuotas:</strong> ${fmtQ(totalCuotas)}</p>
                <p><strong>Monto ingresado:</strong> ${fmtQ(finalData.monto)}</p>
                <p><strong>Banco:</strong> ${finalData.banco_nombre || 'No especificado'}</p>
                <p><strong>Referencia:</strong> ${finalData.referencia || 'No especificado'}</p>
              </div>
            </div>
            <p class="text-sm text-gray-600">El pago será enviado para validación por el administrador.</p>
          </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, enviar pago',
        cancelButtonText: 'Revisar',
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280'
    });

    if (!confirmed.isConfirmed) return;

    const fd = new FormData();
    fd.append('venta_id', ventaId);
    fd.append('cuotas', JSON.stringify(selectedCuotas.map(c => c.id)));
    fd.append('monto_total', totalCuotas);
    fd.append('fecha', finalData.fecha || '');
    fd.append('monto', finalData.monto || '');
    fd.append('referencia', finalData.referencia || '');
    fd.append('concepto', finalData.concepto || '');
    fd.append('banco_id', finalData.banco_id || '');
    fd.append('banco_nombre', finalData.banco_nombre || '');

    if (REQUIRE_COMPROBANTE && !currentBlob) {
        showError('Falta comprobante', 'Debes adjuntar el comprobante');
        return;
    }
    if (currentBlob) {
        fd.append('comprobante', currentBlob, `comprobante_${Date.now()}.jpg`);
    }

    showLoading('Enviando pago para validación...');

    try {
        const res = await fetch('/pagos/cuotas/pagar', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF },
            body: fd
        });
        const json = await res.json();
        Swal.close();

        if (json.codigo === 1) {
            await showSuccess('¡Pago enviado!', 'Tu pago ha sido enviado para validación.');
            closeModal();
            GetFacturas();
        }
        else {
            showError('Error al procesar', json.mensaje || 'No se pudo registrar el pago.');
        }
    } catch (e) {
        console.error(e);
        Swal.close();
        showError('Error de conexión', 'No se pudo conectar con el servidor.');
    }
});

const getFinalData = () => {
    const manualVisible = formDatos && !formWrap?.classList.contains('hidden');
    const bankFromTop = bancoSelectTop && bancoSelectTop.value ? String(bancoSelectTop.value) : null;
    const base = { ...parsed };

    base.banco_id = bankFromTop || base.banco_id || null;
    base.banco_nombre = base.banco_id ? (BANKS[base.banco_id] || '') : '';

    if (manualVisible) {
        base.fecha = formDatos.elements['fecha']?.value || base.fecha || '';
        base.monto = formDatos.elements['monto']?.value || base.monto || '';
        base.referencia = formDatos.elements['referencia']?.value || base.referencia || '';
        base.concepto = formDatos.elements['concepto']?.value || base.concepto || '';
    }
    return base;
};

/* ==== UX y validaciones ==== */
window.addEventListener('resize', () => {
    if (!modal?.classList.contains('hidden')) {
        const modalContent = modal.querySelector('.bg-white');
        if (modalContent && window.innerHeight < 700) {
            modalContent.style.maxHeight = '95vh';
        }
        else if (modalContent) {
            modalContent.style.maxHeight = '90vh';
        }
    }
});

formDatos?.addEventListener('input', (e) => {
    const field = e.target;

    if (field.name === 'monto') {
        const v = Number(field.value);
        if (v <= 0 && field.value !== '') {
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    }

    if (field.name === 'fecha') {
        const rx = /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/;
        if (!rx.test(field.value) && field.value !== '') {
            field.classList.add('border-red-500');
        } else {
            field.classList.remove('border-red-500');
        }
    }
    updateStep2Buttons();
});

const addTooltips = () => {
    const tooltips = [
        { selector: '#btnOcr', text: 'Extrae automáticamente los datos del comprobante (opcional)' },
        { selector: '#btnEditarManual', text: 'Permite ingresar manualmente los datos del comprobante' },
        { selector: '.upload-zone', text: 'Arrastra la imagen del comprobante o haz clic para seleccionar (opcional)' }
    ];

    tooltips.forEach(({ selector, text }) => {
        const el = document.querySelector(selector);
        if (el) el.title = text;
    });
};

const animateStatsOnLoad = () => {
    const cards = document.querySelectorAll('.glass-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${0.1 * index}s`;
        card.classList.add('animate-slideInUp');
    });
};


const originalGetFacturas = GetFacturas;
window.GetFacturas = async () => {
    try {
        await originalGetFacturas();
        setTimeout(animateStatsOnLoad, 100);
    } catch (error) {
        console.error('Error loading facturas:', error);
        throw error;
    }
};

document.addEventListener('DOMContentLoaded', addTooltips);
