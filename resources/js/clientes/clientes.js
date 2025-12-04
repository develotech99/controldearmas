/**
 * Gestor de Clientes - JavaScript
 * Requiere: SweetAlert2 (Swal)
 */

class ClientesManager {
    constructor() {
        // Estado
        this.clientes = [];
        this.isEditing = false;
        this.editingClienteId = null;

        // Filtros
        this.filtros = {
            searchTerm: '',
            tipoFilter: ''
        };

        // CSRF
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Debouncers
        this._debounceTimers = {};

        // Init
        this.init();
    }

    // ==========================
    // Utils
    // ==========================
    debounce(key, fn, delay = 250) {
        clearTimeout(this._debounceTimers[key]);
        this._debounceTimers[key] = setTimeout(fn, delay);
    }

    showAlert(type, title, text) {
        const config = {
            title,
            html: text ?? '',
            icon: type,
            confirmButtonText: 'Entendido'
        };
        if (type === 'success') {
            config.confirmButtonColor = '#10b981';
            config.timer = 3000;
        } else if (type === 'error') {
            config.confirmButtonColor = '#dc2626';
        } else if (type === 'warning') {
            config.confirmButtonColor = '#f59e0b';
        }
        Swal.fire(config);
    }

    // ==========================
    // Init
    // ==========================
    init() {
        console.log('🚀 ClientesManager inicializado');

        this.loadClientes();
        this.setupEventListeners();
        this.renderClientes();
    }

    loadClientes() {
        try {
            const script = document.getElementById('clientes-data');
            this.clientes = script ? JSON.parse(script.textContent) : [];
            console.log('📊 Clientes cargados:', this.clientes.length);
        } catch (e) {
            console.error('❌ Error cargando clientes:', e);
            this.clientes = [];
        }
    }

