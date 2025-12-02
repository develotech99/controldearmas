import Swal from "sweetalert2";
import DataTable from "vanilla-datatables";
import "vanilla-datatables/src/vanilla-dataTables.css";


let datatableUsuarios = null;
let isEditing = false;
let currentUserId = null;
const form = document.getElementById('formUsuario');

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
    datatableUsuarios = new DataTable('#datatableUsuarios', {
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
            headings: ["Usuario", "Email", "Rol", "Registrado", "Acciones"],
            data: []
        },
        order: [[3, 'desc']] // Ordenar por fecha de registro descendente
    });
};

const buscarUsuarios = async (filtros = {}) => {
    try {
        let url = "/api/usuarios/obtener";

        const searchParams = new URLSearchParams();
        if (filtros.search) searchParams.append('search', filtros.search);
        if (filtros.rol) searchParams.append('rol', filtros.rol);
        if (searchParams.toString()) url += '?' + searchParams.toString();

        const config = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };

        const respuesta = await fetch(url, config);
        const data = await respuesta.json();

        const { codigo, mensaje, datos } = data;

        if (codigo == 1) {
            if (Array.isArray(datos) && datos.length) {
                const tableData = datos.map(usuario => {
                    const nombreCompleto = `${usuario.primer_nombre || ''} ${usuario.segundo_nombre || ''} ${usuario.primer_apellido || ''} ${usuario.segundo_apellido || ''}`.trim();
                    const iniciales = getIniciales(nombreCompleto);


                    const usuarioStr = JSON.stringify(usuario).replace(/'/g, '&#39;');

                    return [
                        `<div class="flex items-center gap-3">
              <div class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center">
                <span class="text-sm font-semibold text-white">${iniciales}</span>
              </div>
              <div>
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">${nombreCompleto}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">DPI: ${usuario.dpi_dni || 'N/A'}</div>
              </div>
            </div>`,

                        usuario.email || 'N/D',

                        usuario.rol
                            ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                   ${usuario.rol.nombre}
                 </span>`
                            : '<span class="text-gray-400">Sin rol</span>',

                        usuario.created_at ? new Date(usuario.created_at).toLocaleDateString('es-GT') : '',

                        `<div class="flex items-center justify-end gap-2">
              <button class="btn-ver p-2 text-blue-600 hover:bg-blue-50 rounded-md transition-colors" 
                      data-id="${usuario.id}" data-usuario='${usuarioStr}' title="Ver detalles">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
              <button class="btn-editar p-2 text-green-600 hover:bg-green-50 rounded-md transition-colors" 
                      data-id="${usuario.id}" data-usuario='${usuarioStr}' title="Editar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
              </button>
              <button class="btn-eliminar p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors" 
                      data-id="${usuario.id}" title="Eliminar">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
              </button>
            </div>`
                    ];
                });

                // re-crear la tabla
                if (datatableUsuarios) datatableUsuarios.destroy();
                datatableUsuarios = new DataTable('#datatableUsuarios', {
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
                        headings: ["Usuario", "Email", "Rol", "Registrado", "Acciones"],
                        data: tableData
                    }
                });

            } else {
                // sin datos
                if (datatableUsuarios) datatableUsuarios.destroy();
                datatableUsuarios = new DataTable('#datatableUsuarios', {
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
                        headings: ["Usuario", "Email", "Rol", "Registrado", "Acciones"],
                        data: []
                    }
                });
                Swal.fire('Aviso', 'No hay datos para mostrar', 'warning');
            }
        } else {
            Swal.fire('Error', mensaje || 'Ocurrió un error en la respuesta', 'error');
        }

    } catch (error) {
        console.error("Error cargando usuarios:", error);
        Swal.fire('Error', 'Error de conexión o formato de respuesta inválido', 'error');
    }
};


const getIniciales = (nombreCompleto) => {
    if (!nombreCompleto) return 'US';
    const nombres = nombreCompleto.trim().split(' ');
    if (nombres.length >= 2) {
        return (nombres[0][0] + nombres[nombres.length - 1][0]).toUpperCase();
    }
    return nombres[0][0].toUpperCase() + 'U';
};


const abrirModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
    }
};

const cerrarModal = (modalId) => {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
    }
};


const limpiarFormulario = () => {
    const form = document.getElementById('formUsuario');
    if (form) {
        form.reset();
        document.getElementById('user_id').value = '';
        isEditing = false;
        currentUserId = null;

        document.getElementById('modalUsuarioTitle').textContent = 'Crear usuario';
        document.getElementById('btnSubmitUsuarioText').textContent = 'Crear';


        const passwordField = document.getElementById('password');
        const passwordConfirmField = document.getElementById('password_confirmation');
        if (passwordField && passwordConfirmField) {
            passwordField.required = true;
            passwordConfirmField.required = true;
        }
    }
};

const cargarDatosEdicion = (usuario) => {
    document.getElementById('user_id').value = usuario.id || '';
    document.getElementById('user_dpi_dni').value = usuario.dpi_dni || '';
    document.getElementById('email').value = usuario.email || '';
    document.getElementById('user_primer_nombre').value = usuario.primer_nombre || '';
    document.getElementById('user_segundo_nombre').value = usuario.segundo_nombre || '';
    document.getElementById('user_primer_apellido').value = usuario.primer_apellido || '';
    document.getElementById('user_segundo_apellido').value = usuario.segundo_apellido || '';
    document.getElementById('user_rol').value = usuario.rol_id || '';

    const empresaInput = document.getElementById('user_empresa');
    if (empresaInput) {
        empresaInput.value = usuario.empresa || '';
    }

    document.getElementById('password').value = '';
    document.getElementById('password_confirmation').value = '';
    document.getElementById('password').required = false;
    document.getElementById('password_confirmation').required = false;

    isEditing = true;
    currentUserId = usuario.id;

    document.getElementById('modalUsuarioTitle').textContent = 'Editar usuario';
    document.getElementById('btnSubmitUsuarioText').textContent = 'Actualizar';
};
// Mostrar datos en modal de ver
const mostrarDatosUsuario = (usuario) => {
    const nombreCompleto = `${usuario.primer_nombre || ''} ${usuario.segundo_nombre || ''} ${usuario.primer_apellido || ''} ${usuario.segundo_apellido || ''}`.trim();
    const iniciales = getIniciales(nombreCompleto);

    document.getElementById('verIniciales').textContent = iniciales;
    document.getElementById('verNombre').textContent = nombreCompleto || 'Usuario';
    document.getElementById('verEmail').textContent = usuario.email || 'N/A';
    document.getElementById('verRol').textContent = usuario.rol ? usuario.rol.nombre : 'Sin rol';

    if (usuario.created_at) {
        const fecha = new Date(usuario.created_at);
        document.getElementById('verFecha').textContent = fecha.toLocaleDateString('es-GT');
    } else {
        document.getElementById('verFecha').textContent = 'N/A';
    }
};

// Agregar esta función al inicio de tu archivo JS, después de las otras funciones auxiliares
const mostrarAlerta = (mensaje, tipo = 'info') => {
    const iconos = {
        success: 'success',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };

    Swal.fire({
        title: tipo === 'success' ? 'Éxito' : tipo === 'error' ? 'Error' : 'Aviso',
        text: mensaje,
        icon: iconos[tipo] || 'info',
        confirmButtonText: 'Aceptar'
    });
};

const eliminarUsuario = async (userId) => {
    try {
        // Confirmar antes de eliminar
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción desactivará el usuario",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (!result.isConfirmed) {
            return;
        }

        swalLoadingOpen('Eliminando usuario...');

        const config = {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        };

        const respuesta = await fetch(`/usuarios/${userId}`, config);
        const data = await respuesta.json();

        swalLoadingClose();

        if (data.codigo === 1) {
            await Swal.fire('Éxito', data.mensaje || 'Usuario eliminado exitosamente', 'success');
            buscarUsuarios(); // Recargar datos
        } else {
            Swal.fire('Error', data.mensaje || 'Error al eliminar usuario', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        swalLoadingClose();
        Swal.fire('Error', 'Error de conexión', 'error');
    }
};


function debounce(func, wait) {
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


document.addEventListener('click', (e) => {
    // Cerrar modales
    if (e.target.matches('[data-modal-close]') || e.target.matches('[data-modal-backdrop]')) {
        cerrarModal('modalUsuario');
        cerrarModal('modalVerUsuario');
    }

    // Abrir modal crear
    if (e.target.matches('#btnOpenCreate') || e.target.closest('#btnOpenCreate')) {
        limpiarFormulario();
        abrirModal('modalUsuario');
    }

    // Ver usuario
    if (e.target.matches('.btn-ver') || e.target.closest('.btn-ver')) {
        const btn = e.target.closest('.btn-ver');
        const usuarioData = btn.dataset.usuario;
        if (usuarioData) {
            const usuario = JSON.parse(usuarioData);
            mostrarDatosUsuario(usuario);
            abrirModal('modalVerUsuario');
        }
    }

    // Editar usuario
    if (e.target.matches('.btn-editar') || e.target.closest('.btn-editar')) {
        const btn = e.target.closest('.btn-editar');
        const usuarioData = btn.dataset.usuario;
        if (usuarioData) {
            const usuario = JSON.parse(usuarioData);
            cargarDatosEdicion(usuario);
            abrirModal('modalUsuario');
        }
    }

    // Eliminar usuario
    // Este código ya lo tienes, pero verifica que esté así:
    if (e.target.matches('.btn-eliminar') || e.target.closest('.btn-eliminar')) {
        const btn = e.target.closest('.btn-eliminar');
        const userId = btn.dataset.id;
        eliminarUsuario(userId); // Ya no necesita el confirm() porque lo hace dentro
    }

    // Limpiar filtros
    if (e.target.matches('#btnClearFilters')) {
        document.getElementById('inputSearch').value = '';
        document.getElementById('selectRoleFilter').value = '';
        buscarUsuarios({});
    }
});


const searchInput = document.getElementById('inputSearch');
const roleSelect = document.getElementById('selectRoleFilter');

if (searchInput) {
    searchInput.addEventListener('input', debounce((e) => {
        const searchTerm = e.target.value.trim();
        const roleFilter = roleSelect ? roleSelect.value : '';

        const filtros = {};
        if (searchTerm) filtros.search = searchTerm;
        if (roleFilter) filtros.rol = roleFilter;

        buscarUsuarios(filtros);
    }, 300));
}

if (roleSelect) {
    roleSelect.addEventListener('change', (e) => {
        const roleFilter = e.target.value;
        const searchTerm = searchInput ? searchInput.value.trim() : '';

        const filtros = {};
        if (searchTerm) filtros.search = searchTerm;
        if (roleFilter) filtros.rol = roleFilter;

        buscarUsuarios(filtros);
    });
}


if (form) {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();


        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const datos = Object.fromEntries(formData);


        if (datos.password && datos.password !== datos.password_confirmation) {
            await Swal.fire('Atencion', 'Las contraseñas no coinciden', 'warning')
            return;
        }

        try {
            const url = isEditing ? `/api/usuarios/${currentUserId}` : '/api/usuarios';
            const method = isEditing ? 'PUT' : 'POST';

            const config = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(datos)
            };

            swalLoadingOpen(isEditing ? 'Actualizando usuario...' : 'Creando usuario...');

            const respuesta = await fetch(url, config);
            const data = await respuesta.json();

            swalLoadingClose();

            const { codigo, mensaje } = data;

            if (codigo === 1) {
                await Swal.fire('Éxito', mensaje || (isEditing ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente'), 'success');
                cerrarModal('modalUsuario');
                buscarUsuarios();
            } else {
                Swal.fire('Error', mensaje || 'Error al procesar la solicitud', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            swalLoadingClose();
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
}



// Inicializar aplicación
initDataTable();
buscarUsuarios();
