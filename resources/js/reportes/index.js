/**
 * Gestor del Sistema de Reportes - Armería
 * JavaScript puro - Laravel
 */
import Chart from 'chart.js/auto';
class ReportesManager {
    constructor() {
        this.currentTab = 'dashboard';
        this.kpis = {};
        this.filtros = {
            fecha_inicio: null,
            fecha_fin: null
        };
        this.graficos = {
            ventasDias: null,
            productosTop: null,
            vendedores: null,
            metodosPago: null
        };
        this.data = {
            ventas: [],
            productos: [],
            comisiones: [],
            pagos: []
        };

        this.init();
    }

    /**
     * Inicializar el gestor
     */
    init() {

        this.setupEventListeners();
        this.setupFechasIniciales();
        this.loadInitialData();



    }
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Event listeners para filtros de fecha
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');

        if (fechaInicio) {
            fechaInicio.addEventListener('change', (e) => {
                this.filtros.fecha_inicio = e.target.value;
            });
        }

        if (fechaFin) {
            fechaFin.addEventListener('change', (e) => {
                this.filtros.fecha_fin = e.target.value;
            });
        }
    }

    /**
     * Configurar fechas iniciales (mes actual)
     */
    setupFechasIniciales() {
        const hoy = new Date();
        const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const finMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);

        const fechaInicioEl = document.getElementById('fecha_inicio');
        const fechaFinEl = document.getElementById('fecha_fin');

        if (fechaInicioEl) {
            fechaInicioEl.value = this.formatearFecha(inicioMes);
            this.filtros.fecha_inicio = this.formatearFecha(inicioMes);
        }

        if (fechaFinEl) {
            fechaFinEl.value = this.formatearFecha(finMes);
            this.filtros.fecha_fin = this.formatearFecha(finMes);
        }
    }

    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadDashboard(),
                this.loadFiltros()
            ]);
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showAlert('error', 'Error', 'No se pudieron cargar los datos iniciales');
        }
    }

    /**
     * Cargar dashboard con KPIs y gráficos
     */
    async loadDashboard() {
        try {
            this.showLoading('dashboard');

            const response = await fetch('/reportes/dashboard?' + new URLSearchParams(this.filtros));
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.updateKPIs(result.data.kpis);
                    this.updateGraficos(result.data.graficos);
                }
            }
        } catch (error) {
            console.error('Error cargando dashboard:', error);
        } finally {
            this.hideLoading('dashboard');
        }
    }

    /**
     * Cargar filtros para los formularios
     */
    async loadFiltros() {
        try {
            const response = await fetch('/reportes/filtros');
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.populateFiltros(result.data);
                }
            }
        } catch (error) {
            console.error('Error cargando filtros:', error);
        }
    }

    /**
     * Cambiar tab activo
     */
    cambiarTab(tab) {
        // Actualizar botones
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            btn.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        });

        // Encontrar el botón clickeado y activarlo
        const activeButton = document.querySelector(`button[onclick*="'${tab}'"]`);
        if (activeButton) {
            activeButton.classList.add('active', 'border-blue-500', 'text-blue-600', 'dark:text-blue-400');
            activeButton.classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
        }

        // Mostrar/ocultar contenido
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        const tabContent = document.getElementById(`tab-${tab}`);
        if (tabContent) {
            tabContent.classList.remove('hidden');
        }

        this.currentTab = tab;

        // Cargar datos específicos del tab
        this.loadTabData(tab);
    }

    /**
     * Cargar datos específicos del tab
     */
    /**
     * Cargar datos específicos del tab
     */
    async loadTabData(tab) {
        switch (tab) {
            case 'ventas':
                await this.loadReporteVentas();
                break;
            case 'productos':
                await this.loadReporteProductos();
                break;
            case 'comisiones':
                await this.loadReporteComisiones();
                break;
            case 'pagos':
                await this.loadReportePagos();
                break;
            case 'digecam-armas':
                await this.loadReporteDigecamArmas();
                break;
            case 'digecam-municiones':
                await this.loadReporteDigecamMuniciones();
                break;
            case 'digecam-municiones':
                await this.loadReporteDigecamMuniciones();
                break;
            case 'historial-ventas':
                await this.loadReporteHistorialVentas();
                break;
        }
    }

    /**
     * Actualizar KPIs en el dashboard
     */
    updateKPIs(kpis) {
        const elements = {
            'kpi-total-ventas': this.formatNumber(kpis.total_ventas),
            'kpi-monto-total': this.formatCurrency(kpis.monto_total),
            'kpi-productos-vendidos': this.formatNumber(kpis.productos_vendidos),
            'kpi-comisiones-pendientes': this.formatCurrency(kpis.comisiones_pendientes)
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });

        // Actualizar promedios
        if (kpis.total_ventas > 0) {
            const promedioEl = document.getElementById('kpi-promedio-productos');
            if (promedioEl) {
                promedioEl.textContent = `${Math.round(kpis.productos_vendidos / kpis.total_ventas)} promedio por venta`;
            }
        }
    }

    /**
     * Actualizar gráficos del dashboard
     */
    updateGraficos(graficos) {
        // Solo crear gráficos si Chart.js está disponible
        if (typeof Chart !== 'undefined') {
            this.createGraficoVentasDias(graficos.ventas_por_dia);
            this.createGraficoProductosTop(graficos.productos_mas_vendidos);
            this.createGraficoVendedores(graficos.ventas_por_vendedor);
            this.createGraficoMetodosPago(graficos.metodos_pago);
        } else {
            console.warn('Chart.js no está disponible');
        }
    }

    /**
     * Crear gráfico de ventas por día
     */
    createGraficoVentasDias(data) {
        const ctx = document.getElementById('grafico-ventas-dias');
        if (!ctx) return;

        if (this.graficos.ventasDias) {
            this.graficos.ventasDias.destroy();
        }

        this.graficos.ventasDias = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(item => this.formatearFechaCorta(item.fecha)),
                datasets: [{
                    label: 'Ventas',
                    data: data.map(item => item.total_ventas),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Monto (Q)',
                    data: data.map(item => item.monto_total),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    yAxisID: 'y1',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
    }

    /**
     * Crear gráfico de productos más vendidos
     */
    createGraficoProductosTop(data) {
        const ctx = document.getElementById('grafico-productos-top');
        if (!ctx) return;

        if (this.graficos.productosTop) {
            this.graficos.productosTop.destroy();
        }

        this.graficos.productosTop = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => this.truncateText(item.producto_nombre, 15)),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: data.map(item => item.total_vendido),
                    backgroundColor: 'rgba(234, 179, 8, 0.7)',
                    borderColor: 'rgb(234, 179, 8)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    /**
     * Crear gráfico de ventas por vendedor
     */
    createGraficoVendedores(data) {
        const ctx = document.getElementById('grafico-vendedores');
        if (!ctx) return;

        if (this.graficos.vendedores) {
            this.graficos.vendedores.destroy();
        }

        const colors = [
            'rgb(239, 68, 68)', 'rgb(34, 197, 94)', 'rgb(59, 130, 246)',
            'rgb(234, 179, 8)', 'rgb(168, 85, 247)', 'rgb(236, 72, 153)'
        ];

        this.graficos.vendedores = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.vendedor),
                datasets: [{
                    data: data.map(item => item.monto_total),
                    backgroundColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Crear gráfico de métodos de pago
     */
    createGraficoMetodosPago(data) {
        const ctx = document.getElementById('grafico-metodos-pago');
        if (!ctx) return;

        if (this.graficos.metodosPago) {
            this.graficos.metodosPago.destroy();
        }

        this.graficos.metodosPago = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.metodo),
                datasets: [{
                    data: data.map(item => item.monto_total),
                    backgroundColor: [
                        'rgb(99, 102, 241)', 'rgb(16, 185, 129)', 'rgb(245, 101, 101)',
                        'rgb(251, 191, 36)', 'rgb(139, 92, 246)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    /**
     * Cargar reporte de ventas
     */


    async loadReporteVentas(filtros = {}) {
        try {
            this.showLoading('ventas');



            const params = new URLSearchParams();
            if (filtros.fecha_desde) params.append('fecha_desde', filtros.fecha_desde);
            if (filtros.fecha_hasta) params.append('fecha_hasta', filtros.fecha_hasta);
            if (filtros.vendedor_id) params.append('vendedor_id', filtros.vendedor_id);
            if (filtros.cliente_id) params.append('cliente_id', filtros.cliente_id);

            const url = `/ventas/pendientes${params.toString() ? '?' + params.toString() : ''}`;

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const result = await response.json();


                if (result.success && result.data) {
                    this.renderTablaVentas(result.data);

                    // Mostrar resumen

                } else {

                    this.renderTablaVentas([]);
                }
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

        } catch (error) {

            Swal.fire('Error', 'No se pudieron cargar las ventas pendientes', 'error');
            this.renderTablaVentas([]);
        } finally {
            this.hideLoading('ventas');
        }
    }

    async editarVentaClick(venta) {
        let htmlContent = '<div class="text-left">';

        for (const det of venta.detalles) {
            htmlContent += `
                <div class="mb-4 p-3 border rounded bg-gray-50">
                    <div class="font-bold text-gray-700">${det.producto_nombre}</div>
                    <div class="text-sm text-gray-500 mb-2">Cantidad: ${det.cantidad}</div>
            `;

            if (det.series && det.series.length > 0) {
                htmlContent += `<div class="text-sm font-semibold mb-1">Series Asignadas:</div>`;
                for (const serie of det.series) {
                    htmlContent += `
                        <div class="flex items-center justify-between mb-2 bg-white p-2 rounded border">
                            <span class="text-gray-800 font-mono">${serie.numero}</span>
                            <button class="text-blue-600 text-sm hover:text-blue-800 font-semibold" 
                                onclick="reportesManager.cambiarSerie(${venta.ven_id}, ${det.det_id}, ${det.producto_id}, '${serie.id}', '${serie.numero}')">
                                <i class="fas fa-exchange-alt mr-1"></i> Cambiar
                            </button>
                        </div>
                    `;
                }
            } else if (det.lotes && det.lotes.length > 0) {
                htmlContent += `<div class="text-sm font-semibold mb-1">Lotes Asignados:</div>`;
                for (const lote of det.lotes) {
                    htmlContent += `
                        <div class="flex items-center justify-between mb-2 bg-white p-2 rounded border">
                            <span class="text-gray-800 font-mono">${lote.codigo}</span>
                            <button class="text-blue-600 text-sm hover:text-blue-800 font-semibold" 
                                onclick="reportesManager.cambiarLote(${venta.ven_id}, ${det.det_id}, ${det.producto_id}, '${lote.id}', '${lote.codigo}')">
                                <i class="fas fa-exchange-alt mr-1"></i> Cambiar
                            </button>
                        </div>
                    `;
                }
            } else {
                htmlContent += `<div class="text-sm text-gray-400 italic">Sin series/lotes asignados</div>`;
            }
            htmlContent += `</div>`;
        }
        htmlContent += '</div>';

        Swal.fire({
            title: `Editar Venta #${venta.ven_id}`,
            html: htmlContent,
            showCloseButton: true,
            showConfirmButton: false,
            width: '600px'
        });
    }

    async cambiarSerie(venId, detId, productoId, oldSerieId, oldSerieNumero) {
        try {
            // 1. Fetch available series
            const response = await fetch(`/inventario/productos/${productoId}/series-disponibles`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar series');
            }

            const seriesDisponibles = data.data;

            if (seriesDisponibles.length === 0) {
                Swal.fire('Atención', 'No hay otras series disponibles para este producto.', 'warning');
                return;
            }

            // 2. Show selection modal
            const options = {};
            seriesDisponibles.forEach(s => {
                options[s.serie_id] = s.serie_numero_serie;
            });

            const { value: newSerieId } = await Swal.fire({
                title: 'Seleccionar Nueva Serie',
                text: `Cambiando serie: ${oldSerieNumero}`,
                input: 'select',
                inputOptions: options,
                inputPlaceholder: 'Seleccione una serie',
                showCancelButton: true,
                inputValidator: (value) => {
                    return !value && 'Debe seleccionar una serie';
                }
            });

            if (newSerieId) {
                this.procesarCambio(venId, {
                    det_id: detId,
                    producto_id: productoId,
                    old_serie_id: oldSerieId,
                    new_serie_id: newSerieId
                });
            }

        } catch (error) {
            console.error(error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    async cambiarLote(venId, detId, productoId, oldLoteId, oldLoteCodigo) {
        try {
            // 1. Fetch available lotes
            const response = await fetch(`/inventario/productos/${productoId}/stock-lotes`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Error al cargar lotes');
            }

            // Filter valid lotes
            const lotesDisponibles = (data.data.lotes || []).filter(l => l.cantidad_disponible > 0 && l.lote_id != oldLoteId);

            if (lotesDisponibles.length === 0) {
                Swal.fire('Atención', 'No hay otros lotes con stock disponible.', 'warning');
                return;
            }

            // 2. Show selection modal
            const options = {};
            lotesDisponibles.forEach(l => {
                options[l.lote_id] = `${l.lote_codigo} (Disp: ${l.cantidad_disponible})`;
            });

            const { value: newLoteId } = await Swal.fire({
                title: 'Seleccionar Nuevo Lote',
                text: `Cambiando lote: ${oldLoteCodigo}`,
                input: 'select',
                inputOptions: options,
                inputPlaceholder: 'Seleccione un lote',
                showCancelButton: true,
                inputValidator: (value) => {
                    return !value && 'Debe seleccionar un lote';
                }
            });

            if (newLoteId) {
                this.procesarCambio(venId, {
                    det_id: detId,
                    producto_id: productoId,
                    old_lote_id: oldLoteId,
                    new_lote_id: newLoteId
                });
            }

        } catch (error) {
            console.error(error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    async procesarCambio(venId, cambio) {
        try {
            const updateResponse = await fetch('/ventas/update-editable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    ven_id: venId,
                    cambios: [cambio]
                })
            });

            const result = await updateResponse.json();

            if (result.success) {
                Swal.fire('Éxito', 'Cambio aplicado correctamente', 'success').then(() => {
                    this.loadReporteVentas();
                });
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', error.message, 'error');
        }
    }

    /**
     * Cargar reporte de historial de ventas (TODAS)
     */
    async loadReporteHistorialVentas(page = 1) {
        try {
            this.showLoading('historial-ventas');

            const filtros = {
                page: page,
                fecha_inicio: this.filtros.fecha_inicio,
                fecha_fin: this.filtros.fecha_fin,
                vendedor_id: document.getElementById('filtro-vendedor-historial')?.value || '',
                cliente_buscar: document.getElementById('filtro-cliente-historial')?.value || '',
                estado: document.getElementById('filtro-estado-historial')?.value || '',
                // El endpoint getReporteVentas ya calcula estado_pago, pero no filtra por él en backend
                // Si queremos filtrar por estado_pago, idealmente deberíamos hacerlo en backend o en frontend post-fetch.
                // Por ahora, enviaremos los parámetros que el backend acepta.
            };

            const params = new URLSearchParams();
            Object.entries(filtros).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            // Endpoint existente que devuelve todas las ventas con paginación
            const url = `/reportes/ventas?${params.toString()}`;

            const response = await fetch(url);

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    // Filtrado en cliente para estado_pago si es necesario (ya que el backend no lo tiene nativo en query)
                    const estadoPagoFiltro = document.getElementById('filtro-pago-historial')?.value;
                    let ventas = result.data.data;

                    if (estadoPagoFiltro) {
                        ventas = ventas.filter(v => v.estado_pago === estadoPagoFiltro);
                    }

                    this.renderTablaHistorialVentas(ventas);
                    this.renderPaginacion(result.data, 'paginacion-historial-ventas', (p) => this.loadReporteHistorialVentas(p));
                } else {
                    this.renderTablaHistorialVentas([]);
                }
            }
        } catch (error) {
            console.error('Error cargando historial:', error);
            this.renderTablaHistorialVentas([]);
        } finally {
            this.hideLoading('historial-ventas');
        }
    }

    aplicarFiltrosHistorial() {
        this.loadReporteHistorialVentas(1);
    }

    renderTablaHistorialVentas(ventas) {
        const tbody = document.getElementById('tbody-historial-ventas');
        if (!tbody) return;

        if (!ventas || ventas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="9" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                        No se encontraron ventas en el historial
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = ventas.map(venta => {
            const clienteNombre = venta.cliente
                ? `${venta.cliente.cliente_nombre1} ${venta.cliente.cliente_apellido1}`
                : 'N/A';

            const vendedorNombre = venta.vendedor
                ? `${venta.vendedor.user_primer_nombre} ${venta.vendedor.user_primer_apellido}`
                : 'N/A';

            // Colores para estado de pago
            let colorPago = 'bg-gray-100 text-gray-800';
            if (venta.estado_pago === 'COMPLETADO') colorPago = 'bg-green-100 text-green-800';
            else if (venta.estado_pago === 'PARCIAL') colorPago = 'bg-yellow-100 text-yellow-800';
            else if (venta.estado_pago === 'PENDIENTE') colorPago = 'bg-red-100 text-red-800';

            // Colores para estado de venta
            let colorVenta = 'bg-gray-100 text-gray-800';
            if (venta.ven_situacion === 'ACTIVA') colorVenta = 'bg-green-100 text-green-800';
            else if (venta.ven_situacion === 'ANULADA') colorVenta = 'bg-red-100 text-red-800';
            else if (venta.ven_situacion === 'PENDIENTE') colorVenta = 'bg-yellow-100 text-yellow-800';

            return `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        ${this.formatearFechaDisplay(venta.ven_fecha)}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                        ${clienteNombre}
                        <div class="text-xs text-gray-500">${venta.cliente?.cliente_dpi || ''}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                        ${vendedorNombre}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                        <div class="flex flex-col space-y-1">
                            ${venta.productos_formateados ? venta.productos_formateados.map(p =>
                `<span class="text-xs border-b border-gray-100 last:border-0 pb-1">
                                    <strong>${p.cantidad}x</strong> ${p.nombre} 
                                    <span class="text-gray-500">(${p.sku})</span>
                                    ${p.series && p.series.length > 0 ?
                    `<div class="text-[10px] text-gray-400 mt-0.5">SN: ${p.series.join(', ')}</div>`
                    : ''}
                                </span>`
            ).join('') : '<span class="text-gray-400 italic">Sin productos</span>'}
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                        ${this.formatCurrency(venta.ven_total_vendido)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                        ${this.formatCurrency(venta.total_pagado)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        ${this.formatCurrency(venta.saldo_pendiente)}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorPago}">
                            ${venta.estado_pago}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorVenta}">
                            ${venta.ven_situacion}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                         <button onclick="reportesManager.verDetalleVenta(${venta.ven_id})"
                            class="text-blue-600 hover:text-blue-900 mr-2" title="Ver Detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <a href="/reportes/ventas/${venta.ven_id}/imprimir" target="_blank"
                            class="text-gray-600 hover:text-gray-900" title="Imprimir Comprobante">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }
    renderTablaVentas(ventas) {
        const tbody = document.getElementById('tbody-ventas');
        if (!tbody) {
            console.error('❌ No se encontró #tbody-ventas');
            return;
        }

        if (!ventas || ventas.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No se encontraron ventas pendientes</p>
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = ventas.map(venta => {
            // Preparar los datos para el dataset (aunque ahora usaremos el objeto completo en memoria si es posible, o lo pasamos al click)
            // Para simplificar, pasamos ven_id y usamos una funcion para buscar los datos o pasamos lo minimo.

            const totalItems = venta.total_items || 0;
            const resumenProductos = venta.productos_resumen || 'Sin productos';

            return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <!-- Fecha -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    <div class="text-gray-500">${this.formatearFechaDisplay(venta.ven_fecha)}</div>
                </td>
                
                <!-- Cliente -->
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                    <div class="font-medium">${venta.cliente || 'N/A'}</div>
                    <div class="text-xs text-gray-500">${venta.empresa || ''}</div>
                </td>
                
                <!-- Vendedor -->
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                    ${venta.vendedor || 'N/A'}
                </td>
                
                <!-- Productos Resumen -->
                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        ${totalItems} producto(s)
                    </div>
                    <div class="text-xs truncate max-w-xs" title="${resumenProductos}">
                        ${resumenProductos}
                    </div>
                    <button onclick='reportesManager.verDetalleVenta(${venta.ven_id})'
                            class="text-blue-600 hover:text-blue-800 text-xs mt-1 underline">
                        Ver Detalle Completo
                    </button>
                </td>
                
                <!-- Total -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-bold text-gray-900 dark:text-gray-100">
                        ${this.formatCurrency(venta.ven_total_vendido)}
                    </div>
                </td>
                
                <!-- Estado -->
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                        ${venta.ven_situacion === 'EDITABLE' ? 'bg-blue-100 text-blue-800' :
                    venta.ven_situacion === 'AUTORIZADA' ? 'bg-green-100 text-green-800' :
                        'bg-yellow-100 text-yellow-800'}">
                        <i class="fas fa-clock mr-1"></i>
                        ${venta.ven_situacion || 'PENDIENTE'}
                    </span>
                </td>
                
                <!-- Acciones -->
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${venta.ven_situacion === 'EDITABLE' ? `
                        <button onclick='reportesManager.editarVentaClick(${JSON.stringify(venta)})'
                                class="text-blue-600 hover:text-blue-900 mr-2" 
                                title="Editar / Corregir">
                            <i class="fas fa-edit text-lg"></i>
                        </button>
                    ` : ''}
                    
                    <button onclick='reportesManager.autorizarVentaClick(${JSON.stringify(venta)})'
                            class="text-green-600 hover:text-green-900 mr-2" 
                            title="Autorizar">
                        <i class="fas fa-check-circle text-lg"></i>
                    </button>
                    <button onclick="reportesManager.cancelarVentaClick(${venta.ven_id})"
                            class="text-red-600 hover:text-red-900" 
                            title="Rechazar">
                        <i class="fas fa-times-circle text-lg"></i>
                    </button>
                </td>
            </tr>
        `;
        }).join('');
    }

    verDetalleVenta(venta) {
        if (!venta || !venta.detalles) return;

        const detallesHtml = venta.detalles.map(det => {
            const seriesHtml = det.series && det.series.length > 0
                ? `<div class="text-xs text-gray-500">SN: ${det.series.join(', ')}</div>`
                : '';
            const lotesHtml = det.lotes && det.lotes.length > 0
                ? `<div class="text-xs text-gray-500">Lotes: ${det.lotes.join(', ')}</div>`
                : '';

            return `
                <tr class="border-b">
                    <td class="px-4 py-2 text-left">
                        <div class="font-medium">${det.producto_nombre}</div>
                        ${seriesHtml}
                        ${lotesHtml}
                    </td>
                    <td class="px-4 py-2 text-center">${det.cantidad}</td>
                    <td class="px-4 py-2 text-right">${this.formatCurrency(det.precio_venta)}</td>
                    <td class="px-4 py-2 text-right font-bold">${this.formatCurrency(det.subtotal)}</td>
                </tr>
            `;
        }).join('');

        Swal.fire({
            title: `Detalle de Venta #${venta.ven_id}`,
            html: `
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left">Producto</th>
                                <th class="px-4 py-2 text-center">Cant.</th>
                                <th class="px-4 py-2 text-right">Precio</th>
                                <th class="px-4 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${detallesHtml}
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right">Total:</td>
                                <td class="px-4 py-2 text-right">${this.formatCurrency(venta.ven_total_vendido)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `,
            width: '600px',
            confirmButtonText: 'Cerrar'
        });
    }

    async autorizarVentaClick(venta) {
        try {
            const { isConfirmed, isDenied } = await Swal.fire({
                title: 'Autorizar Venta',
                html: `
                    <p class="mb-4">¿Cómo deseas procesar esta venta?</p>
                    <div class="text-sm text-gray-600 mb-4">
                        <p><strong>Cliente:</strong> ${venta.cliente}</p>
                        <p><strong>Total:</strong> ${this.formatCurrency(venta.ven_total_vendido)}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#10b981', // Verde
                denyButtonColor: '#3b82f6',    // Azul
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-file-invoice mr-2"></i>Autorizar y Facturar',
                denyButtonText: '<i class="fas fa-check mr-2"></i>Autorizar sin Facturar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            });

            if (!isConfirmed && !isDenied) return;

            const tipo = isDenied ? 'sin_facturar' : 'facturar';

            if (tipo === 'sin_facturar') {
                const { isConfirmed: confirmSinFactura } = await Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Esta venta quedará autorizada sin proceso de facturación.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3b82f6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, autorizar sin factura',
                    cancelButtonText: 'Cancelar'
                });

                if (!confirmSinFactura) return;
            }

            this.showLoading('ventas');

            const response = await fetch('/ventas/autorizar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payloadFinal)
            });

            if (response.status === 419)
                throw new Error('Token CSRF inválido. Recarga la página e intenta nuevamente.');

            if (!response.ok)
                throw new Error(`Error ${response.status}: ${await response.text()}`);

            const result = await response.json();

            if (result.codigo !== 1)
                throw new Error(result.mensaje || result.detalle || 'Error al autorizar la venta');

            // Redirección según opción
            // Redirección según opción -> AHORA APERTURA DE MODAL
            if (modoFacturacion === 'normal' || modoFacturacion === 'cambiaria') {
                try {
                    Swal.fire({
                        title: 'Abriendo facturación...',
                        text: 'Obteniendo detalles de la venta',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    // 1. Fetch full sale details
                    const res = await fetch(`/facturacion/buscar-venta?q=${ventaData.ven_id}`);
                    const data = await res.json();

                    Swal.close();

                    if (data.codigo === 1 && data.data.length > 0) {
                        const ventaFull = data.data[0];

                        // 2. Open Modal & Select
                        if (modoFacturacion === 'normal') {
                            if (window.abrirModal) {
                                window.abrirModal('modalFactura');
                                // Ensure items container is ready if needed
                                const contenedorItems = document.getElementById("contenedorItems");
                                if (contenedorItems && contenedorItems.querySelectorAll('.item-factura').length === 0) {
                                    if (window.agregarItem) window.agregarItem();
                                }
                            }
                            if (window.seleccionarVenta) window.seleccionarVenta(ventaFull);
                        } else {
                            if (window.resetModalFacturaCambiaria) window.resetModalFacturaCambiaria();
                            if (window.abrirModal) window.abrirModal('modalFacturaCambiaria');
                            if (window.seleccionarVentaCambiaria) window.seleccionarVentaCambiaria(ventaFull);
                        }
                    } else {
                        Swal.fire('Error', 'No se pudieron obtener los detalles de la venta para facturar.', 'error');
                    }
                } catch (e) {
                    console.error(e);
                    Swal.close();
                    Swal.fire('Error', 'Error al cargar datos de la venta.', 'error');
                }
                return;
            }

            // Si es Solo Autorizar
            let mensajeExito = result.mensaje || '¡Venta autorizada!';

            // 💡 Licencias (Solo si se queda en esta pantalla)
            if (seriesArray.length > 0) {
                // ... (Lógica de licencias existente) ...
                let htmlLicencias = `
        <div style="max-height: 280px; overflow-y: auto; text-align: left;">
          <p class="text-sm text-gray-600 mb-3">Ingresa las licencias para cada serie:</p>
      `;
                seriesArray.forEach(serieId => {
                    htmlLicencias += `
          <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 8px; background-color: #f9fafb;">
            <div style="font-weight: 600; font-size: 13px; margin-bottom: 4px; color: #374151;">🔫 Serie ID: ${serieId}</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 6px;">
              <input id="lic-ant-${serieId}" class="swal2-input" style="margin:0;padding:6px;font-size:13px;" placeholder="Licencia anterior">
              <input id="lic-nueva-${serieId}" class="swal2-input" style="margin:0;padding:6px;font-size:13px;" placeholder="Licencia nueva">
            </div>
          </div>`;
                });
                htmlLicencias += '</div>';

                const { value: formValues } = await Swal.fire({
                    title: 'Actualizar Licencias',
                    html: htmlLicencias,
                    width: '500px',
                    focusConfirm: false,
                    showCancelButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: '<i class="fas fa-save mr-2"></i>Guardar',
                    confirmButtonColor: '#10b981',
                    preConfirm: () => {
                        const licencias = [];
                        let errorMsg = '';
                        seriesArray.forEach(serieId => {
                            const anterior = document.getElementById(`lic-ant-${serieId}`)?.value.trim();
                            const nueva = document.getElementById(`lic-nueva-${serieId}`)?.value.trim();
                            if (!anterior || !nueva) {
                                errorMsg = `⚠️ Debes llenar ambas licencias para la serie ${serieId}`;
                            }
                            licencias.push({ serie_id: serieId, licencia_anterior: anterior, licencia_nueva: nueva });
                        });
                        if (errorMsg) {
                            Swal.showValidationMessage(errorMsg);
                            return false;
                        }
                        return licencias;
                    }
                });

                if (formValues && formValues.length > 0) {
                    Swal.fire({
                        title: 'Actualizando licencias...',
                        html: 'Por favor espere',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    const updateResponse = await fetch('/ventas/actualizar-licencias', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ven_id: ventaData.ven_id, licencias: formValues })
                    });

                    const updateResult = await updateResponse.json();

                    if (!updateResponse.ok || updateResult.codigo !== 1) {
                        throw new Error(updateResult.detalle || updateResult.mensaje || 'Error al actualizar licencias');
                    }

                    mensajeExito += `<br><small class="text-green-600">✅ Licencias actualizadas: ${formValues.length} serie(s)</small>`;
                }
            }

            await Swal.fire({
                icon: 'success',
                title: '¡Venta autorizada!',
                html: mensajeExito,
                confirmButtonColor: '#10b981'
            });

            if (this.cargarVentasPendientes) {
                this.cargarVentasPendientes();
            } else if (typeof cargarVentasPendientes === 'function') {
                cargarVentasPendientes();
            }

        } catch (error) {
            console.error('❌ Error completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `
        <div class="text-left">
          <p class="font-semibold mb-2">No se pudo autorizar la venta:</p>
          <p class="text-sm">${error.message}</p>
        </div>
      `,
                confirmButtonColor: '#ef4444'
            });
        }
    }



    /**
     * Cancelar una venta pendiente
     */
    async cancelarVentaClick(ventaId) {
        try {
            // Solicitar motivo de cancelación
            const { value: motivo } = await Swal.fire({
                title: '¿Cancelar esta venta?',
                html: `
                <p class="text-sm text-gray-600 mb-3">Esta acción no se puede deshacer.</p>
                <textarea 
                    id="motivo-cancelacion" 
                    class="swal2-input w-full" 
                    placeholder="Motivo de cancelación (opcional)"
                    rows="3"></textarea>
            `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-ban mr-2"></i>Sí, cancelar venta',
                cancelButtonText: '<i class="fas fa-arrow-left mr-2"></i>No, volver',
                preConfirm: () => {
                    return document.getElementById('motivo-cancelacion')?.value.trim() || 'Cancelación sin motivo especificado';
                }
            });

            if (!motivo) return; // Usuario canceló

            // Mostrar loading
            Swal.fire({
                title: 'Cancelando venta...',
                html: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            // Enviar solicitud de cancelación
            const response = await fetch('/ventas/cancelar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ven_id: ventaId,
                    motivo: motivo
                })
            });

            if (response.status === 419) {
                throw new Error('Token CSRF inválido. Recarga la página e intenta nuevamente.');
            }

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Error ${response.status}: ${errorText}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Error al cancelar la venta');
            }

            // Mostrar éxito
            await Swal.fire({
                icon: 'success',
                title: '¡Venta cancelada!',
                text: 'La venta ha sido cancelada exitosamente',
                confirmButtonColor: '#10b981'
            });

            // Recargar la tabla de ventas
            if (this.cargarVentasPendientes) {
                this.cargarVentasPendientes();
            } else if (typeof cargarVentasPendientes === 'function') {
                cargarVentasPendientes();
            }

        } catch (error) {
            console.error('❌ Error cancelando venta:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: `
                <div class="text-left">
                    <p class="font-semibold mb-2">No se pudo cancelar la venta:</p>
                    <p class="text-sm">${error.message}</p>
                </div>
            `,
                confirmButtonColor: '#ef4444'
            });
        }
    }



    async cargarVentasPendientes(filtros = {}) {
        try {
            ('🔄 Cargando ventas pendientes... con los select ', filtros);

            // Construir query params desde los filtros
            const params = new URLSearchParams();
            if (filtros.cliente_id) params.append('cliente_id', filtros.cliente_id);
            if (filtros.vendedor_id) params.append('vendedor_id', filtros.vendedor_id);

            const url = `/ventas/pendientes${params.toString() ? '?' + params.toString() : ''}`;
            ('📡 URL:', url);

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            ('📡 Pendientes response status:', response.status);

            if (!response.ok) {
                throw new Error(`Error ${response.status}`);
            }

            const data = await response.json();
            ('📥 Ventas pendientes:', data);

            if (data.success) {
                this.renderTablaVentas(data.data);
                (`✅ ${data.total} ventas pendientes cargadas`);
            } else {
                throw new Error(data.message || 'Error al cargar ventas');
            }

        } catch (error) {
            console.error('❌ Error al cargar ventas pendientes:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron cargar las ventas pendientes',
                confirmButtonColor: '#ef4444'
            });
        }
    }




    /**
     * Cargar reporte de productos
     */
    async loadReporteProductos(filtros = {}) {
        try {
            this.showLoading('productos');

            const params = { ...this.filtros, ...filtros };
            const response = await fetch('/reportes/productos?' + new URLSearchParams(params));

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.renderTablaProductos(result.data);
                }
            }
        } catch (error) {
            console.error('Error cargando reporte de productos:', error);
        } finally {
            this.hideLoading('productos');
        }
    }

    /**
     * Cargar reporte de comisiones
     */
    async loadReporteComisiones(filtros = {}) {
        try {
            this.showLoading('comisiones');

            const params = { ...this.filtros, ...filtros };
            const response = await fetch('/reportes/comisiones?' + new URLSearchParams(params));

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.renderTablaComisiones(result.data.comisiones.data);
                    this.updateResumenComisiones(result.data.resumen);
                }
            }
        } catch (error) {
            console.error('Error cargando reporte de comisiones:', error);
        } finally {
            this.hideLoading('comisiones');
        }
    }

    /**
     * Cargar reporte de pagos
     */
    async loadReportePagos(filtros = {}) {
        try {
            this.showLoading('pagos');

            const params = { ...this.filtros, ...filtros };
            const response = await fetch('/reportes/pagos?' + new URLSearchParams(params));

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data.data) {
                    this.renderTablaPagos(result.data.data);
                }
            }
        } catch (error) {
            console.error('Error cargando reporte de pagos:', error);
        } finally {
            this.hideLoading('pagos');
        }
    }


    /**
     * Cargar reporte DIGECAM de armas
     */
    async loadReporteDigecamArmas(filtros = {}) {
        try {
            this.showLoading('digecam-armas');

            const mes = filtros.mes || new Date().getMonth() + 1;
            const anio = filtros.anio || new Date().getFullYear();

            const mesSelect = document.getElementById('filtro-mes-digecam-armas');
            const anioSelect = document.getElementById('filtro-anio-digecam-armas');

            if (mesSelect && !filtros.mes) mesSelect.value = mes;
            if (anioSelect && !filtros.anio) anioSelect.value = anio;

            const params = new URLSearchParams({ mes, anio });
            const response = await fetch(`/reportes/digecam/armas?${params}`);

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.renderTablaDigecamArmas(result.data);
                    this.updateDigecamArmasInfo(result);
                }
            }
        } catch (error) {
            console.error('Error cargando reporte DIGECAM armas:', error);
            this.showAlert('error', 'Error', 'No se pudo cargar el reporte de armas');
        } finally {
            this.hideLoading('digecam-armas');
        }
    }

    /**
     * Cargar reporte DIGECAM de municiones
     */
    async loadReporteDigecamMuniciones(filtros = {}) {
        try {
            this.showLoading('digecam-municiones');

            const params = new URLSearchParams({
                fecha_inicio: filtros.fecha_inicio || this.filtros.fecha_inicio,
                fecha_fin: filtros.fecha_fin || this.filtros.fecha_fin
            });

            const response = await fetch(`/reportes/digecam/municiones?${params}`);

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.renderTablaDigecamMuniciones(result.data);
                    this.updateDigecamMunicionesInfo(result);
                }
            }
        } catch (error) {
            console.error('Error cargando reporte DIGECAM municiones:', error);
            this.showAlert('error', 'Error', 'No se pudo cargar el reporte de municiones');
        } finally {
            this.hideLoading('digecam-municiones');
        }
    }

    // /**
    //  * Renderizar tabla de armas DIGECAM
    //  */
    // renderTablaDigecamArmas(armas) {
    //     const tbody = document.getElementById('tbody-digecam-armas');
    //     if (!tbody) return;

    //     if (armas.length === 0) {
    //         tbody.innerHTML = `
    //         <tr>
    //             <td colspan="12" class="px-6 py-4 text-center text-gray-500">
    //                 No se encontraron ventas de armas en este período
    //             </td>
    //         </tr>
    //     `;
    //         return;
    //     }

    //     tbody.innerHTML = armas.map((arma, index) => `
    //     <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
    //         <td class="px-2 py-2 text-center text-xs">${index + 1}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.pro_tenencia_anterior || ''}</td>
    //         <td class="px-2 py-2 text-center text-xs bg-yellow-100">${arma.pro_tenencia_nueva || ''}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.tipo}</td>
    //         <td class="px-2 py-2 text-center text-xs bg-yellow-100 font-semibold">${arma.serie}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.marca || 'N/A'}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.modelo || 'N/A'}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.calibre || 'N/A'}</td>
    //         <td class="px-2 py-2 text-xs">${arma.comprador.toUpperCase()}</td>
    //         <td class="px-2 py-2 text-center text-xs">${arma.autorizacion}</td>
    //         <td class="px-2 py-2 text-center text-xs">${this.formatearFechaDisplay(arma.fecha)}</td>
    //         <td class="px-2 py-2 text-center text-xs bg-yellow-100">${arma.factura || ''}</td>
    //     </tr>
    // `).join('');
    // }

    /**
    * Renderizar tabla de armas DIGECAM
    */
    renderTablaDigecamArmas(armas) {
        const tbody = document.getElementById('tbody-digecam-armas');
        if (!tbody) return;

        if (armas.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="12" class="px-6 py-4 text-center text-gray-500">
                    No se encontraron ventas de armas en este período
                </td>
            </tr>
        `;
            return;
        }

        tbody.innerHTML = armas.map((arma, index) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-2 py-2 text-center text-xs">${index + 1}</td>
            <td class="px-2 py-2 text-center text-xs">${arma.tipo}</td>
            <td class="px-2 py-2 text-center text-xs bg-yellow-100 font-semibold">${arma.serie}</td>
            <td class="px-2 py-2 text-center text-xs">${arma.marca || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs">${arma.modelo || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs">${arma.calibre || 'N/A'}</td>
            <td class="px-2 py-2 text-xs">${arma.comprador.toUpperCase()}</td>
            <td class="px-2 py-2 text-center text-xs">${arma.autorizacion}</td>
            <td class="px-2 py-2 text-center text-xs">${this.formatearFechaDisplay(arma.fecha)}</td>
            <td class="px-2 py-2 text-center text-xs bg-yellow-100">${arma.factura || ''}</td>
        </tr>
    `).join('');
    }

    /**
     * Renderizar tabla de municiones DIGECAM
     */
    renderTablaDigecamMuniciones(municiones) {
        const tbody = document.getElementById('tbody-digecam-municiones');
        if (!tbody) return;

        if (municiones.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="11" class="px-6 py-4 text-center text-gray-500">
                    No se encontraron ventas de municiones en este período
                </td>
            </tr>
        `;
            return;
        }

        let totalMuniciones = 0;

        tbody.innerHTML = municiones.map((municion, index) => {
            totalMuniciones += parseInt(municion.cantidad) || 0;
            return `
        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
            <td class="px-2 py-2 text-center text-xs">${index + 1}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.autorizacion}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.documento}</td>
            <td class="px-2 py-2 text-xs">${municion.nombre.toUpperCase()}</td>
            <td class="px-2 py-2 text-center text-xs bg-yellow-100">${municion.factura || ''}</td>
            <td class="px-2 py-2 text-center text-xs">${this.formatearFechaDisplay(municion.fecha)}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.serie_arma || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.clase_arma || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.calibre_arma || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs">${municion.calibre_vendido || 'N/A'}</td>
            <td class="px-2 py-2 text-center text-xs bg-yellow-100 font-bold">${municion.cantidad}</td>
        </tr>
    `}).join('');

        const totalEl = document.getElementById('total-municiones-vendidas');
        if (totalEl) {
            totalEl.textContent = this.formatNumber(totalMuniciones);
        }
    }

    /**
     * Actualizar información del reporte de armas
     */
    updateDigecamArmasInfo(data) {
        const mesEl = document.getElementById('digecam-armas-mes');
        const anioEl = document.getElementById('digecam-armas-anio');
        const totalEl = document.getElementById('digecam-armas-total');

        if (mesEl) mesEl.textContent = data.mes_nombre;
        if (anioEl) anioEl.textContent = data.anio;
        if (totalEl) totalEl.textContent = data.data.length;
    }

    /**
     * Actualizar información del reporte de municiones
     */
    updateDigecamMunicionesInfo(data) {
        const fechaInicioEl = document.getElementById('digecam-municiones-fecha-inicio');
        const fechaFinEl = document.getElementById('digecam-municiones-fecha-fin');
        const totalEl = document.getElementById('digecam-municiones-total');

        if (fechaInicioEl) fechaInicioEl.textContent = this.formatearFechaDisplay(data.fecha_inicio);
        if (fechaFinEl) fechaFinEl.textContent = this.formatearFechaDisplay(data.fecha_fin);
        if (totalEl) totalEl.textContent = data.data.length;
    }

    /**
     * Exportar reporte DIGECAM
     */
    async exportarReporteDigecam(tipo) {
        try {
            let params = new URLSearchParams({ tipo });

            if (tipo === 'armas') {
                const mes = document.getElementById('filtro-mes-digecam-armas')?.value || new Date().getMonth() + 1;
                const anio = document.getElementById('filtro-anio-digecam-armas')?.value || new Date().getFullYear();
                params.append('mes', mes);
                params.append('anio', anio);
            } else {
                params.append('fecha_inicio', this.filtros.fecha_inicio);
                params.append('fecha_fin', this.filtros.fecha_fin);
            }

            const url = `/reportes/digecam/exportar-pdf?${params}`;
            window.open(url, '_blank');

            this.showAlert('success', 'Exportación exitosa', `Reporte DIGECAM de ${tipo} generado correctamente`);
        } catch (error) {
            console.error('Error exportando reporte DIGECAM:', error);
            this.showAlert('error', 'Error', 'No se pudo exportar el reporte');
        }
    }

    /**
     * Aplicar filtros de armas DIGECAM
     */
    aplicarFiltrosDigecamArmas() {
        const mes = document.getElementById('filtro-mes-digecam-armas')?.value;
        const anio = document.getElementById('filtro-anio-digecam-armas')?.value;

        if (!mes || !anio) {
            this.showAlert('warning', 'Filtros incompletos', 'Debe seleccionar mes y año');
            return;
        }

        this.loadReporteDigecamArmas({ mes, anio });
    }

    /**
     * Aplicar filtros de municiones DIGECAM
     */
    aplicarFiltrosDigecamMuniciones() {
        const fechaInicio = document.getElementById('filtro-fecha-inicio-municiones')?.value;
        const fechaFin = document.getElementById('filtro-fecha-fin-municiones')?.value;

        if (!fechaInicio || !fechaFin) {
            this.showAlert('warning', 'Filtros incompletos', 'Debe seleccionar ambas fechas');
            return;
        }

        this.loadReporteDigecamMuniciones({ fecha_inicio: fechaInicio, fecha_fin: fechaFin });
    }





    /**
     * Renderizar tabla de ventas
     */
    /**
     * Renderizar tabla de ventas - CORREGIDO
     */


    /**
     * Aplicar filtros de ventas - CORREGIDO
     */
    /**
     * Aplicar filtros de ventas
     */
    aplicarFiltrosVentas() {
        const filtros = {};

        const vendedorEl = document.getElementById('filtro-vendedor-ventas');
        const clienteEl = document.getElementById('filtro-cliente-ventas');
        const estadoEl = document.getElementById('filtro-estado-ventas');

        if (vendedorEl?.value) {
            filtros.vendedor_id = vendedorEl.value;
            // vendedor:', vendedorEl.value);
        }

        if (clienteEl?.value) {
            filtros.cliente_id = clienteEl.value;
            // cliente:', clienteEl.value);
        }

        if (estadoEl?.value) {
            filtros.estado = estadoEl.value;
            // estado:', estadoEl.value);
        }

        // ('📋 Aplicando filtros:', filtros);
        this.cargarVentasPendientes(filtros);
    }

    /**
     * Inicializar Select2 para clientes
     */
    /**
     * Inicializar Select2 para clientes
     */
    /**
     * Inicializar select de clientes (sin Select2, estilo normal)
     */
    async initClienteSelect() {
        try {
            ('🔄 Cargando clientes...');

            const response = await fetch('/reportes/buscar-clientes?q=');
            const data = await response.json();

            if (data.success && data.results) {
                this.populateSelect('filtro-cliente-ventas', data.results, 'id', 'text');
                ('✅ Clientes cargados:', data.results.length);
            } else {
                console.warn('⚠️ No se encontraron clientes');
            }
        } catch (error) {
            console.error('❌ Error cargando clientes:', error);
        }
    }

    /**
     * Cargar filtros para los formularios
     */
    async loadFiltros() {
        try {
            const response = await fetch('/reportes/filtros');
            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.populateFiltros(result.data);
                    // ✅ Cargar clientes después de los otros filtros
                    await this.initClienteSelect();
                }
            }
        } catch (error) {
            console.error('Error cargando filtros:', error);
        }
    }

    /**
     * Renderizar tabla de productos
     */
    renderTablaProductos(productos) {
        const tbody = document.getElementById('tbody-productos');
        if (!tbody) return;

        tbody.innerHTML = productos.map((producto, index) => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full ${this.getRankingColor(index)} flex items-center justify-center text-white text-sm font-bold">
                                ${index + 1}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        ${producto.producto_nombre}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        SKU: ${producto.pro_codigo_sku || 'N/A'}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${producto.categoria_nombre || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${producto.marca_nombre || '-'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                    ${this.formatNumber(producto.total_vendido)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatCurrency(producto.precio_promedio)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                    ${this.formatCurrency(producto.total_ingresos)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatNumber(producto.total_transacciones)}
                </td>
            </tr>
        `).join('');
    }

    /**
     * Renderizar tabla de comisiones
     */
    renderTablaComisiones(comisiones) {
        const tbody = document.getElementById('tbody-comisiones');
        if (!tbody) return;

        tbody.innerHTML = comisiones.map(comision => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatearFecha(comision.porc_vend_fecha_asignacion)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${comision.vendedor?.user_primer_nombre || ''} ${comision.vendedor?.user_primer_apellido || ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    #${comision.venta?.ven_id || 'N/A'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatCurrency(comision.porc_vend_monto_base)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${comision.porc_vend_porcentaje}%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                    ${this.formatCurrency(comision.porc_vend_cantidad_ganancia)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.renderEstadoComision(comision.porc_vend_estado)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <button onclick="reportesManager.pagarComision(${comision.porc_vend_id})" 
                            class="text-green-600 hover:text-green-900">
                        <i class="fas fa-dollar-sign"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    /**
     * Renderizar tabla de pagos
     */
    renderTablaPagos(pagos) {
        const tbody = document.getElementById('tbody-pagos');
        if (!tbody) return;

        tbody.innerHTML = pagos.map(pago => `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatearFecha(pago.pago_fecha_inicio)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${pago.venta?.cliente?.cliente_nombre1 || ''} ${pago.venta?.cliente?.cliente_apellido1 || ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${pago.venta?.vendedor?.user_primer_nombre || ''} ${pago.venta?.vendedor?.user_primer_apellido || ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${this.formatCurrency(pago.pago_monto_total)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                    ${this.formatCurrency(pago.pago_monto_abonado)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                    ${this.formatCurrency(pago.pago_monto_total - pago.pago_monto_abonado)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                    ${pago.pago_tipo_pago}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${this.renderEstadoPago(pago.pago_estado)}
                </td>
            </tr>
        `).join('');
    }

    /**
     * Aplicar filtro de fecha
     */
    aplicarFiltroFecha() {
        if (!this.filtros.fecha_inicio || !this.filtros.fecha_fin) {
            this.showAlert('warning', 'Fechas requeridas', 'Debe seleccionar fecha de inicio y fin');
            return;
        }

        if (new Date(this.filtros.fecha_inicio) > new Date(this.filtros.fecha_fin)) {
            this.showAlert('warning', 'Fechas inválidas', 'La fecha de inicio debe ser menor a la fecha de fin');
            return;
        }

        // Recargar datos con las nuevas fechas
        if (this.currentTab === 'dashboard') {
            this.loadDashboard();
        } else {
            this.loadTabData(this.currentTab);
        }
    }

    /**
     * Aplicar filtros específicos de ventas
     */
    aplicarFiltrosVentas() {
        const filtros = {};

        const vendedorEl = document.getElementById('filtro-vendedor-ventas');
        const clienteEl = document.getElementById('filtro-cliente-ventas');
        const estadoEl = document.getElementById('filtro-estado-ventas');

        if (vendedorEl?.value) filtros.vendedor_id = vendedorEl.value;
        if (clienteEl?.value) filtros.cliente_id = clienteEl.value;
        if (estadoEl?.value) filtros.estado = estadoEl.value;

        this.loadReporteVentas(filtros);
    }

    /**
     * Aplicar filtros específicos de productos
     */
    aplicarFiltrosProductos() {
        const filtros = {};

        const categoriaEl = document.getElementById('filtro-categoria-productos');
        const marcaEl = document.getElementById('filtro-marca-productos');
        const limiteEl = document.getElementById('filtro-limite-productos');

        if (categoriaEl?.value) filtros.categoria_id = categoriaEl.value;
        if (marcaEl?.value) filtros.marca_id = marcaEl.value;
        if (limiteEl?.value) filtros.limit = limiteEl.value;

        this.loadReporteProductos(filtros);
    }

    /**
     * Aplicar filtros específicos de comisiones
     */
    aplicarFiltrosComisiones() {
        const filtros = {};

        const vendedorEl = document.getElementById('filtro-vendedor-comisiones');
        const estadoEl = document.getElementById('filtro-estado-comisiones');

        if (vendedorEl?.value) filtros.vendedor_id = vendedorEl.value;
        if (estadoEl?.value) filtros.estado = estadoEl.value;

        this.loadReporteComisiones(filtros);
    }

    /**
     * Aplicar filtros específicos de pagos
     */
    aplicarFiltrosPagos() {
        const filtros = {};

        const estadoEl = document.getElementById('filtro-estado-pagos');
        const tipoEl = document.getElementById('filtro-tipo-pagos');

        if (estadoEl?.value) filtros.estado = estadoEl.value;
        if (tipoEl?.value) filtros.tipo_pago = tipoEl.value;

        this.loadReportePagos(filtros);
    }

    /**
     * Exportar reporte
     */
    async exportarReporte(tipo, formato) {
        try {
            const params = new URLSearchParams({
                ...this.filtros,
                tipo_reporte: tipo,
                formato: formato
            });

            const url = `/reportes/exportar-${formato}?${params}`;
            window.open(url, '_blank');

            this.showAlert('success', 'Exportación', `Reporte de ${tipo} exportado a ${formato.toUpperCase()}`);
        } catch (error) {
            console.error('Error exportando reporte:', error);
            this.showAlert('error', 'Error', 'Error al exportar el reporte');
        }
    }

    /**
     * Cambiar tipo de gráfico
     */
    cambiarTipoGrafico(grafico, tipo) {
        // Actualizar botones activos
        const container = event.currentTarget.closest('.bg-white, .bg-gray-800');
        container.querySelectorAll('.grafico-tipo-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-blue-100', 'text-blue-600');
            btn.classList.add('bg-gray-100', 'text-gray-600');
        });

        event.currentTarget.classList.add('active', 'bg-blue-100', 'text-blue-600');
        event.currentTarget.classList.remove('bg-gray-100', 'text-gray-600');

        // Cambiar tipo de gráfico y recrear
        if (grafico === 'ventas' && this.graficos.ventasDias) {
            this.graficos.ventasDias.config.type = tipo;
            this.graficos.ventasDias.update();
        } else if (grafico === 'vendedor' && this.graficos.vendedores) {
            this.graficos.vendedores.config.type = tipo;
            this.graficos.vendedores.update();
        }
    }

    /**
     * Ver detalle de productos
     */
    verDetalleProductos() {
        this.cambiarTab('productos');
    }

    /**
     * Ver detalle de venta
     */
    async verDetalleVenta(ventaId) {
        try {
            this.showLoading('detalle-venta');
            const response = await fetch(`/reportes/ventas/${ventaId}/detalle`);

            if (response.ok) {
                const result = await response.json();
                if (result.success) {
                    this.mostrarModalDetalleVenta(result.data);
                } else {
                    this.showAlert('error', 'Error', result.message || 'No se pudo cargar el detalle de la venta');
                }
            } else {
                throw new Error('Error en la petición');
            }
        } catch (error) {
            console.error('Error cargando detalle de venta:', error);
            this.showAlert('error', 'Error', 'Ocurrió un error al cargar el detalle de la venta');
        } finally {
            this.hideLoading('detalle-venta');
        }
    }

    mostrarModalDetalleVenta(venta) {
        // Implementar lógica para mostrar modal con detalles
        // Por ahora usaremos SweetAlert2 con HTML

        const productosHtml = venta.detalle_ventas.map(d => `
            <tr>
                <td class="text-left">
                    ${d.producto.producto_nombre}
                    ${d.series && d.series.length > 0 ?
                `<div class="text-xs text-gray-500">SN: ${d.series.join(', ')}</div>`
                : ''}
                </td>
                <td class="text-center">${d.det_cantidad}</td>
                <td class="text-right">Q ${this.formatNumber(d.det_precio)}</td>
                <td class="text-right">Q ${this.formatNumber(d.det_cantidad * d.det_precio)}</td>
            </tr>
        `).join('');

        const pagosHtml = venta.pagos.map(p => `
            <tr>
                <td class="text-left">${this.formatearFechaDisplay(p.pago_fecha_inicio)}</td>
                <td class="text-center">${p.pago_tipo_pago}</td>
                <td class="text-right">Q ${this.formatNumber(p.pago_monto_pagado)}</td>
                <td class="text-center"><span class="badge badge-${p.pago_estado === 'COMPLETADO' ? 'success' : 'warning'}">${p.pago_estado}</span></td>
            </tr>
        `).join('');

        Swal.fire({
            title: `<strong>Detalle de Venta #${venta.ven_id}</strong>`,
            html: `
                <div class="text-left text-sm">
                    <div class="mb-4 grid grid-cols-2 gap-2">
                        <div><strong>Cliente:</strong> ${venta.cliente.cliente_nombre1} ${venta.cliente.cliente_apellido1}</div>
                        <div><strong>Vendedor:</strong> ${venta.vendedor.user_primer_nombre} ${venta.vendedor.user_primer_apellido}</div>
                        <div><strong>Fecha:</strong> ${this.formatearFechaDisplay(venta.ven_fecha)}</div>
                        <div><strong>Estado:</strong> ${venta.ven_situacion}</div>
                    </div>
                    
                    <h4 class="font-bold border-b mb-2 mt-4">Productos</h4>
                    <table class="w-full mb-4">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-1">Producto</th>
                                <th class="text-center p-1">Cant.</th>
                                <th class="text-right p-1">Precio</th>
                                <th class="text-right p-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>${productosHtml}</tbody>
                        <tfoot>
                            <tr class="font-bold border-t">
                                <td colspan="3" class="text-right p-1">Total:</td>
                                <td class="text-right p-1">Q ${this.formatNumber(venta.ven_total_vendido)}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <h4 class="font-bold border-b mb-2 mt-4">Pagos</h4>
                    <table class="w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="text-left p-1">Fecha</th>
                                <th class="text-center p-1">Tipo</th>
                                <th class="text-right p-1">Monto</th>
                                <th class="text-center p-1">Estado</th>
                            </tr>
                        </thead>
                        <tbody>${pagosHtml}</tbody>
                    </table>
                </div>
            `,
            width: '800px',
            showCloseButton: true,
            showCancelButton: false,
            focusConfirm: false,
            confirmButtonText: '<i class="fa fa-print"></i> Imprimir',
            confirmButtonAriaLabel: 'Imprimir',
            showDenyButton: true,
            denyButtonText: 'Cerrar',
        }).then((result) => {
            if (result.isConfirmed) {
                this.imprimirVenta(venta.ven_id);
            }
        });
    }

    /**
     * Imprimir venta
     */
    imprimirVenta(ventaId) {
        window.open(`/reportes/ventas/${ventaId}/imprimir`, '_blank');
    }

    /**
     * Pagar comisión
     */
    pagarComision(comisionId) {
        // Implementar funcionalidad de pago de comisión
        ('Pagar comisión:', comisionId);
        this.showAlert('info', 'Información', 'Función de pago de comisión en desarrollo');
    }

    /**
     * Renderizar paginación para ventas
     */
    renderPaginacionVentas(paginationData) {
        const container = document.getElementById('paginacion-ventas');
        if (!container) return;

        const { current_page, last_page, per_page, total } = paginationData;

        if (last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHtml = `
            <div class="flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                    Mostrando ${((current_page - 1) * per_page) + 1} a ${Math.min(current_page * per_page, total)} de ${total} resultados
                </div>
                <div class="flex items-center space-x-2">
        `;

        // Botón anterior
        if (current_page > 1) {
            paginationHtml += `
                <button onclick="reportesManager.cambiarPagina(${current_page - 1})" 
                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Anterior
                </button>
            `;
        }

        // Números de página
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(last_page, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHtml += `
                <button onclick="reportesManager.cambiarPagina(${i})" 
                        class="px-3 py-2 text-sm font-medium ${isActive ? 'text-white bg-blue-600' : 'text-gray-500 bg-white hover:bg-gray-50'} border border-gray-300 rounded-md">
                    ${i}
                </button>
            `;
        }

        // Botón siguiente
        if (current_page < last_page) {
            paginationHtml += `
                <button onclick="reportesManager.cambiarPagina(${current_page + 1})" 
                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Siguiente
                </button>
            `;
        }

        paginationHtml += '</div></div>';
        container.innerHTML = paginationHtml;
    }

    /**
     * Cambiar página
     */
    cambiarPagina(pagina) {
        const filtros = { page: pagina };

        switch (this.currentTab) {
            case 'ventas':
                this.loadReporteVentas(filtros);
                break;
            case 'comisiones':
                this.loadReporteComisiones(filtros);
                break;
            case 'pagos':
                this.loadReportePagos(filtros);
                break;
        }
    }

    /**
     * Actualizar resumen de comisiones
     */
    updateResumenComisiones(resumen) {
        const elements = {
            'resumen-total-comisiones': this.formatCurrency(resumen.total_comisiones),
            'resumen-pendientes-comisiones': this.formatCurrency(resumen.pendientes),
            'resumen-pagadas-comisiones': this.formatCurrency(resumen.pagadas),
            'resumen-canceladas-comisiones': this.formatCurrency(resumen.canceladas)
        };

        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        });
    }

    // ================================
    // MÉTODOS AUXILIARES
    // ================================

    /**
     * Poblar filtros en los formularios
     */
    populateFiltros(data) {
        // Vendedores
        this.populateSelect('filtro-vendedor-ventas', data.vendedores, 'user_id', (item) =>
            `${item.user_primer_nombre} ${item.user_primer_apellido}`);
        this.populateSelect('filtro-vendedor-comisiones', data.vendedores, 'user_id', (item) =>
            `${item.user_primer_nombre} ${item.user_primer_apellido}`);

        // Categorías y marcas
        this.populateSelect('filtro-categoria-productos', data.categorias, 'categoria_id', 'categoria_nombre');
        this.populateSelect('filtro-marca-productos', data.marcas, 'marca_id', 'marca_descripcion');

        // ✅ FIX: Poblar filtro de métodos de pago
        if (data.metodos_pago) {
            this.populateSelect('filtro-tipo-pagos', data.metodos_pago, 'metpago_descripcion', 'metpago_descripcion');
        }
    }

    populateSelect(selectId, options, valueField, textField) {
        const select = document.getElementById(selectId);
        if (!select) return;

        const currentValue = select.value;
        const placeholder = select.children[0];

        select.innerHTML = '';
        if (placeholder) {
            select.appendChild(placeholder);
        }

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option[valueField];

            if (typeof textField === 'function') {
                optionElement.textContent = textField(option);
            } else {
                optionElement.textContent = option[textField];
            }

            select.appendChild(optionElement);
        });

        select.value = currentValue;
    }

    /**
     * Renderizar estado de pago
     */
    renderEstadoPago(estado) {
        const estados = {
            'PENDIENTE': { class: 'bg-yellow-100 text-yellow-800', text: 'Pendiente' },
            'PARCIAL': { class: 'bg-blue-100 text-blue-800', text: 'Parcial' },
            'COMPLETADO': { class: 'bg-green-100 text-green-800', text: 'Pagado' },
            'VENCIDO': { class: 'bg-red-100 text-red-800', text: 'Vencido' },
            'SIN_CONTROL': { class: 'bg-gray-100 text-gray-800', text: 'Sin control' }
        };

        const config = estados[estado] || estados['SIN_CONTROL'];
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.class}">${config.text}</span>`;
    }

    /**
     * Renderizar estado de comisión
     */
    renderEstadoComision(estado) {
        const estados = {
            'PENDIENTE': { class: 'bg-yellow-100 text-yellow-800', text: 'Pendiente' },
            'PAGADO': { class: 'bg-green-100 text-green-800', text: 'Pagada' },
            'CANCELADO': { class: 'bg-red-100 text-red-800', text: 'Cancelada' }
        };

        const config = estados[estado] || estados['PENDIENTE'];
        return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${config.class}">${config.text}</span>`;
    }

    /**
     * Obtener color según ranking
     */
    getRankingColor(index) {
        const colors = ['bg-yellow-500', 'bg-gray-400', 'bg-yellow-600', 'bg-blue-500', 'bg-green-500'];
        return colors[Math.min(index, colors.length - 1)];
    }

    /**
     * Formatear fecha para input date
     */
    formatearFecha(fecha) {
        if (!fecha) return '';
        const date = new Date(fecha);
        return date.toISOString().split('T')[0];
    }

    /**
     * Formatear fecha para mostrar
     */
    formatearFechaDisplay(fecha) {
        if (!fecha) return '';
        return new Date(fecha).toLocaleDateString('es-GT');
    }

    formatearFechaCorta(fecha) {
        if (!fecha) return '';
        return new Date(fecha).toLocaleDateString('es-GT', { month: 'short', day: 'numeric' });
    }

    /**
     * Truncar texto
     */
    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    /**
     * Formatear números
     */
    formatNumber(num) {
        return new Intl.NumberFormat('es-GT').format(num || 0);
    }

    /**
     * Formatear moneda
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(amount || 0);
    }

    /**
     * Mostrar/ocultar loading
     */
    showLoading(section) {
        (`Cargando ${section}...`);
        // Implementar loading spinner si es necesario
    }

    hideLoading(section) {
        (`${section} cargado`);
        // Ocultar loading spinner si es necesario
    }

    /**
     * Mostrar alertas
     */
    showAlert(type, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: type,
                confirmButtonColor: type === 'success' ? '#10b981' : '#dc2626'
            });
        } else {
            // Fallback a alert nativo si SweetAlert2 no está disponible
            alert(`${title}: ${text}`);
        }
    }
    /**
     * Renderizar paginación genérica
     */
    renderPaginacion(paginationData, containerId, callback) {
        const container = document.getElementById(containerId);
        if (!container) return;

        const { current_page, last_page, per_page, total } = paginationData;

        if (last_page <= 1) {
            container.innerHTML = '';
            return;
        }

        let paginationHtml = `
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center text-sm text-gray-700 dark:text-gray-300">
                    Mostrando ${((current_page - 1) * per_page) + 1} a ${Math.min(current_page * per_page, total)} de ${total} resultados
                </div>
                <div class="flex items-center space-x-2">
        `;

        // Botón anterior
        if (current_page > 1) {
            paginationHtml += `
                <button data-page="${current_page - 1}" 
                        class="pagination-btn px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Anterior
                </button>
            `;
        }

        // Números de página
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(last_page, current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === current_page;
            paginationHtml += `
                <button data-page="${i}" 
                        class="pagination-btn px-3 py-2 text-sm font-medium ${isActive ? 'text-white bg-blue-600' : 'text-gray-500 bg-white hover:bg-gray-50'} border border-gray-300 rounded-md">
                    ${i}
                </button>
            `;
        }

        // Botón siguiente
        if (current_page < last_page) {
            paginationHtml += `
                <button data-page="${current_page + 1}" 
                        class="pagination-btn px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Siguiente
                </button>
            `;
        }

        paginationHtml += '</div></div>';
        container.innerHTML = paginationHtml;

        // Agregar event listeners
        container.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = parseInt(btn.dataset.page);
                if (callback) callback(page);
            });
        });
    }
}

// Inicializar globalmente INMEDIATAMENTE
window.reportesManager = null;

// Función de inicialización
function initReportesManager() {
    ('🔄 Intentando inicializar ReportesManager...');

    // Verificar dependencias
    const dependencies = [
        { name: 'Chart.js', check: () => typeof Chart !== 'undefined' },
        { name: 'SweetAlert2', check: () => typeof Swal !== 'undefined', optional: true }
    ];

    const missing = dependencies.filter(dep => !dep.optional && !dep.check());

    if (missing.length > 0) {
        console.warn('⚠️ Dependencias faltantes:', missing.map(d => d.name).join(', '));
        console.warn('Continuando sin algunas funcionalidades...');
    }

    // Verificar si ya existe una instancia
    if (window.reportesManager) {
        ('✅ ReportesManager ya está inicializado');
        return window.reportesManager;
    }

    // Inicializar ReportesManager
    try {
        window.reportesManager = new ReportesManager();
        ('✅ Sistema de reportes inicializado correctamente');

        // Disparar evento personalizado para notificar que está listo
        if (typeof CustomEvent !== 'undefined') {
            window.dispatchEvent(new CustomEvent('reportesManagerReady', {
                detail: { reportesManager: window.reportesManager }
            }));
        }

        return window.reportesManager;
    } catch (error) {
        console.error('❌ Error inicializando sistema de reportes:', error);

        // Crear un objeto fallback para evitar errores
        window.reportesManager = {
            cambiarTab: function (tab) { console.warn('ReportesManager no inicializado - cambiarTab:', tab); },
            aplicarFiltroFecha: function () { console.warn('ReportesManager no inicializado - aplicarFiltroFecha'); },
            exportarReporte: function (tipo, formato) { console.warn('ReportesManager no inicializado - exportarReporte:', tipo, formato); },
            cambiarTipoGrafico: function (grafico, tipo) { console.warn('ReportesManager no inicializado - cambiarTipoGrafico:', grafico, tipo); },
            verDetalleProductos: function () { console.warn('ReportesManager no inicializado - verDetalleProductos'); },
            aplicarFiltrosVentas: function () { console.warn('ReportesManager no inicializado - aplicarFiltrosVentas'); },
            aplicarFiltrosProductos: function () { console.warn('ReportesManager no inicializado - aplicarFiltrosProductos'); },
            aplicarFiltrosComisiones: function () { console.warn('ReportesManager no inicializado - aplicarFiltrosComisiones'); },
            aplicarFiltrosPagos: function () { console.warn('ReportesManager no inicializado - aplicarFiltrosPagos'); },
            verDetalleVenta: function (id) { console.warn('ReportesManager no inicializado - verDetalleVenta:', id); },
            imprimirVenta: function (id) { console.warn('ReportesManager no inicializado - imprimirVenta:', id); },
            pagarComision: function (id) { console.warn('ReportesManager no inicializado - pagarComision:', id); },
            cambiarPagina: function (pagina) { console.warn('ReportesManager no inicializado - cambiarPagina:', pagina); }
        };

        return window.reportesManager;
    }
}

// Múltiples estrategias de inicialización
('📄 Script de reportes cargado, estado del DOM:', document.readyState);

// 1. Si el DOM ya está listo, inicializar inmediatamente
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    ('🚀 DOM listo, inicializando inmediatamente...');
    initReportesManager();
} else {
    // 2. Si el DOM aún se está cargando, esperar al evento
    console.log('⏳ Esperando a que el DOM esté listo...');
    document.addEventListener('DOMContentLoaded', function () {
        console.log('📋 DOM cargado, inicializando...');
        initReportesManager();
    });
}