    setupEventListeners() {
        // Filtro de búsqueda (con debounce)
        const search = document.getElementById('search-clientes');
        if (search) {
            search.addEventListener('input', (e) => {
                const val = e.target.value;
                this.debounce('search', () => {
                    this.filtros.searchTerm = val;
                    this.aplicarFiltros();
                }, 250);
            });
        }

        // Filtro de tipo
        const tipo = document.getElementById('tipo-filter');
        if (tipo) {
            tipo.addEventListener('change', (e) => {
                this.filtros.tipoFilter = e.target.value;
                this.aplicarFiltros();
            });
        }

        // Form submit
        const form = document.getElementById('cliente-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit(e);
            });
        }
    }

    // ==========================
    // Filtros
    // ==========================
    aplicarFiltros() {
        this.renderClientes();
    }

    limpiarFiltros() {
        this.filtros = {
            searchTerm: '',
            tipoFilter: ''
        };

        const searchInput = document.getElementById('search-clientes');
        const tipoSelect = document.getElementById('tipo-filter');

        if (searchInput) searchInput.value = '';
        if (tipoSelect) tipoSelect.value = '';

        this.renderClientes();
    }

    getClientesFiltrados() {
        return this.clientes.filter(cliente => {
            const { searchTerm, tipoFilter } = this.filtros;

            // Filtro de búsqueda
            if (searchTerm) {
                const term = searchTerm.toLowerCase();
                const match =
                    cliente.nombre_completo?.toLowerCase().includes(term) ||
                    cliente.cliente_dpi?.toLowerCase().includes(term) ||
                    cliente.cliente_nit?.toLowerCase().includes(term) ||
                    cliente.cliente_nom_empresa?.toLowerCase().includes(term) ||
                    cliente.cliente_correo?.toLowerCase().includes(term) ||
                    cliente.cliente_telefono?.toLowerCase().includes(term);

                if (!match) return false;
            }

            // Filtro de tipo
            if (tipoFilter && cliente.cliente_tipo != tipoFilter) {
                return false;
            }

            return true;
        });
    }

    // ==========================
    // Render
    // ==========================
    renderClientes() {
        const tbody = document.getElementById('clientes-tbody');
        const emptyState = document.getElementById('empty-state');

        if (!tbody) return;

        const clientesFiltrados = this.getClientesFiltrados();

        if (clientesFiltrados.length === 0) {
            tbody.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        tbody.innerHTML = clientesFiltrados.map(cliente => this.renderClienteRow(cliente)).join('');
    }

    renderClienteRow(cliente) {
        const tipoLabel = this.getTipoLabel(cliente.cliente_tipo);
        const tipoBadge = this.getTipoBadge(cliente.cliente_tipo);

        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <div class="h-10 w-10 rounded-full bg-gradient-to-br ${tipoBadge.gradient} flex items-center justify-center text-white font-bold">
                                ${this.getIniciales(cliente)}
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                ${cliente.nombre_completo}
                            </div>
                            ${this.renderEmpresasNombres(cliente)}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 dark:text-gray-100">
                        ${cliente.cliente_dpi ? `<div><span class="font-medium">DPI:</span> ${cliente.cliente_dpi}</div>` : ''}
                        ${cliente.cliente_nit ? `<div><span class="font-medium">NIT:</span> ${cliente.cliente_nit}</div>` : ''}
                        ${!cliente.cliente_dpi && !cliente.cliente_nit ? '<span class="text-gray-400">Sin datos</span>' : ''}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm text-gray-900 dark:text-gray-100">
                        ${cliente.cliente_telefono ? `<div><i class="fas fa-phone text-gray-400 mr-1"></i>${cliente.cliente_telefono}</div>` : ''}
                        ${cliente.cliente_correo ? `<div class="truncate max-w-xs"><i class="fas fa-envelope text-gray-400 mr-1"></i>${cliente.cliente_correo}</div>` : ''}
                        ${!cliente.cliente_telefono && !cliente.cliente_correo ? '<span class="text-gray-400">Sin datos</span>' : ''}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${tipoBadge.class}">
                        ${tipoLabel}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${cliente.cliente_situacion == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                        ${cliente.cliente_situacion == 1 ? 'Activo' : 'Inactivo'}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                        <button onclick="window.clientesManager.openEmpresasModal(${cliente.cliente_id})" 
                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                title="Gestionar Empresas">
                            <i class="fas fa-building"></i>
                        </button>
                        <button onclick="window.clientesManager.openEditModal(${cliente.cliente_id})" 
                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="window.clientesManager.confirmDelete(${cliente.cliente_id})" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }

    renderEmpresasNombres(cliente) {
        if (!cliente.empresas || cliente.empresas.length === 0) {
            // Fallback to legacy field if no companies array
            return cliente.cliente_nom_empresa ? `
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-building mr-1"></i>${cliente.cliente_nom_empresa}
                </div>
            ` : '';
        }

        return cliente.empresas.map(emp => `
            <div class="text-xs text-gray-500 dark:text-gray-400">
                <i class="fas fa-building mr-1"></i>${emp.emp_nombre}
            </div>
        `).join('');
    }

    getIniciales(cliente) {
        const nombre = cliente.cliente_nombre1?.charAt(0) || '';
        const apellido = cliente.cliente_apellido1?.charAt(0) || '';
        return (nombre + apellido).toUpperCase();
    }

    getTipoLabel(tipo) {
        const tipos = {
            1: 'Normal',
            2: 'Premium',
            3: 'Empresa'
        };
        return tipos[tipo] || 'Desconocido';
    }

    getTipoBadge(tipo) {
        const badges = {
            1: { class: 'bg-blue-100 text-blue-800', gradient: 'from-blue-400 to-blue-600' },
            2: { class: 'bg-yellow-100 text-yellow-800', gradient: 'from-yellow-400 to-yellow-600' },
            3: { class: 'bg-green-100 text-green-800', gradient: 'from-green-400 to-green-600' }
        };
        return badges[tipo] || { class: 'bg-gray-100 text-gray-800', gradient: 'from-gray-400 to-gray-600' };
    }

    // ==========================
    // Modal Cliente
    // ==========================
    openCreateModal() {
        this.isEditing = false;
        this.editingClienteId = null;

        const modalTitle = document.getElementById('modal-title');
        const form = document.getElementById('cliente-form');
        const camposEmpresa = document.getElementById('campos-empresa');

        if (modalTitle) modalTitle.textContent = 'Nuevo Cliente';
        if (form) form.reset();
        if (camposEmpresa) camposEmpresa.classList.add('hidden');

        this.toggleModal(true);
    }

    openEditModal(clienteId) {
        this.isEditing = true;
        this.editingClienteId = clienteId;

        const cliente = this.clientes.find(c => c.cliente_id === clienteId);
        if (!cliente) {
            this.showAlert('error', 'Error', 'Cliente no encontrado');
            return;
        }

        const modalTitle = document.getElementById('modal-title');
        if (modalTitle) modalTitle.textContent = 'Editar Cliente';

        this.fillForm(cliente);
        this.toggleModal(true);
    }

    closeModal() {
        this.toggleModal(false);
        const form = document.getElementById('cliente-form');
        if (form) form.reset();
        this.isEditing = false;
        this.editingClienteId = null;
    }

    toggleModal(show) {
        const modal = document.getElementById('cliente-modal');
        if (!modal) return;

        if (show) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    fillForm(cliente) {
        const fields = {
            'cliente_nombre1': cliente.cliente_nombre1 || '',
            'cliente_nombre2': cliente.cliente_nombre2 || '',
            'cliente_apellido1': cliente.cliente_apellido1 || '',
            'cliente_apellido2': cliente.cliente_apellido2 || '',
            'cliente_dpi': cliente.cliente_dpi || '',
            'cliente_nit': cliente.cliente_nit || '',
            'cliente_telefono': cliente.cliente_telefono || '',
            'cliente_correo': cliente.cliente_correo || '',
            'cliente_direccion': cliente.cliente_direccion || '',
            'cliente_tipo': cliente.cliente_tipo || '',
            'cliente_user_id': cliente.cliente_user_id || '',
            'cliente_nom_empresa': cliente.cliente_nom_empresa || '',
            'cliente_nom_vendedor': cliente.cliente_nom_vendedor || '',
            'cliente_cel_vendedor': cliente.cliente_cel_vendedor || '',
            'cliente_ubicacion': cliente.cliente_ubicacion || ''
        };

        Object.entries(fields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.value = value;
        });

        // Mostrar campos empresa si es tipo 3
        if (cliente.cliente_tipo == 3) {
            this.toggleCamposEmpresa();
        }
    }

    toggleCamposEmpresa() {
        const tipoSelect = document.getElementById('cliente_tipo');
        const camposEmpresa = document.getElementById('campos-empresa');
        const container = document.getElementById('empresas-container');

        if (!tipoSelect || !camposEmpresa) return;

        const tipo = tipoSelect.value;

        if (tipo == '3') {
            camposEmpresa.classList.remove('hidden');
            // Si no hay campos de empresa, agregar uno por defecto
            if (container && container.children.length === 0) {
                this.addEmpresaField();
            }
        } else {
            camposEmpresa.classList.add('hidden');
        }
    }

    addEmpresaField() {
        const container = document.getElementById('empresas-container');
        if (!container) return;

        const index = container.children.length;
        const id = Date.now(); // Unique ID for DOM elements

        const html = `
            <div class="empresa-item bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600 relative animate-fade-in-down" id="empresa-${id}">
                ${index > 0 ? `
                <button type="button" onclick="document.getElementById('empresa-${id}').remove()" 
                        class="absolute top-2 right-2 text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                ` : ''}
                
                <h5 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase mb-3">Empresa ${index + 1}</h5>
                
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la Empresa *</label>
                        <input type="text" name="empresas[${index}][nombre]" required
                               class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIT</label>
                        <input type="text" name="empresas[${index}][nit]"
                               class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Dirección</label>
                        <input type="text" name="empresas[${index}][direccion]"
                               class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre Vendedor</label>
                        <input type="text" name="empresas[${index}][vendedor]"
                               class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Celular Vendedor</label>
                        <input type="text" name="empresas[${index}][cel_vendedor]"
                               class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-600 dark:text-white rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fas fa-file-pdf text-red-600 mr-1"></i> PDF Licencia de Compraventa
                        </label>
                        <input type="file" name="empresas[${index}][licencia]" accept=".pdf"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', html);
    }

    // ==========================
    // Modal PDF
    // ==========================
    verPdfLicenciaModal(clienteId) {
        const modal = document.getElementById('pdf-modal');
        const iframe = document.getElementById('pdf-iframe');
        const modalTitle = document.getElementById('pdf-modal-title');

        if (!modal || !iframe) {
            console.error('Modal de PDF no encontrado');
            return;
        }

        // Encontrar el cliente
        const cliente = this.clientes.find(c => c.cliente_id === clienteId);
        if (!cliente) {
            this.showAlert('error', 'Error', 'Cliente no encontrado');
            return;
        }

        // Actualizar título
        if (modalTitle) {
            modalTitle.textContent = `Licencia - ${cliente.nombre_completo}`;
        }

        // Mostrar loading
        iframe.classList.add('hidden');
        const loadingDiv = document.getElementById('pdf-loading');
        if (loadingDiv) loadingDiv.classList.remove('hidden');

        // Cargar PDF
        const pdfUrl = `/clientes/${clienteId}/ver-pdf-licencia`;
        iframe.src = pdfUrl;

        // Manejar carga del PDF
        iframe.onload = () => {
            if (loadingDiv) loadingDiv.classList.add('hidden');
            iframe.classList.remove('hidden');
        };

        iframe.onerror = () => {
            if (loadingDiv) loadingDiv.classList.add('hidden');
            this.closePdfModal();
            this.showAlert('error', 'Error', 'No se pudo cargar el PDF');
        };

        // Mostrar modal
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    closePdfModal() {
        const modal = document.getElementById('pdf-modal');
        const iframe = document.getElementById('pdf-iframe');

        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Limpiar iframe
        if (iframe) {
            iframe.src = '';
        }
    }

    // ==========================
    // Modal Empresas
    // ==========================
    openEmpresasModal(clienteId) {
        this.currentClienteId = clienteId;
        const cliente = this.clientes.find(c => c.cliente_id === clienteId);

        if (!cliente) return;

        document.getElementById('empresas-modal-title').textContent = `Empresas de ${cliente.nombre_completo}`;
        document.getElementById('emp_cliente_id').value = clienteId;

        this.renderEmpresasTable(cliente.empresas || []);
        this.resetEmpresaForm();

        const modal = document.getElementById('empresas-modal');
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Setup form listener
        const form = document.getElementById('empresa-form');
        form.onsubmit = (e) => this.handleEmpresaSubmit(e);
    }

    closeEmpresasModal() {
        const modal = document.getElementById('empresas-modal');
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    renderEmpresasTable(empresas) {
        const tbody = document.getElementById('empresas-tbody');
        if (!tbody) return;

        if (empresas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No hay empresas registradas</td></tr>';
            return;
        }

        tbody.innerHTML = empresas.map(emp => `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">${emp.emp_nombre}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${emp.emp_nit || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${emp.emp_direccion || '-'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                    ${emp.emp_licencia_compraventa ? `<a href="/storage/${emp.emp_licencia_compraventa}" target="_blank" class="text-blue-600 hover:underline"><i class="fas fa-file-pdf"></i> Ver</a>` : '-'}
                    ${emp.emp_licencia_vencimiento ? `<br><span class="text-xs text-gray-400">Vence: ${emp.emp_licencia_vencimiento}</span>` : ''}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="window.clientesManager.editEmpresa(${emp.emp_id})" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                    <button onclick="window.clientesManager.deleteEmpresa(${emp.emp_id})" class="text-red-600 hover:text-red-900">Eliminar</button>
                </td>
            </tr>
        `).join('');
    }

    resetEmpresaForm() {
        const form = document.getElementById('empresa-form');
        form.reset();
        document.getElementById('emp_id').value = '';
        document.getElementById('emp_cliente_id').value = this.currentClienteId;
        document.getElementById('form-empresa-title').textContent = 'Nueva Empresa';
    }

    editEmpresa(empId) {
        const cliente = this.clientes.find(c => c.cliente_id === this.currentClienteId);
        const empresa = cliente.empresas.find(e => e.emp_id === empId);

        if (!empresa) return;

        document.getElementById('emp_id').value = empresa.emp_id;
        document.getElementById('emp_nombre').value = empresa.emp_nombre;
        document.getElementById('emp_nit').value = empresa.emp_nit || '';
        document.getElementById('emp_direccion').value = empresa.emp_direccion || '';
        document.getElementById('emp_nom_vendedor').value = empresa.emp_nom_vendedor || '';
        document.getElementById('emp_cel_vendedor').value = empresa.emp_cel_vendedor || '';
        document.getElementById('emp_licencia_vencimiento').value = empresa.emp_licencia_vencimiento || '';
        // Nota: El input file no se puede prellenar por seguridad
        document.getElementById('form-empresa-title').textContent = 'Editar Empresa';
    }

    async handleEmpresaSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const empId = formData.get('emp_id');
        const clienteId = this.currentClienteId;

        const url = empId
            ? `/clientes/empresas/${empId}`
            : `/clientes/${clienteId}/empresas`;

        if (empId) formData.append('_method', 'PUT');

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken },
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.resetEmpresaForm();
                // Recargar datos del cliente
                // Idealmente solo recargaríamos el cliente específico, pero por simplicidad recargamos la página o actualizamos el array local
                // Vamos a actualizar el array local para no recargar toda la página
                const cliente = this.clientes.find(c => c.cliente_id === clienteId);
                if (empId) {
                    const index = cliente.empresas.findIndex(e => e.emp_id == empId);
                    if (index !== -1) cliente.empresas[index] = data.data;
                } else {
                    if (!cliente.empresas) cliente.empresas = [];
                    cliente.empresas.push(data.data);
                }
                this.renderEmpresasTable(cliente.empresas);
            } else {
                this.showAlert('error', 'Error', data.message);
            }
        } catch (error) {
            console.error(error);
            this.showAlert('error', 'Error', 'Ocurrió un error');
        }
    }

    async deleteEmpresa(empId) {
        const result = await Swal.fire({
            title: '¿Eliminar empresa?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Sí, eliminar'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`/clientes/empresas/${empId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showAlert('success', 'Eliminado', data.message);
                    const cliente = this.clientes.find(c => c.cliente_id === this.currentClienteId);
                    cliente.empresas = cliente.empresas.filter(e => e.emp_id !== empId);
                    this.renderEmpresasTable(cliente.empresas);
                } else {
                    this.showAlert('error', 'Error', data.message);
                }
            } catch (error) {
                console.error(error);
                this.showAlert('error', 'Error', 'Ocurrió un error');
            }
        }
    }

    // ==========================
    // CRUD
    // ==========================
    async handleSubmit(e) {
        e.preventDefault();

        const btnText = document.getElementById('btn-text');
        const btnLoading = document.getElementById('btn-loading');

        if (btnText) btnText.classList.add('hidden');
        if (btnLoading) btnLoading.classList.remove('hidden');

        try {
            const formData = new FormData(e.target);

            if (this.isEditing) {
                await this.updateCliente(this.editingClienteId, formData);
            } else {
                await this.createCliente(formData);
            }
        } catch (error) {
            console.error('Error en submit:', error);
        } finally {
            if (btnText) btnText.classList.remove('hidden');
            if (btnLoading) btnLoading.classList.add('hidden');
        }
    }

    async createCliente(formData) {
        try {
            const response = await fetch('/clientes', { // CAMBIADO DE /api/clientes/create a /clientes (resource)
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            // Verificar éxito usando ambos formatos
            const isSuccess = data.success === true || data.codigo === 1;

            if (isSuccess) {
                const mensaje = data.mensaje || data.message || 'Cliente guardado correctamente';
                this.showAlert('success', '¡Éxito!', mensaje);
                this.closeModal();

                setTimeout(() => window.location.reload(), 1500);
            } else {
                // Manejar errores
                const errores = data.errores || data.errors;

                if (errores) {
                    // Convertir objeto de errores a texto legible
                    const mensajesError = Object.values(errores)
                        .flat()
                        .join('<br>');

                    this.showAlert('error', 'Error de validación', mensajesError);
                } else {
                    const mensaje = data.mensaje || data.message || 'Ocurrió un error al crear el cliente';
                    this.showAlert('error', 'Error', mensaje);
                }
            }
        } catch (error) {
            console.error('Error al crear cliente:', error);
            this.showAlert('error', 'Error', 'Ocurrió un error al crear el cliente');
        }
    }

    async updateCliente(clienteId, formData) {
        try {
            formData.append('_method', 'PUT');

            const response = await fetch(`/clientes/${clienteId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('success', '¡Éxito!', data.message);
                this.closeModal();

                // Recargar página para actualizar datos
                setTimeout(() => window.location.reload(), 1500);
            } else {
                this.handleErrors(data);
            }
        } catch (error) {
            console.error('Error al actualizar cliente:', error);
            this.showAlert('error', 'Error', 'Ocurrió un error al actualizar el cliente');
        }
    }

    async confirmDelete(clienteId) {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción desactivará el cliente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            await this.deleteCliente(clienteId);
        }
    }

    async deleteCliente(clienteId) {
        try {
            const response = await fetch(`/clientes/${clienteId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('success', '¡Eliminado!', data.message);

                // Recargar página para actualizar datos
                setTimeout(() => window.location.reload(), 1500);
            } else {
                this.showAlert('error', 'Error', data.message);
            }
        } catch (error) {
            console.error('Error al eliminar cliente:', error);
            this.showAlert('error', 'Error', 'Ocurrió un error al eliminar el cliente');
        }
    }

    // ==========================
    // Errores
    // ==========================
    handleErrors(data) {
        if (data.errors) {
            const errorMessages = Object.values(data.errors).flat().join('<br>');
            this.showAlert('error', 'Error de validación', errorMessages);
        } else {
            this.showAlert('error', 'Error', data.message || 'Ocurrió un error');
        }
    }
}

// Inicializar cuando el DOM esté listo
// IMPORTANTE: Asignar a window para acceso global
document.addEventListener('DOMContentLoaded', () => {
    window.clientesManager = new ClientesManager();
    console.log('✅ clientesManager disponible globalmente');
});