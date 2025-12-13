import Swal from "sweetalert2";
import DataTable from "vanilla-datatables";
import "vanilla-datatables/src/vanilla-dataTables.css";

let datatableComisiones = null;
let datatableResumen = null;
let currentComisionId = null;

const swalLoadingOpen = (title = 'Procesando...') => {
    Swal.fire({
        title,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
};

const swalLoadingClose = () => Swal.close();

const initDataTable = () => {
    datatableComisiones = new DataTable('#datatableComisiones', {
        searchable: false,
        sortable: true,
        fixedHeight: true,
        perPage: 10,
        perPageSelect: [5, 10, 20, 50],
        labels: {
            placeholder: "Buscar...",
            perPage: "{select} registros por página",
            noRows: "No se encontraron registros",
            info: "Mostrando {start} a {end} de {rows} registros",
        },
        data: {
            headings: ["Vendedor", "Venta #", "Fecha Venta", "Monto Venta", "% Comisión", "Ganancia", "Estado", "Días", "Acciones"],
            data: []
        }
    });
};

const buscarComisiones = async (filtros = {}) => {
    try {
        let url = "/comisiones/search";
        const searchParams = new URLSearchParams();
        if (filtros.vendedor_id) searchParams.append('vendedor_id', filtros.vendedor_id);
        if (filtros.fecha_inicio) searchParams.append('fecha_inicio', filtros.fecha_inicio);
        if (filtros.fecha_fin) searchParams.append('fecha_fin', filtros.fecha_fin);
        if (filtros.estado) searchParams.append('estado', filtros.estado);
        if (searchParams.toString()) url += '?' + searchParams.toString();

        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };

        swalLoadingOpen('Cargando comisiones...');
        const respuesta = await fetch(url, config);
        const data = await respuesta.json();
        swalLoadingClose();

        const { codigo, mensaje, datos } = data;

        if (codigo == 1) {
            if (Array.isArray(datos) && datos.length) {
                const tableData = datos.map(comision => {
                    const comisionStr = JSON.stringify(comision).replace(/'/g, '&#39;');
                    const montoFormateado = new Intl.NumberFormat('es-GT', {
                        style: 'currency',
                        currency: 'GTQ'
                    }).format(comision.monto_venta);

                    const gananciaFormateada = new Intl.NumberFormat('es-GT', {
                        style: 'currency',
                        currency: 'GTQ'
                    }).format(comision.ganancia_calculada);

                    const estadoBadge = getEstadoBadge(comision.estado_pago);
                    const acciones = getAcciones(comision, comisionStr);
                    const diasFormateados = Math.floor(comision.dias_transcurridos || 0);

                    return [
                        `<div class="text-sm font-medium text-gray-900 dark:text-gray-100">${comision.vendedor_nombre}</div>`,
                        `<div class="text-sm text-gray-900 dark:text-gray-100">#${comision.venta_id}</div>`,
                        `<div class="text-sm text-gray-900 dark:text-gray-100">${comision.fecha_venta || 'N/A'}</div>`,
                        `<div class="text-sm font-medium text-gray-900 dark:text-gray-100">${montoFormateado}</div>`,
                        `<div class="text-sm text-gray-900 dark:text-gray-100">${comision.porcentaje_comision}%</div>`,
                        `<div class="text-sm font-medium text-green-600">${gananciaFormateada}</div>`,
                        estadoBadge,
                        `<div class="text-sm text-gray-900 dark:text-gray-100">${diasFormateados} días</div>`,
                        acciones
                    ];
                });

                if (datatableComisiones) datatableComisiones.destroy();
                datatableComisiones = new DataTable('#datatableComisiones', {
                    searchable: false,
                    sortable: true,
                    fixedHeight: true,
                    perPage: 10,
                    perPageSelect: [5, 10, 20, 50],
                    labels: {
                        placeholder: "Buscar...",
                        perPage: "{select} registros por página",
                        noRows: "No se encontraron registros",
                        info: "Mostrando {start} a {end} de {rows} registros",
                    },
                    data: {
                        headings: ["Vendedor", "Venta #", "Fecha Venta", "Monto Venta", "% Comisión", "Ganancia", "Estado", "Días", "Acciones"],
                        data: tableData
                    }
                });

            } else {
                if (datatableComisiones) datatableComisiones.destroy();
                initDataTable();
                Swal.fire('Aviso', 'No hay comisiones para mostrar con los filtros aplicados', 'info');
            }
        } else {
            Swal.fire('Error', mensaje || 'Ocurrió un error en la respuesta', 'error');
        }

    } catch (error) {
        console.error("Error cargando comisiones:", error);
        swalLoadingClose();
        Swal.fire('Error', 'Error de conexión o formato de respuesta inválido', 'error');
    }
};

// Función para marcar como pagado
const marcarComoPagado = async (comisionId, observaciones) => {
    try {
        const config = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                id: comisionId,
                observaciones: observaciones,
                estado: 'PAGADO'
            })
        };

        swalLoadingOpen('Marcando como pagado...');
        const respuesta = await fetch('/comisiones', config);
        const data = await respuesta.json();
        swalLoadingClose();

        if (respuesta.ok && data.codigo === 1) {
            await Swal.fire('Éxito', data.mensaje || 'Comisión marcada como pagada exitosamente', 'success');
            buscarComisiones(obtenerFiltrosActuales());
        } else {
            Swal.fire('Error', data.mensaje || 'Error al marcar comisión como pagada', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        swalLoadingClose();
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};

// FUNCIÓN FALTANTE: Cancelar comisión
const cancelarComision = async (comisionId) => {
    try {
        const config = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                id: comisionId
            })
        };

        swalLoadingOpen('Cancelando comisión...');
        const respuesta = await fetch('/comisiones/cancelar', config);
        const data = await respuesta.json();
        swalLoadingClose();

        if (respuesta.ok && data.codigo === 1) {
            await Swal.fire('Éxito', data.mensaje || 'Comisión cancelada exitosamente', 'success');
            buscarComisiones(obtenerFiltrosActuales());
        } else {
            Swal.fire('Error', data.mensaje || 'Error al cancelar comisión', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        swalLoadingClose();
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};

// Función para buscar resumen MEJORADA
const buscarResumen = async (filtros = {}) => {
    try {
        let url = "/comisiones/resumen";
        const searchParams = new URLSearchParams();
        if (filtros.vendedor_id) searchParams.append('vendedor_id', filtros.vendedor_id);
        if (filtros.fecha_inicio) searchParams.append('fecha_inicio', filtros.fecha_inicio);
        if (filtros.fecha_fin) searchParams.append('fecha_fin', filtros.fecha_fin);
        if (searchParams.toString()) url += '?' + searchParams.toString();

        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };

        swalLoadingOpen('Cargando resumen...');
        const respuesta = await fetch(url, config);
        const data = await respuesta.json();
        swalLoadingClose();

        const { codigo, mensaje, datos, totales } = data;

        if (codigo == 1 && Array.isArray(datos)) {
            // Actualizar tarjetas de totales
            actualizarTotalesResumen(totales);

            const tableData = datos.map(resumen => {
                const totalVendidoFormateado = new Intl.NumberFormat('es-GT', {
                    style: 'currency',
                    currency: 'GTQ'
                }).format(resumen.total_vendido || 0);

                const totalComisionesFormateado = new Intl.NumberFormat('es-GT', {
                    style: 'currency',
                    currency: 'GTQ'
                }).format(resumen.total_comisiones || 0);

                const comisionesPagadasFormateado = new Intl.NumberFormat('es-GT', {
                    style: 'currency',
                    currency: 'GTQ'
                }).format(resumen.comisiones_pagadas || 0);

                const comisionesPendientesFormateado = new Intl.NumberFormat('es-GT', {
                    style: 'currency',
                    currency: 'GTQ'
                }).format(resumen.comisiones_pendientes || 0);

                const porcentajePagado = resumen.total_comisiones > 0 ?
                    ((resumen.comisiones_pagadas / resumen.total_comisiones) * 100).toFixed(1) : 0;

                return [
                    `<div class="text-sm font-semibold text-gray-900 dark:text-gray-100">${resumen.nombre_vendedor}</div>`,
                    `<div class="text-sm text-center text-gray-900 dark:text-gray-100 font-medium">${resumen.total_ventas}</div>`,
                    `<div class="text-sm font-medium text-blue-600 dark:text-blue-400">${totalVendidoFormateado}</div>`,
                    `<div class="text-sm text-center text-gray-900 dark:text-gray-100">${parseFloat(resumen.porcentaje_promedio || 0).toFixed(2)}%</div>`,
                    `<div class="text-sm font-bold text-purple-600 dark:text-purple-400">${totalComisionesFormateado}</div>`,
                    `<div class="text-sm font-medium text-green-600 dark:text-green-400">${comisionesPagadasFormateado}</div>`,
                    `<div class="text-sm font-medium text-orange-600 dark:text-orange-400">${comisionesPendientesFormateado}</div>`,
                    `<div class="text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${porcentajePagado >= 80 ? 'bg-green-100 text-green-800' : porcentajePagado >= 50 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'}">
                            ${porcentajePagado}%
                        </span>
                    </div>`
                ];
            });

            if (datatableResumen) datatableResumen.destroy();
            datatableResumen = new DataTable('#datatableResumen', {
                searchable: true,
                sortable: true,
                fixedHeight: false,
                perPage: 10,
                perPageSelect: [5, 10, 20, 50],
                labels: {
                    placeholder: "Buscar vendedor...",
                    perPage: "{select} registros por página",
                    noRows: "No se encontraron registros",
                    info: "Mostrando {start} a {end} de {rows} registros",
                },
                data: {
                    headings: ["Vendedor", "Ventas", "Total Vendido", "% Promedio", "Comisiones", "Pagadas", "Pendientes", "% Pagado"],
                    data: tableData
                }
            });
        } else {
            Swal.fire('Aviso', 'No hay datos de resumen para mostrar', 'warning');
        }

    } catch (error) {
        console.error("Error cargando resumen:", error);
        swalLoadingClose();
        Swal.fire('Error', 'Error de conexión o formato de respuesta inválido', 'error');
    }
};

// Función para actualizar las tarjetas de totales
const actualizarTotalesResumen = (totales) => {
    if (totales) {
        document.getElementById('totalVendedores').textContent = totales.total_vendedores || 0;
        document.getElementById('totalVentas').textContent = totales.total_ventas_general || 0;
        document.getElementById('totalVendido').textContent = new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(totales.total_vendido_general || 0);
        document.getElementById('totalComisiones').textContent = new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(totales.total_comisiones_general || 0);
        document.getElementById('totalPagadas').textContent = new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(totales.total_comisiones_pagadas_general || 0);
        document.getElementById('totalPendientes').textContent = new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(totales.total_comisiones_pendientes_general || 0);
    }
};

const getEstadoBadge = (estado) => {
    switch (estado) {
        case 'PAGADO':
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        PAGADO
                    </span>`;
        case 'CANCELADO':
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        CANCELADO
                    </span>`;
        default:
            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 001 1h1a1 1 0 100-2v-3z" clip-rule="evenodd"></path>
                        </svg>
                        PENDIENTE
                    </span>`;
    }
};

const getAcciones = (comision, comisionStr) => {
    // Si es vendedor, no mostrar acciones de pago/cancelación
    if (window.isSeller) {
        return `<div class="flex items-center justify-end gap-2">
                  <span class="text-sm text-gray-500 dark:text-gray-400 italic">${comision.estado_pago}</span>
                </div>`;
    }

    if (comision.estado_pago === 'PENDIENTE') {
        return `<div class="flex items-center justify-end gap-2">
                   <button class="btn-pagar p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900 rounded-md transition-colors" 
                           data-id="${comision.id}" data-comision='${comisionStr}' title="Marcar como pagado">
                     <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                     </svg>
                   </button>
                   <button class="btn-cancelar p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900 rounded-md transition-colors" 
                           data-id="${comision.id}" data-comision='${comisionStr}' title="Cancelar comisión">
                     <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                     </svg>
                   </button>
                 </div>`;
    }
    return `<div class="flex items-center justify-end gap-2">
              <span class="text-sm text-gray-500 dark:text-gray-400 italic">${comision.estado_pago === 'PAGADO' ? 'Ya pagado' : 'Cancelado'}</span>
            </div>`;
};

const abrirModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
};

const cerrarModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
};

const obtenerFiltrosActuales = () => {
    return {
        vendedor_id: document.getElementById('filtroVendedor')?.value || '',
        fecha_inicio: document.getElementById('filtroFechaInicio')?.value || '',
        fecha_fin: document.getElementById('filtroFechaFin')?.value || '',
        estado: document.getElementById('filtroEstado')?.value || ''
    };
};

const limpiarFiltros = () => {
    document.getElementById('filtroVendedor').value = '';
    document.getElementById('filtroFechaInicio').value = '';
    document.getElementById('filtroFechaFin').value = '';
    document.getElementById('filtroEstado').value = '';
    buscarComisiones({});
};

const configurarModalPagar = (comision) => {
    document.getElementById('comision_id').value = comision.id;
    document.getElementById('infoPagoVendedor').textContent = comision.vendedor_nombre;
    document.getElementById('infoPagoVenta').textContent = `#${comision.venta_id}`;

    const gananciaFormateada = new Intl.NumberFormat('es-GT', {
        style: 'currency',
        currency: 'GTQ'
    }).format(comision.ganancia_calculada);
    document.getElementById('infoPagoGanancia').textContent = gananciaFormateada;

    document.getElementById('observaciones_pago').value = '';
    currentComisionId = comision.id;
};

// Event Listeners
document.addEventListener('click', (e) => {
    // Cerrar modales
    if (e.target.matches('[data-modal-close]') || e.target.matches('[data-modal-backdrop]')) {
        cerrarModal('modalResumen');
        cerrarModal('modalPagar');
    }

    // Buscar comisiones
    if (e.target.matches('#btnBuscar') || e.target.closest('#btnBuscar')) {
        const filtros = obtenerFiltrosActuales();
        buscarComisiones(filtros);
    }

    // Ver resumen
    if (e.target.matches('#btnResumen') || e.target.closest('#btnResumen')) {
        const filtros = obtenerFiltrosActuales();
        buscarResumen(filtros);
        abrirModal('modalResumen');
    }

    // Limpiar filtros
    if (e.target.matches('#btnLimpiar') || e.target.closest('#btnLimpiar')) {
        limpiarFiltros();
    }

    // Marcar como pagado
    if (e.target.matches('.btn-pagar') || e.target.closest('.btn-pagar')) {
        const btn = e.target.closest('.btn-pagar');
        const comisionData = btn.dataset.comision;
        if (comisionData) {
            const comision = JSON.parse(comisionData);
            configurarModalPagar(comision);
            abrirModal('modalPagar');
        }
    }

    // Cancelar comisión
    if (e.target.matches('.btn-cancelar') || e.target.closest('.btn-cancelar')) {
        const btn = e.target.closest('.btn-cancelar');
        const comisionData = btn.dataset.comision;
        if (comisionData) {
            const comision = JSON.parse(comisionData);
            Swal.fire({
                title: '¿Cancelar comisión?',
                html: `<div class="text-left">
                        <p class="mb-2"><strong>Vendedor:</strong> ${comision.vendedor_nombre}</p>
                        <p class="mb-2"><strong>Venta:</strong> #${comision.venta_id}</p>
                        <p class="mb-4"><strong>Ganancia:</strong> <span class="text-red-600 font-semibold">${new Intl.NumberFormat('es-GT', { style: 'currency', currency: 'GTQ' }).format(comision.ganancia_calculada)}</span></p>
                        <p class="text-sm text-gray-600">Esta acción no se puede deshacer.</p>
                       </div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-times mr-1"></i> Sí, cancelar',
                cancelButtonText: '<i class="fas fa-arrow-left mr-1"></i> No, mantener',
                customClass: {
                    confirmButton: 'swal2-confirm-btn',
                    cancelButton: 'swal2-cancel-btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    cancelarComision(comision.id);
                }
            });
        }
    }
});

// Form submit para marcar como pagado
const formPagar = document.getElementById('formPagar');
if (formPagar) {
    formPagar.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!currentComisionId) {
            Swal.fire('Error', 'No se ha seleccionado una comisión válida', 'error');
            return;
        }

        const observaciones = document.getElementById('observaciones_pago').value.trim();

        try {
            await marcarComoPagado(currentComisionId, observaciones);
            cerrarModal('modalPagar');
            currentComisionId = null;
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
}

// Inicializar aplicación
document.addEventListener('DOMContentLoaded', function () {
    initDataTable();
    // Cargar todas las comisiones sin filtros al inicio
    buscarComisiones();
});