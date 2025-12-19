
/**
 * Gestor del Sistema de Inventario para Armería
 * JavaScript puro - Laravel 12
 */
class InventarioManager {
    constructor() {
        this.productos = [];
        this.categorias = [];
        this.marcas = [];
        this.modelos = [];
        this.paises = [];
        this.calibres = [];
        this.productoSeleccionadoEgreso = null;
        this.seriesSeleccionadasEgreso = [];
        this.origenEgresoSeleccionado = null;
        this.licenciaSeleccionada = null;
        this.licenciaSeleccionadaRegistro = null;
        this.lotePreview = '';
        this.excelData = [];
        this.excelFilteredData = [];
        this.excelCurrentPage = 1;
        this.excelRecordsPerPage = 50;
        this.excelIsExpanded = false;
        this.currentProductoId = null;
        this.stats = {
            totalProductos: 0,
            stockTotal: 0,
            stockBajo: 0,
            stockAgotado: 0
        };
        this.filtros = {
            search: '',
            categoria: '',
            stock: ''
        };
        this.productoSeleccionado = null;

        this.init();
    }

    /**
     * Inicializar el gestor
     */
    init() {
        console.log('🚀 InventarioManager inicializado');
        this.setupEventListeners();
        this.setupExcelFilters();
        this.setupFotosHandling();
        this.setupPreciosHandling();
        this.loadInitialData();
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        try {
            // Filtros
            document.getElementById('search-productos').addEventListener('input', (e) => {
                this.filtros.search = e.target.value;
                this.aplicarFiltros();
            });

            document.getElementById('filter-categoria').addEventListener('change', (e) => {
                this.filtros.categoria = e.target.value;
                this.aplicarFiltros();
            });

            document.getElementById('filter-stock').addEventListener('change', (e) => {
                this.filtros.stock = e.target.value;
                this.aplicarFiltros();
            });

            // Formularios
            document.getElementById('registro-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleRegistroSubmit();
            });

            document.getElementById('ingreso-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleIngresoSubmit();
            });

            // Búsqueda de productos para ingreso
            document.getElementById('buscar_producto').addEventListener('input', (e) => {
                this.buscarProductos(e.target.value);
            });

            // Cambios en categoría para cargar subcategorías
            document.getElementById('producto_categoria').addEventListener('change', (e) => {
                this.loadSubcategorias(e.target.value);
            });

            // Cambios en marca para cargar modelos
            document.getElementById('producto_marca').addEventListener('change', (e) => {
                this.loadModelos(e.target.value);
            });

