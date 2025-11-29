/**
 * Gestor de Categorías y Subcategorías
 * Sistema CRM con JavaScript puro - Laravel 12
 */
class CategoriasManager {
    constructor() {
        this.categorias = [];
        this.categoriasOriginales = [];
        this.isEditing = {
            categoria: false,
            subcategoria: false
        };
        this.editingId = {
            categoria: null,
            subcategoria: null
        };
        this.filtros = {
            searchCategorias: '',
            searchSubcategorias: '',
            statusCategorias: '',
            statusSubcategorias: ''
        };
        
        this.init();
    }

    /**
     * Inicializar el gestor
     */
    init() {
        console.log('🚀 CategoriasManager inicializado');
        this.loadCategorias();
        this.setupEventListeners();
        this.renderCategorias();
        this.renderSubcategorias();
        this.updateStats();
        this.loadCategoriasForSelect();
    }

    /**
     * Cargar categorías desde el script tag
     */
    loadCategorias() {
        try {
            const categoriasData = document.getElementById('categorias-data');
            if (categoriasData) {
                this.categorias = JSON.parse(categoriasData.textContent);
                this.categoriasOriginales = [...this.categorias];
                console.log('📊 Categorías cargadas:', this.categorias.length);
            }
        } catch (error) {
            console.error('❌ Error cargando categorías:', error);
            this.categorias = [];
            this.categoriasOriginales = [];
        }
    }

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Filtros
        document.getElementById('search-categorias').addEventListener('input', (e) => {
            this.filtros.searchCategorias = e.target.value;
            this.aplicarFiltros();
        });

        document.getElementById('search-subcategorias').addEventListener('input', (e) => {
            this.filtros.searchSubcategorias = e.target.value;
            this.aplicarFiltros();
        });

        document.getElementById('status-filter-categorias').addEventListener('change', (e) => {
            this.filtros.statusCategorias = e.target.value;
            this.aplicarFiltros();
        });

        document.getElementById('status-filter-subcategorias').addEventListener('change', (e) => {
            this.filtros.statusSubcategorias = e.target.value;
            this.aplicarFiltros();
        });

