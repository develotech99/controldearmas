import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css',
                'resources/js/app.js',
                'resources/js/usuarios/index.js',
                'resources/js/modelos/pro_modelo.js',
                'resources/js/usuarios/mapa.js',
                'resources/js/ventas/index.js',
                'resources/js/inventario/index.js',
                'resources/js/prolicencias/index.js',
                'resources/js/paises/index.js',
                'resources/js/metodos-pago/index.js',
                'resources/js/empresas/index.js',
                'resources/js/categorias/index.js',
                'resources/js/marcas/index.js',
                'resources/js/unidades-medida/index.js',
                'resources/js/calibres/index.js',
                'resources/js/comisiones/index.js',
                'resources/js/pagos/mispagos.js',
                'resources/js/pagos/administrar.js',
                'resources/js/reportes/index.js',
                'resources/js/clientes/clientes.js',
                'resources/js/dashboard.js',
                'resources/js/facturacion/index.js',
                'resources/js/ventas/reservadas.js',
                'resources/js/clientes/deudas.js'

            ],
            refresh: true,
        }),
    ],
});