            // Cerrar modales con ESC
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeModal('registro');
                    this.closeModal('ingreso');
                }
            });


            // Búsqueda de licencias
            const buscarLicenciaInput = document.getElementById('buscar_licencia');
            if (buscarLicenciaInput) {
                buscarLicenciaInput.addEventListener('input', (e) => {
                    this.buscarLicencias(e.target.value);
                });
            }

            // Radio buttons para generar lote  
            document.querySelectorAll('input[name="generar_lote"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.toggleLoteInput(e.target.value);
                });
            });

            // Contador de series
            const numerosSeriesInput = document.getElementById('numeros_series');
            if (numerosSeriesInput) {
                numerosSeriesInput.addEventListener('input', (e) => {
                    this.contarSeries(e.target.value);
                });
            }

            // Checkbox para agregar precios
            const checkboxPrecios = document.getElementById('agregar_precios');
            if (checkboxPrecios) {
                checkboxPrecios.addEventListener('change', (e) => {
                    const seccionPrecios = document.getElementById('seccion_precios');
                    if (seccionPrecios) {
                        if (e.target.checked) {
                            seccionPrecios.classList.remove('hidden');
                        } else {
                            seccionPrecios.classList.add('hidden');
                        }
                    }
                });
            }

            // Checkbox para producto importado
            const checkboxImportado = document.getElementById('producto_es_importado');
            if (checkboxImportado) {
                checkboxImportado.addEventListener('change', (e) => {
                    const seccionLicencia = document.getElementById('seccion_licencia_registro');
                    if (seccionLicencia) {
                        if (e.target.checked) {
                            seccionLicencia.classList.remove('hidden');
                        } else {
                            seccionLicencia.classList.add('hidden');
                            this.limpiarLicenciaSeleccionadaRegistro();
                        }
                    }
                });
            }

            // Búsqueda de licencias en registro
            const buscarLicenciaRegistro = document.getElementById('buscar_licencia_registro');
            if (buscarLicenciaRegistro) {
                buscarLicenciaRegistro.addEventListener('input', (e) => {
                    this.buscarLicenciasRegistro(e.target.value);
                });
            }


            const checkboxUsarLotes = document.getElementById('usar_lotes');
            if (checkboxUsarLotes) {
                checkboxUsarLotes.addEventListener('change', (e) => {
                    const opcionesLote = document.getElementById('opciones_lote');
                    if (e.target.checked) {
                        opcionesLote.classList.remove('hidden');
                        this.configurarTipoLote('automatico'); // Por defecto automático
                    } else {
                        opcionesLote.classList.add('hidden');
                        this.limpiarConfiguracionLotes();
                    }
                });
            }

            // NUEVO: Radio buttons para tipo de lote
            document.querySelectorAll('input[name="tipo_lote"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.configurarTipoLote(e.target.value);
                });
            });

            document.getElementById('egreso-form').addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleEgresoSubmit();
            });

            document.getElementById('buscar_producto_egreso').addEventListener('input', (e) => {
                this.buscarProductosEgreso(e.target.value);
            });
        } catch (error) {
            console.error('Error in setupEventListeners:', error);
        }

        const buscarLoteInput = document.getElementById('buscar_lote');
        if (buscarLoteInput) {
            buscarLoteInput.addEventListener('input', (e) => {
                this.buscarLotesExistentes(e.target.value);
            });
        }

        //    // Event listener para búsqueda Excel
        //         const excelSearchInput = document.getElementById('excel-search');
        //         if (excelSearchInput) {
        //             excelSearchInput.addEventListener('input', () => this.buscarEnExcel());
        //         }
    }

    /**
 * Configurar event listeners para filtros Excel
 */
    setupExcelFilters() {
        // Event listener para búsqueda de texto
        const excelSearchInput = document.getElementById('excel-search');
        if (excelSearchInput) {
            excelSearchInput.addEventListener('input', () => this.aplicarFiltrosExcel());
        }

        // Event listener para filtro de estado
        const excelFilterEstado = document.getElementById('excel-filter-estado');
        if (excelFilterEstado) {
            excelFilterEstado.addEventListener('change', () => this.aplicarFiltrosExcel());
        }

        // Event listener para filtro de categoría
        const excelFilterCategoria = document.getElementById('excel-filter-categoria');
        if (excelFilterCategoria) {
            excelFilterCategoria.addEventListener('change', () => this.aplicarFiltrosExcel());
        }
    }

    /**
     * Cargar categorías en el select de filtros
     */
    async cargarCategoriasExcel() {
        try {
            const response = await fetch('/categorias/activas');
            if (response.ok) {
                const data = await response.json();
                const select = document.getElementById('excel-filter-categoria');

                if (select) {
                    let options = '<option value="">Todas las categorías</option>';

                    data.data.forEach(categoria => {
                        options += `<option value="${categoria.categoria_id}">${categoria.categoria_nombre}</option>`;
                    });

                    select.innerHTML = options;
                }
            }
        } catch (error) {
            console.error('Error cargando categorías para filtro:', error);
        }
    }

    /**
     * Cargar datos iniciales
     */
    async loadInitialData() {
        try {
            await Promise.all([
                this.loadProductos(),
                this.loadCategorias(),
                this.loadMarcas(),
                this.loadPaises(),
                this.loadCalibres(),
                this.loadStats(),
                this.loadAlertas()
            ]);
        } catch (error) {
            console.error('Error cargando datos iniciales:', error);
            this.showAlert('error', 'Error', 'No se pudieron cargar los datos iniciales');
        }
    }


    resetEgresoForm() {
        document.getElementById('egreso-form').reset();
        this.clearErrors('egreso');

        document.getElementById('egreso-step-1').classList.remove('hidden');
        document.getElementById('egreso-step-2').classList.add('hidden');
        document.getElementById('productos_encontrados_egreso').classList.add('hidden');

        // Limpiar campos dinámicos
        const cantidadInput = document.getElementById('egr_cantidad');
        if (cantidadInput) {
            cantidadInput.removeAttribute('max');
            cantidadInput.placeholder = 'Ej: 5';
        }

        // Limpiar labels dinámicos
        const cantidadLabel = document.querySelector('#cantidad_section_egreso label');
        if (cantidadLabel) {
            cantidadLabel.textContent = 'Cantidad *';
        }

        // Limpiar información de origen seleccionado
        const origenInfo = document.getElementById('origen_seleccionado_info');
        if (origenInfo) {
            origenInfo.innerHTML = '';
        }

        // Resetear radio buttons
        const radios = document.querySelectorAll('input[name="origen_egreso"]');
        radios.forEach(radio => {
            radio.checked = false;
        });

        // Resetear variables internas
        this.productoSeleccionadoEgreso = null;
        this.seriesSeleccionadasEgreso = [];
        this.origenEgresoSeleccionado = null;
    }

    openEgresoModal() {
        try {
            this.resetEgresoForm();
            this.showModal('egreso');
        } catch (error) {
            console.error('Error opening Egreso Modal:', error);
        }
    }

    egresoRapido(productoId) {
        // Pre-seleccionar el producto y abrir modal de egreso
        this.openEgresoModal();
        setTimeout(() => {
            this.seleccionarProductoEgreso(productoId);
        }, 100);
    }


    /**
     * NUEVO: Configurar tipo de lote seleccionado
     */
    configurarTipoLote(tipo) {
        const loteManualInput = document.getElementById('lote_manual_input');
        const loteAutomaticoPreview = document.getElementById('lote_automatico_preview');
        const loteBuscarInput = document.getElementById('lote_buscar_input');

        // Ocultar todas las secciones
        if (loteManualInput) loteManualInput.classList.add('hidden');
        if (loteAutomaticoPreview) loteAutomaticoPreview.classList.add('hidden');
        if (loteBuscarInput) loteBuscarInput.classList.add('hidden');

        // Mostrar la sección correspondiente
        switch (tipo) {
            case 'manual':
                if (loteManualInput) loteManualInput.classList.remove('hidden');
                break;
            case 'automatico':
                if (loteAutomaticoPreview) {
                    loteAutomaticoPreview.classList.remove('hidden');
                    this.generarPreviewLote();
                }
                break;
            case 'buscar':
                if (loteBuscarInput) loteBuscarInput.classList.remove('hidden');
                break;
        }
    }

    // Agregar las nuevas funciones
    async buscarProductosEgreso(query) {
        const container = document.getElementById('productos_encontrados_egreso');

        if (!query || query.length < 2) {
            container.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`/inventario/buscar-productos?q=${encodeURIComponent(query)}`);
            if (response.ok) {
                const data = await response.json();
                this.renderResultadosBusquedaEgreso(data.data || []);
            }
        } catch (error) {
            console.error('Error buscando productos:', error);
        }
    }

    renderResultadosBusquedaEgreso(productos) {
        const container = document.getElementById('productos_encontrados_egreso');

        if (productos.length === 0) {
            container.innerHTML = `<div class="p-3 text-center text-gray-500">No se encontraron productos</div>`;
            container.classList.remove('hidden');
            return;
        }

        container.innerHTML = productos.map(producto => `
        <div onclick="inventarioManager.seleccionarProductoEgreso(${producto.producto_id})" 
             class="p-3 hover:bg-gray-100 cursor-pointer border-b border-gray-200 last:border-b-0">
            <div class="font-medium text-gray-900">${producto.producto_nombre}</div>
            <div class="text-sm text-gray-500">SKU: ${producto.pro_codigo_sku} • Stock: ${producto.stock_cantidad_disponible || 0}</div>
        </div>
    `).join('');

        container.classList.remove('hidden');
    }

    async seleccionarProductoEgreso(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}`);
            if (response.ok) {
                const data = await response.json();
                this.productoSeleccionadoEgreso = data.data;

                document.getElementById('egreso-step-1').classList.add('hidden');
                document.getElementById('egreso-step-2').classList.remove('hidden');

                document.getElementById('producto_seleccionado_nombre_egreso').textContent = this.productoSeleccionadoEgreso.producto_nombre;
                document.getElementById('producto_seleccionado_info_egreso').textContent =
                    `Stock actual: ${this.productoSeleccionadoEgreso.stock_cantidad_disponible || 0} • SKU: ${this.productoSeleccionadoEgreso.pro_codigo_sku}`;

                const cantidadSection = document.getElementById('cantidad_section_egreso');
                const seriesSection = document.getElementById('series_section_egreso');

                if (this.productoSeleccionadoEgreso.producto_requiere_serie) {
                    // PRODUCTO CON SERIE: Mostrar series para seleccionar
                    cantidadSection.classList.add('hidden');
                    seriesSection.classList.remove('hidden');
                    this.cargarSeriesDisponibles(productoId);

                    // Cambiar label para series
                    const label = seriesSection.querySelector('label');
                    if (label) {
                        label.textContent = 'Seleccionar series a egresar *';
                    }
                } else {
                    // PRODUCTO SIN SERIE: Mostrar lotes + stock sin lote
                    cantidadSection.classList.add('hidden');
                    seriesSection.classList.remove('hidden');
                    this.cargarStockPorLotes(productoId);

                    // Cambiar label para lotes
                    const label = seriesSection.querySelector('label');
                    if (label) {
                        label.textContent = 'Seleccionar origen del egreso *';
                    }
                }

                document.getElementById('productos_encontrados_egreso').classList.add('hidden');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error al cargar el producto');
        }
    }
    // Nueva función para cargar stock por lotes
    async cargarStockPorLotes(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/stock-lotes`);
            if (response.ok) {
                const data = await response.json();
                this.renderStockPorLotes(data.data || {});
            } else {
                this.renderStockPorLotes({});
            }
        } catch (error) {
            console.error('Error cargando stock por lotes:', error);
            this.renderStockPorLotes({});
        }
    }


    // Nueva función para stock por lotes
    renderStockPorLotes(stockData) {
        const container = document.getElementById('series_disponibles_container');
        this.origenEgresoSeleccionado = null;

        if (!stockData.lotes && !stockData.sin_lote) {
            container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-2"></i>
                <p class="text-sm">No hay stock disponible para egreso</p>
            </div>
        `;
            return;
        }

        let html = `
        <div class="p-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Stock total disponible: ${stockData.total_disponible} unidades
            </div>
        </div>
    `;

        // Mostrar lotes disponibles
        if (stockData.lotes && stockData.lotes.length > 0) {
            html += `
            <div class="p-2 bg-blue-50 dark:bg-blue-900">
                <h4 class="text-sm font-medium text-blue-700 dark:text-blue-300">Stock en Lotes</h4>
            </div>
        `;

            stockData.lotes.forEach(lote => {
                html += `
                <div class="flex items-center justify-between p-3 border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                    <div class="flex items-center">
                        <input type="radio" 
                               name="origen_egreso" 
                               value="lote_${lote.lote_id}" 
                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 mr-3"
                               onchange="inventarioManager.seleccionarOrigenEgreso('lote', ${lote.lote_id}, ${lote.cantidad_disponible}, '${lote.lote_codigo}')">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">${lote.lote_codigo}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Ingreso: ${new Date(lote.fecha_ingreso).toLocaleDateString()}
                            </div>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        ${lote.cantidad_disponible} uds
                    </span>
                </div>
            `;
            });
        }

        // Mostrar stock sin lote
        if (stockData.sin_lote > 0) {
            html += `
            <div class="p-2 bg-gray-50 dark:bg-gray-800">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">Stock Sin Lote</h4>
            </div>
            <div class="flex items-center justify-between p-3 hover:bg-gray-50 dark:hover:bg-gray-600">
                <div class="flex items-center">
                    <input type="radio" 
                           name="origen_egreso" 
                           value="sin_lote" 
                           class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 mr-3"
                           onchange="inventarioManager.seleccionarOrigenEgreso('sin_lote', null, ${stockData.sin_lote}, 'Stock General')">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-gray-100">Stock General</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Sin asignación de lote específico
                        </div>
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ${stockData.sin_lote} uds
                </span>
            </div>
        `;
        }

        container.innerHTML = html;

        // Mostrar campo de cantidad después de la selección
        this.mostrarCamposCantidad();
    }

    // Nueva función para manejar selección de origen (solo para productos sin serie)
    seleccionarOrigenEgreso(tipo, loteId, cantidadMaxima, nombre) {
        this.origenEgresoSeleccionado = {
            tipo: tipo,
            lote_id: loteId,
            cantidad_maxima: cantidadMaxima,
            nombre: nombre
        };

        // Mostrar campos de cantidad
        this.mostrarCamposCantidad();

        // Actualizar información
        const infoElement = document.getElementById('origen_seleccionado_info');
        if (infoElement) {
            infoElement.innerHTML = `
            <div class="mt-3 p-2 bg-blue-50 dark:bg-blue-900 rounded">
                <span class="text-sm text-blue-700 dark:text-blue-300">
                    Origen seleccionado: <strong>${nombre}</strong> (${cantidadMaxima} disponibles)
                </span>
            </div>
        `;
        }
    }

    // Función para mostrar campos de cantidad (solo para productos sin serie)
    mostrarCamposCantidad() {
        if (this.productoSeleccionadoEgreso.producto_requiere_serie) return;

        const cantidadSection = document.getElementById('cantidad_section_egreso');
        const cantidadInput = document.getElementById('egr_cantidad');

        cantidadSection.classList.remove('hidden');

        if (this.origenEgresoSeleccionado) {
            cantidadInput.max = this.origenEgresoSeleccionado.cantidad_maxima;
            cantidadInput.placeholder = `Máximo: ${this.origenEgresoSeleccionado.cantidad_maxima}`;

            const label = cantidadSection.querySelector('label');
            if (label) {
                label.textContent = `Cantidad a egresar (máx: ${this.origenEgresoSeleccionado.cantidad_maxima}) *`;
            }
        }
    }


    // Nueva función para cargar series disponibles
    async cargarSeriesDisponibles(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/series-disponibles`);
            if (response.ok) {
                const data = await response.json();
                this.renderSeriesDisponibles(data.data || []);
            } else {
                this.renderSeriesDisponibles([]);
            }
        } catch (error) {
            console.error('Error cargando series:', error);
            this.renderSeriesDisponibles([]);
        }
    }

    // Renderizar series disponibles para selección
    renderSeriesDisponibles(series) {
        const container = document.getElementById('series_disponibles_container');
        this.seriesSeleccionadasEgreso = []; // Resetear selección
        this.actualizarContadorSeries();

        if (series.length === 0) {
            container.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-2"></i>
                <p class="text-sm">No hay series disponibles para egreso</p>
            </div>
        `;
            return;
        }

        container.innerHTML = `
        <div class="p-3 border-b border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    ${series.length} series disponibles
                </span>
                <div class="space-x-2">
                    <button onclick="inventarioManager.seleccionarTodasLasSeries()" 
                            class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                        Seleccionar todas
                    </button>
                    <button onclick="inventarioManager.deseleccionarTodasLasSeries()" 
                            class="text-xs px-2 py-1 bg-gray-500 text-white rounded hover:bg-gray-600">
                        Deseleccionar todas
                    </button>
                </div>
            </div>
        </div>
        ${series.map(serie => `
            <div class="flex items-center p-3 border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                <input type="checkbox" 
                       id="serie_${serie.serie_id}" 
                       class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded mr-3"
                       onchange="inventarioManager.toggleSerieSeleccion(${serie.serie_id}, '${serie.serie_numero_serie}')">
                <label for="serie_${serie.serie_id}" class="flex-1 cursor-pointer">
                    <div class="font-mono text-sm text-gray-900 dark:text-gray-100">${serie.serie_numero_serie}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Ingresado: ${new Date(serie.serie_fecha_ingreso).toLocaleDateString()}
                    </div>
                </label>
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                    Disponible
                </span>
            </div>
        `).join('')}
    `;
    }

    // Toggle selección de serie individual
    toggleSerieSeleccion(serieId, numeroSerie) {
        const checkbox = document.getElementById(`serie_${serieId}`);

        if (checkbox.checked) {
            // Agregar a seleccionadas
            if (!this.seriesSeleccionadasEgreso.find(s => s.id === serieId)) {
                this.seriesSeleccionadasEgreso.push({ id: serieId, numero: numeroSerie });
            }
        } else {
            // Remover de seleccionadas
            this.seriesSeleccionadasEgreso = this.seriesSeleccionadasEgreso.filter(s => s.id !== serieId);
        }

        this.actualizarContadorSeries();
    }

    // Seleccionar todas las series
    seleccionarTodasLasSeries() {
        const checkboxes = document.querySelectorAll('#series_disponibles_container input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            if (!checkbox.checked) {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    }

    // Deseleccionar todas las series
    deseleccionarTodasLasSeries() {
        const checkboxes = document.querySelectorAll('#series_disponibles_container input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    }


    actualizarContadorSeries() {
        const countElement = document.getElementById('series_seleccionadas_count');
        const hiddenInput = document.getElementById('series_seleccionadas_input');

        if (countElement) {
            countElement.textContent = this.seriesSeleccionadasEgreso.length;
        }

        // Actualizar input hidden con las series seleccionadas
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(this.seriesSeleccionadasEgreso);
        }
    }




    async handleEgresoSubmit() {
        if (!this.productoSeleccionadoEgreso) {
            this.showAlert('error', 'Error', 'Debe seleccionar un producto');
            return;
        }

        if (!this.validateEgresoForm()) {
            return;
        }

        // MOVER LA DECLARACIÓN DE formData AQUÍ AL INICIO
        const formData = new FormData();
        formData.append('producto_id', this.productoSeleccionadoEgreso.producto_id);

        // Verificar que los elementos existen antes de acceder a value
        const tipoElement = document.getElementById('egr_tipo');
        const destinoElement = document.getElementById('egr_destino');
        const observacionesElement = document.getElementById('egr_observaciones');

        if (!tipoElement || !destinoElement) {
            this.showAlert('error', 'Error', 'Error en el formulario. Recarga la página e intenta de nuevo.');
            return;
        }

        formData.append('mov_tipo', tipoElement.value);
        formData.append('mov_destino', destinoElement.value);
        formData.append('mov_observaciones', observacionesElement ? observacionesElement.value : '');

        if (this.productoSeleccionadoEgreso.producto_requiere_serie) {
            // PRODUCTO CON SERIE
            if (!this.seriesSeleccionadasEgreso || this.seriesSeleccionadasEgreso.length === 0) {
                this.showAlert('error', 'Error', 'Debe seleccionar al menos una serie');
                return;
            }
            formData.append('series_seleccionadas', JSON.stringify(this.seriesSeleccionadasEgreso));
        } else {
            // PRODUCTO SIN SERIE CON LOTES
            const cantidadElement = document.getElementById('egr_cantidad');
            if (!cantidadElement) {
                this.showAlert('error', 'Error', 'Campo cantidad no encontrado');
                return;
            }

            if (!this.origenEgresoSeleccionado) {
                this.showAlert('error', 'Error', 'Debe seleccionar el origen del egreso');
                return;
            }

            formData.append('mov_cantidad', cantidadElement.value);
            formData.append('origen_tipo', this.origenEgresoSeleccionado.tipo);

            if (this.origenEgresoSeleccionado.lote_id) {
                formData.append('lote_especifico_id', this.origenEgresoSeleccionado.lote_id);
            }
        }

        this.setLoading('egreso', true);

        try {
            const response = await fetch('/inventario/egresar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.resetEgresoForm();
                this.closeModal('egreso');

                await Promise.all([
                    this.loadProductos(),
                    this.loadStats(),
                    this.loadAlertas()
                ]);
            } else {
                if (data.errors) {
                    this.showValidationErrors('egreso', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al procesar egreso');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('egreso', false);
        }
    }

    validateEgresoForm() {
        const tipo = document.getElementById('egr_tipo').value;
        const destino = document.getElementById('egr_destino').value.trim();

        this.clearErrors('egreso');
        let isValid = true;

        if (!tipo) {
            this.showFieldError('egr_tipo', 'El tipo de movimiento es obligatorio');
            isValid = false;
        }

        if (!destino) {
            this.showFieldError('egr_destino', 'El destino es obligatorio');
            isValid = false;
        }

        if (this.productoSeleccionadoEgreso.producto_requiere_serie) {
            // VALIDACIÓN PARA SERIES
            if (!this.seriesSeleccionadasEgreso || this.seriesSeleccionadasEgreso.length === 0) {
                this.showFieldError('numeros_series_egreso', 'Debe seleccionar al menos una serie');
                isValid = false;
            }
        } else {
            // VALIDACIÓN PARA LOTES/CANTIDAD
            if (!this.origenEgresoSeleccionado) {
                this.showFieldError('egr_cantidad', 'Debe seleccionar el origen del egreso');
                isValid = false;
            }

            const cantidad = document.getElementById('egr_cantidad').value;
            if (!cantidad || parseInt(cantidad) <= 0) {
                this.showFieldError('egr_cantidad', 'La cantidad debe ser mayor a 0');
                isValid = false;
            } else if (parseInt(cantidad) > this.origenEgresoSeleccionado.cantidad_maxima) {
                this.showFieldError('egr_cantidad', `Cantidad excede el máximo disponible: ${this.origenEgresoSeleccionado.cantidad_maxima}`);
                isValid = false;
            }
        }

        return isValid;
    }







    /**
     * NUEVO: Limpiar configuración de lotes
     */
    limpiarConfiguracionLotes() {
        // Limpiar inputs
        const numeroLoteInput = document.getElementById('numero_lote');
        const buscarLoteInput = document.getElementById('buscar_lote');
        const loteIdHidden = document.getElementById('lote_id');

        if (numeroLoteInput) numeroLoteInput.value = '';
        if (buscarLoteInput) buscarLoteInput.value = '';
        if (loteIdHidden) loteIdHidden.value = '';

        // Limpiar preview
        this.lotePreview = '';

        // Resetear radio a automático
        const radioAutomatico = document.querySelector('input[name="tipo_lote"][value="automatico"]');
        if (radioAutomatico) {
            radioAutomatico.checked = true;
        }
    }
    /**
     * Cargar productos
     */
    /**
     * Cargar productos
     */
    async loadProductos() {
        const container = document.getElementById('productos-list');
        if (container) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-gray-500 dark:text-gray-400">Cargando inventario...</p>
                </div>
            `;
        }

        try {
            const response = await fetch('/inventario/productos-stock');
            if (response.ok) {
                const data = await response.json();
                this.productos = data.data || [];
                // Solo renderizar si realmente hay datos o si la carga fue exitosa
                this.renderProductos();
            } else {
                // Si hay error, mantener productos como array vacío
                this.productos = [];
                this.renderProductos();
            }
        } catch (error) {
            console.error('Error cargando productos:', error);
            // En caso de error, mantener como vacío
            this.productos = [];
            this.renderProductos();
        }
    }


    /**
     * Buscar lotes existentes
     */
    async buscarLotesExistentes(query) {
        const container = document.getElementById('lotes_encontrados');

        if (!query || query.length < 2) {
            if (container) {
                container.classList.add('hidden');
            }
            return;
        }

        // Mostrar loading
        if (container) {
            container.innerHTML = `
            <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Buscando lotes...
            </div>
        `;
            container.classList.remove('hidden');
        }

        try {
            const response = await fetch(`/inventario/lotes/buscar?q=${encodeURIComponent(query)}`);
            if (response.ok) {
                const data = await response.json();
                this.renderResultadosLotes(data.data || []);
            } else {
                this.renderResultadosLotes([]);
            }
        } catch (error) {
            console.error('Error buscando lotes:', error);
            if (container) {
                container.innerHTML = `
                <div class="p-3 text-center text-red-500 dark:text-red-400">
                    Error al buscar lotes
                </div>
            `;
            }
        }
    }

    /**
     * Renderizar resultados de búsqueda de lotes
     */
    renderResultadosLotes(lotes) {
        const container = document.getElementById('lotes_encontrados');
        if (!container) return;

        if (lotes.length === 0) {
            container.innerHTML = `
            <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                No se encontraron lotes
            </div>
        `;
            container.classList.remove('hidden');
            return;
        }

        container.innerHTML = lotes.map(lote => `
        <div onclick="inventarioManager.seleccionarLoteExistente(${lote.lote_id})" 
             class="p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <div class="font-medium text-gray-900 dark:text-gray-100">${lote.lote_codigo}</div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        ${lote.lote_descripcion || 'Sin descripción'}
                    </div>
                    <div class="text-xs text-gray-400 dark:text-gray-500">
                        Creado: ${new Date(lote.lote_fecha).toLocaleDateString()}
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                 ${lote.lote_situacion === 1 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'}">
                        ${lote.lote_situacion === 1 ? 'Activo' : 'Cerrado'}
                    </span>
                    <div class="text-xs text-gray-500 mt-1">
                        Disponible: ${lote.cantidad_disponible || 0}
                    </div>
                </div>
            </div>
        </div>
    `).join('');

        container.classList.remove('hidden');
    }

    /**
     * Seleccionar lote existente
     */
    async seleccionarLoteExistente(loteId) {
        try {
            const response = await fetch(`/inventario/lotes/${loteId}`);
            if (response.ok) {
                const data = await response.json();
                const lote = data.data;

                // Actualizar interfaz
                const container = document.getElementById('lote_seleccionado');
                const inputHidden = document.getElementById('lote_id');
                const searchInput = document.getElementById('buscar_lote');

                if (container) {
                    container.innerHTML = `
                    <div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg">
                        <div class="flex-1">
                            <div class="font-medium text-green-900 dark:text-green-100">
                                ${lote.lote_codigo}
                            </div>
                            <div class="text-sm text-green-700 dark:text-green-300">
                                ${lote.lote_descripcion || 'Lote seleccionado'}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400">
                                Disponible: ${lote.cantidad_disponible || 0} unidades
                            </div>
                        </div>
                        <button onclick="inventarioManager.limpiarLoteSeleccionado()" 
                                class="ml-3 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                }

                if (inputHidden) {
                    inputHidden.value = loteId;
                }

                if (searchInput) {
                    searchInput.value = lote.lote_codigo;
                }

                // Ocultar resultados de búsqueda
                document.getElementById('lotes_encontrados').classList.add('hidden');

                // Mostrar mensaje de éxito
                this.showToast(`Lote seleccionado: ${lote.lote_codigo}`, 'success');

            } else {
                this.showAlert('error', 'Error', 'No se pudo obtener la información del lote');
            }
        } catch (error) {
            console.error('Error obteniendo lote:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Limpiar lote seleccionado
     */
    limpiarLoteSeleccionado() {
        const container = document.getElementById('lote_seleccionado');
        const inputHidden = document.getElementById('lote_id');
        const searchInput = document.getElementById('buscar_lote');

        if (container) {
            container.innerHTML = `
            <div class="p-3 bg-gray-100 dark:bg-gray-600 rounded-md text-sm text-gray-500 dark:text-gray-400">
                Ningún lote seleccionado
            </div>
        `;
        }

        if (inputHidden) {
            inputHidden.value = '';
        }

        if (searchInput) {
            searchInput.value = '';
        }

        this.showToast('Lote deseleccionado', 'info');
    }




    /**
     * Cargar categorías
     */
    async loadCategorias() {
        try {
            const response = await fetch('/categorias/activas');
            if (response.ok) {
                const data = await response.json();
                this.categorias = data.data || [];
                this.populateSelect('producto_categoria', this.categorias, 'categoria_id', 'categoria_nombre');
                this.populateSelect('filter-categoria', this.categorias, 'categoria_id', 'categoria_nombre');
            }
        } catch (error) {
            console.error('Error cargando categorías:', error);
        }
    }

    /**
     * Cargar marcas
     */
    async loadMarcas() {
        try {
            const response = await fetch('/marcas/activas');
            if (response.ok) {
                const data = await response.json();
                this.marcas = data.data || [];
                this.populateSelect('producto_marca', this.marcas, 'marca_id', 'marca_descripcion');
            }
        } catch (error) {
            console.error('Error cargando marcas:', error);
        }
    }

    /**
     * Cargar calibres
     */
    async loadCalibres() {
        try {
            const response = await fetch('/calibres/activos');
            if (response.ok) {
                const data = await response.json();
                this.calibres = data.data || [];
                this.populateSelect('producto_calibre', this.calibres, 'calibre_id', 'calibre_nombre');
            }
        } catch (error) {
            console.error('Error cargando calibres:', error);
        }
    }

    /**
     * Cargar subcategorías por categoría
     */
    async loadSubcategorias(categoriaId) {
        const select = document.getElementById('producto_subcategoria');
        select.innerHTML = '<option value="">Seleccionar subcategoría</option>';

        if (!categoriaId) return;

        try {
            const response = await fetch(`/categorias/${categoriaId}/subcategorias`);
            if (response.ok) {
                const data = await response.json();
                this.populateSelect('producto_subcategoria', data.data || [], 'subcategoria_id', 'subcategoria_nombre');
            }
        } catch (error) {
            console.error('Error cargando subcategorías:', error);
        }
    }

    /**
     * Cargar modelos por marca
     */
    async loadModelos(marcaId) {
        const select = document.getElementById('producto_modelo');
        select.innerHTML = '<option value="">Seleccionar modelo</option>';

        if (!marcaId) return;

        try {
            const response = await fetch(`/marcas/${marcaId}/modelos`);
            if (response.ok) {
                const data = await response.json();
                this.populateSelect('producto_modelo', data.data || [], 'modelo_id', 'modelo_descripcion');
            }
        } catch (error) {
            console.error('Error cargando modelos:', error);
        }
    }

    /**
     * Cargar estadísticas
     */
    async loadStats() {
        try {
            const response = await fetch('/inventario/estadisticas');
            if (response.ok) {
                const data = await response.json();
                this.stats = data.data || this.stats;
                this.updateStats();
            }
        } catch (error) {
            console.error('Error cargando estadísticas:', error);
        }
    }

    /**
     * Cargar alertas
     */
    async loadAlertas() {
        try {
            const response = await fetch('/inventario/alertas-stock');
            if (response.ok) {
                const data = await response.json();
                this.renderAlertas(data.data || []);
            }
        } catch (error) {
            console.error('Error cargando alertas:', error);
        }
    }

    /**
 * Configurar manejo de fotos en formulario de registro
 */
    setupFotosHandling() {
        // Checkbox para mostrar/ocultar sección de fotos
        const checkboxFotos = document.getElementById('agregar_fotos');
        const seccionFotos = document.getElementById('seccion_fotos');

        if (checkboxFotos && seccionFotos) {
            checkboxFotos.addEventListener('change', (e) => {
                if (e.target.checked) {
                    seccionFotos.classList.remove('hidden');
                } else {
                    seccionFotos.classList.add('hidden');
                    this.limpiarPreviewFotos();
                }
            });
        }

        // Manejo de archivo seleccionado
        const inputFotos = document.getElementById('fotos_producto');
        if (inputFotos) {
            inputFotos.addEventListener('change', (e) => {
                this.handleFotosSeleccionadas(e.target.files);
            });
        }



        // Drag & Drop
        const dropZone = document.getElementById('foto_drop_zone');
        if (dropZone) {
            this.setupDragAndDrop(dropZone, inputFotos);
        }
    }

    /**
     * Manejar fotos seleccionadas
     */
    /**
    * Manejar fotos seleccionadas
    */
    /**
     * Manejar fotos seleccionadas
     */
    handleFotosSeleccionadas(archivos) {
        if (archivos.length > 5) {
            this.showAlert('warning', 'Límite excedido', 'Máximo 5 fotos por producto');
            return;
        }

        // Validar tipos de archivo
        const tiposValidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const archivosInvalidos = Array.from(archivos).filter(archivo =>
            !tiposValidos.includes(archivo.type) || archivo.size > 2048000
        );

        if (archivosInvalidos.length > 0) {
            this.showAlert('error', 'Archivos inválidos', 'Solo se permiten JPG, PNG, WebP hasta 2MB');
            return;
        }

        // ✅ Determinar qué contenedor usar
        const previewContainerRegistro = document.getElementById('preview_fotos');
        const previewContainerGestion = document.getElementById('preview_nuevas_fotos');

        // Verificar cuál modal está activo
        const modalRegistro = document.getElementById('registro-modal');
        const modalGestion = document.getElementById('fotos-modal');

        if (modalGestion && !modalGestion.classList.contains('hidden')) {
            // Estamos en el modal de gestión
            this.previewFotosGestion(archivos);
        } else if (modalRegistro && !modalRegistro.classList.contains('hidden')) {
            // Estamos en el modal de registro
            this.previewFotos(archivos);
        }
    }

    /**
     * ✅ NUEVA FUNCIÓN: Preview específico para gestión de fotos
     */
    previewFotosGestion(archivos) {
        const container = document.getElementById('preview_nuevas_fotos');
        if (!container) return;

        container.innerHTML = '';

        Array.from(archivos).forEach((archivo, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group';
                previewDiv.innerHTML = `
                <div class="relative">
                    <img src="${e.target.result}" 
                        alt="Preview ${index + 1}"
                        class="w-20 h-20 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-600">
                    <button type="button" 
                            onclick="this.closest('.relative.group').remove()"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                        ×
                    </button>
                  
                </div>
            `;
                container.appendChild(previewDiv);
            };
            reader.readAsDataURL(archivo);
        });
    }
    /**
     * Mostrar preview de fotos
     */
    previewFotos(archivos) {
        const container = document.getElementById('preview_fotos');
        if (!container) return;

        container.innerHTML = '';

        Array.from(archivos).forEach((archivo, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewDiv = document.createElement('div');
                previewDiv.className = 'relative group';
                previewDiv.innerHTML = `
                    <div class="relative">
                        <img src="${e.target.result}" 
                            alt="Preview ${index + 1}"
                            class="w-20 h-20 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-600">
                        <button type="button" 
                                onclick="inventarioManager.eliminarPreview(this)"
                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-xs hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                            ×
                        </button>
                        ${index === 0 ? '<span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white text-xs px-1 rounded">Principal</span>' : ''}
                    </div>
                `;
                container.appendChild(previewDiv);
            };
            reader.readAsDataURL(archivo);
        });
    }

    /**
     * Configurar drag and drop
     */
    setupDragAndDrop(dropZone, inputFile) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            inputFile.files = files;
            this.handleFotosSeleccionadas(files);
        });

        dropZone.addEventListener('click', () => {
            inputFile.click();
        });
    }

    /**
     * Eliminar preview individual
     */
    eliminarPreview(boton) {
        const previewDiv = boton.closest('.relative.group');
        previewDiv.remove();

        // Actualizar el input file (esto es más complejo en vanilla JS)
        // Por ahora solo removemos visualmente
    }

    /**
     * Limpiar preview de fotos
     */
    limpiarPreviewFotos() {
        const container = document.getElementById('preview_fotos');
        if (container) {
            container.innerHTML = '';
        }

        const inputFotos = document.getElementById('fotos_producto');
        if (inputFotos) {
            inputFotos.value = '';
        }
    }

    /**
     * Abrir modal de gestión de fotos
     */
    /**
     * Abrir modal de gestión de fotos
     */
    async openFotosModal(productoId) {
        this.currentProductoId = productoId;

        try {
            // Cargar fotos existentes
            const response = await fetch(`/inventario/productos/${productoId}/fotos`);
            if (response.ok) {
                const data = await response.json();
                this.renderFotosExistentes(data.data || []);
                this.showModal('fotos');


                const inputNuevasFotos = document.getElementById('nuevas_fotos');
                if (inputNuevasFotos) {
                    inputNuevasFotos.addEventListener('change', (e) => {
                        this.handleFotosSeleccionadas(e.target.files);
                    });
                }

                // Cargar información del producto
                this.cargarInfoProductoFotos(productoId);
            }
        } catch (error) {
            console.error('Error cargando fotos:', error);
            this.showAlert('error', 'Error', 'No se pudieron cargar las fotos');
        }
    }
    /**
     * Cargar información del producto en modal de fotos
     */
    async cargarInfoProductoFotos(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}`);
            if (response.ok) {
                const data = await response.json();
                const infoElement = document.getElementById('fotos_producto_info');
                if (infoElement) {
                    infoElement.innerHTML = `
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-box text-blue-500"></i>
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">${data.data.producto_nombre}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">SKU: ${data.data.pro_codigo_sku}</p>
                            </div>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error cargando info del producto:', error);
        }
    }

    /**
     * Renderizar fotos existentes en modal
     */


    renderFotosExistentes(fotos) {
        const container = document.getElementById('fotos_existentes');
        const countElement = document.getElementById('fotos_count');

        if (!container) return;

        // Actualizar contador
        if (countElement) {
            countElement.textContent = `${fotos.length}/5 fotos`;
            countElement.className = fotos.length >= 5 ? 'text-red-600 font-medium' : 'text-gray-600';
        }

        if (fotos.length === 0) {
            container.innerHTML = `
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-image text-gray-400 text-4xl mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No hay fotos para este producto</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Sube hasta 5 fotos para mostrar tu producto</p>
                </div>
            `;
            this.actualizarEstadoSubida(0);
            return;
        }

        // Estructura mejorada con botones SIEMPRE visibles
        container.innerHTML = fotos.map((foto, index) => `
            <div class="relative group" style="min-height: 120px; min-width: 120px;">
                <img src="${foto.foto_url}"
                     alt="Foto producto"
                     class="w-24 h-24 object-cover rounded-lg border-2 ${foto.foto_principal ? 'border-blue-500' : 'border-gray-200 dark:border-gray-600'}"
                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIGltYWdlbjwvdGV4dD48L3N2Zz4=';">
                
                <!-- Botón eliminar - MÁS VISIBLE -->
                <button onclick="inventarioManager.eliminarFotoProducto(${foto.foto_id})"
                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-6 h-6 text-xs hover:bg-red-600 transition-colors z-10 flex items-center justify-center shadow-lg"
                title="Eliminar foto">
            ×
        </button>
                ${foto.foto_principal ?
                '<span class="absolute -top-1 left-1/2 transform -translate-x-1/2 bg-blue-500 text-white text-xs px-2 py-1 rounded z-10">Principal</span>' :
                `<button onclick="inventarioManager.establecerPrincipal(${foto.foto_id})" 
                             class="absolute -top-1 left-1/2 transform -translate-x-1/2 bg-gray-500 text-white text-xs px-2 py-1 rounded hover:bg-blue-500 transition-colors z-10 opacity-80 hover:opacity-100">
                        Hacer Principal
                     </button>`
            }
                
                <!-- Número de orden -->
                <div class="absolute bottom-1 left-1">
                <span class="bg-black bg-opacity-70 text-white text-xs px-1 py-0.5 rounded">
                    ${index + 1}
                </span>
            </div>
            </div>
        `).join('');

        // Actualizar estado del botón de subir
        this.actualizarEstadoSubida(fotos.length);
        this.actualizarEstadisticasFotos({
            total_fotos: fotos.length,
            tamaño_total: this.calcularTamañoTotal(fotos)
        });
    }
    /**
 * Actualizar estadísticas en el modal
 */
    actualizarEstadisticasFotos(stats) {
        const container = document.getElementById('fotos_estadisticas');
        if (!container) return;

        container.innerHTML = `
        <div class="grid grid-cols-2 gap-2">
            <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-center">
                <div class="font-medium text-gray-900 dark:text-gray-100">${stats.total_fotos}/5</div>
                <div class="text-gray-500 dark:text-gray-400">Fotos</div>
            </div>
            <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded text-center">
                <div class="font-medium text-gray-900 dark:text-gray-100">${stats.tamaño_total || '0 KB'}</div>
                <div class="text-gray-500 dark:text-gray-400">Tamaño</div>
            </div>
        </div>
    `;
    }

    /**
     * Calcular tamaño total (placeholder)
     */
    calcularTamañoTotal(fotos) {
        // Si las fotos tienen información de tamaño, la sumamos
        let totalKB = 0;
        fotos.forEach(foto => {
            if (foto.size_info) {
                const match = foto.size_info.match(/(\d+(?:\.\d+)?)\s*KB/);
                if (match) {
                    totalKB += parseFloat(match[1]);
                }
            }
        });

        if (totalKB > 1024) {
            return `${(totalKB / 1024).toFixed(1)} MB`;
        }
        return totalKB > 0 ? `${totalKB.toFixed(0)} KB` : '0 KB';
    }
    /**
     * Subir nuevas fotos
     */
    async subirNuevasFotos() {
        const inputFile = document.getElementById('nuevas_fotos');
        if (!inputFile.files || inputFile.files.length === 0) {
            this.showAlert('warning', 'Sin archivos', 'Selecciona al menos una foto para subir');
            return;
        }

        const formData = new FormData();
        Array.from(inputFile.files).forEach(archivo => {
            formData.append('fotos[]', archivo);
        });

        this.mostrarProgresoSubida();

        try {
            const response = await fetch(`/inventario/productos/${this.currentProductoId}/fotos`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);

                // Recargar fotos existentes
                const fotosResponse = await fetch(`/inventario/productos/${this.currentProductoId}/fotos`);
                if (fotosResponse.ok) {
                    const fotosData = await fotosResponse.json();
                    this.renderFotosExistentes(fotosData.data || []);
                }

                // Limpiar input y preview
                inputFile.value = '';
                const previewContainer = document.getElementById('preview_nuevas_fotos');
                if (previewContainer) {
                    previewContainer.innerHTML = '';
                }

            } else {
                this.showAlert('error', 'Error', data.message || 'Error al subir fotos');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión al subir fotos');
        } finally {
            this.ocultarProgresoSubida();
        }
    }

    /**
     * Validar archivos antes de subir
     */
    validarArchivos(files) {
        const errores = [];
        const tiposValidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        const tamañoMaximo = 2 * 1024 * 1024; // 2MB

        Array.from(files).forEach((file, index) => {
            if (!tiposValidos.includes(file.type)) {
                errores.push(`Archivo ${index + 1}: Tipo no válido (${file.type})`);
            }

            if (file.size > tamañoMaximo) {
                errores.push(`Archivo ${index + 1}: Tamaño excede 2MB`);
            }
        });

        return errores;
    }


    mostrarProgresoSubida() {
        const botonSubir = document.querySelector('[onclick="inventarioManager.subirNuevasFotos()"]');
        if (botonSubir) {
            botonSubir.disabled = true;
            botonSubir.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subiendo...';
        }
    }

    /**
     * Ocultar progreso y restaurar botón
     */
    ocultarProgresoSubida() {
        const botonSubir = document.querySelector('[onclick="inventarioManager.subirNuevasFotos()"]');
        if (botonSubir) {
            botonSubir.disabled = false;
            botonSubir.innerHTML = '<i class="fas fa-upload mr-2"></i>Subir Fotos';
        }
    }
    actualizarEstadoSubida(fotosActuales) {
        const seccionSubida = document.getElementById('seccion_subir_fotos');
        const inputFile = document.getElementById('nuevas_fotos');
        const botonSubir = document.querySelector('[onclick="inventarioManager.subirNuevasFotos()"]');
        const mensajeLimite = document.getElementById('mensaje_limite_fotos');

        if (fotosActuales >= 5) {
            if (seccionSubida) seccionSubida.classList.add('opacity-50', 'pointer-events-none');
            if (inputFile) inputFile.disabled = true;
            if (botonSubir) {
                botonSubir.disabled = true;
                botonSubir.innerHTML = '<i class="fas fa-ban mr-2"></i>Límite alcanzado';
            }
            if (mensajeLimite) {
                mensajeLimite.textContent = 'Límite máximo alcanzado (5/5 fotos). Elimina fotos para subir nuevas.';
                mensajeLimite.className = 'mt-2 text-xs text-red-500 font-medium';
            }
        } else {
            if (seccionSubida) seccionSubida.classList.remove('opacity-50', 'pointer-events-none');
            if (inputFile) inputFile.disabled = false;
            if (botonSubir) {
                botonSubir.disabled = false;
                botonSubir.innerHTML = '<i class="fas fa-upload mr-2"></i>Subir Fotos';
            }
            if (mensajeLimite) {
                const restantes = 5 - fotosActuales;
                mensajeLimite.textContent = `Puedes subir ${restantes} foto${restantes > 1 ? 's' : ''} más`;
                mensajeLimite.className = 'mt-2 text-xs text-gray-500 dark:text-gray-400';
            }
        }
    }
    /**
     * Eliminar foto del producto
     */
    async eliminarFotoProducto(fotoId) {
        const confirmacion = await Swal.fire({
            title: '¿Eliminar foto?',
            text: 'Esta acción eliminará permanentemente la foto del producto',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            customClass: {
                popup: 'dark:bg-gray-800',
                title: 'dark:text-gray-100',
                content: 'dark:text-gray-300'
            }
        });

        if (!confirmacion.isConfirmed) return;

        try {
            const response = await fetch(`/inventario/fotos/${fotoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);

                // Recargar fotos
                const fotosResponse = await fetch(`/inventario/productos/${this.currentProductoId}/fotos`);
                if (fotosResponse.ok) {
                    const fotosData = await fotosResponse.json();
                    this.renderFotosExistentes(fotosData.data || []);
                }
            } else {
                this.showAlert('error', 'Error', data.message || 'Error al eliminar foto');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Establecer foto como principal
     */
    async establecerPrincipal(fotoId) {
        try {
            const response = await fetch(`/inventario/fotos/${fotoId}/principal`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);

                // Recargar fotos para reflejar cambios
                const fotosResponse = await fetch(`/inventario/productos/${this.currentProductoId}/fotos`);
                if (fotosResponse.ok) {
                    const fotosData = await fotosResponse.json();
                    this.renderFotosExistentes(fotosData.data || []);
                }

            } else {
                this.showAlert('error', 'Error', data.message || 'Error al actualizar foto principal');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Poblar select con opciones
     */
    populateSelect(selectId, options, valueField, textField) {
        const select = document.getElementById(selectId);
        const placeholder = select.querySelector('option[value=""]');

        // Limpiar opciones excepto el placeholder
        select.innerHTML = '';
        if (placeholder) {
            select.appendChild(placeholder);
        }

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option[valueField];
            optionElement.textContent = option[textField];
            select.appendChild(optionElement);
        });
    }

    /**
     * Aplicar filtros a productos
     */
    aplicarFiltros() {
        this.renderProductos();
    }

    /**
     * Limpiar filtros
     */
    clearFilters() {
        this.filtros = { search: '', categoria: '', stock: '' };

        document.getElementById('search-productos').value = '';
        document.getElementById('filter-categoria').value = '';
        document.getElementById('filter-stock').value = '';

        this.renderProductos();
    }

    /**
     * Filtrar productos
     */
    getProductosFiltrados() {
        return this.productos.filter(producto => {
            const matchSearch = !this.filtros.search ||
                producto.producto_nombre.toLowerCase().includes(this.filtros.search.toLowerCase()) ||
                (producto.pro_codigo_sku && producto.pro_codigo_sku.toLowerCase().includes(this.filtros.search.toLowerCase())) ||
                (producto.producto_codigo_barra && producto.producto_codigo_barra.toLowerCase().includes(this.filtros.search.toLowerCase()));

            const matchCategoria = !this.filtros.categoria ||
                producto.producto_categoria_id.toString() === this.filtros.categoria;

            let matchStock = true;
            if (this.filtros.stock) {
                const stock = producto.stock_cantidad_disponible || 0;
                const minimo = producto.producto_stock_minimo || 0;

                switch (this.filtros.stock) {
                    case 'disponible':
                        matchStock = stock > 0;
                        break;
                    case 'bajo':
                        matchStock = stock <= minimo && stock > 0;
                        break;
                    case 'agotado':
                        matchStock = stock <= 0;
                        break;
                }
            }

            return matchSearch && matchCategoria && matchStock;
        });
    }

    async loadPaises() {
        try {
            const response = await fetch('/paises/activos');
            if (response.ok) {
                const data = await response.json();
                this.paises = data.data || [];
                this.populateSelect('producto_madein', data.data || [], 'pais_id', 'pais_descripcion');
            }
        } catch (error) {
            console.error('Error cargando países:', error);
        }
    }


    // ================================
    // 4. NUEVO MÉTODO: setupPreciosHandling()
    // ================================
    /**
     * Configurar manejo de precios en formulario de registro - VERSIÓN ACTUALIZADA
     */
    setupPreciosHandling() {
        // Función para calcular margenes tanto individual como empresa
        const calcularMargenRegistro = () => {
            const costInput = document.getElementById('precio_costo');
            const ventaInput = document.getElementById('precio_venta');
            const ventaEmpresaInput = document.getElementById('precio_venta_empresa');
            const margenElement = document.getElementById('margen_calculado');
            const gananciaElement = document.getElementById('ganancia_calculada');
            const margenElementEmpresa = document.getElementById('margen_calculado_empresa');
            const gananciaElementEmpresa = document.getElementById('ganancia_calculada_empresa');

            if (!costInput || !ventaInput || !ventaEmpresaInput) return;

            const costo = parseFloat(costInput.value) || 0;
            const venta = parseFloat(ventaInput.value) || 0;
            const ventaEmpresa = parseFloat(ventaEmpresaInput.value) || 0;

            // CÁLCULO PARA PRECIO INDIVIDUAL
            if (margenElement && gananciaElement && costo > 0 && venta > 0) {
                const ganancia = venta - costo;
                const margen = ((ganancia / costo) * 100);

                margenElement.textContent = `${margen.toFixed(1)}%`;
                gananciaElement.textContent = `Q${ganancia.toFixed(2)}`;

                // Colorear según el margen
                if (margen < 10) {
                    margenElement.className = 'text-red-600 font-bold';
                } else if (margen < 25) {
                    margenElement.className = 'text-yellow-600 font-bold';
                } else {
                    margenElement.className = 'text-green-600 font-bold';
                }
            } else if (margenElement && gananciaElement) {
                margenElement.textContent = '0%';
                gananciaElement.textContent = 'Q0.00';
                margenElement.className = 'text-gray-400 font-bold';
            }

            // CÁLCULO PARA PRECIO EMPRESA
            if (margenElementEmpresa && gananciaElementEmpresa && costo > 0 && ventaEmpresa > 0) {
                const gananciaEmpresa = ventaEmpresa - costo;
                const margenEmpresa = ((gananciaEmpresa / costo) * 100);

                margenElementEmpresa.textContent = `${margenEmpresa.toFixed(1)}%`;
                gananciaElementEmpresa.textContent = `Q${gananciaEmpresa.toFixed(2)}`;

                // Colorear según el margen empresa
                if (margenEmpresa < 10) {
                    margenElementEmpresa.className = 'text-red-600 font-bold';
                } else if (margenEmpresa < 25) {
                    margenElementEmpresa.className = 'text-yellow-600 font-bold';
                } else {
                    margenElementEmpresa.className = 'text-green-600 font-bold';
                }
            } else if (margenElementEmpresa && gananciaElementEmpresa) {
                margenElementEmpresa.textContent = '0%';
                gananciaElementEmpresa.textContent = 'Q0.00';
                margenElementEmpresa.className = 'text-gray-400 font-bold';
            }
        };

        // Agregar event listeners a todos los campos de precio
        const camposPrecios = ['precio_costo', 'precio_venta', 'precio_venta_empresa'];

        camposPrecios.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                // Remover listener anterior si existe
                if (input._calcularMargenRegistroHandler) {
                    input.removeEventListener('input', input._calcularMargenRegistroHandler);
                }

                // Crear y almacenar nueva función handler
                input._calcularMargenRegistroHandler = calcularMargenRegistro;
                input.addEventListener('input', input._calcularMargenRegistroHandler);
            }
        });
    }

    // ================================
    // 5. NUEVOS MÉTODOS PARA LICENCIAS
    // ================================

    /**
     * Buscar licencias disponibles
     */
    async buscarLicencias(query) {
        const container = document.getElementById('licencias_encontradas');

        if (!query || query.length < 2) {
            if (container) {
                container.classList.add('hidden');
            }
            return;
        }

        try {
            const response = await fetch(`/licencias/buscar?q=${encodeURIComponent(query)}`);
            if (response.ok) {
                const data = await response.json();
                this.renderResultadosLicencias(data.data || []);
            }
        } catch (error) {
            console.error('Error buscando licencias:', error);
        }
    }

    /**
     * Renderizar resultados de búsqueda de licencias
     */
    renderResultadosLicencias(licencias) {
        const container = document.getElementById('licencias_encontradas');
        if (!container) return;

        if (licencias.length === 0) {
            container.innerHTML = `
            <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                No se encontraron licencias
            </div>
        `;
            container.classList.remove('hidden');
            return;
        }

        container.innerHTML = licencias.map(licencia => `
        <div onclick="inventarioManager.seleccionarLicencia(${licencia.lipaimp_id})" 
             class="p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <div class="font-medium text-gray-900 dark:text-gray-100">
                Licencia: ${licencia.lipaimp_numero}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Póliza: ${licencia.lipaimp_poliza || 'N/A'} • ${licencia.lipaimp_descripcion}
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500">
                Vence: ${new Date(licencia.lipaimp_fecha_vencimiento).toLocaleDateString()}
            </div>
        </div>
    `).join('');

        container.classList.remove('hidden');
    }

    /**
     * Seleccionar licencia específica
     */
    async seleccionarLicencia(licenciaId) {
        try {
            const response = await fetch(`/licencias/${licenciaId}`);
            if (response.ok) {
                const data = await response.json();
                this.licenciaSeleccionada = data.data;

                // Actualizar interfaz
                const container = document.getElementById('licencia_seleccionada');
                const inputHidden = document.getElementById('licencia_id');
                const searchInput = document.getElementById('buscar_licencia');

                if (container) {
                    container.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                Licencia: ${this.licenciaSeleccionada.lipaimp_numero}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Póliza: ${this.licenciaSeleccionada.lipaimp_poliza || 'N/A'} • ${this.licenciaSeleccionada.lipaimp_descripcion}
                            </div>
                        </div>
                        <button onclick="inventarioManager.limpiarLicenciaSeleccionada()" 
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                }

                if (inputHidden) {
                    inputHidden.value = licenciaId;
                }

                if (searchInput) {
                    searchInput.value = this.licenciaSeleccionada.lipaimp_numero;
                }

                // Ocultar resultados
                document.getElementById('licencias_encontradas').classList.add('hidden');
            }
        } catch (error) {
            console.error('Error obteniendo licencia:', error);
        }
    }

    /**
     * Limpiar licencia seleccionada
     */
    limpiarLicenciaSeleccionada() {
        this.licenciaSeleccionada = null;

        const container = document.getElementById('licencia_seleccionada');
        const inputHidden = document.getElementById('licencia_id');
        const searchInput = document.getElementById('buscar_licencia');

        if (container) {
            container.innerHTML = `
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Ninguna licencia seleccionada
            </div>
        `;
        }

        if (inputHidden) {
            inputHidden.value = '';
        }

        if (searchInput) {
            searchInput.value = '';
        }
    }

    /**
     * Buscar licencias para registro (Ingreso)
     */
    async buscarLicenciasRegistro(query) {
        const container = document.getElementById('licencias_encontradas_registro');

        if (!query || query.length < 2) {
            if (container) {
                container.classList.add('hidden');
            }
            return;
        }

        try {
            const response = await fetch(`/licencias/buscar?q=${encodeURIComponent(query)}`);
            if (response.ok) {
                const data = await response.json();
                this.renderResultadosLicenciasRegistro(data.data || []);
            }
        } catch (error) {
            console.error('Error buscando licencias:', error);
        }
    }

    /**
     * Renderizar resultados de búsqueda de licencias para registro
     */
    renderResultadosLicenciasRegistro(licencias) {
        const container = document.getElementById('licencias_encontradas_registro');
        if (!container) return;

        if (licencias.length === 0) {
            container.innerHTML = `
            <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                No se encontraron licencias
            </div>
        `;
            container.classList.remove('hidden');
            return;
        }

        container.innerHTML = licencias.map(licencia => `
        <div onclick="inventarioManager.seleccionarLicenciaRegistro(${licencia.lipaimp_id})" 
             class="p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-600 last:border-b-0">
            <div class="font-medium text-gray-900 dark:text-gray-100">
                Licencia: ${licencia.lipaimp_numero}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Póliza: ${licencia.lipaimp_poliza || 'N/A'} • ${licencia.lipaimp_descripcion}
            </div>
            <div class="text-xs text-gray-400 dark:text-gray-500">
                Vence: ${new Date(licencia.lipaimp_fecha_vencimiento).toLocaleDateString()}
            </div>
        </div>
    `).join('');

        container.classList.remove('hidden');
    }

    /**
     * Seleccionar licencia para registro
     */
    async seleccionarLicenciaRegistro(licenciaId) {
        try {
            const response = await fetch(`/licencias/${licenciaId}`);
            if (response.ok) {
                const data = await response.json();
                this.licenciaSeleccionadaRegistro = data.data;

                // Actualizar interfaz
                const container = document.getElementById('licencia_seleccionada_registro');
                const inputHidden = document.getElementById('licencia_id_registro');
                const searchInput = document.getElementById('buscar_licencia_registro');

                if (container) {
                    container.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-gray-100">
                                Licencia: ${this.licenciaSeleccionadaRegistro.lipaimp_numero}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Póliza: ${this.licenciaSeleccionadaRegistro.lipaimp_poliza || 'N/A'}
                            </div>
                        </div>
                        <button onclick="inventarioManager.limpiarLicenciaSeleccionadaRegistro()" 
                                type="button"
                                class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                }

                if (inputHidden) {
                    inputHidden.value = licenciaId;
                }

                if (searchInput) {
                    searchInput.value = this.licenciaSeleccionadaRegistro.lipaimp_numero;
                }

                // Ocultar resultados
                document.getElementById('licencias_encontradas_registro').classList.add('hidden');
            }
        } catch (error) {
            console.error('Error obteniendo licencia:', error);
        }
    }

    /**
     * Limpiar licencia seleccionada para registro
     */
    limpiarLicenciaSeleccionadaRegistro() {
        this.licenciaSeleccionadaRegistro = null;

        const container = document.getElementById('licencia_seleccionada_registro');
        const inputHidden = document.getElementById('licencia_id_registro');
        const searchInput = document.getElementById('buscar_licencia_registro');

        if (container) {
            container.innerHTML = `
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Ninguna licencia seleccionada
            </div>
        `;
        }

        if (inputHidden) {
            inputHidden.value = '';
        }

        if (searchInput) {
            searchInput.value = '';
        }
    }

    // ================================
    // 6. NUEVOS MÉTODOS PARA LOTES
    // ================================

    /**
     * Toggle entre lote automático y manual
     */
    toggleLoteInput(tipo) {
        const loteManualInput = document.getElementById('lote_manual_input');
        const loteAutomaticoPreview = document.getElementById('lote_automatico_preview');

        if (tipo === 'manual') {
            if (loteManualInput) loteManualInput.classList.remove('hidden');
            if (loteAutomaticoPreview) loteAutomaticoPreview.classList.add('hidden');
        } else {
            if (loteManualInput) loteManualInput.classList.add('hidden');
            if (loteAutomaticoPreview) loteAutomaticoPreview.classList.remove('hidden');
            this.generarPreviewLote();
        }
    }

    /**
     * Generar preview del lote automático
     */
    generarPreviewLote() {
        if (!this.productoSeleccionado) return;

        const fecha = new Date();
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');

        // Obtener código de marca del producto seleccionado
        let marcaCode = 'AUTO';
        if (this.productoSeleccionado.marca_nombre) {
            marcaCode = this.productoSeleccionado.marca_nombre.substring(0, 3).toUpperCase();
        }

        this.lotePreview = `L${año}-${mes}-${marcaCode}-001`;

        const previewElement = document.getElementById('lote_preview');
        if (previewElement) {
            previewElement.textContent = this.lotePreview;
        }

        // Actualizar descripción del preview
        const descripcionElement = document.getElementById('lote_automatico_preview');
        if (descripcionElement) {
            descripcionElement.innerHTML = `
            El sistema generará: <span id="lote_preview" class="font-mono text-green-600">${this.lotePreview}</span>
            <small class="block text-xs text-gray-400 mt-1">Basado en: Año-Mes-Marca-Secuencial</small>
        `;
        }
    }

    // ================================
    // 7. NUEVOS MÉTODOS PARA SERIES
    // ================================

    /**
     * Contar series ingresadas
     */
    contarSeries(texto) {
        const series = texto.split('\n').filter(line => line.trim() !== '');
        const countElement = document.getElementById('series_count');

        if (countElement) {
            countElement.textContent = series.length;

            // Cambiar color según cantidad
            if (series.length > 0) {
                countElement.className = 'font-semibold text-green-600';
            } else {
                countElement.className = 'font-semibold text-gray-400';
            }
        }
    }

    // ================================
    // 8. NUEVOS MÉTODOS PARA PRECIOS
    // ================================

    /**
     * Calcular margen automáticamente
     */
    calcularMargen() {
        const costInput = document.getElementById('precio_costo');
        const ventaInput = document.getElementById('precio_venta');
        const ventaInputEmpresa = document.getElementById('precio_venta_empresa');
        const margenElement = document.getElementById('margen_calculado');
        const gananciaElement = document.getElementById('ganancia_calculada');
        const margenElementEmpresa = document.getElementById('margen_calculado_empresa');
        const gananciaElementEmpresa = document.getElementById('ganancia_calculada_empresa');

        if (!costInput || !ventaInput || !margenElement || !gananciaElement) return;

        const costo = parseFloat(costInput.value) || 0;
        const venta = parseFloat(ventaInput.value) || 0;
        const ventaEmpresa = parseFloat(ventaInputEmpresa.value) || 0;


        if (costo > 0 && venta > 0) {
            const ganancia = venta - costo;
            const margen = ((ganancia / costo) * 100);

            margenElement.textContent = `${margen.toFixed(1)}%`;
            gananciaElement.textContent = `Q${ganancia.toFixed(2)}`;

            // Colorear según el margen
            if (margen < 10) {
                margenElement.className = 'text-red-600 font-bold';
            } else if (margen < 25) {
                margenElement.className = 'text-yellow-600 font-bold';
            } else {
                margenElement.className = 'text-green-600 font-bold';
            }
        } else {
            margenElement.textContent = '0%';
            gananciaElement.textContent = 'Q0.00';
            margenElement.className = 'text-gray-400 font-bold';
        }


        if (costo > 0 && ventaEmpresa > 0) {
            const ganancia = ventaEmpresa - costo;
            const margen = ((ganancia / costo) * 100);

            margenElementEmpresa.textContent = `${margen.toFixed(1)}%`;
            gananciaElementEmpresa.textContent = `Q${ganancia.toFixed(2)}`;

            // Colorear según el margen
            if (margen < 10) {
                margenElementEmpresa.className = 'text-red-600 font-bold';
            } else if (margen < 25) {
                margenElementEmpresa.className = 'text-yellow-600 font-bold';
            } else {
                margenElementEmpresa.className = 'text-green-600 font-bold';
            }
        } else {
            margenElementEmpresa.textContent = '0%';
            gananciaElementEmpresa.textContent = 'Q0.00';
            margenElementEmpresa.className = 'text-gray-400 font-bold';
        }
    }

    // ================================
    //seleccionarProducto() 
    // ================================


    /**
     * Eliminar producto (con validaciones completas)
     */
    async eliminarProducto(productoId) {
        // Obtener información del producto primero
        const producto = this.productos.find(p => p.producto_id === productoId);
        if (!producto) {
            this.showAlert('error', 'Error', 'Producto no encontrado');
            return;
        }

        // Advertencia adicional si tiene stock
        const tieneStock = (producto.stock_cantidad_total || 0) > 0;
        const tieneSeries = producto.producto_requiere_serie;

        let mensajeAdvertencia = 'Esta acción eliminará el producto permanentemente.';
        if (tieneStock) {
            mensajeAdvertencia = `⚠️ ATENCIÓN: Este producto tiene ${producto.stock_cantidad_total} unidades en stock.`;
        }
        if (tieneSeries) {
            mensajeAdvertencia += ' También se verificarán las series registradas.';
        }

        const confirmacion = await Swal.fire({
            title: '¿Eliminar producto?',
            html: `
            <div class="text-left">
                <p class="font-medium text-gray-900 mb-2">${producto.producto_nombre}</p>
                <p class="text-sm text-gray-600 mb-3">SKU: ${producto.pro_codigo_sku}</p>
                <p class="text-sm text-orange-600">${mensajeAdvertencia}</p>
                <p class="text-xs text-gray-500 mt-2">No se puede deshacer esta acción.</p>
            </div>
        `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            width: '500px'
        });

        if (!confirmacion.isConfirmed) return;

        // Mostrar loading
        Swal.fire({
            title: 'Eliminando producto...',
            html: 'Verificando restricciones y eliminando datos asociados',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        try {
            const response = await fetch(`/inventario/productos/${productoId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                Swal.fire({
                    title: 'Producto eliminado',
                    text: data.message,
                    icon: 'success',
                    timer: 3000,
                    timerProgressBar: true
                });

                // Recargar datos
                await Promise.all([
                    this.loadProductos(),
                    this.loadStats(),
                    this.loadAlertas()
                ]);
            } else {
                Swal.fire({
                    title: 'No se puede eliminar',
                    text: data.message,
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error de conexión',
                text: 'No se pudo conectar con el servidor',
                icon: 'error',
                confirmButtonColor: '#dc2626'
            });
        }
    }
/**
 * Renderizar productos
 */renderProductos() {
        const container = document.getElementById('productos-list');
        const emptyState = document.getElementById('empty-state');
        const productosFiltrados = this.getProductosFiltrados();

        // Si no hay productos filtrados
        if (productosFiltrados.length === 0) {
            // Si literalmente no hay productos cargados (array vacío o null)
            if (!this.productos || this.productos.length === 0) {
                emptyState.style.display = 'block';
                container.innerHTML = '';
            } else {
                // Hay productos pero los filtros no muestran ninguno
                emptyState.style.display = 'none';
                container.innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-search text-gray-400 text-3xl mb-2"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron productos con los filtros aplicados</p>
                    <button onclick="inventarioManager.clearFilters()" 
                            class="mt-3 inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <i class="fas fa-eraser mr-2"></i>
                        Limpiar Filtros
                    </button>
                </div>
            `;
            }
            document.getElementById('productos-count').textContent = '0';
            return;
        }

        // Hay productos para mostrar
        emptyState.style.display = 'none';
        container.innerHTML = productosFiltrados.map(producto => this.renderProductoCard(producto)).join('');
        document.getElementById('productos-count').textContent = productosFiltrados.length;
    }
    /**
     * Renderizar card de producto
     */
    // Método renderProductoCard actualizado con foto principal y todas las acciones


    renderProductoCard(producto) {
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
                     </div>` :
                `<div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium text-sm">
                        ${iniciales}
                     </div>`
            }
            </div>

            <!-- Información del producto -->
            <div class="flex-1 min-w-0">
                <div class="font-medium text-gray-900 dark:text-gray-100">
                    ${producto.producto_nombre}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    SKU: ${producto.pro_codigo_sku} • ${producto.categoria_nombre || 'Sin categoría'} • ${producto.marca_nombre || 'Sin marca'}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Stock: ${stock} unidades${producto.producto_requiere_serie ? ' • Control por serie' : ''}
                </div>
            </div>

            <!-- Estado y Acciones -->
            <div class="flex items-center space-x-2">
                <!-- Estado del stock -->
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${stockClass}">
                    <i class="fas ${stockIcon} mr-1"></i>
                    ${stockText}
                </span>

                <!-- Botones de acciones -->
                <div class="flex space-x-1 ml-2">
                    <button onclick="inventarioManager.verDetalleProducto(${producto.producto_id})" 
                            class="p-1 text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900 rounded"
                            title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button onclick="inventarioManager.editarProducto(${producto.producto_id})" 
                            class="p-1 text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900 rounded"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="inventarioManager.openFotosModal(${producto.producto_id})" 
                            class="p-1 text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300 hover:bg-purple-100 dark:hover:bg-purple-900 rounded"
                            title="Gestionar fotos">
                        <i class="fas fa-camera"></i>
                    </button>
                    <button onclick="inventarioManager.eliminarProducto(${producto.producto_id})" 
                            class="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900 rounded"
                            title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button onclick="inventarioManager.ingresoRapido(${producto.producto_id})" 
                            class="p-1 text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-300 hover:bg-orange-100 dark:hover:bg-orange-900 rounded"
                            title="Ingreso rápido">
                        <i class="fas fa-plus-circle"></i>
                    </button>
                    <button onclick="inventarioManager.egresoRapido(${producto.producto_id})" 
                            class="p-1 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900 rounded"
                            title="Egreso rápido">
                        <i class="fas fa-minus-circle"></i>
                    </button>
                    <!-- Botón Gestión de Precios -->
                    <button onclick="inventarioManager.gestionarPrecios(${producto.producto_id})" 
                    class="p-1 text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 hover:bg-green-100 dark:hover:bg-green-900 rounded"
                            title="Gestionar precios">
                        <i class="fas fa-dollar-sign"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    }
    /**
     * Actualizar estadísticas en el dashboard
     */
    updateStats() {
        document.getElementById('total-productos').textContent = this.stats.totalProductos;
        document.getElementById('stock-total').textContent = this.stats.stockTotal;
        document.getElementById('stock-bajo').textContent = this.stats.stockBajo;
        document.getElementById('stock-agotado').textContent = this.stats.stockAgotado;
    }

    /**
     * Renderizar alertas
     */
    renderAlertas(alertas) {
        const container = document.getElementById('alertas-list');
        const badge = document.getElementById('alertas-badge');
        const count = document.getElementById('alertas-count');

        badge.textContent = alertas.length;
        count.textContent = alertas.length;

        if (alertas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6">
                    <i class="fas fa-check-circle text-green-400 text-2xl mb-2"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay alertas pendientes</p>
                </div>
            `;
            return;
        }

        container.innerHTML = alertas.slice(0, 5).map(alerta => `
            <div class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">
                        ${alerta.producto_nombre}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Stock: ${alerta.stock_actual}
                    </p>
                </div>
            </div>
        `).join('');
    }

    /**
     * Buscar productos para el modal de ingreso
     */
    async buscarProductos(query) {
        const container = document.getElementById('productos_encontrados');

        if (!query || query.length < 2) {
            container.classList.add('hidden');
            return;
        }

        try {
            const response = await fetch(`/inventario/buscar-productos?q=${encodeURIComponent(query)}`);
            if (response.ok) {
                const data = await response.json();
                this.renderResultadosBusqueda(data.data || []);
            }
        } catch (error) {
            console.error('Error buscando productos:', error);
        }
    }

    /**
     * Renderizar resultados de búsqueda
     */
    renderResultadosBusqueda(productos) {
        const container = document.getElementById('productos_encontrados');

        if (productos.length === 0) {
            container.innerHTML = `
                <div class="p-3 text-center text-gray-500 dark:text-gray-400">
                    No se encontraron productos
                </div>
            `;
            container.classList.remove('hidden');
            return;
        }

        container.innerHTML = productos.map(producto => `
            <div onclick="inventarioManager.seleccionarProducto(${producto.producto_id})" 
                 class="p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-600">
                <div class="font-medium text-gray-900 dark:text-gray-100">${producto.producto_nombre}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    SKU: ${producto.pro_codigo_sku} • Stock actual: ${producto.stock_cantidad_disponible || 0}
                </div>
            </div>
        `).join('');

        container.classList.remove('hidden');
    }

    /**
     * Seleccionar producto para ingreso
     */
    async seleccionarProducto(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}`);
            if (response.ok) {
                const data = await response.json();
                this.productoSeleccionado = data.data;

                // Ocultar step 1 y mostrar step 2
                document.getElementById('ingreso-step-1').classList.add('hidden');
                document.getElementById('ingreso-step-2').classList.remove('hidden');

                // Actualizar información del producto
                document.getElementById('producto_seleccionado_nombre').textContent = this.productoSeleccionado.producto_nombre;
                document.getElementById('producto_seleccionado_info').textContent =
                    `Stock actual: ${this.productoSeleccionado.stock_cantidad_disponible || 0} • SKU: ${this.productoSeleccionado.pro_codigo_sku}`;

                // Obtener elementos del DOM
                const cantidadSection = document.getElementById('cantidad_section');
                const seriesSection = document.getElementById('series_section');
                const loteSection = document.getElementById('lote_section');
                const movCantidadInput = document.getElementById('mov_cantidad');
                const numerosSeriesTextarea = document.getElementById('numeros_series');

                if (this.productoSeleccionado.producto_requiere_serie) {
                    // PRODUCTO CON SERIE
                    console.log('Producto requiere serie - configurando campos');

                    // Mostrar sección de series, ocultar cantidad y lotes
                    if (seriesSection) seriesSection.classList.remove('hidden');
                    if (cantidadSection) cantidadSection.classList.add('hidden');
                    if (loteSection) loteSection.classList.add('hidden');

                    // Configurar atributos required
                    if (movCantidadInput) {
                        movCantidadInput.removeAttribute('required');
                        movCantidadInput.value = '';
                        console.log('Removed required from mov_cantidad');
                    }
                    if (numerosSeriesTextarea) {
                        numerosSeriesTextarea.setAttribute('required', 'required');
                        console.log('Added required to numeros_series');
                    }

                } else {
                    // PRODUCTO SIN SERIE
                    console.log('Producto NO requiere serie - configurando campos');

                    // Mostrar sección de cantidad y lotes, ocultar series
                    if (cantidadSection) cantidadSection.classList.remove('hidden');
                    if (loteSection) loteSection.classList.remove('hidden');
                    if (seriesSection) seriesSection.classList.add('hidden');

                    // Configurar atributos required
                    if (movCantidadInput) {
                        movCantidadInput.setAttribute('required', 'required');
                        movCantidadInput.value = '1';
                        console.log('Added required to mov_cantidad');
                    }
                    if (numerosSeriesTextarea) {
                        numerosSeriesTextarea.removeAttribute('required');
                        numerosSeriesTextarea.value = '';
                        console.log('Removed required from numeros_series');
                    }

                    // Generar preview del lote
                    this.generarPreviewLote();
                }

                // Gestión de licencias (si aplica)
                const licenciaSection = document.getElementById('licencia_section');
                if (this.productoSeleccionado.requiere_licencia && licenciaSection) {
                    licenciaSection.classList.remove('hidden');
                } else if (licenciaSection) {
                    licenciaSection.classList.add('hidden');
                }

                // Ocultar resultados de búsqueda
                document.getElementById('productos_encontrados').classList.add('hidden');
            }
        } catch (error) {
            console.error('Error obteniendo detalle del producto:', error);
            this.showAlert('error', 'Error', 'Error al cargar el producto');
        }
    }

    /**
     * Abrir modal de registro de producto
     */
    openRegistroModal() {
        try {
            this.resetRegistroForm();
            this.showModal('registro');
        } catch (error) {
            console.error('Error opening Registro Modal:', error);
        }
    }

    /**
     * Abrir modal de ingreso a inventario
     */
    openIngresoModal() {
        try {
            this.showModal('ingreso');
        } catch (error) {
            console.error('Error opening Ingreso Modal:', error);
        }
    }



    /**
     * Ver historial de movimientos
     */


    /**
     * Generar reporte
     */
    generarReporte() {
        this.showAlert('info', 'Próximamente', 'Generación de reportes en desarrollo');
    }

    /**
     * Ingreso rápido a producto existente
     */
    ingresoRapido(productoId) {
        // Pre-seleccionar el producto y abrir modal de ingreso
        this.openIngresoModal();
        setTimeout(() => {
            this.seleccionarProducto(productoId);
        }, 100);
    }







    /**
     * Manejar envío del formulario de registro
     */

    async handleRegistroSubmit() {
        const form = document.getElementById('registro-form');
        const formData = new FormData(form);

        if (!this.validateRegistroForm()) {
            return;
        }

        const requiereStock = document.getElementById('producto_requiere_stock')?.checked;
        formData.set('producto_requiere_stock', requiereStock ? '0' : '1');
        // MANTENER: Manejo de precios en registro
        const agregaPrecios = document.getElementById('agregar_precios')?.checked;
        formData.set('agregar_precios', agregaPrecios ? '1' : '0');

        if (!agregaPrecios) {
            formData.delete('precio_costo');
            formData.delete('precio_venta');
            formData.delete('precio_especial');
            formData.delete('precio_moneda');
            formData.delete('precio_justificacion');
        }

        this.setLoading('registro', true);

        try {
            const response = await fetch('/inventario/productos', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.closeModal('registro');
                this.loadProductos();
                this.loadStats();
            } else {
                if (data.errors) {
                    this.showValidationErrors('registro', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al procesar la solicitud');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('registro', false);
        }
    }


    /**
     * Manejar envío del formulario de ingreso
     */
    async handleIngresoSubmit() {
        if (!this.productoSeleccionado) {
            this.showAlert('error', 'Error', 'Debe seleccionar un producto');
            return;
        }

        if (!this.validateIngresoForm()) {
            return;
        }

        const form = document.getElementById('ingreso-form');
        const formData = new FormData(form);

        formData.append('producto_id', this.productoSeleccionado.producto_id);

        // MANTENER: Lógica según tipo de producto
        if (this.productoSeleccionado.producto_requiere_serie) {
            formData.delete('mov_cantidad');
            formData.delete('usar_lotes');
            formData.delete('tipo_lote');
            formData.delete('numero_lote');
            formData.delete('lote_id');
        } else {
            formData.delete('numeros_series');

            // MANTENER: Lógica de lotes (SIEMPRE disponible)
            const usarLotes = document.getElementById('usar_lotes').checked;
            formData.set('usar_lotes', usarLotes ? '1' : '0');

            if (!usarLotes) {
                formData.delete('tipo_lote');
                formData.delete('numero_lote');
                formData.delete('lote_id');
            } else {
                const tipoLote = document.querySelector('input[name="tipo_lote"]:checked')?.value;
                formData.set('tipo_lote', tipoLote);

                switch (tipoLote) {
                    case 'manual':
                        formData.delete('lote_id');
                        break;
                    case 'automatico':
                        formData.delete('numero_lote');
                        formData.delete('lote_id');
                        break;
                    case 'buscar':
                        formData.delete('numero_lote');
                        break;
                }
            }
        }

        // MANTENER: Lógica de importación/licencias
        const esImportado = document.getElementById('producto_es_importado').checked;
        formData.set('producto_es_importado', esImportado ? '1' : '0');

        if (!esImportado) {
            formData.delete('licencia_id_registro');
            formData.delete('cantidad_licencia');
        }

        // QUITAR: Todos los campos de precios del ingreso
        formData.delete('agregar_precios');
        formData.delete('precio_costo');
        formData.delete('precio_venta');
        formData.delete('precio_especial');
        formData.delete('precio_moneda');
        formData.delete('precio_justificacion');

        this.setLoading('ingreso', true);

        try {
            const response = await fetch('/inventario/ingresar', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.closeModal('ingreso');

                await Promise.all([
                    this.loadProductos(),
                    this.loadStats(),
                    this.loadAlertas()
                ]);
            } else {
                if (data.errors) {
                    this.showValidationErrors('ingreso', data.errors);
                } else {
                    // Check for specific duplicate series error
                    if (data.message && data.message.includes('Una o más series ya existen')) {

                        // Play warning sound loop
                        let soundInterval;
                        const playWarningSound = () => {
                            try {
                                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                                const playBeep = () => {
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();

                                    osc.connect(gain);
                                    gain.connect(ctx.destination);

                                    osc.type = 'sawtooth';
                                    osc.frequency.value = 200; // Low frequency for "Error"

                                    gain.gain.setValueAtTime(0.5, ctx.currentTime);
                                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);

                                    osc.start();
                                    osc.stop(ctx.currentTime + 0.5);

                                    // Second beep
                                    setTimeout(() => {
                                        const osc2 = ctx.createOscillator();
                                        const gain2 = ctx.createGain();
                                        osc2.connect(gain2);
                                        gain2.connect(ctx.destination);
                                        osc2.type = 'sawtooth';
                                        osc2.frequency.value = 150;
                                        gain2.gain.setValueAtTime(0.5, ctx.currentTime);
                                        gain2.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.5);
                                        osc2.start();
                                        osc2.stop(ctx.currentTime + 0.5);
                                    }, 200);

                                };

                                playBeep(); // Play immediately
                                soundInterval = setInterval(playBeep, 1500); // Loop every 1.5 seconds

                            } catch (e) {
                                console.error('Audio play failed', e);
                            }
                        };

                        playWarningSound();

                        let detallesHtml = '';
                        if (data.detalles_duplicados && data.detalles_duplicados.length > 0) {
                            const rows = data.detalles_duplicados.map(d => `
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-bold text-gray-900 border-r">${d.serie}</td>
                                    <td class="px-4 py-2 text-gray-600 border-r">${d.fecha_ingreso}</td>
                                    <td class="px-4 py-2 text-gray-600 border-r">${d.usuario}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold ${d.estado === 'vendido' ? 'bg-blue-100 text-blue-800' :
                                    d.estado === 'disponible' ? 'bg-green-100 text-green-800' :
                                        d.estado === 'reservado' ? 'bg-yellow-100 text-yellow-800' :
                                            'bg-gray-100 text-gray-800'
                                }">
                                            ${d.estado ? d.estado.toUpperCase() : 'DESCONOCIDO'}
                                        </span>
                                    </td>
                                </tr>
                            `).join('');

                            detallesHtml = `
                                <div class="mt-6 mb-4 text-left">
                                    <p class="text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">🔍 RASTREO DE SERIES CONFLICTIVAS:</p>
                                    <div class="overflow-hidden rounded-lg border border-gray-300 shadow-sm">
                                        <table class="min-w-full text-sm text-left">
                                            <thead class="bg-gray-100 text-gray-700 font-bold uppercase text-xs">
                                                <tr>
                                                    <th class="px-4 py-2 border-r">Serie</th>
                                                    <th class="px-4 py-2 border-r">Fecha Ingreso</th>
                                                    <th class="px-4 py-2 border-r">Ingresado Por</th>
                                                    <th class="px-4 py-2">Estado Actual</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                ${rows}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            `;
                        }

                        Swal.fire({
                            title: '🚨 ¡ALERTA CRÍTICA DE SEGURIDAD! 🚨',
                            html: `
                                <style>
                                    @keyframes blink-critical {
                                        0% { opacity: 1; color: #ff0000; transform: scale(1); }
                                        50% { opacity: 0.5; color: #990000; transform: scale(1.1); }
                                        100% { opacity: 1; color: #ff0000; transform: scale(1); }
                                    }
                                    .critical-blink {
                                        animation: blink-critical 0.8s infinite;
                                        font-weight: 900;
                                        font-size: 1.8rem;
                                        text-transform: uppercase;
                                        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
                                        margin-bottom: 20px;
                                    }
                                    .weapon-check {
                                        background-color: #fee2e2;
                                        border: 4px solid #ef4444;
                                        padding: 15px;
                                        border-radius: 12px;
                                        margin: 20px 0;
                                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                                    }
                                    .physical-check {
                                        background-color: #fef3c7;
                                        border: 3px dashed #d97706;
                                        padding: 15px;
                                        border-radius: 8px;
                                        margin-top: 20px;
                                    }
                                </style>
                                <div class="text-left">
                                    <div class="critical-blink text-center">
                                        🛑 ¡DETENTE AHORA! 🛑
                                    </div>
                                    
                                    <div class="weapon-check text-center">
                                        <i class="fas fa-exclamation-circle text-5xl text-red-600 mb-3"></i>
                                        <p class="font-black text-2xl text-red-900 mb-2">
                                            DUPLICIDAD DE SERIE DETECTADA
                                        </p>
                                        <p class="text-red-800 font-bold text-lg">
                                            ESTA SERIE YA EXISTE EN EL SISTEMA
                                        </p>
                                    </div>

                                    ${detallesHtml}

                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
                                        <p class="font-bold mb-2">Posibles causas graves:</p>
                                        <ul class="list-none space-y-2 text-sm font-medium text-gray-700">
                                            <li class="flex items-center"><span class="text-2xl mr-2">❌</span> El arma ya fue vendida y no debería estar aquí.</li>
                                            <li class="flex items-center"><span class="text-2xl mr-2">❌</span> El arma ya tuvo un egreso manual previo.</li>
                                            <li class="flex items-center"><span class="text-2xl mr-2">❌</span> Estás intentando ingresar la misma arma dos veces.</li>
                                        </ul>
                                    </div>

                                    <div class="physical-check text-center">
                                        <p class="font-black text-xl mb-2 text-yellow-900">👀 REVISIÓN FÍSICA OBLIGATORIA</p>
                                        <p class="text-gray-900 font-bold text-lg">
                                            ¿TIENES EL <span class="text-red-600 underline decoration-4">ARMA FÍSICA</span> EN TUS MANOS?
                                        </p>
                                        <p class="text-sm mt-2 text-gray-600">
                                            Verifica el troquelado en el metal. No confíes solo en la etiqueta.
                                        </p>
                                    </div>
                                </div>
                            `,
                            icon: 'error',
                            iconColor: '#ff0000',
                            showCancelButton: false,
                            confirmButtonText: '🚨 HE VERIFICADO EL ARMA FÍSICA 🚨',
                            confirmButtonColor: '#dc2626',
                            width: '850px',
                            padding: '2.5em',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            backdrop: `
                                rgba(120,0,0,0.7)
                                left top
                                no-repeat
                            `,
                            willClose: () => {
                                if (soundInterval) {
                                    clearInterval(soundInterval);
                                }
                            }
                        });
                    } else {
                        this.showAlert('error', 'Error', data.message || 'Error al procesar la solicitud');
                    }
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('ingreso', false);
        }
    }

    /**
     * Validación manual sin depender de HTML5 - NUEVA FUNCIÓN
     */
    validateIngresoFormManual() {
        const tipo = document.getElementById('mov_tipo').value;
        const origen = document.getElementById('mov_origen').value.trim();

        this.clearErrors('ingreso');

        let isValid = true;

        // Validaciones básicas
        if (!tipo) {
            this.showFieldError('mov_tipo', 'El tipo de movimiento es obligatorio');
            isValid = false;
        }

        if (!origen) {
            this.showFieldError('mov_origen', 'El origen es obligatorio');
            isValid = false;
        }

        // Validación según tipo de producto
        if (this.productoSeleccionado.producto_requiere_serie) {
            // VALIDAR SERIES
            const series = document.getElementById('numeros_series').value.trim();
            if (!series) {
                this.showFieldError('numeros_series', 'Los números de serie son obligatorios');
                isValid = false;
            } else {
                const seriesArray = series.split('\n').filter(line => line.trim() !== '');
                if (seriesArray.length === 0) {
                    this.showFieldError('numeros_series', 'Debe ingresar al menos un número de serie válido');
                    isValid = false;
                }
            }
        } else {
            // VALIDAR CANTIDAD
            const cantidad = document.getElementById('mov_cantidad').value;
            if (!cantidad || parseInt(cantidad) <= 0) {
                this.showFieldError('mov_cantidad', 'La cantidad debe ser mayor a 0');
                isValid = false;
            }

            // VALIDAR LOTES (solo si está activado el checkbox)
            const usarLotes = document.getElementById('usar_lotes').checked;
            if (usarLotes) {
                const tipoLote = document.querySelector('input[name="tipo_lote"]:checked')?.value;

                if (!tipoLote) {
                    this.showFieldError('tipo_lote', 'Debe seleccionar un tipo de lote');
                    isValid = false;
                }

                switch (tipoLote) {
                    case 'manual':
                        const numeroLote = document.getElementById('numero_lote').value.trim();
                        if (!numeroLote) {
                            this.showFieldError('numero_lote', 'El número de lote es obligatorio');
                            isValid = false;
                        }
                        break;
                    case 'buscar':
                        const loteId = document.getElementById('lote_id').value;
                        if (!loteId) {
                            this.showFieldError('lote_id', 'Debe seleccionar un lote existente');
                            isValid = false;
                        }
                        break;
                    // 'automatico' no requiere validaciones adicionales
                }
            }
        }

        // Validación de producto importado
        const esImportado = document.getElementById('producto_es_importado')?.checked;
        if (esImportado) {
            const licenciaIdRegistro = document.getElementById('licencia_id_registro')?.value;
            const cantidadLicencia = document.getElementById('cantidad_licencia_registro')?.value;

            if (!licenciaIdRegistro) {
                this.showFieldError('licencia_id_registro', 'Debe seleccionar una licencia para productos importados');
                isValid = false;
            }

            if (!cantidadLicencia || parseInt(cantidadLicencia) <= 0) {
                this.showFieldError('cantidad_licencia_registro', 'La cantidad para la licencia debe ser mayor a 0');
                isValid = false;
            }
        }

        // Validación de precios si están habilitados
        const agregaPrecios = document.getElementById('agregar_precios')?.checked;
        if (agregaPrecios) {
            const precioCosto = document.getElementById('precio_costo').value;
            const precioVenta = document.getElementById('precio_venta').value;

            if (!precioCosto || parseFloat(precioCosto) <= 0) {
                this.showFieldError('precio_costo', 'El precio de costo es obligatorio');
                isValid = false;
            }

            if (!precioVenta || parseFloat(precioVenta) <= 0) {
                this.showFieldError('precio_venta', 'El precio de venta es obligatorio');
                isValid = false;
            }

            if (parseFloat(precioVenta) <= parseFloat(precioCosto)) {
                this.showFieldError('precio_venta', 'El precio de venta debe ser mayor al costo');
                isValid = false;
            }
        }

        return isValid;
    }



    // ================================
    //  Verificar si producto requiere licencia (OPCIONAL)
    // ================================
    async verificarRequiereLicencia(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/requiere-licencia`);
            if (response.ok) {
                const data = await response.json();
                return data.requiere_licencia;
            }
            return false;
        } catch (error) {
            console.error('Error verificando licencia:', error);
            return false;
        }
    }

    /**
     * Validar formulario de registro
     */
    /**
 * Validar formulario de registro - VERSIÓN ACTUALIZADA para dos precios
 */
    validateRegistroForm() {
        const nombre = document.getElementById('producto_nombre').value.trim();
        const categoria = document.getElementById('producto_categoria').value;
        const subcategoria = document.getElementById('producto_subcategoria').value;
        const marca = document.getElementById('producto_marca').value;

        this.clearErrors('registro');

        let isValid = true;

        // Validaciones básicas del producto
        if (!nombre) {
            this.showFieldError('producto_nombre', 'El nombre del producto es obligatorio');
            isValid = false;
        }

        if (!categoria) {
            this.showFieldError('producto_categoria_id', 'La categoría es obligatoria');
            isValid = false;
        }

        if (!subcategoria) {
            this.showFieldError('producto_subcategoria_id', 'La subcategoría es obligatoria');
            isValid = false;
        }

        if (!marca) {
            this.showFieldError('producto_marca_id', 'La marca es obligatoria');
            isValid = false;
        }

        // VALIDACIONES DE PRECIOS ACTUALIZADAS
        const agregaPrecios = document.getElementById('agregar_precios')?.checked;
        if (agregaPrecios) {
            const precioCosto = parseFloat(document.getElementById('precio_costo').value) || 0;
            const precioVenta = parseFloat(document.getElementById('precio_venta').value) || 0;
            const precioVentaEmpresa = parseFloat(document.getElementById('precio_venta_empresa').value) || 0;

            // Validación precio de costo
            if (precioCosto <= 0) {
                this.showFieldError('precio_costo', 'El precio de costo debe ser mayor a 0');
                isValid = false;
            }

            // Validación precio de venta individual
            if (precioVenta <= 0) {
                this.showFieldError('precio_venta', 'El precio de venta individual debe ser mayor a 0');
                isValid = false;
            } else if (precioVenta <= precioCosto) {
                this.showFieldError('precio_venta', 'El precio de venta individual debe ser mayor al costo');
                isValid = false;
            }

            // Validación precio de venta empresa
            if (precioVentaEmpresa <= 0) {
                this.showFieldError('precio_venta_empresa', 'El precio de venta empresa debe ser mayor a 0');
                isValid = false;
            } else if (precioVentaEmpresa <= precioCosto) {
                this.showFieldError('precio_venta_empresa', 'El precio de venta empresa debe ser mayor al costo');
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Validar formulario de ingreso
     */
    validateIngresoForm() {
        const tipo = document.getElementById('mov_tipo').value;
        const origen = document.getElementById('mov_origen').value.trim();

        this.clearErrors('ingreso');

        let isValid = true;

        // Validaciones básicas
        if (!tipo) {
            this.showFieldError('mov_tipo', 'El tipo de movimiento es obligatorio');
            isValid = false;
        }

        if (!origen) {
            this.showFieldError('mov_origen', 'El origen es obligatorio');
            isValid = false;
        }

        // Validación según tipo de producto
        if (this.productoSeleccionado.producto_requiere_serie) {
            const series = document.getElementById('numeros_series').value.trim();
            if (!series) {
                this.showFieldError('numeros_series', 'Los números de serie son obligatorios');
                isValid = false;
            } else {
                const seriesArray = series.split('\n').filter(line => line.trim() !== '');
                if (seriesArray.length === 0) {
                    this.showFieldError('numeros_series', 'Debe ingresar al menos un número de serie válido');
                    isValid = false;
                }
            }
        } else {
            const cantidad = document.getElementById('mov_cantidad').value;
            if (!cantidad || parseInt(cantidad) <= 0) {
                this.showFieldError('mov_cantidad', 'La cantidad debe ser mayor a 0');
                isValid = false;
            }

            // MANTENER: Validar lotes si están activados
            const usarLotes = document.getElementById('usar_lotes').checked;
            if (usarLotes) {
                const tipoLote = document.querySelector('input[name="tipo_lote"]:checked')?.value;

                if (!tipoLote) {
                    this.showFieldError('tipo_lote', 'Debe seleccionar un tipo de lote');
                    isValid = false;
                }

                switch (tipoLote) {
                    case 'manual':
                        const numeroLote = document.getElementById('numero_lote').value.trim();
                        if (!numeroLote) {
                            this.showFieldError('numero_lote', 'El número de lote es obligatorio');
                            isValid = false;
                        }
                        break;
                    case 'buscar':
                        const loteId = document.getElementById('lote_id').value;
                        if (!loteId) {
                            this.showFieldError('lote_id', 'Debe seleccionar un lote existente');
                            isValid = false;
                        }
                        break;
                }
            }
        }

        // MANTENER: Validación de importación/licencias
        const esImportado = document.getElementById('producto_es_importado')?.checked;
        if (esImportado) {
            const licenciaIdRegistro = document.getElementById('licencia_id_registro')?.value;
            const cantidadLicencia = document.getElementById('cantidad_licencia_registro')?.value;

            if (!licenciaIdRegistro) {
                this.showFieldError('licencia_id_registro', 'Debe seleccionar una licencia para productos importados');
                isValid = false;
            }

            if (!cantidadLicencia || parseInt(cantidadLicencia) <= 0) {
                this.showFieldError('cantidad_licencia_registro', 'La cantidad para la licencia debe ser mayor a 0');
                isValid = false;
            }
        }

        // QUITAR: Sin validación de precios en ingreso

        return isValid;
    }

    /**
     * MANTENER: calcularMargen() para formulario de registro
     */




    /**
     * Mostrar modal
     */
    showModal(type) {

        try {
            const modal = document.getElementById(`${type}-modal`);
            if (!modal) {
                console.error(`Modal ${type}-modal not found!`);
                return;
            }
            modal.classList.remove('hidden');

            document.body.style.overflow = 'hidden';
        } catch (error) {
            console.error(`Error showing modal ${type}:`, error);
        }
    }

    /**
     * Cerrar modal
     */
    closeModal(type) {
        const modal = document.getElementById(`${type}-modal`);
        if (modal) {
            modal.classList.add('hidden');
        }
        document.body.style.overflow = 'auto';

        this.setLoading(type, false);
        this.clearErrors(type);

        if (type === 'registro') {
            this.resetRegistroForm();
        } else if (type === 'ingreso') {
            this.resetIngresoForm();
        } else if (type === 'editar') {
            this.resetEditarForm();
        } else if (type === 'detalle') {
            // Limpiar datos del detalle
            this.currentProductoId = null;
        } else if (type === 'fotos') {
            // Limpiar datos de fotos
            this.currentProductoId = null;
        }
    }
    /**
     * Resetear formulario de registro
     */
    /**
     * Resetear formulario de registro - VERSIÓN ACTUALIZADA
     */
    resetRegistroForm() {
        document.getElementById('registro-form').reset();
        this.clearErrors('registro');

        // Resetear selects a su estado inicial
        document.getElementById('producto_subcategoria').innerHTML = '<option value="">Seleccionar subcategoría</option>';
        document.getElementById('producto_modelo').innerHTML = '<option value="">Seleccionar modelo</option>';

        // Limpiar secciones opcionales
        const seccionFotos = document.getElementById('seccion_fotos');
        const seccionPrecios = document.getElementById('seccion_precios');

        if (seccionFotos) {
            seccionFotos.classList.add('hidden');
        }

        if (seccionPrecios) {
            seccionPrecios.classList.add('hidden');
        }

        // Resetear checkboxes
        const checkboxFotos = document.getElementById('agregar_fotos');
        const checkboxPrecios = document.getElementById('agregar_precios');

        if (checkboxFotos) {
            checkboxFotos.checked = false;
        }

        if (checkboxPrecios) {
            checkboxPrecios.checked = false;
        }

        // Limpiar preview de fotos
        this.limpiarPreviewFotos();

        // Limpiar displays de precios de manera segura
        const preciosDisplays = [
            { id: 'margen_calculado', defaultText: '0%', className: 'text-gray-400 font-bold' },
            { id: 'ganancia_calculada', defaultText: 'Q0.00' },
            { id: 'margen_calculado_empresa', defaultText: '0%', className: 'text-gray-400 font-bold' },
            { id: 'ganancia_calculada_empresa', defaultText: 'Q0.00' }
        ];

        preciosDisplays.forEach(({ id, defaultText, className }) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = defaultText;
                if (className) {
                    element.className = className;
                }
            }
        });
    }

    /**
     * Resetear formulario de ingreso
     */
    resetIngresoForm() {
        // Resetear el formulario HTML
        document.getElementById('ingreso-form').reset();
        this.clearErrors('ingreso');

        // Resetear estado visual del modal
        document.getElementById('ingreso-step-1').classList.remove('hidden');
        document.getElementById('ingreso-step-2').classList.add('hidden');
        document.getElementById('productos_encontrados').classList.add('hidden');

        // Configurar estado inicial de campos
        const movCantidadInput = document.getElementById('mov_cantidad');
        const numerosSeriesTextarea = document.getElementById('numeros_series');
        const cantidadSection = document.getElementById('cantidad_section');
        const seriesSection = document.getElementById('series_section');
        const loteSection = document.getElementById('lote_section');
        const importadoSection = document.getElementById('contenedor_importacion');

        // Estado inicial: mostrar cantidad, ocultar series, lotes e importación
        if (cantidadSection) cantidadSection.classList.remove('hidden');
        if (seriesSection) seriesSection.classList.add('hidden');
        if (loteSection) loteSection.classList.remove('hidden');
        if (importadoSection) importadoSection.classList.add('hidden'); // 👈 OCULTAR POR DEFECTO

        // Estado inicial de required attributes
        if (movCantidadInput) {
            movCantidadInput.removeAttribute('required');
            movCantidadInput.value = '';
        }
        if (numerosSeriesTextarea) {
            numerosSeriesTextarea.removeAttribute('required');
            numerosSeriesTextarea.value = '';
        }

        // Resetear todas las secciones opcionales
        const seccionLicenciaRegistro = document.getElementById('seccion_licencia_registro');
        const seccionPrecios = document.getElementById('seccion_precios');
        const opcionesLote = document.getElementById('opciones_lote');

        if (seccionLicenciaRegistro) seccionLicenciaRegistro.classList.add('hidden');
        if (seccionPrecios) seccionPrecios.classList.add('hidden');
        if (opcionesLote) opcionesLote.classList.add('hidden');

        // Resetear checkboxes
        const checkboxImportado = document.getElementById('producto_es_importado');
        const checkboxPrecios = document.getElementById('agregar_precios');
        const checkboxUsarLotes = document.getElementById('usar_lotes');

        if (checkboxImportado) checkboxImportado.checked = false;
        if (checkboxPrecios) checkboxPrecios.checked = false;
        if (checkboxUsarLotes) checkboxUsarLotes.checked = false;

        // Limpiar configuración de lotes
        this.limpiarConfiguracionLotes();

        // Resetear licencias seleccionadas
        this.limpiarLicenciaSeleccionada();
        this.limpiarLicenciaSeleccionadaRegistro();

        // Resetear estado interno
        this.productoSeleccionado = null;
        this.licenciaSeleccionada = null;
        this.licenciaSeleccionadaRegistro = null;
        this.lotePreview = '';

        console.log('Formulario de ingreso reseteado correctamente');
    }


    // ================================
    //  Validación adicional para formulario con licencias
    // ================================
    validateLicenciaSeleccionada() {
        if (!this.productoSeleccionado?.requiere_licencia) {
            return true; // No requiere validación
        }

        const licenciaId = document.getElementById('licencia_id')?.value;

        if (!licenciaId) {
            this.showAlert('error', 'Error', 'Debe seleccionar una licencia para este producto');
            return false;
        }

        // Verificar que la licencia no esté vencida
        if (this.licenciaSeleccionada?.lipaimp_fecha_vencimiento) {
            const fechaVencimiento = new Date(this.licenciaSeleccionada.lipaimp_fecha_vencimiento);
            const hoy = new Date();

            if (fechaVencimiento < hoy) {
                this.showAlert('error', 'Error', 'La licencia seleccionada está vencida');
                return false;
            }
        }

        return true;
    }


    /*
    /** 
    *///aquí empiezan los vergazos;  las acciones desde aquí hasta donde allá otro comentario como este para no planchar

    ///detalle de producto modal y sus funciones 


    // Agregar estos métodos a tu InventarioManager

    /**
     * Ver detalle completo de un producto
     */
    async verDetalleProducto(productoId) {
        this.currentProductoId = productoId;

        try {
            // Cargar datos del producto
            const response = await fetch(`/inventario/productos/${productoId}/detalle`);
            if (response.ok) {
                const data = await response.json();

                // AGREGAR: Guardar producto seleccionado para usar en renderMovimientosRecientes
                this.productoSeleccionado = data.data;

                this.renderDetalleProducto(data.data);
                this.showModal('detalle');

                // Cargar datos adicionales
                this.loadMovimientosRecientes(productoId);
                if (data.data.producto_requiere_serie) {
                    this.loadSeriesProducto(productoId);
                }
            } else {
                this.showAlert('error', 'Error', 'No se pudo cargar el detalle del producto');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Renderizar datos del producto en el modal de detalle
     */
    renderDetalleProducto(producto) {
        // Información básica
        document.getElementById('detalle_producto_nombre').textContent = producto.producto_nombre;
        document.getElementById('detalle_producto_sku').textContent = `SKU: ${producto.pro_codigo_sku}`;

        // Información general
        document.getElementById('detalle_categoria').textContent = producto.categoria_nombre || '-';
        document.getElementById('detalle_subcategoria').textContent = producto.subcategoria_nombre || '-';
        document.getElementById('detalle_marca').textContent = producto.marca_nombre || '-';
        document.getElementById('detalle_modelo').textContent = producto.modelo_nombre || '-';
        document.getElementById('detalle_calibre').textContent = producto.calibre_nombre || '-';
        document.getElementById('detalle_pais').textContent = producto.pais_nombre || '-';
        document.getElementById('detalle_codigo_barra').textContent = producto.producto_codigo_barra || '-';

        // Requiere serie
        const requiereSerieElement = document.getElementById('detalle_requiere_serie');
        if (producto.producto_requiere_serie) {
            requiereSerieElement.innerHTML = `
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                <i class="fas fa-check mr-1"></i>
                Sí
            </span>
        `;
            // Mostrar sección de series
            document.getElementById('detalle_series_container').classList.remove('hidden');
        } else {
            requiereSerieElement.innerHTML = `
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                <i class="fas fa-times mr-1"></i>
                No
            </span>
        `;
            // Ocultar sección de series
            document.getElementById('detalle_series_container').classList.add('hidden');
        }

        // Descripción
        const descripcionContainer = document.getElementById('detalle_descripcion_container');
        const descripcionElement = document.getElementById('detalle_descripcion');
        if (producto.producto_descripcion && producto.producto_descripcion.trim()) {
            descripcionElement.textContent = producto.producto_descripcion;
            descripcionContainer.classList.remove('hidden');
        } else {
            descripcionContainer.classList.add('hidden');
        }



        // ✅ Acceder a stock_actual correctamente
        const stockActual = producto.stock_actual || {};

        // Stock
        // FIX: Usar valores calculados del controlador (producto.*) en lugar de los raw de la BD (stockActual.*)
        document.getElementById('detalle_stock_total').textContent = producto.stock_cantidad_total || 0;
        document.getElementById('detalle_stock_disponible').textContent = producto.stock_cantidad_disponible || 0;
        document.getElementById('detalle_stock_venta_pendiente').textContent = producto.stock_cantidad_reservada || 0;
        document.getElementById('detalle_stock_reservado').textContent = producto.stock_cantidad_reservada2 || 0;
        document.getElementById('detalle_stock_minimo').textContent = producto.producto_stock_minimo || 0;

        // Foto principal
        this.renderFotoPrincipal(producto.foto_principal);

        // Precios
        this.renderPreciosDetalle(producto.precios || []);
    }

    /**
     * Renderizar foto principal
     */
    renderFotoPrincipal(fotoUrl) {
        const container = document.getElementById('detalle_foto_principal');

        if (fotoUrl) {
            container.innerHTML = `
            <img src="${fotoUrl}" 
                 alt="Foto del producto"
                 class="w-full h-48 object-cover rounded-lg border border-gray-300 dark:border-gray-600">
        `;
        } else {
            container.innerHTML = `
            <div class="w-full h-48 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-image text-gray-400 text-3xl mb-2"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin foto</p>
                </div>
            </div>
        `;
        }
    }

    /**
     * Renderizar precios en el detalle
     */
    renderPreciosDetalle(precios) {
        const container = document.getElementById('detalle_precios_container');

        if (precios.length === 0) {
            container.innerHTML = `
            <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                Sin precios registrados
            </div>
        `;
            return;
        }

        const precioActual = precios[0]; // Asumiendo que el primero es el actual

        container.innerHTML = `
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Costo:</span>
                <span class="font-medium text-gray-900 dark:text-gray-100">Q${precioActual.precio_costo}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Venta (Individual):</span>
                <span class="font-medium text-green-600">Q${precioActual.precio_venta}</span>
            </div>
            ${precioActual.precio_venta_empresa ? `
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Venta (Empresa):</span>
                    <span class="font-medium text-blue-600">Q${precioActual.precio_venta_empresa}</span>
                </div>
            ` : ''}
            ${precioActual.precio_especial ? `
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Especial:</span>
                    <span class="font-medium text-orange-600">Q${precioActual.precio_especial}</span>
                </div>
            ` : ''}
            <hr class="border-gray-300 dark:border-gray-600">
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Margen (Individual):</span>
                <span class="font-medium text-purple-600">${precioActual.precio_margen || 0}%</span>
            </div>
            ${precioActual.precio_margen_empresa ? `
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Margen (Empresa):</span>
                    <span class="font-medium text-indigo-600">${precioActual.precio_margen_empresa}%</span>
                </div>
            ` : ''}
        </div>
    `;
    }

    /**
     * Cargar movimientos recientes del producto
     */
    /**
     * Cargar movimientos recientes del producto (solo 5 para vista previa)
     */
    async loadMovimientosRecientes(productoId) {
        try {
            // SIN LÍMITE - traer todos y agrupar
            const response = await fetch(`/inventario/productos/${productoId}/movimientos`);
            if (response.ok) {
                const data = await response.json();

                // Agrupar todos los movimientos
                const movimientosAgrupados = this.agruparMovimientosPorIngreso(
                    data.data || [],
                    this.productoSeleccionado?.producto_requiere_serie
                );

                // Mostrar solo los primeros 5 grupos más recientes
                this.renderMovimientosRecientes(movimientosAgrupados.slice(0, 5));
            }
        } catch (error) {
            console.error('Error cargando movimientos:', error);
            document.getElementById('detalle_movimientos_container').innerHTML = `
            <div class="text-center text-red-500 dark:text-red-400 py-4 text-sm">
                Error al cargar movimientos
            </div>
        `;
        }
    }

    /**
     * Renderizar movimientos recientes
     */
    /**
     * Renderizar movimientos recientes - CON AGRUPACIÓN
     */
    /**
     * Renderizar movimientos recientes - VERSIÓN CORREGIDA
     */
    renderMovimientosRecientes(movimientosAgrupados) {
        const container = document.getElementById('detalle_movimientos_container');

        if (!movimientosAgrupados || movimientosAgrupados.length === 0) {
            container.innerHTML = `
            <div class="text-center text-gray-500 dark:text-gray-400 py-4 text-sm">
                Sin movimientos registrados
            </div>
        `;
            return;
        }

        container.innerHTML = movimientosAgrupados.map(mov => {
            const tipoClass = (mov.mov_tipo || '').toLowerCase().includes('ingreso') ? 'text-green-600' : 'text-red-600';
            const tipoIcon = (mov.mov_tipo || '').toLowerCase().includes('ingreso') ? 'fa-arrow-up' : 'fa-arrow-down';

            return `
            <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                <div class="flex items-center space-x-3">
                    <i class="fas ${tipoIcon} ${tipoClass}"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">${mov.mov_tipo}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">${mov.mov_origen || mov.mov_destino || 'N/A'}</p>
                        ${mov.detalle_series ? `
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                <i class="fas fa-barcode mr-1"></i>${mov.detalle_series}
                            </p>
                        ` : ''}
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium ${tipoClass}">
                        ${mov.cantidad_total > 0 ? '+' : ''}${mov.cantidad_total}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">${new Date(mov.mov_fecha).toLocaleDateString()}</p>
                </div>
            </div>
        `;
        }).join('') + `
        <div class="text-center pt-3">
            <button onclick="inventarioManager.verHistorialCompleto(${this.currentProductoId})"
                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                Ver historial completo →
            </button>
        </div>
    `;
    }

    /**
     * Ver historial completo de movimientos de un producto
     */
    /**
     * Ver historial completo de movimientos de un producto
     */
    async verHistorialCompleto(productoId) {
        try {
            // QUITAR el límite - traer TODOS los movimientos
            const response = await fetch(`/inventario/productos/${productoId}/movimientos`);
            if (response.ok) {
                const data = await response.json();
                const movimientos = data.data || [];

                (`Total de movimientos sin filtrar: ${movimientos.length}`); // DEBUG

                // Obtener información del producto
                const productoResponse = await fetch(`/inventario/productos/${productoId}`);
                const productoData = await productoResponse.json();
                const producto = productoData.data;

                this.mostrarModalHistorial(producto, movimientos);
            } else {
                this.showAlert('error', 'Error', 'No se pudo cargar el historial');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }


    /**
     * Mostrar modal con historial completo - VERSIÓN CORREGIDA PARA SERIES
     */
    mostrarModalHistorial(producto, movimientos) {
        // CORRECCIÓN: Agrupar movimientos por fecha/origen para mostrar totales
        const movimientosAgrupados = this.agruparMovimientosPorIngreso(movimientos, producto.producto_requiere_serie);

        // Calcular estadísticas sobre movimientos agrupados
        const ingresos = movimientosAgrupados.filter(m => m.mov_tipo.toLowerCase() === 'ingreso');
        const egresos = movimientosAgrupados.filter(m => m.mov_tipo.toLowerCase() === 'egreso');

        const totalIngresos = ingresos.length;
        const totalEgresos = egresos.length;

        const cantidadIngresada = ingresos.reduce((sum, m) => sum + parseInt(m.cantidad_total), 0);
        const cantidadEgresada = egresos.reduce((sum, m) => sum + Math.abs(parseInt(m.cantidad_total)), 0);

        const contenidoHTML = `
        <div class="text-left">
            <!-- Header con info del producto -->
            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                    ${producto.producto_nombre}
                </h4>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    SKU: ${producto.pro_codigo_sku}
                    ${producto.producto_requiere_serie ? ' • <span class="text-blue-600 font-medium">Control por Serie</span>' : ''}
                </div>
            </div>
            
            <!-- Resumen de movimientos -->
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-green-700 dark:text-green-300">Ingresos</span>
                        <i class="fas fa-arrow-up text-green-600"></i>
                    </div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">${totalIngresos}</div>
                    <div class="text-xs text-green-700 dark:text-green-300">+${cantidadIngresada} ${producto.producto_requiere_serie ? 'series' : 'unidades'}</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-red-700 dark:text-red-300">Egresos</span>
                        <i class="fas fa-arrow-down text-red-600"></i>
                    </div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">${totalEgresos}</div>
                    <div class="text-xs text-red-700 dark:text-red-300">-${cantidadEgresada} ${producto.producto_requiere_serie ? 'series' : 'unidades'}</div>
                </div>
            </div>
            
            <!-- Lista de movimientos -->
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                    <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <div class="col-span-2">Fecha</div>
                        <div class="col-span-1">Tipo</div>
                        <div class="col-span-1 text-center">Cant.</div>
                        <div class="col-span-3">Origen/Destino</div>
                        <div class="col-span-3">Observaciones</div>
                        <div class="col-span-2">Usuario</div>
                    </div>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    ${movimientosAgrupados.length === 0 ? `
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2"></i>
                            <p class="text-sm">Sin movimientos registrados</p>
                        </div>
                    ` : movimientosAgrupados.map(mov => {
            const esIngreso = (mov.mov_tipo.toLowerCase() === 'ingreso');
            const tipoClass = esIngreso ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
            const tipoIcon = esIngreso ? 'fa-arrow-up' : 'fa-arrow-down';
            const tipoBg = esIngreso ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900';
            const fecha = new Date(mov.mov_fecha);

            let origenDestino = '';
            if (esIngreso) {
                origenDestino = mov.mov_origen || 'No especificado';
            } else {
                origenDestino = mov.mov_destino || 'No especificado';
            }

            return `
                            <div class="grid grid-cols-12 gap-2 px-4 py-3 text-xs border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <!-- Fecha -->
                                <div class="col-span-2 text-gray-900 dark:text-gray-100">
                                    <div class="font-medium">${fecha.toLocaleDateString('es-GT')}</div>
                                    <div class="text-gray-500 dark:text-gray-400 text-xs">${fecha.toLocaleTimeString('es-GT', { hour: '2-digit', minute: '2-digit' })}</div>
                                </div>
                                
                                <!-- Tipo -->
                                <div class="col-span-1">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${tipoBg} ${tipoClass}">
                                        <i class="fas ${tipoIcon} mr-1"></i>
                                        ${mov.mov_tipo}
                                    </span>
                                </div>
                                
                                <!-- Cantidad CORREGIDA -->
                                <div class="col-span-1 text-center">
                                    <div class="font-medium ${tipoClass}">
                                        ${esIngreso ? '+' : '-'}${mov.cantidad_total}
                                    </div>
                                    ${mov.detalle_series ? `
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            ${mov.detalle_series}
                                        </div>
                                    ` : ''}
                                </div>
                                
                                <!-- Origen/Destino -->
                                <div class="col-span-3 text-gray-700 dark:text-gray-300">
                                    <div class="truncate font-medium" title="${origenDestino}">
                                        ${origenDestino}
                                    </div>
                                </div>
                                
                                <!-- Observaciones -->
                                <div class="col-span-3 text-gray-600 dark:text-gray-400">
                                    ${mov.mov_observaciones ? `
                                        <div class="text-xs truncate" title="${mov.mov_observaciones}">
                                            ${mov.mov_observaciones}
                                        </div>
                                    ` : '<span class="text-gray-400">-</span>'}
                                </div>
                                
                                <!-- Usuario -->
                                <div class="col-span-2 text-gray-600 dark:text-gray-400 truncate" title="${mov.usuario_nombre || 'Sistema'}">
                                    ${mov.usuario_nombre || 'Sistema'}
                                </div>
                            </div>
                        `;
        }).join('')}
                </div>
            </div>
            
            <!-- Footer con total -->
            <div class="mt-3 text-center text-sm text-gray-600 dark:text-gray-400">
                Total: <strong>${movimientosAgrupados.length}</strong> movimientos registrados
            </div>
        </div>
    `;

        Swal.fire({
            title: `Historial de Movimientos (${movimientosAgrupados.length})`,
            html: contenidoHTML,
            width: '1000px',
            showCloseButton: true,
            showConfirmButton: true,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3b82f6',
            footer: `
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Stock actual: <strong class="text-blue-600">${producto.stock_cantidad_disponible || 0}</strong> ${producto.producto_requiere_serie ? 'series' : 'unidades'} disponibles
            </div>
        `,
            customClass: {
                popup: 'dark:bg-gray-800',
                title: 'dark:text-gray-100',
                htmlContainer: 'dark:text-gray-300',
                footer: 'dark:bg-gray-700 dark:border-gray-600'
            }
        });
    }
    /**
     * Agrupar movimientos por ingreso/egreso - CORREGIDO CANTIDAD TOTAL
     */
    agruparMovimientosPorIngreso(movimientos, requiereSerie) {
        if (!requiereSerie) {
            // Si no requiere serie, devolver movimientos tal cual
            return movimientos.map(mov => ({
                ...mov,
                cantidad_total: Math.abs(parseInt(mov.mov_cantidad) || 0),
                detalle_series: null
            }));
        }

        // Para productos con serie, agrupar por fecha completa + origen + usuario
        const grupos = {};

        movimientos.forEach(mov => {
            const obsBase = (mov.mov_observaciones || '').split(' - Serie:')[0].trim();
            const fechaCompleta = mov.mov_fecha;
            const origen = mov.mov_origen || mov.mov_destino || '';
            const clave = `${fechaCompleta}_${mov.mov_tipo}_${origen}_${mov.mov_usuario_id || ''}`;

            if (!grupos[clave]) {
                grupos[clave] = {
                    ...mov,
                    cantidad_total: 0,  // ← Iniciar en 0
                    series: [],
                    mov_observaciones: obsBase
                };
            }

            // ✅ CORRECCIÓN: Sumar la cantidad de cada movimiento (siempre 1 para series, pero sumarlos)
            grupos[clave].cantidad_total += Math.abs(parseInt(mov.mov_cantidad) || 1);

            // Extraer número de serie
            const serieMatch = (mov.mov_observaciones || '').match(/Serie:\s*([^\s,]+)/);
            if (serieMatch) {
                grupos[clave].series.push(serieMatch[1]);
            }
        });

        return Object.values(grupos)
            .map(grupo => ({
                ...grupo,
                detalle_series: grupo.series.length > 0 ? `${grupo.series.length} serie(s)` : null
            }))
            .sort((a, b) => new Date(b.mov_fecha) - new Date(a.mov_fecha));
    }
    /**
     * Cargar series del producto (si requiere serie)
     */
    async loadSeriesProducto(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/series`);
            if (response.ok) {
                const data = await response.json();
                this.renderSeriesProducto(data.data || []);
            }
        } catch (error) {
            console.error('Error cargando series:', error);
        }
    }

    /**
     * Renderizar series del producto
     */
    renderSeriesProducto(series) {
        const countElement = document.getElementById('detalle_series_count');
        const listaElement = document.getElementById('detalle_series_lista');

        countElement.textContent = `${series.length} series`;

        // Calcular contadores
        const disponibles = series.filter(s => s.serie_estado === 'disponible').length;
        const reservadas = series.filter(s => s.serie_estado === 'reservado' || s.serie_estado === 'reserva').length;
        const pendientes = series.filter(s => s.serie_estado === 'pendiente').length;
        const vendidas = series.filter(s => s.serie_estado === 'vendido').length;

        // Actualizar resumen (Chips UI)
        const resumenContainer = document.getElementById('series_resumen_container');
        if (resumenContainer) {
            resumenContainer.innerHTML = `
                <div class="flex flex-wrap gap-2 mb-4 justify-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border border-green-200 dark:border-green-700" title="Disponibles">
                        D: ${disponibles}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-200 dark:border-orange-700" title="Pendientes">
                        P: ${pendientes}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700" title="Reservadas">
                        R: ${reservadas}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 border border-red-200 dark:border-red-700" title="Vendidas">
                        V: ${vendidas}
                    </span>
                </div>
            `;
        }

        // Sincronizar Stock Actual con Series (si hay series)
        if (series.length > 0) {
            // Actualizar contadores de Stock Actual para coincidir con las series
            const stockTotalEl = document.getElementById('detalle_stock_total');
            const stockDispEl = document.getElementById('detalle_stock_disponible');
            const stockPendEl = document.getElementById('detalle_stock_venta_pendiente');
            const stockResEl = document.getElementById('detalle_stock_reservado');

            if (stockTotalEl) stockTotalEl.textContent = disponibles + pendientes + reservadas; // Total activo (sin vendidas)
            if (stockDispEl) stockDispEl.textContent = disponibles;
            if (stockPendEl) stockPendEl.textContent = pendientes;
            if (stockResEl) stockResEl.textContent = reservadas;
        }

        if (series.length === 0) {
            listaElement.innerHTML = `
            <div class="text-center text-gray-500 dark:text-gray-400 py-4 text-sm">
                Sin series registradas
            </div>
        `;
            return;
        }

        listaElement.innerHTML = series.slice(0, 10).map(serie => {
            const estadoClass = {
                'disponible': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'reservado': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'pendiente': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                'vendido': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            };

            return `
            <div class="flex items-center justify-between py-1">
                <span class="text-xs font-mono text-gray-700 dark:text-gray-300">${serie.serie_numero_serie}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${estadoClass[serie.serie_estado] || 'bg-gray-100 text-gray-800'}">
                    ${serie.serie_estado}
                </span>
            </div>
        `;
        }).join('');

        if (series.length > 10) {
            listaElement.innerHTML += `
            <div class="text-center pt-2">
                <button onclick="inventarioManager.verTodasLasSeries(${this.currentProductoId})"
                        class="text-blue-600 hover:text-blue-800 text-xs">
                    Ver todas las ${series.length} series
                </button>
            </div>
        `;
        }
    }



    /**
     * Ver todas las series de un producto en un modal
     */
    async verTodasLasSeries(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/series`);
            if (response.ok) {
                const data = await response.json();
                const series = data.data || [];

                // Obtener información del producto
                const productoResponse = await fetch(`/inventario/productos/${productoId}`);
                const productoData = await productoResponse.json();
                const producto = productoData.data;

                this.mostrarModalSeries(producto, series);
            } else {
                this.showAlert('error', 'Error', 'No se pudieron cargar las series');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Mostrar modal con todas las series
     */
    mostrarModalSeries(producto, series) {
        // Contar series por estado
        const disponibles = series.filter(s => s.serie_estado === 'disponible').length;
        const reservadas = series.filter(s => s.serie_estado === 'reservado' || s.serie_estado === 'reserva').length;
        const pendientes = series.filter(s => s.serie_estado === 'pendiente').length;
        const vendidas = series.filter(s => s.serie_estado === 'vendido').length;

        const contenidoHTML = `
        <div class="text-left">
            <!-- Header con info del producto -->
            <div class="bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-4">
                <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">
                    ${producto.producto_nombre}
                </h4>
                <div class="text-sm text-blue-700 dark:text-blue-300">
                    SKU: ${producto.pro_codigo_sku}
                </div>
            </div>
            
            <!-- Resumen de estados (Chips UI) -->
            <div class="flex flex-wrap gap-2 mb-4 justify-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border border-green-200 dark:border-green-700" title="Disponibles">
                    D: ${disponibles}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200 border border-orange-200 dark:border-orange-700" title="Pendientes">
                    P: ${pendientes}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border border-yellow-200 dark:border-yellow-700" title="Reservadas">
                    R: ${reservadas}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 border border-red-200 dark:border-red-700" title="Vendidas">
                    V: ${vendidas}
                </span>
            </div>
            
            <!-- Lista de series -->
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                    <div class="grid grid-cols-12 gap-2 text-xs font-medium text-gray-700 dark:text-gray-300">
                        <div class="col-span-1">#</div>
                        <div class="col-span-6">Número de Serie</div>
                        <div class="col-span-2">Estado</div>
                        <div class="col-span-3">Fecha Ingreso</div>
                    </div>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    ${series.map((serie, index) => {
            const estadoClass = {
                'disponible': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                'reservado': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                'vendido': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
            };

            return `
                            <div class="grid grid-cols-12 gap-2 px-4 py-2 text-xs border-b border-gray-100 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <div class="col-span-1 text-gray-500 dark:text-gray-400">${index + 1}</div>
                                <div class="col-span-6 font-mono text-gray-900 dark:text-gray-100">${serie.serie_numero_serie}</div>
                                <div class="col-span-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${estadoClass[serie.serie_estado] || 'bg-gray-100 text-gray-800'}">
                                        ${serie.serie_estado}
                                    </span>
                                </div>
                                <div class="col-span-3 text-gray-600 dark:text-gray-400">
                                    ${new Date(serie.serie_fecha_ingreso).toLocaleDateString()}
                                </div>
                            </div>
                        `;
        }).join('')}
                </div>
            </div>
            
            <!-- Footer con total -->
            <div class="mt-3 text-center text-sm text-gray-600 dark:text-gray-400">
                Total: <strong>${series.length}</strong> series registradas
            </div>
        </div>
    `;

        Swal.fire({
            title: `Series Registradas (${series.length})`,
            html: contenidoHTML,
            width: '800px',
            showCloseButton: true,
            showConfirmButton: true,
            confirmButtonText: 'Cerrar',
            confirmButtonColor: '#3b82f6',
            customClass: {
                popup: 'dark:bg-gray-800',
                title: 'dark:text-gray-100',
                htmlContainer: 'dark:text-gray-300'
            }
        });
    }


    //termina detalle de producto


    // empieza edición de producto 


    // Agregar estos métodos a tu InventarioManager

    /**
     * Abrir modal de edición de producto
     */
    async editarProducto(productoId) {
        this.currentProductoId = productoId;

        try {
            // Cargar datos del producto
            const response = await fetch(`/inventario/productos/${productoId}`);
            if (response.ok) {
                const data = await response.json();
                await this.prepararFormularioEdicion(data.data);
                this.showModal('editar');
            } else {
                this.showAlert('error', 'Error', 'No se pudo cargar el producto para editar');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Preparar formulario de edición con datos del producto
     */
    async prepararFormularioEdicion(producto) {
        // Llenar campos básicos
        document.getElementById('editar_producto_id').value = producto.producto_id;
        document.getElementById('editar_producto_nombre').value = producto.producto_nombre || '';
        document.getElementById('editar_producto_descripcion').value = producto.producto_descripcion || '';
        document.getElementById('editar_producto_codigo_barra').value = producto.producto_codigo_barra || '';
        document.getElementById('editar_producto_stock_minimo').value = producto.producto_stock_minimo || 0;
        document.getElementById('editar_producto_stock_maximo').value = producto.producto_stock_maximo || 0;

        // Mostrar tipo de control actual
        const requiereSerieDisplay = document.getElementById('editar_requiere_serie_display');
        if (producto.producto_requiere_serie) {
            requiereSerieDisplay.innerHTML = `
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                <i class="fas fa-check mr-1"></i>
                Control por serie
            </span>
        `;
        } else {
            requiereSerieDisplay.innerHTML = `
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                <i class="fas fa-list mr-1"></i>
                Control por cantidad
            </span>
        `;
        }

        // Poblar selects con datos actuales
        await this.poblarSelectsEdicion();

        // Establecer valores seleccionados
        document.getElementById('editar_producto_categoria').value = producto.producto_categoria_id || '';
        document.getElementById('editar_producto_marca').value = producto.producto_marca_id || '';
        document.getElementById('editar_producto_modelo').value = producto.producto_modelo_id || '';
        document.getElementById('editar_producto_calibre').value = producto.producto_calibre_id || '';
        document.getElementById('editar_producto_madein').value = producto.producto_madein || '';

        // Cargar subcategorías si hay categoría seleccionada
        if (producto.producto_categoria_id) {
            await this.loadSubcategoriasEdicion(producto.producto_categoria_id);
            document.getElementById('editar_producto_subcategoria').value = producto.producto_subcategoria_id || '';
        }
        // PASO 4: Cargar modelos si hay marca seleccionada
        if (producto.producto_marca_id) {
            await this.loadModelosEdicion(producto.producto_marca_id);
            // Establecer modelo después de cargarlos
            setTimeout(() => {
                if (producto.producto_modelo_id) {
                    document.getElementById('editar_producto_modelo').value = producto.producto_modelo_id;
                }
            }, 100);
        }
        // Configurar event listeners para el formulario de edición
        this.setupEditarEventListeners();
    }



    /**
     * Cargar modelos para edición
     */
    async loadModelosEdicion(marcaId) {
        const select = document.getElementById('editar_producto_modelo');
        select.innerHTML = '<option value="">Seleccionar modelo</option>';

        if (!marcaId) return;

        try {
            const response = await fetch(`/marcas/${marcaId}/modelos`);
            if (response.ok) {
                const data = await response.json();
                this.populateSelect('editar_producto_modelo', data.data || [], 'modelo_id', 'modelo_descripcion');
                ('Modelos cargados para marca', marcaId, ':', data.data?.length || 0);
            } else {
                console.error('Error cargando modelos para marca', marcaId);
            }
        } catch (error) {
            console.error('Error cargando modelos:', error);
        }
    }
    /**
     * Poblar selects del formulario de edición
     */
    /**
     * Método poblarSelectsEdicion con debug - TEMPORAL
     */
    async poblarSelectsEdicion() {
        console.log('=== DEBUG POBLAR SELECTS EDICIÓN ===');
        console.log('this.categorias:', this.categorias?.length);
        console.log('this.marcas:', this.marcas?.length);
        console.log('this.calibres:', this.calibres?.length);
        console.log('this.paises antes:', this.paises?.length);

        // Usar los datos ya cargados
        if (this.categorias && this.categorias.length > 0) {
            this.populateSelect('editar_producto_categoria', this.categorias, 'categoria_id', 'categoria_nombre');
            console.log('✅ Categorías pobladas');
        } else {
            console.log('❌ No hay categorías');
        }

        if (this.marcas && this.marcas.length > 0) {
            this.populateSelect('editar_producto_marca', this.marcas, 'marca_id', 'marca_descripcion');
            console.log('✅ Marcas pobladas');
        } else {
            console.log('❌ No hay marcas');
        }

        if (this.calibres && this.calibres.length > 0) {
            this.populateSelect('editar_producto_calibre', this.calibres, 'calibre_id', 'calibre_nombre');
            console.log('✅ Calibres poblados');
        } else {
            console.log('❌ No hay calibres');
        }

        // Cargar países si no están cargados
        if (!this.paises || this.paises.length === 0) {
            console.log('Cargando países...');
            await this.loadPaises();
            console.log('this.paises después:', this.paises?.length);
        }

        if (this.paises && this.paises.length > 0) {
            this.populateSelect('editar_producto_madein', this.paises, 'pais_id', 'pais_descripcion');
            console.log('✅ Países poblados');
        } else {
            console.log('❌ No hay países');
        }

        console.log('=== FIN DEBUG ===');
    }

    /**
     * Cargar subcategorías para edición
     */
    async loadSubcategoriasEdicion(categoriaId) {
        const select = document.getElementById('editar_producto_subcategoria');
        select.innerHTML = '<option value="">Seleccionar subcategoría</option>';

        if (!categoriaId) return;

        try {
            const response = await fetch(`/categorias/${categoriaId}/subcategorias`);
            if (response.ok) {
                const data = await response.json();
                this.populateSelect('editar_producto_subcategoria', data.data || [], 'subcategoria_id', 'subcategoria_nombre');
            }
        } catch (error) {
            console.error('Error cargando subcategorías:', error);
        }
    }

    /**
     * Configurar event listeners para el formulario de edición
     */
    setupEditarEventListeners() {
        // Cambios en categoría
        const categoriaSelect = document.getElementById('editar_producto_categoria');
        categoriaSelect.removeEventListener('change', this.handleCategoriaChangeEdicion); // Remover listener previo
        categoriaSelect.addEventListener('change', (e) => {
            this.loadSubcategoriasEdicion(e.target.value);
        });

        // Cambios en marca para cargar modelos
        const marcaSelect = document.getElementById('editar_producto_marca');
        marcaSelect.removeEventListener('change', this.handleMarcaChangeEdicion); // Remover listener previo
        marcaSelect.addEventListener('change', (e) => {
            this.loadModelosEdicion(e.target.value);
        });

        // Submit del formulario
        const form = document.getElementById('editar-form');
        form.removeEventListener('submit', this.handleEditarSubmit); // Remover listener previo
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleEditarSubmit();
        });
    }

    /**
     * Cargar modelos para edición
     */
    async loadModelosEdicion(marcaId) {
        const select = document.getElementById('editar_producto_modelo');
        select.innerHTML = '<option value="">Seleccionar modelo</option>';

        if (!marcaId) return;

        try {
            const response = await fetch(`/marcas/${marcaId}/modelos`);
            if (response.ok) {
                const data = await response.json();
                this.populateSelect('editar_producto_modelo', data.data || [], 'modelo_id', 'modelo_descripcion');
            }
        } catch (error) {
            console.error('Error cargando modelos:', error);
        }
    }

    /**
     * Manejar envío del formulario de edición
     */

    async handleEditarSubmit() {
        if (!this.validateEditarForm()) {
            return;
        }

        const form = document.getElementById('editar-form');

        // Para PUT requests, usar URLSearchParams en lugar de FormData
        const formData = new URLSearchParams();

        // Agregar todos los campos manualmente
        formData.append('producto_nombre', document.getElementById('editar_producto_nombre').value);
        formData.append('producto_descripcion', document.getElementById('editar_producto_descripcion').value || '');
        formData.append('producto_categoria_id', document.getElementById('editar_producto_categoria').value);
        formData.append('producto_subcategoria_id', document.getElementById('editar_producto_subcategoria').value);
        formData.append('producto_marca_id', document.getElementById('editar_producto_marca').value);
        formData.append('producto_modelo_id', document.getElementById('editar_producto_modelo').value || '');
        formData.append('producto_calibre_id', document.getElementById('editar_producto_calibre').value || '');
        formData.append('producto_madein', document.getElementById('editar_producto_madein').value || '');
        formData.append('producto_codigo_barra', document.getElementById('editar_producto_codigo_barra').value || '');
        formData.append('producto_stock_minimo', document.getElementById('editar_producto_stock_minimo').value || '0');
        formData.append('producto_stock_maximo', document.getElementById('editar_producto_stock_maximo').value || '0');

        this.setLoading('editar', true);

        try {
            const response = await fetch(`/inventario/productos/${this.currentProductoId}`, {
                method: 'PUT',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message || 'Producto actualizado exitosamente');
                this.closeModal('editar');

                // Recargar datos
                await Promise.all([
                    this.loadProductos(),
                    this.loadStats()
                ]);

                // Si el modal de detalle está abierto, actualizarlo
                const detalleModal = document.getElementById('detalle-modal');
                if (!detalleModal.classList.contains('hidden')) {
                    await this.verDetalleProducto(this.currentProductoId);
                }
            } else {
                if (data.errors) {
                    this.showValidationErrors('editar', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al actualizar el producto');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('editar', false);
        }
    }
    /**
     * Validar formulario de edición
     */
    validateEditarForm() {
        const nombre = document.getElementById('editar_producto_nombre').value.trim();
        const categoria = document.getElementById('editar_producto_categoria').value;
        const subcategoria = document.getElementById('editar_producto_subcategoria').value;
        const marca = document.getElementById('editar_producto_marca').value;

        this.clearErrors('editar');

        let isValid = true;

        if (!nombre) {
            this.showFieldError('editar_producto_nombre', 'El nombre del producto es obligatorio');
            isValid = false;
        }

        if (!categoria) {
            this.showFieldError('editar_producto_categoria_id', 'La categoría es obligatoria');
            isValid = false;
        }

        if (!subcategoria) {
            this.showFieldError('editar_producto_subcategoria_id', 'La subcategoría es obligatoria');
            isValid = false;
        }

        if (!marca) {
            this.showFieldError('editar_producto_marca_id', 'La marca es obligatoria');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Resetear formulario de edición
     */
    resetEditarForm() {
        document.getElementById('editar-form').reset();
        this.clearErrors('editar');

        // Resetear selects a su estado inicial
        document.getElementById('editar_producto_subcategoria').innerHTML = '<option value="">Seleccionar subcategoría</option>';
        document.getElementById('editar_producto_modelo').innerHTML = '<option value="">Seleccionar modelo</option>';
    }


    ///actualizar precios empieza

    /**
     * Abrir modal de gestión de precios
     */
    async gestionarPrecios(productoId) {
        this.currentProductoId = productoId;

        try {
            // Cargar datos del producto
            const response = await fetch(`/inventario/productos/${productoId}`);
            if (response.ok) {
                const data = await response.json();
                this.prepararGestionPrecios(data.data);
                this.showModal('precios');

                // Cargar historial de precios
                this.loadHistorialPrecios(productoId);
            } else {
                this.showAlert('error', 'Error', 'No se pudo cargar el producto');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Preparar modal de gestión de precios
     */
    /**
     * Preparar modal de gestión de precios - VERSIÓN CORREGIDA
     */
    prepararGestionPrecios(producto) {
        // Actualizar título
        document.getElementById('precios_producto_nombre').textContent =
            `${producto.producto_nombre} (SKU: ${producto.pro_codigo_sku})`;

        // Limpiar formulario
        document.getElementById('precio-form').reset();
        this.clearErrors('precios');

        // Limpiar los displays de margen y ganancia de forma segura
        const elementos = [
            'nuevo_margen_calculado',
            'nueva_ganancia_calculada',
            'nuevo_margen_calculado_empresa',
            'nueva_ganancia_calculada_empresa'
        ];

        elementos.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                if (id.includes('margen')) {
                    element.textContent = '0%';
                    element.className = 'font-medium text-gray-600';
                } else {
                    element.textContent = 'Q0.00';
                }
            }
        });

        // Configurar cálculo automático de margen
        this.setupCalculoMargenPrecios();

        // Configurar envío del formulario
        this.setupPreciosFormSubmit();
    }

    /**
     * Configurar cálculo automático de margen en precios
     */
    /**
     * Configurar cálculo automático de margen en precios - VERSIÓN CORREGIDA
     */
    setupCalculoMargenPrecios() {
        const costInput = document.getElementById('nuevo_precio_costo');
        const ventaInput = document.getElementById('nuevo_precio_venta');
        const ventaInputEmpresa = document.getElementById('nuevo_precio_venta_empresa');

        const calcularMargen = () => {
            const costo = parseFloat(costInput?.value) || 0;
            const venta = parseFloat(ventaInput?.value) || 0;
            const ventaEmpresa = parseFloat(ventaInputEmpresa?.value) || 0;

            const margenElement = document.getElementById('nuevo_margen_calculado');
            const gananciaElement = document.getElementById('nueva_ganancia_calculada');
            const margenElementEmpresa = document.getElementById('nuevo_margen_calculado_empresa');
            const gananciaElementEmpresa = document.getElementById('nueva_ganancia_calculada_empresa');

            // CÁLCULO PARA PRECIO INDIVIDUAL
            if (margenElement && gananciaElement && costo > 0 && venta > 0) {
                const ganancia = venta - costo;
                const margen = ((ganancia / costo) * 100);

                margenElement.textContent = `${margen.toFixed(1)}%`;
                gananciaElement.textContent = `Q${ganancia.toFixed(2)}`;

                // Colorear según el margen
                if (margen < 10) {
                    margenElement.className = 'font-medium text-red-600';
                } else if (margen < 25) {
                    margenElement.className = 'font-medium text-yellow-600';
                } else {
                    margenElement.className = 'font-medium text-green-600';
                }
            } else if (margenElement && gananciaElement) {
                margenElement.textContent = '0%';
                gananciaElement.textContent = 'Q0.00';
                margenElement.className = 'font-medium text-gray-600';
            }

            // CÁLCULO PARA PRECIO EMPRESA
            if (margenElementEmpresa && gananciaElementEmpresa && costo > 0 && ventaEmpresa > 0) {
                const gananciaEmpresa = ventaEmpresa - costo;
                const margenEmpresa = ((gananciaEmpresa / costo) * 100);

                margenElementEmpresa.textContent = `${margenEmpresa.toFixed(1)}%`;
                gananciaElementEmpresa.textContent = `Q${gananciaEmpresa.toFixed(2)}`;

                // Colorear según el margen empresa
                if (margenEmpresa < 10) {
                    margenElementEmpresa.className = 'font-medium text-red-600';
                } else if (margenEmpresa < 25) {
                    margenElementEmpresa.className = 'font-medium text-yellow-600';
                } else {
                    margenElementEmpresa.className = 'font-medium text-green-600';
                }
            } else if (margenElementEmpresa && gananciaElementEmpresa) {
                margenElementEmpresa.textContent = '0%';
                gananciaElementEmpresa.textContent = 'Q0.00';
                margenElementEmpresa.className = 'font-medium text-gray-600';
            }
        };

        // Remover listeners anteriores SOLO si los elementos existen
        if (costInput) {
            // Crear función bound para poder removerla después
            if (!costInput._calcularMargenHandler) {
                costInput._calcularMargenHandler = calcularMargen;
            } else {
                costInput.removeEventListener('input', costInput._calcularMargenHandler);
            }
            costInput.addEventListener('input', costInput._calcularMargenHandler);
        }

        if (ventaInput) {
            if (!ventaInput._calcularMargenHandler) {
                ventaInput._calcularMargenHandler = calcularMargen;
            } else {
                ventaInput.removeEventListener('input', ventaInput._calcularMargenHandler);
            }
            ventaInput.addEventListener('input', ventaInput._calcularMargenHandler);
        }

        if (ventaInputEmpresa) {
            if (!ventaInputEmpresa._calcularMargenHandler) {
                ventaInputEmpresa._calcularMargenHandler = calcularMargen;
            } else {
                ventaInputEmpresa.removeEventListener('input', ventaInputEmpresa._calcularMargenHandler);
            }
            ventaInputEmpresa.addEventListener('input', ventaInputEmpresa._calcularMargenHandler);
        }
    }

    /**
     * Configurar envío del formulario de precios
     */
    setupPreciosFormSubmit() {
        const form = document.getElementById('precio-form');

        // Remover listener anterior
        form.removeEventListener('submit', this.handlePreciosSubmit);

        // Agregar nuevo listener
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handlePreciosSubmit();
        });
    }

    /**
     * Manejar envío del formulario de precios
     */
    /**
     * Manejar envío del formulario de precios - VERSIÓN CORREGIDA
     */
    async handlePreciosSubmit() {
        if (!this.validatePreciosForm()) {
            return;
        }

        const formData = new URLSearchParams();
        formData.append('precio_costo', document.getElementById('nuevo_precio_costo').value);
        formData.append('precio_venta', document.getElementById('nuevo_precio_venta').value);
        formData.append('precio_venta_empresa', document.getElementById('nuevo_precio_venta_empresa').value);
        formData.append('precio_especial', document.getElementById('nuevo_precio_especial').value || '');
        formData.append('precio_justificacion', document.getElementById('nuevo_precio_justificacion').value);
        formData.append('precio_moneda', document.getElementById('nuevo_precio_moneda').value);

        this.setLoading('precios', true);

        try {
            const response = await fetch(`/inventario/productos/${this.currentProductoId}/precios`, {
                method: 'PUT',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message || 'Precios actualizados exitosamente');

                // Recargar historial de precios
                this.loadHistorialPrecios(this.currentProductoId);

                // Limpiar formulario y displays
                document.getElementById('precio-form').reset();

                // Limpiar displays de manera segura
                const displayElements = [
                    { id: 'nuevo_margen_calculado', defaultText: '0%' },
                    { id: 'nueva_ganancia_calculada', defaultText: 'Q0.00' },
                    { id: 'nuevo_margen_calculado_empresa', defaultText: '0%' },
                    { id: 'nueva_ganancia_calculada_empresa', defaultText: 'Q0.00' }
                ];

                displayElements.forEach(({ id, defaultText }) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = defaultText;
                        if (id.includes('margen')) {
                            element.className = 'font-medium text-gray-600';
                        }
                    }
                });

            } else {
                if (data.errors) {
                    this.showValidationErrors('precios', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al actualizar precios');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('precios', false);
        }
    }
    /**
     * Validar formulario de precios
     */
    /**
     * Validar formulario de precios - VERSIÓN CORREGIDA
     */
    validatePreciosForm() {
        const costo = parseFloat(document.getElementById('nuevo_precio_costo').value) || 0;
        const venta = parseFloat(document.getElementById('nuevo_precio_venta').value) || 0;
        const ventaEmpresa = parseFloat(document.getElementById('nuevo_precio_venta_empresa').value) || 0;
        const justificacion = document.getElementById('nuevo_precio_justificacion').value.trim();

        this.clearErrors('precios');

        let isValid = true;

        // Validación precio de costo
        if (costo <= 0) {
            this.showFieldError('nuevo_precio_costo', 'El precio de costo debe ser mayor a 0');
            isValid = false;
        }

        // Validación precio de venta individual
        if (venta <= 0) {
            this.showFieldError('nuevo_precio_venta', 'El precio de venta debe ser mayor a 0');
            isValid = false;
        } else if (venta <= costo) {
            this.showFieldError('nuevo_precio_venta', 'El precio de venta debe ser mayor al costo');
            isValid = false;
        }

        // Validación precio de venta empresa
        if (ventaEmpresa <= 0) {
            this.showFieldError('nuevo_precio_venta_empresa', 'El precio de venta empresa debe ser mayor a 0');
            isValid = false;
        } else if (ventaEmpresa <= costo) {
            this.showFieldError('nuevo_precio_venta_empresa', 'El precio de venta empresa debe ser mayor al costo');
            isValid = false;
        }

        // Validación justificación
        if (!justificacion) {
            this.showFieldError('nuevo_precio_justificacion', 'Debe indicar el motivo del cambio de precio');
            isValid = false;
        }

        return isValid;
    }

    /**
     * Cargar historial de precios
     */
    async loadHistorialPrecios(productoId) {
        try {
            const response = await fetch(`/inventario/productos/${productoId}/precios`);
            if (response.ok) {
                const data = await response.json();
                this.renderHistorialPrecios(data.data || []);
            } else {
                this.renderHistorialPrecios([]);
            }
        } catch (error) {
            console.error('Error cargando historial de precios:', error);
            this.renderHistorialPrecios([]);
        }
    }

    /**
     * Renderizar historial de precios
     */
    renderHistorialPrecios(precios) {
        const container = document.getElementById('precios_historial_container');

        if (precios.length === 0) {
            container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-dollar-sign text-gray-400 text-3xl mb-2"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">No hay historial de precios</p>
            </div>
        `;
            return;
        }

        container.innerHTML = precios.map((precio, index) => {
            const esPrecioActual = index === 0;
            const fechaFormateada = new Date(precio.precio_fecha_asignacion).toLocaleDateString();

            return `
            <div class="border ${esPrecioActual ? 'border-blue-300 bg-blue-50 dark:bg-blue-900' : 'border-gray-200 dark:border-gray-600'} rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        ${esPrecioActual ?
                    '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">Actual</span>' :
                    '<span class="text-xs text-gray-500 dark:text-gray-400">Histórico</span>'
                }
                        <span class="text-xs text-gray-500 dark:text-gray-400">${fechaFormateada}</span>
                    </div>
                    <span class="text-xs font-medium text-purple-600">${precio.precio_margen}% margen</span>
                </div>
                
                <!-- ✅ ACTUALIZADO: Ahora muestra 4 columnas incluyendo Venta Empresa -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Costo</span>
                        <span class="font-medium">Q${precio.precio_costo}</span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Venta (Individual)</span>
                        <span class="font-medium text-green-600">Q${precio.precio_venta}</span>
                    </div>
                    ${precio.precio_venta_empresa ? `
                        <div>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">Venta (Empresa)</span>
                            <span class="font-medium text-blue-600">Q${precio.precio_venta_empresa}</span>
                        </div>
                    ` : '<div></div>'}
                    <div>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Especial</span>
                        <span class="font-medium text-orange-600">${precio.precio_especial ? 'Q' + precio.precio_especial : '-'}</span>
                    </div>
                </div>
                
                <!-- ✅ NUEVO: Mostrar margen empresa si existe -->
                ${precio.precio_margen_empresa ? `
                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Margen Individual:</span>
                            <span class="font-medium text-purple-600">${precio.precio_margen}%</span>
                        </div>
                        <div class="flex items-center justify-between text-xs mt-1">
                            <span class="text-gray-500 dark:text-gray-400">Margen Empresa:</span>
                            <span class="font-medium text-indigo-600">${precio.precio_margen_empresa}%</span>
                        </div>
                    </div>
                ` : ''}
                
                ${precio.precio_justificacion ? `
                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-600">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Motivo: </span>
                        <span class="text-xs">${precio.precio_justificacion}</span>
                    </div>
                ` : ''}
            </div>
        `;
        }).join('');
    }

    //termina actualizar precios




    // mostrar las alertas


    /**
     * Mostrar alertas en SweetAlert
     */
    async toggleAlertas() {
        try {
            const response = await fetch('/inventario/alertas-stock');
            if (response.ok) {
                const data = await response.json();
                this.mostrarAlertasDetalladas(data.data || []);
            } else {
                this.showAlert('error', 'Error', 'No se pudieron cargar las alertas');
            }
        } catch (error) {
            console.error('Error cargando alertas:', error);
            this.showAlert('error', 'Error', 'Error de conexión al cargar alertas');
        }
    }

    /**
     * Mostrar alertas detalladas en SweetAlert
     */
    /**
     * Mostrar alertas detalladas en SweetAlert
     */
    /**
     * Mostrar alertas detalladas con paginación
     */
    async mostrarAlertasDetalladas(alertas, pagination = null) {
        if (!pagination && alertas.length === 0) {
            Swal.fire({
                title: 'Sin alertas pendientes',
                html: `
                <div class="text-center py-4">
                    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                    <p class="text-gray-600">¡Perfecto! No hay productos con stock bajo.</p>
                </div>
            `,
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Entendido'
            });
            return;
        }

        // Header con información de paginación
        const headerInfo = pagination ?
            `Mostrando ${alertas.length} de ${pagination.total_items} alertas (Página ${pagination.current_page} de ${pagination.total_pages})` :
            `${alertas.length} productos requieren atención`;

        let contenidoHTML = `
        <div class="text-left">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="font-medium text-yellow-800 mb-3 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Productos que requieren atención
                </h4>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    ${alertas.map(producto => {
            const stock = producto.stock_cantidad_disponible || producto.stock_actual || 0;
            const minimo = producto.producto_stock_minimo || 0;
            const isAgotado = stock <= 0;

            return `
                            <div class="flex justify-between items-center py-2 px-3 ${isAgotado ? 'bg-red-100 border border-red-200' : 'bg-white border border-gray-200'} rounded">
                                <div>
                                    <div class="font-medium ${isAgotado ? 'text-red-800' : 'text-yellow-800'}">${producto.producto_nombre}</div>
                                    <div class="text-xs text-gray-600">SKU: ${producto.pro_codigo_sku || 'N/A'}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium ${isAgotado ? 'text-red-600' : 'text-yellow-600'}">
                                        ${stock} / ${minimo}
                                    </div>
                                    <div class="text-xs ${isAgotado ? 'text-red-500' : 'text-yellow-500'}">
                                        ${isAgotado ? 'AGOTADO' : 'STOCK BAJO'}
                                    </div>
                                </div>
                            </div>
                        `;
        }).join('')}
                </div>
            </div>
        </div>
    `;

        // Agregar controles de paginación si hay múltiples páginas
        if (pagination && pagination.total_pages > 1) {
            contenidoHTML += `
            <div class="mt-4 flex justify-between items-center">
                <button onclick="inventarioManager.cargarAlertasPagina(${pagination.current_page - 1})" 
                        ${pagination.current_page <= 1 ? 'disabled' : ''} 
                        class="px-3 py-1 bg-blue-500 text-white rounded text-sm disabled:opacity-50">
                    ← Anterior
                </button>
                <span class="text-sm text-gray-600">
                    Página ${pagination.current_page} de ${pagination.total_pages}
                </span>
                <button onclick="inventarioManager.cargarAlertasPagina(${pagination.current_page + 1})" 
                        ${pagination.current_page >= pagination.total_pages ? 'disabled' : ''} 
                        class="px-3 py-1 bg-blue-500 text-white rounded text-sm disabled:opacity-50">
                    Siguiente →
                </button>
            </div>
        `;
        }

        Swal.fire({
            title: headerInfo,
            html: contenidoHTML,
            icon: 'warning',
            width: '650px',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Cerrar',
            customClass: {
                popup: 'text-left'
            }
        });
    }

    /**
     * Cargar alertas por página
     */
    async cargarAlertasPagina(page) {
        try {
            const response = await fetch(`/inventario/alertas-stock?page=${page}&limit=20`);
            if (response.ok) {
                const data = await response.json();
                this.mostrarAlertasDetalladas(data.data || [], data.pagination);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error al cargar la página');
        }
    }




    //VER HISTORIAL 

    /**
     * Ver historial de movimientos
     */
    verHistorial() {
        this.showModal('historial');
        setTimeout(() => {
            this.initHistorialDataTable();
        }, 100);
    }

    /**
     * Inicializar DataTable para historial
     */
    initHistorialDataTable() {
        // Destruir tabla existente si existe
        if ($.fn.DataTable.isDataTable('#historial-table')) {
            $('#historial-table').DataTable().destroy();
        }

        const table = $('#historial-table').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: '/inventario/movimientos',
                type: 'GET',
                data: function (d) {
                    // Convertir parámetros de DataTables a tu formato
                    d.page = Math.floor(d.start / d.length) + 1;
                    d.limit = d.length;
                    d.search = d.search.value;
                    d.filtro_producto = $('#filtro_producto').val();
                    d.filtro_tipo = $('#filtro_tipo').val();
                    d.filtro_fecha = $('#filtro_fecha').val();
                },
                dataSrc: function (json) {
                    // Convertir tu respuesta al formato que espera DataTables
                    return json.data;
                }
            },
            columns: [
                {
                    data: 'mov_fecha',
                    title: 'Fecha',
                    render: function (data) {
                        const fecha = new Date(data);
                        return fecha.toLocaleDateString() + '<br><small class="text-gray-500">' + fecha.toLocaleTimeString() + '</small>';
                    }
                },
                {
                    data: 'producto_nombre',
                    title: 'Producto',
                    render: function (data, type, row) {
                        return `<strong class="text-gray-900 dark:text-gray-100">${data}</strong><br><small class="text-gray-500">${row.producto_sku}</small>`;
                    }
                },
                {
                    data: 'mov_tipo',
                    title: 'Tipo',
                    render: function (data, type, row) {
                        const color = row.mov_cantidad > 0 ? 'text-green-600' : 'text-red-600';
                        const icon = row.mov_cantidad > 0 ? 'fa-arrow-up' : 'fa-arrow-down';
                        return `<span class="${color}"><i class="fas ${icon} mr-1"></i>${data}</span>`;
                    }
                },
                {
                    data: 'mov_cantidad',
                    title: 'Cantidad',
                    render: function (data) {
                        const color = data > 0 ? 'text-green-600' : 'text-red-600';
                        return `<span class="${color} font-medium">${data > 0 ? '+' : ''}${data}</span>`;
                    }
                },
                {
                    data: 'mov_origen',
                    title: 'Origen/Destino'
                },
                {
                    data: 'lote_codigo',
                    title: 'Lote/Serie',
                    render: function (data, type, row) {
                        let html = '';
                        if (row.lote_codigo) {
                            html += `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1">L: ${row.lote_codigo}</span>`;
                        }
                        if (row.serie_numero) {
                            html += `<span class="inline-block bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">S: ${row.serie_numero}</span>`;
                        }
                        return html || '<span class="text-gray-400">-</span>';
                    }
                },
                {
                    data: 'usuario_nombre',
                    title: 'Usuario'
                }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            language: {
                processing: "Procesando...",
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                search: "Buscar:",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            // DOM personalizado para Tailwind
            dom: '<"flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4"<"mb-2 sm:mb-0"l><"mb-2 sm:mb-0"B><"sm:ml-auto"f>>rtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel mr-1"></i> Excel',
                    className: 'bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm mr-2 transition-colors'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf mr-1"></i> PDF',
                    className: 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm mr-2 transition-colors'
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print mr-1"></i> Imprimir',
                    className: 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors'
                }
            ],
            // Aplicar estilos de Tailwind después de inicializar
            initComplete: function () {
                // Estilos para controles
                $('.dataTables_length select').addClass('border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm bg-white dark:bg-gray-700 dark:text-gray-100');
                $('.dataTables_filter input').addClass('border border-gray-300 dark:border-gray-600 rounded-md px-3 py-1 text-sm ml-2 bg-white dark:bg-gray-700 dark:text-gray-100');

                // Estilos para paginación
                $('.dataTables_paginate .paginate_button').addClass('px-3 py-1 mx-1 border border-gray-300 dark:border-gray-600 rounded text-sm hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors');
                $('.dataTables_paginate .paginate_button.current').addClass('bg-blue-500 text-white border-blue-500');
                $('.dataTables_paginate .paginate_button.disabled').addClass('opacity-50 cursor-not-allowed');

                // Estilos para info
                $('.dataTables_info').addClass('text-sm text-gray-700 dark:text-gray-300');
                $('.dataTables_length').addClass('text-sm text-gray-700 dark:text-gray-300');
                $('.dataTables_filter').addClass('text-sm text-gray-700 dark:text-gray-300');
            }
        });

        // Event listeners para filtros
        $('#filtro_producto, #filtro_tipo, #filtro_fecha').on('change keyup', function () {
            table.draw();
        });

        return table;
    }





    ///aquí terminarán los vergazos de las acciones 




    /// aqui otro purrum que pidio el cliente 


    // ================================
    // VISTA EXCEL - NUEVAS FUNCIONES
    // ================================

    /**
     * Alternar vista Excel
     */
    toggleExcelView() {
        const content = document.getElementById('excel-content');
        const chevron = document.getElementById('excel-chevron');

        this.excelIsExpanded = !this.excelIsExpanded;

        if (this.excelIsExpanded) {
            content.classList.remove('hidden');
            chevron.classList.add('rotate-180');

            // Cargar datos si no están cargados
            if (this.excelData.length === 0) {
                this.cargarDatosExcel();
                this.cargarCategoriasExcel();
            }
        } else {
            content.classList.add('hidden');
            chevron.classList.remove('rotate-180');
        }
    }

    /**
     * Cargar datos para vista Excel
     */
    async cargarDatosExcel() {
        try {
            this.showToast('Cargando vista detallada...', 'info');

            const response = await fetch('/inventario/productos-excel');
            const data = await response.json();

            if (data.success) {
                this.excelData = data.data;
                this.excelFilteredData = [...this.excelData];
                this.actualizarContadorExcel();
                this.mostrarDatosExcel();
                this.showToast('Vista detallada cargada', 'success');
            } else {
                this.showAlert('error', 'Error', 'Error al cargar datos: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        }
    }

    /**
     * Mostrar datos en la tabla Excel
     *//**
* Mostrar datos en la tabla Excel - VERSIÓN ACTUALIZADA
*/
    mostrarDatosExcel() {
        const tbody = document.getElementById('excel-tbody');

        // Calcular registros para la página actual
        const startIndex = (this.excelCurrentPage - 1) * this.excelRecordsPerPage;
        const endIndex = startIndex + this.excelRecordsPerPage;
        const pageData = this.excelFilteredData.slice(startIndex, endIndex);

        // Generar HTML de las filas
        let html = '';
        pageData.forEach((item, index) => {
            const globalIndex = startIndex + index + 1;
            const simboloMoneda = item.precio_moneda === 'USD' ? '$' : 'Q';

            html += `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-600">
                <td class="px-3 py-3 text-sm text-gray-900 dark:text-gray-100">${globalIndex}</td>
                <td class="px-3 py-3 text-sm text-gray-900 dark:text-gray-100 font-mono">${item.codigo || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-900 dark:text-gray-100 font-medium">${item.producto_nombre}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.categoria_nombre || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.subcategoria_nombre || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.marca_nombre || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.modelo_nombre || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.calibre_nombre || '-'}</td>
                <td class="px-3 py-3 text-sm font-mono ${item.numero_serie ? 'text-blue-600' : 'text-gray-400'} dark:${item.numero_serie ? 'text-blue-400' : 'text-gray-500'}">
                    ${item.numero_serie || 'Sin serie'}
                </td>
                <td class="px-3 py-3 text-sm">
                    <span class="px-2 py-1 text-xs rounded-full ${this.getEstadoBadgeClass(item.estado)}">
                        ${item.estado || 'disponible'}
                    </span>
                </td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.licencia_codigo || '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${item.lote_codigo || '-'}</td>
                <td class="px-3 py-3 text-sm text-center font-medium dark:text-gray-100">${item.stock || 0}</td>
                <td class="px-3 py-3 text-sm text-gray-900 dark:text-gray-100">${simboloMoneda}${parseFloat(item.precio_costo || 0).toFixed(2)}</td>
                <td class="px-3 py-3 text-sm text-green-600 dark:text-green-400">${simboloMoneda}${parseFloat(item.precio_venta || 0).toFixed(2)}</td>
                <td class="px-3 py-3 text-sm text-blue-600 dark:text-blue-400">${simboloMoneda}${parseFloat(item.precio_venta_empresa || 0).toFixed(2)}</td>
                <td class="px-3 py-3 text-sm text-purple-600 dark:text-purple-400">${item.precio_especial ? simboloMoneda + parseFloat(item.precio_especial).toFixed(2) : '-'}</td>
                <td class="px-3 py-3 text-sm text-gray-600 dark:text-gray-400">${this.formatearFecha(item.fecha_ingreso)}</td>
            </tr>
        `;
        });

        tbody.innerHTML = html;
        this.actualizarPaginacionExcel();
    }

    /**
     * Buscar en vista Excel
     *//**
* Aplicar filtros en vista Excel
*/
    aplicarFiltrosExcel() {
        // Obtener valores de todos los filtros
        const searchTerm = document.getElementById('excel-search')?.value.toLowerCase().trim() || '';
        const estadoFiltro = document.getElementById('excel-filter-estado')?.value || '';
        const categoriaFiltro = document.getElementById('excel-filter-categoria')?.value || '';

        // Aplicar filtros
        this.excelFilteredData = this.excelData.filter(item => {
            // Filtro de búsqueda por texto
            const matchesSearch = !searchTerm ||
                (item.producto_nombre?.toLowerCase().includes(searchTerm)) ||
                (item.codigo?.toLowerCase().includes(searchTerm)) ||
                (item.marca_nombre?.toLowerCase().includes(searchTerm)) ||
                (item.modelo_nombre?.toLowerCase().includes(searchTerm)) ||
                (item.numero_serie?.toLowerCase().includes(searchTerm)) ||
                (item.categoria_nombre?.toLowerCase().includes(searchTerm)) ||
                (item.calibre_nombre?.toLowerCase().includes(searchTerm));

            // Filtro por estado
            const matchesEstado = !estadoFiltro ||
                (item.estado?.toLowerCase() === estadoFiltro.toLowerCase());

            // Filtro por categoría
            const matchesCategoria = !categoriaFiltro ||
                (item.categoria_id?.toString() === categoriaFiltro);

            return matchesSearch && matchesEstado && matchesCategoria;
        });

        // Reiniciar a la primera página
        this.excelCurrentPage = 1;

        // Actualizar vista y contadores
        this.mostrarDatosExcel();
        this.actualizarContadorExcel();
        this.actualizarIndicadorFiltrosActivos();
    }


    /**
     * Actualizar indicador de filtros activos
     */
    actualizarIndicadorFiltrosActivos() {
        const searchTerm = document.getElementById('excel-search')?.value.trim() || '';
        const estadoFiltro = document.getElementById('excel-filter-estado')?.value || '';
        const categoriaFiltro = document.getElementById('excel-filter-categoria')?.value || '';

        // Contar filtros activos
        let filtrosActivos = 0;
        if (searchTerm) filtrosActivos++;
        if (estadoFiltro) filtrosActivos++;
        if (categoriaFiltro) filtrosActivos++;

        // Mostrar/ocultar indicador
        const indicador = document.getElementById('excel-filtros-activos');
        const contador = document.getElementById('excel-filtros-count');

        if (indicador && contador) {
            if (filtrosActivos > 0) {
                indicador.classList.remove('hidden');
                contador.textContent = filtrosActivos;
            } else {
                indicador.classList.add('hidden');
            }
        }
    }


    /**
     * Limpiar búsqueda Excel
     */
    /**
     * Limpiar todos los filtros
     */
    limpiarFiltrosExcel() {
        // Limpiar todos los campos de filtro
        const searchInput = document.getElementById('excel-search');
        const estadoSelect = document.getElementById('excel-filter-estado');
        const categoriaSelect = document.getElementById('excel-filter-categoria');

        if (searchInput) searchInput.value = '';
        if (estadoSelect) estadoSelect.value = '';
        if (categoriaSelect) categoriaSelect.value = '';

        // Reaplicar filtros (que ahora estarán vacíos)
        this.aplicarFiltrosExcel();
    }
    /**
     * Cambiar página Excel
     */
    cambiarPaginaExcel(direction) {
        const totalPages = Math.ceil(this.excelFilteredData.length / this.excelRecordsPerPage);

        if (direction === 'prev' && this.excelCurrentPage > 1) {
            this.excelCurrentPage--;
        } else if (direction === 'next' && this.excelCurrentPage < totalPages) {
            this.excelCurrentPage++;
        } else if (typeof direction === 'number') {
            this.excelCurrentPage = direction;
        }

        this.mostrarDatosExcel();
    }

    /**
     * Actualizar contador Excel
     */
    actualizarContadorExcel() {
        const countElement = document.getElementById('excel-count');
        if (countElement) {
            countElement.textContent = `${this.excelFilteredData.length} registros`;
        }
    }

    /**
     * Actualizar paginación Excel
     */
    actualizarPaginacionExcel() {
        const total = this.excelFilteredData.length;
        const totalPages = Math.ceil(total / this.excelRecordsPerPage);
        const start = Math.min((this.excelCurrentPage - 1) * this.excelRecordsPerPage + 1, total);
        const end = Math.min(this.excelCurrentPage * this.excelRecordsPerPage, total);

        // Actualizar información de registros mostrados
        const startElement = document.getElementById('excel-showing-start');
        const endElement = document.getElementById('excel-showing-end');
        const totalElement = document.getElementById('excel-total-records');

        if (startElement) startElement.textContent = start;
        if (endElement) endElement.textContent = end;
        if (totalElement) totalElement.textContent = total;

        // Actualizar botones de navegación
        const prevButton = document.getElementById('excel-btn-prev');
        const nextButton = document.getElementById('excel-btn-next');

        if (prevButton) prevButton.disabled = this.excelCurrentPage === 1;
        if (nextButton) nextButton.disabled = this.excelCurrentPage === totalPages;

        // Generar números de página
        const pageNumbers = document.getElementById('excel-page-numbers');
        if (pageNumbers) {
            let html = '';

            const startPage = Math.max(1, this.excelCurrentPage - 2);
            const endPage = Math.min(totalPages, this.excelCurrentPage + 2);

            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === this.excelCurrentPage;
                html += `
                <button onclick="inventarioManager.cambiarPaginaExcel(${i})" 
                        class="px-3 py-1 text-sm border rounded-md ${isActive ? 'bg-blue-500 text-white border-blue-500' : 'border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-gray-100'}">
                    ${i}
                </button>
            `;
            }

            pageNumbers.innerHTML = html;
        }
    }

    /**
     * Obtener clase CSS para badge de estado
     */
    getEstadoBadgeClass(estado) {
        switch (estado) {
            case 'disponible':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'reservado':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'vendido':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            case 'baja':
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
        }
    }

    /**
     * Formatear fecha
     */
    formatearFecha(fecha) {
        if (!fecha) return '-';
        return new Date(fecha).toLocaleDateString('es-GT');
    }


    //termina purrum que pidio el cliente ver en texto plano 

    /**
     * Establecer estado de carga
     */
    setLoading(type, loading) {
        const submitBtn = document.getElementById(`${type}-submit-btn`);
        const submitText = document.getElementById(`${type}-submit-text`);
        const loadingSpan = document.getElementById(`${type}-loading`);

        if (!submitBtn || !submitText || !loadingSpan) return;

        submitBtn.disabled = loading;

        if (loading) {
            submitText.classList.add('hidden');
            loadingSpan.classList.remove('hidden');
        } else {
            submitText.classList.remove('hidden');
            loadingSpan.classList.add('hidden');
        }
    }

    /**
     * Limpiar errores
     */
    clearErrors(type) {
        const form = document.getElementById(`${type}-form`);
        if (!form) return;

        const errorElements = form.querySelectorAll('.text-red-600');
        errorElements.forEach(element => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    /**
     * Mostrar error de campo
     */
    showFieldError(fieldName, message) {
        const errorElement = document.getElementById(`${fieldName}_error`);
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
    }

    /**
     * Mostrar errores de validación del servidor
     */
    showValidationErrors(type, errors) {
        Object.keys(errors).forEach(field => {
            if (errors[field] && errors[field].length > 0) {
                this.showFieldError(field, errors[field][0]);
            }
        });
    }

    /**
     * Mostrar alerta con SweetAlert2
     */
    showAlert(type, title, text) {
        const config = {
            title: title,
            text: text,
            icon: type,
            customClass: {
                popup: 'dark:bg-gray-800 dark:text-gray-100',
                title: 'dark:text-gray-100',
                content: 'dark:text-gray-300'
            }
        };

        if (type === 'success') {
            config.confirmButtonColor = '#10b981';
            config.timer = 3000;
            config.timerProgressBar = true;
        } else if (type === 'error') {
            config.confirmButtonColor = '#dc2626';
        } else if (type === 'warning') {
            config.confirmButtonColor = '#f59e0b';
        } else if (type === 'info') {
            config.confirmButtonColor = '#3b82f6';
        }

        Swal.fire(config);
    }

    /**
     * Mostrar notificación toast
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-md shadow-lg text-white transition-all duration-300 transform translate-x-full`;

        const bgColors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };

        toast.classList.add(bgColors[type] || bgColors.info);
        toast.textContent = message;

        document.body.appendChild(toast);

        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);

        // Remover después de 3 segundos
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    /**
     * Formatear números
     */
    formatNumber(num) {
        return new Intl.NumberFormat('es-GT').format(num);
    }

    /**
     * Formatear moneda
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('es-GT', {
            style: 'currency',
            currency: 'GTQ'
        }).format(amount);
    }

    /**
     * Debounce para búsquedas
     */
    debounce(func, wait) {
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
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Verificar dependencias necesarias
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 no está cargado');
        return;
    }

    // Inicializar el gestor de inventario
    try {
        window.inventarioManager = new InventarioManager();
        console.log('Sistema de inventario inicializado correctamente');
    } catch (error) {
        console.error('Error initializing InventarioManager:', error);
    }
});