/**
 * Manual de Usuario Manager
 * Gestión del manual de sistema global
 */

class ManualManager {
    constructor() {
        this.init();
    }

    init() {
        console.log('Iniciando Manual Manager...');
        this.configurarEventos();

        // Exponer función global para abrir secciones específicas
        window.openManual = (section) => this.abrirManual(section);
    }

    configurarEventos() {
        const modal = document.getElementById('modalManual');
        const btnCerrar = document.getElementById('btn-cerrar-manual');
        const btnEntendido = document.getElementById('btn-entendido-manual');
        const backdrop = document.getElementById('modalManualBackdrop');

        // Botones que abren el manual (clase global)
        document.querySelectorAll('.btn-abrir-manual').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const section = btn.dataset.section || '1';
                this.abrirManual(section);
            });
        });

        // Cerrar modal
        const cerrarModal = () => {
            if (modal) modal.classList.add('hidden');
        };

        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnEntendido) btnEntendido.addEventListener('click', cerrarModal);
        if (backdrop) backdrop.addEventListener('click', cerrarModal);

        // Navegación del manual
        const navButtons = document.querySelectorAll('#manual-nav button');
        const contents = document.querySelectorAll('#manual-content > div');

        navButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const step = btn.dataset.step;
                this.mostrarSeccion(step);
            });
        });
    }

    abrirManual(sectionId = '1') {
        const modal = document.getElementById('modalManual');
        if (modal) {
            modal.classList.remove('hidden');

            // Mapeo de nombres a IDs si es necesario
            const map = {
                'inventario': '1',
                'ventas': '2',
                'preventas': '3',
                'autorizacion': '4',
                'facturacion': '5',
                'clientes': '6'
            };

            const step = map[sectionId] || sectionId || '1';
            this.mostrarSeccion(step);
        }
    }

    mostrarSeccion(step) {
        const navButtons = document.querySelectorAll('#manual-nav button');
        const contents = document.querySelectorAll('#manual-content > div');

        // Actualizar botones
        navButtons.forEach(b => {
            if (b.dataset.step === step) {
                b.className = 'w-full text-left px-3 py-2 rounded-md text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 transition-colors';
            } else {
                b.className = 'w-full text-left px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white transition-colors';
            }
        });

        // Actualizar contenido
        contents.forEach(content => {
            if (content.dataset.content === step) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
    }
}

// Inicializar al cargar el DOM
document.addEventListener('DOMContentLoaded', function () {
    window.manualManager = new ManualManager();
});