        // Formularios
        document.getElementById('categoria-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCategoriaSubmit();
        });

        document.getElementById('subcategoria-form').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleSubcategoriaSubmit();
        });

        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal('categoria');
                this.closeModal('subcategoria');
            }
        });
    }

    /**
     * Aplicar filtros
     */
    aplicarFiltros() {
        this.renderCategorias();
        this.renderSubcategorias();
        this.updateStats();
    }

    /**
     * Limpiar filtros
     */
    clearFilters() {
        this.filtros = {
            searchCategorias: '',
            searchSubcategorias: '',
            statusCategorias: '',
            statusSubcategorias: ''
        };

        document.getElementById('search-categorias').value = '';
        document.getElementById('search-subcategorias').value = '';
        document.getElementById('status-filter-categorias').value = '';
        document.getElementById('status-filter-subcategorias').value = '';

        this.aplicarFiltros();
    }

    /**
     * Filtrar categorías
     */
    getCategoriasFiltradas() {
        return this.categorias.filter(categoria => {
            const matchSearch = !this.filtros.searchCategorias || 
                categoria.categoria_nombre.toLowerCase().includes(this.filtros.searchCategorias.toLowerCase());
            
            const matchStatus = this.filtros.statusCategorias === '' || 
                categoria.categoria_situacion.toString() === this.filtros.statusCategorias;

            return matchSearch && matchStatus;
        });
    }

    /**
     * Filtrar subcategorías
     */
    getSubcategoriasFiltradas() {
        let subcategorias = [];
        
        this.categorias.forEach(categoria => {
            if (categoria.subcategorias) {
                categoria.subcategorias.forEach(sub => {
                    subcategorias.push({
                        ...sub,
                        categoria_nombre: categoria.categoria_nombre
                    });
                });
            }
        });

        return subcategorias.filter(subcategoria => {
            const matchSearch = !this.filtros.searchSubcategorias || 
                subcategoria.subcategoria_nombre.toLowerCase().includes(this.filtros.searchSubcategorias.toLowerCase());
            
            const matchStatus = this.filtros.statusSubcategorias === '' || 
                subcategoria.subcategoria_situacion.toString() === this.filtros.statusSubcategorias;

            return matchSearch && matchStatus;
        });
    }

    /**
     * Renderizar categorías
     */
    renderCategorias() {
        const container = document.getElementById('categorias-list');
        const categoriasFiltradas = this.getCategoriasFiltradas();

        if (categoriasFiltradas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-4L5 3m14 8l-14 4" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No hay categorías que mostrar</p>
                </div>
            `;
            return;
        }

        container.innerHTML = categoriasFiltradas.map(categoria => `
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center">
                                <span class="text-xs font-medium text-white">${categoria.categoria_nombre.substring(0, 2).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                ${categoria.categoria_nombre}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                ${categoria.subcategorias_count} subcategorías • ${categoria.subcategorias_activas} activas
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            categoria.categoria_situacion == 1 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        }">
                            ${categoria.categoria_situacion == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                        <button onclick="categoriasManager.editCategoria(${categoria.categoria_id})" 
                                class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 p-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button onclick="categoriasManager.deleteCategoria(${categoria.categoria_id})" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('categorias-count').textContent = categoriasFiltradas.length;
    }

    /**
     * Renderizar subcategorías
     */
    renderSubcategorias() {
        const container = document.getElementById('subcategorias-list');
        const subcategoriasFiltradas = this.getSubcategoriasFiltradas();

        if (subcategoriasFiltradas.length === 0) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No hay subcategorías que mostrar</p>
                </div>
            `;
            return;
        }

        container.innerHTML = subcategoriasFiltradas.map(subcategoria => `
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-8 w-8">
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-xs font-medium text-white">${subcategoria.subcategoria_nombre.substring(0, 2).toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                ${subcategoria.subcategoria_nombre}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                Categoría: ${subcategoria.categoria_nombre}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                            subcategoria.subcategoria_situacion == 1 
                                ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' 
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        }">
                            ${subcategoria.subcategoria_situacion == 1 ? 'Activo' : 'Inactivo'}
                        </span>
                        <button onclick="categoriasManager.editSubcategoria(${subcategoria.subcategoria_id})" 
                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button onclick="categoriasManager.deleteSubcategoria(${subcategoria.subcategoria_id})" 
                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-1">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        document.getElementById('subcategorias-count').textContent = subcategoriasFiltradas.length;
    }

    /**
     * Actualizar estadísticas
     */
    updateStats() {
        const totalCategorias = this.categorias.length;
        const categoriasActivas = this.categorias.filter(c => c.categoria_situacion == 1).length;
        
        let totalSubcategorias = 0;
        let subcategoriasActivas = 0;
        
        this.categorias.forEach(categoria => {
            if (categoria.subcategorias) {
                totalSubcategorias += categoria.subcategorias.length;
                subcategoriasActivas += categoria.subcategorias.filter(s => s.subcategoria_situacion == 1).length;
            }
        });

        document.getElementById('total-categorias').textContent = totalCategorias;
        document.getElementById('categorias-activas').textContent = categoriasActivas;
        document.getElementById('total-subcategorias').textContent = totalSubcategorias;
        document.getElementById('subcategorias-activas').textContent = subcategoriasActivas;
    }

    /**
     * Cargar categorías para el select
     */
    async loadCategoriasForSelect() {
        try {
            const response = await fetch('/categorias/activas');
            if (response.ok) {
                const data = await response.json();
                const select = document.getElementById('subcategoria_idcategoria');
                
                // Limpiar options existentes (excepto el placeholder)
                select.innerHTML = '<option value="">Seleccionar categoría</option>';
                
                // Agregar las categorías activas
                data.data.forEach(categoria => {
                    const option = document.createElement('option');
                    option.value = categoria.categoria_id;
                    option.textContent = categoria.categoria_nombre;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error cargando categorías para select:', error);
        }
    }

    /**
     * Abrir modal para crear categoría
     */
    openCreateCategoriaModal() {
        this.isEditing.categoria = false;
        this.editingId.categoria = null;
        
        document.getElementById('categoria-modal-title').textContent = 'Crear Nueva Categoría';
        document.getElementById('categoria-submit-text').textContent = 'Crear';
        
        this.resetCategoriaForm();
        this.showModal('categoria');
    }

    /**
     * Abrir modal para crear subcategoría
     */
    openCreateSubcategoriaModal() {
        this.isEditing.subcategoria = false;
        this.editingId.subcategoria = null;
        
        document.getElementById('subcategoria-modal-title').textContent = 'Crear Nueva Subcategoría';
        document.getElementById('subcategoria-submit-text').textContent = 'Crear';
        
        this.resetSubcategoriaForm();
        this.loadCategoriasForSelect();
        this.showModal('subcategoria');
    }

    /**
     * Editar categoría
     */
    editCategoria(categoriaId) {
        const categoria = this.categorias.find(c => c.categoria_id === categoriaId);
        
        if (!categoria) {
            this.showAlert('error', 'Error', 'Categoría no encontrada');
            return;
        }

        this.isEditing.categoria = true;
        this.editingId.categoria = categoriaId;
        
        document.getElementById('categoria-modal-title').textContent = 'Editar Categoría';
        document.getElementById('categoria-submit-text').textContent = 'Actualizar';
        
        // Llenar el formulario
        document.getElementById('categoria_nombre').value = categoria.categoria_nombre;
        
        this.showModal('categoria');
    }

    /**
     * Editar subcategoría
     */
    editSubcategoria(subcategoriaId) {
        let subcategoria = null;
        
        // Buscar la subcategoría en todas las categorías
        for (const categoria of this.categorias) {
            if (categoria.subcategorias) {
                subcategoria = categoria.subcategorias.find(s => s.subcategoria_id === subcategoriaId);
                if (subcategoria) {
                    subcategoria.categoria_id = categoria.categoria_id;
                    break;
                }
            }
        }
        
        if (!subcategoria) {
            this.showAlert('error', 'Error', 'Subcategoría no encontrada');
            return;
        }

        this.isEditing.subcategoria = true;
        this.editingId.subcategoria = subcategoriaId;
        
        document.getElementById('subcategoria-modal-title').textContent = 'Editar Subcategoría';
        document.getElementById('subcategoria-submit-text').textContent = 'Actualizar';
        
        // Cargar categorías y luego llenar el formulario
        this.loadCategoriasForSelect().then(() => {
            document.getElementById('subcategoria_idcategoria').value = subcategoria.categoria_id;
            document.getElementById('subcategoria_nombre').value = subcategoria.subcategoria_nombre;

        });
        
        this.showModal('subcategoria');
    }

    /**
     * Manejar envío del formulario de categoría
     */
    async handleCategoriaSubmit() {
        const form = document.getElementById('categoria-form');
        const formData = new FormData(form);
        
        // Validar formulario
        if (!this.validateCategoriaForm()) {
            return;
        }

        this.setLoading('categoria', true);

        try {
            const url = this.isEditing.categoria 
                ? `/categorias/${this.editingId.categoria}`
                : '/categorias';
            
            const method = 'POST';
            
            // Agregar método para PUT si estamos editando
            if (this.isEditing.categoria) {
                formData.append('_method', 'PUT');
            }
            
            // Agregar CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.closeModal('categoria');
                
                // Recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                if (data.errors) {
                    this.showValidationErrors('categoria', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al procesar la solicitud');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('categoria', false);
        }
    }

    /**
     * Manejar envío del formulario de subcategoría
     */
    async handleSubcategoriaSubmit() {
        const form = document.getElementById('subcategoria-form');
        const formData = new FormData(form);
        
        // Validar formulario
        if (!this.validateSubcategoriaForm()) {
            return;
        }

        this.setLoading('subcategoria', true);

        try {
            const url = this.isEditing.subcategoria 
                ? `/categorias/subcategorias/${this.editingId.subcategoria}`
                : '/categorias/subcategorias';
            
            const method = 'POST';
            
            // Agregar método para PUT si estamos editando
            if (this.isEditing.subcategoria) {
                formData.append('_method', 'PUT');
            }
            
            // Agregar CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('_token', csrfToken);
            }

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.showAlert('success', 'Éxito', data.message);
                this.closeModal('subcategoria');
                
                // Recargar la página después de un breve delay
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                if (data.errors) {
                    this.showValidationErrors('subcategoria', data.errors);
                } else {
                    this.showAlert('error', 'Error', data.message || 'Error al procesar la solicitud');
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('error', 'Error', 'Error de conexión');
        } finally {
            this.setLoading('subcategoria', false);
        }
    }

/**
 * Eliminar categoría
 */
async deleteCategoria(categoriaId) {
    const categoria = this.categorias.find(c => c.categoria_id === categoriaId);
    
    if (!categoria) {
        this.showAlert('error', 'Error', 'Categoría no encontrada');
        return;
    }

    // Verificar si tiene subcategorías
    if (categoria.subcategorias_count > 0) {
        this.showAlert('warning', 'Advertencia', 
            `No se puede eliminar la categoría "${categoria.categoria_nombre}" porque tiene ${categoria.subcategorias_count} subcategorías asociadas. Elimine primero las subcategorías.`);
        return;
    }

    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar la categoría "${categoria.categoria_nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'dark:bg-gray-800 dark:text-gray-100',
            title: 'dark:text-gray-100',
            content: 'dark:text-gray-300'
        }
    });

    if (result.isConfirmed) {
        await this.executeDelete(`/categorias/${categoriaId}`, 'categoría');
    }
}

/**
 * Eliminar subcategoría
 */
async deleteSubcategoria(subcategoriaId) {
    let subcategoria = null;
    
    // Buscar la subcategoría
    for (const categoria of this.categorias) {
        if (categoria.subcategorias) {
            subcategoria = categoria.subcategorias.find(s => s.subcategoria_id === subcategoriaId);
            if (subcategoria) break;
        }
    }
    
    if (!subcategoria) {
        this.showAlert('error', 'Error', 'Subcategoría no encontrada');
        return;
    }

    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `¿Deseas eliminar la subcategoría "${subcategoria.subcategoria_nombre}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        customClass: {
            popup: 'dark:bg-gray-800 dark:text-gray-100',
            title: 'dark:text-gray-100',
            content: 'dark:text-gray-300'
        }
    });

    if (result.isConfirmed) {
        await this.executeDelete(`/categorias/subcategorias/${subcategoriaId}`, 'subcategoría');
    }
}

/**
 * Ejecutar eliminación mediante AJAX
 */
async executeDelete(url, tipo) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            this.showAlert('success', 'Éxito', data.message);
            
            // Recargar la página después de un breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            this.showAlert('error', 'Error', data.message || `Error al eliminar la ${tipo}`);
        }
    } catch (error) {
        console.error('Error:', error);
        this.showAlert('error', 'Error', 'Error de conexión al eliminar');
    }
}

  

    /**
     * Validar formulario de categoría
     */
    validateCategoriaForm() {
        const nombre = document.getElementById('categoria_nombre').value.trim();
   
        
        this.clearErrors('categoria');
        
        let isValid = true;
        
        if (!nombre) {
            this.showFieldError('categoria_nombre', 'El nombre de la categoría es obligatorio');
            isValid = false;
        } else if (nombre.length > 100) {
            this.showFieldError('categoria_nombre', 'El nombre no puede exceder 100 caracteres');
            isValid = false;
        }
        
        
        return isValid;
    }

    /**
     * Validar formulario de subcategoría
     */
    validateSubcategoriaForm() {
        const nombre = document.getElementById('subcategoria_nombre').value.trim();
        const categoria = document.getElementById('subcategoria_idcategoria').value;
  
        
        this.clearErrors('subcategoria');
        
        let isValid = true;
        
        if (!categoria) {
            this.showFieldError('subcategoria_idcategoria', 'La categoría es obligatoria');
            isValid = false;
        }
        
        if (!nombre) {
            this.showFieldError('subcategoria_nombre', 'El nombre de la subcategoría es obligatorio');
            isValid = false;
        } else if (nombre.length > 100) {
            this.showFieldError('subcategoria_nombre', 'El nombre no puede exceder 100 caracteres');
            isValid = false;
        }
        
        
        return isValid;
    }

    /**
     * Mostrar modal
     */
    showModal(type) {
        const modal = document.getElementById(`${type}-modal`);
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    /**
     * Cerrar modal
     */
    closeModal(type) {
        const modal = document.getElementById(`${type}-modal`);
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        
        this.setLoading(type, false);
        this.clearErrors(type);
        
        if (type === 'categoria') {
            this.resetCategoriaForm();
        } else {
            this.resetSubcategoriaForm();
        }
    }

    /**
     * Resetear formulario de categoría
     */
    resetCategoriaForm() {
        document.getElementById('categoria-form').reset();
        this.clearErrors('categoria');
    }

    /**
     * Resetear formulario de subcategoría
     */
    resetSubcategoriaForm() {
        document.getElementById('subcategoria-form').reset();
        this.clearErrors('subcategoria');
    }

    /**
     * Establecer estado de carga
     */
    setLoading(type, loading) {
        const submitBtn = document.getElementById(`${type}-submit-btn`);
        const submitText = document.getElementById(`${type}-submit-text`);
        const loadingSpan = document.getElementById(`${type}-loading`);
        
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
     * Mostrar errores de validación
     */
    showValidationErrors(type, errors) {
        Object.keys(errors).forEach(field => {
            const errorElement = document.getElementById(`${field}_error`);
            if (errorElement && errors[field].length > 0) {
                errorElement.textContent = errors[field][0];
                errorElement.classList.remove('hidden');
            }
        });
    }

    /**
     * Mostrar alerta
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
        } else if (type === 'error') {
            config.confirmButtonColor = '#dc2626';
        } else if (type === 'warning') {
            config.confirmButtonColor = '#f59e0b';
        }

        Swal.fire(config);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.categoriasManager = new CategoriasManager();
});