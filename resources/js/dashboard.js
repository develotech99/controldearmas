/**
 * Dashboard Manager
 * Gestión del dashboard principal del sistema
 */

class DashboardManager {
    constructor() {
        this.refreshInterval = null;
        this.init();
    }

    init() {
        console.log('Iniciando Dashboard Manager...');
        this.cargarEstadisticas();
        this.configurarEventos();
        this.iniciarActualizacionAutomatica();
    }

    /**
     * Cargar estadísticas del dashboard
     */
    async cargarEstadisticas() {
        try {
            const response = await fetch('/api/dashboard/estadisticas', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                }
            });

            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                this.actualizarEstadisticas(result.data.estadisticas);
                this.renderVentasRecientes(result.data.ventas_recientes);
                this.renderAlertasStock(result.data.productos_stock_bajo);
            } else {
                this.mostrarError(result.message || 'Error al cargar las estadísticas');
            }

        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error al conectar con el servidor');
        }
    }

    /**
     * Actualizar tarjetas de estadísticas
     */
    actualizarEstadisticas(stats) {
        // Total Armas
        const totalArmasEl = document.querySelector('[data-stat="total-armas"]');
        if (totalArmasEl) {
            this.animarNumero(totalArmasEl, stats.total_armas);
        }

        // Ventas del Mes
        const ventasMesEl = document.querySelector('[data-stat="ventas-mes"]');
        if (ventasMesEl) {
            this.animarNumero(ventasMesEl, stats.ventas_mes);
        }

        // Total Clientes
        const clientesEl = document.querySelector('[data-stat="total-clientes"]');
        if (clientesEl) {
            this.animarNumero(clientesEl, stats.total_clientes);
        }

        // Licencias Activas
        const licenciasEl = document.querySelector('[data-stat="licencias-activas"]');
        if (licenciasEl) {
            this.animarNumero(licenciasEl, stats.licencias_activas);
        }

        console.log('Estadísticas actualizadas:', stats);
    }

    /**
     * Renderizar ventas recientes
     */
    renderVentasRecientes(ventas) {
        const container = document.getElementById('ventas-recientes-container');

        if (!container) return;

        if (!ventas || ventas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p class="text-slate-500">No hay ventas registradas</p>
                    <p class="text-sm text-slate-400 mt-1">Las ventas recientes aparecerán aquí</p>
                </div>
            `;
            return;
        }

        let html = '<div class="space-y-3">';

        ventas.forEach(venta => {
            const estadoClass = venta.estado === 'COMPLETADA' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';

            html += `
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <span class="font-medium text-slate-900">${venta.cliente}</span>
                            <span class="px-2 py-1 text-xs rounded-full ${estadoClass}">
                                ${venta.estado}
                            </span>
                        </div>
                        <div class="text-sm text-slate-600 mt-1">
                            <span class="mr-3">
                                👤 ${venta.vendedor}
                            </span>
                            <span class="mr-3">
                                📦 ${venta.items} items
                            </span>
                            <span>
                                📅 ${this.formatearFecha(venta.fecha)}
                            </span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-green-600">
                            Q${venta.total}
                        </div>
                        <a href="/reportes" class="text-xs text-blue-600 hover:text-blue-800">
                            Ver detalle →
                        </a>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Renderizar alertas de stock
     */
    renderAlertasStock(productos) {
        const container = document.getElementById('alertas-stock-container');

        if (!container) return;

        if (!productos || productos.length === 0) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-12 h-12 text-green-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-slate-500">No hay alertas de stock</p>
                    <p class="text-sm text-slate-400 mt-1">El inventario está en niveles óptimos</p>
                </div>
            `;
            return;
        }

        let html = '<div class="space-y-3">';

        productos.forEach(producto => {
            const estadoClass = producto.estado === 'AGOTADO'
                ? 'bg-red-100 text-red-800 border-red-200'
                : 'bg-yellow-100 text-yellow-800 border-yellow-200';

            const iconoEstado = producto.estado === 'AGOTADO'
                ? '❌'
                : '⚠️';

            html += `
                <div class="flex items-center justify-between p-3 border ${estadoClass} rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">
                            ${iconoEstado}
                        </div>
                        <div>
                            <div class="font-medium text-slate-900">${producto.nombre}</div>
                            <div class="text-sm text-slate-600">
                                Código: ${producto.codigo || 'N/A'}
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-medium">
                            Stock: <span class="text-lg font-bold">${producto.stock_actual}</span>
                        </div>
                        <div class="text-xs text-slate-600">
                            Mínimo: ${producto.stock_minimo}
                            ${producto.estado === 'BAJO' ? `(Faltan ${producto.diferencia})` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;
    }

    /**
     * Animar números con efecto de conteo
     */
    animarNumero(elemento, valorFinal, duracion = 1000) {
        const valorInicial = parseInt(elemento.textContent) || 0;
        const diferencia = valorFinal - valorInicial;
        const incremento = diferencia / (duracion / 16);
        let valorActual = valorInicial;

        const animar = () => {
            valorActual += incremento;

            if ((incremento > 0 && valorActual >= valorFinal) ||
                (incremento < 0 && valorActual <= valorFinal)) {
                elemento.textContent = valorFinal.toLocaleString();
                return;
            }

            elemento.textContent = Math.floor(valorActual).toLocaleString();
            requestAnimationFrame(animar);
        };

        animar();
    }

    /**
     * Formatear fecha
     */
    formatearFecha(fecha) {
        const date = new Date(fecha);
        const opciones = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('es-GT', opciones);
    }

    /**
     * Configurar eventos de botones
     */
    configurarEventos() {
        // Botón de actualizar
        const btnActualizar = document.getElementById('btn-actualizar-dashboard');
        if (btnActualizar) {
            btnActualizar.addEventListener('click', () => {
                this.cargarEstadisticas();
            });
        }

        // Acciones rápidas
        this.configurarAccionesRapidas();
    }

    /**
     * Configurar acciones rápidas
     */
    configurarAccionesRapidas() {
        const botones = document.querySelectorAll('button[data-href]');
        botones.forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.href;
                if (url) {
                    window.location.href = url;
                }
            });
        });
    }

    /**
     * Iniciar actualización automática cada 5 minutos
     */
    iniciarActualizacionAutomatica() {
        this.refreshInterval = setInterval(() => {
            console.log('Actualizando dashboard automáticamente...');
            this.cargarEstadisticas();
        }, 300000); // 5 minutos
    }

    /**
     * Detener actualización automática
     */
    detenerActualizacionAutomatica() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    /**
     * Mostrar error
     */
    mostrarError(mensaje) {
        console.error(mensaje);

        // Mostrar en contenedores
        const ventasContainer = document.getElementById('ventas-recientes-container');
        const stockContainer = document.getElementById('alertas-stock-container');

        const errorHTML = `
            <div class="text-center py-12">
                <div class="text-red-500 text-4xl mb-4">⚠️</div>
                <p class="text-slate-600">${mensaje}</p>
                <button onclick="window.dashboardManager.cargarEstadisticas()" 
                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Reintentar
                </button>
            </div>
        `;

        if (ventasContainer) ventasContainer.innerHTML = errorHTML;
        if (stockContainer) stockContainer.innerHTML = errorHTML;
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function () {
    window.dashboardManager = new DashboardManager();
});

// Limpiar al salir
window.addEventListener('beforeunload', function () {
    if (window.dashboardManager) {
        window.dashboardManager.detenerActualizacionAutomatica();
    }
});