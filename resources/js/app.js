import './bootstrap';
import '../css/app.css';
import '../css/datatables-tailwind.css';

import Alpine from 'alpinejs';
import Swal from 'sweetalert2';

import $ from 'jquery';
window.$ = window.jQuery = $;

import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons/js/buttons.html5.js';
import 'datatables.net-buttons/js/buttons.print.js';
import 'datatables.net-responsive';

window.Swal = Swal;
window.Alpine = Alpine;

Alpine.start();

/**
 * Validación simple de formularios (modo exclusión por "excepciones")
 * - Ignora inputs hidden y disabled por defecto
 * - Agrega clase/estilos al campo inválido y limpia al escribir
 * - Muestra TODOS los campos faltantes en un alerta
 *
 * @param {HTMLFormElement} formulario
 * @param {string[]} excepciones  // ids o names a EXCLUIR de la validación
 * @returns {boolean}
 */
export const validarFormulario = (formulario, excepciones = []) => {
    const elements = Array.from(formulario.querySelectorAll('input, select, textarea'));

    elements.forEach(element => {
        element.style.borderColor = '';
        element.style.borderWidth = '';
        element.style.backgroundColor = '';
        element.style.boxShadow = '';
        element.classList.remove('error-field');

        const label = formulario.querySelector(`label[for="${element.id}"]`);
        if (label) {
            label.style.color = '';
            label.style.fontWeight = '';
        }
        const errNode = element.parentElement?.querySelector?.('[data-error-node]');
        if (errNode) errNode.textContent = '';
    });

    const faltantes = [];

    elements.forEach(element => {

        if (element.type === 'hidden') return;
        if (element.disabled) return;

        if (element.hasAttribute('data-optional')) return;

        const excluido = excepciones.includes(element.id) || excepciones.includes(element.name);
        if (excluido) return;

        let estaVacio = false;
        const tipo = (element.type || '').toLowerCase();
        if (tipo === 'checkbox' || tipo === 'radio') {
            const group = formulario.querySelectorAll(`input[name="${element.name}"]`);
            const algunoMarcado = Array.from(group).some(i => i.checked);
            estaVacio = !algunoMarcado;
        } else {
            estaVacio = !String(element.value || '').trim();
        }

        if (estaVacio) {
            element.style.borderColor = '#3b82f6';
            element.style.borderWidth = '2px';
            element.style.backgroundColor = '#eff6ff';
            element.style.boxShadow = '0 0 0 2px rgba(59, 130, 246, 0.12)';
            element.classList.add('error-field');

            const label = formulario.querySelector(`label[for="${element.id}"]`);
            if (label) {
                label.style.color = '#1d4ed8';
                label.style.fontWeight = '500';
            }

            const nombreCampo = element.id || element.name || 'Campo';
            const textoLabel = label?.textContent?.trim() || nombreCampo;
            faltantes.push({ element, texto: textoLabel });

            const limpiar = () => {
                element.style.borderColor = '';
                element.style.borderWidth = '';
                element.style.backgroundColor = '';
                element.style.boxShadow = '';
                element.classList.remove('error-field');
                if (label) {
                    label.style.color = '';
                    label.style.fontWeight = '';
                }
                element.removeEventListener('input', limpiar);
                element.removeEventListener('change', limpiar);
            };
            element.addEventListener('input', limpiar);
            element.addEventListener('change', limpiar);
        }
    });

    if (faltantes.length > 0) {
        const lista = faltantes
            .map(f => `<li style="margin:.2rem 0;">• ${f.texto}</li>`)
            .join('');

        Swal.fire({
            icon: 'error',
            title: 'Faltan campos por completar',
            html: `<ul style="text-align:left; margin:0; padding-left:1rem;">${lista}</ul>`,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Entendido'
        });

        const primero = faltantes[0]?.element;
        if (primero) {
            primero.focus();
            primero.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }

    return true;
};

window.validarFormulario = validarFormulario;