// 3. Backup: intentar nuevamente después de un breve delay
setTimeout(function () {
    if (!window.reportesManager || typeof window.reportesManager.cambiarTab !== 'function') {
        console.log('🔄 Reintentando inicialización después del timeout...');
        initReportesManager();
    }
}, 1000);

// 4. Interceptor global para funciones llamadas antes de la inicialización
window.reportesManagerProxy = new Proxy({}, {
    get: function (target, prop) {
        if (window.reportesManager && typeof window.reportesManager[prop] === 'function') {
            return window.reportesManager[prop].bind(window.reportesManager);
        } else {
            return function (...args) {
                console.warn(`⚠️ Función ${prop} llamada antes de la inicialización completa. Args:`, args);

                // Intentar inicializar si no está listo
                if (!window.reportesManager) {
                    initReportesManager();
                }

                // Si ahora está disponible, ejecutar
                if (window.reportesManager && typeof window.reportesManager[prop] === 'function') {
                    return window.reportesManager[prop](...args);
                } else {
                    // Mostrar mensaje amigable al usuario
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Sistema Inicializando',
                            text: 'Por favor espere unos segundos mientras el sistema termina de cargar.',
                            icon: 'info',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('El sistema se está inicializando. Por favor, intente nuevamente en unos segundos.');
                    }
                }
            };
        }
    }
});

// 5. Hacer que reportesManager sea accesible incluso antes de la inicialización
if (!window.reportesManager) {
    window.reportesManager = window.reportesManagerProxy;
}

