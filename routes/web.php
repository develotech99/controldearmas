<?php

use App\Http\Controllers\FacturacionController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminPagosController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaisController;
use App\Http\Controllers\UnidadMedidaController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\CalibreController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PagosController;
use App\Http\Controllers\MarcasController;
use App\Http\Controllers\VentasController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\TipoArmaController;
use App\Http\Controllers\ProModeloController;
use App\Http\Controllers\ComisionesController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PagoLicenciaController;
use App\Http\Controllers\UsersUbicacionController;
use App\Http\Controllers\ProEmpresaDeImportacionController;
use App\Http\Controllers\ProLicenciaParaImportacionController;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/api/usuarios/verificar', [UserController::class, 'verificarCorreoAPI'])->name('usuarios.verificar');
Route::get('/confirmemail-register', [UserController::class, 'confirmEmailSucess'])->name('confirmemail.success');

Route::middleware('auth')->group(function () {

    Route::resource('prolicencias', ProLicenciaParaImportacionController::class);

    // Route::get('/dashboard', function () {
    //       return view('dashboard');
    // })->name('dashboard');


    //Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // API Routes para Dashboard
    Route::prefix('api/dashboard')->group(function () {
        Route::get('/estadisticas', [DashboardController::class, 'getEstadisticas']);
        Route::get('/resumen-ventas', [DashboardController::class, 'getResumenVentas']);
        Route::get('/productos-vendidos', [DashboardController::class, 'getProductosMasVendidos']);
    });


    Route::resource('proempresas', ProEmpresaDeImportacionController::class);

    // Rutas para pagos de licencias
    Route::get('/prolicencias/{licencia}/pagos', [PagoLicenciaController::class, 'index']);
    Route::post('/prolicencias/{licencia}/pagos', [PagoLicenciaController::class, 'store']);
    Route::get('/prolicencias/pagos/{pago}', [PagoLicenciaController::class, 'show']);
    Route::put('/prolicencias/pagos/{pago}', [PagoLicenciaController::class, 'update']);
    Route::delete('prolicencias/pagos/{pago:pago_lic_id}', [PagoLicenciaController::class, 'destroy']);

    // Documentos de licencias
    Route::post('prolicencias/{licencia}/upload-pdfs', [ProLicenciaParaImportacionController::class, 'uploadPdfs']);
    Route::get('prolicencias/{licencia}/documentos', [ProLicenciaParaImportacionController::class, 'listDocumentos']);
    Route::get('prolicencias/{licencia}/documentos/info', [ProLicenciaParaImportacionController::class, 'getDocumentos']);
    Route::delete('prolicencias/documento/{documento}', [ProLicenciaParaImportacionController::class, 'destroyDocumento'])
        ->name('prolicencias.documento.destroy');

    Route::get('prolicencias/comprobante/{filename}', [ProLicenciaParaImportacionController::class, 'serveComprobante'])
        ->name('prolicencias.comprobante.serve')
        ->where('filename', '[A-Za-z0-9\-_\.]+');

    Route::get('prolicencias/file/{path}', [ProLicenciaParaImportacionController::class, 'serveFile'])
        ->name('prolicencias.file.serve')
        ->where('path', '.*');

    Route::put('prolicencias/{id}/estado', [ProLicenciaParaImportacionController::class, 'updateEstado'])->name('prolicencias.updateEstado');

    // APIs de usuarios

    Route::put('/api/usuarios/{id}', [UserController::class, 'update']);
    Route::post('/api/usuarios', [UserController::class, 'registroAPI'])->name('usuarios.store.api');
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Usuarios - Resource genera automáticamente store, update, destroy
    Route::resource('usuarios', UserController::class);
    Route::get('/api/usuarios/obtener', [UserController::class, 'getUsers'])->name('usuario.get');
    Route::post('/usuarios/reenviar-verificacion', [UserController::class, 'reenviarVerificacionAPI']);

    // Métodos de pago
    Route::get('/metodos-pago', [MetodoPagoController::class, 'index'])->name('metodos-pago.index');
    Route::get('/metodos-pago/search', [MetodoPagoController::class, 'search'])->name('metodos-pago.search');
    Route::post('/metodos-pago', [MetodoPagoController::class, 'store'])->name('metodos-pago.store');
    Route::put('/metodos-pago/{id}', [MetodoPagoController::class, 'update'])->name('metodos-pago.update');
    Route::delete('/metodos-pago/{id}', [MetodoPagoController::class, 'destroy'])->name('metodos-pago.destroy');

    // Países
    Route::get('/paises', [PaisController::class, 'index'])->name('paises.index');
    Route::get('/paises/search', [PaisController::class, 'search'])->name('paises.search');
    Route::post('/paises', [PaisController::class, 'store'])->name('paises.store');
    Route::put('/paises/{id}', [PaisController::class, 'update'])->name('paises.update');
    Route::delete('/paises/{id}', [PaisController::class, 'destroy'])->name('paises.destroy');

    // Unidades de Medida
    Route::get('/unidades-medida', [UnidadMedidaController::class, 'index'])->name('unidades-medida.index');
    Route::get('/unidades-medida/search', [UnidadMedidaController::class, 'search'])->name('unidades-medida.search');
    Route::post('/unidades-medida', [UnidadMedidaController::class, 'store'])->name('unidades-medida.store');
    Route::put('/unidades-medida/{id}', [UnidadMedidaController::class, 'update'])->name('unidades-medida.update');
    Route::delete('/unidades-medida/{id}', [UnidadMedidaController::class, 'destroy'])->name('unidades-medida.destroy');
    Route::get('/unidades-medida/activos', [UnidadMedidaController::class, 'getActivos'])->name('unidades-medida.activos');
    Route::get('/unidades-medida/por-tipo', [UnidadMedidaController::class, 'getByTipo'])->name('unidades-medida.por-tipo');

    // Calibres
    Route::get('/calibres', [CalibreController::class, 'index'])->name('calibres.index');
    Route::get('/calibres/search', [CalibreController::class, 'search'])->name('calibres.search');
    Route::post('/calibres', [CalibreController::class, 'store'])->name('calibres.store');
    Route::put('/calibres/{id}', [CalibreController::class, 'update'])->name('calibres.update');
    Route::delete('/calibres/{id}', [CalibreController::class, 'destroy'])->name('calibres.destroy');
    Route::get('/calibres/activos', [CalibreController::class, 'getActivos'])->name('calibres.activos');
    Route::get('/calibres/por-unidad', [CalibreController::class, 'getByUnidad'])->name('calibres.por-unidad');

    // Categorías y Subcategorías
    Route::prefix('categorias')->name('categorias.')->group(function () {
        Route::get('/', [CategoriasController::class, 'index'])->name('index');
        Route::post('/', [CategoriasController::class, 'store'])->name('store');
        Route::put('/{categoria}', [CategoriasController::class, 'update'])->name('update');
        Route::delete('/{categoria}', [CategoriasController::class, 'destroy'])->name('destroy');
        Route::post('/subcategorias', [CategoriasController::class, 'storeSubcategoria'])->name('subcategorias.store');
        Route::put('/subcategorias/{subcategoria}', [CategoriasController::class, 'updateSubcategoria'])->name('subcategorias.update');
        Route::delete('/subcategorias/{subcategoria}', [CategoriasController::class, 'destroySubcategoria'])->name('subcategorias.destroy');
        Route::get('/activas', [CategoriasController::class, 'getCategoriasActivas'])->name('activas');
    });

    // Marcas
    Route::get('/marcas', [MarcasController::class, 'index'])->name('marcas.index');
    Route::get('/marcas/search', [MarcasController::class, 'search'])->name('marcas.search');
    Route::post('/marcas', [MarcasController::class, 'store'])->name('marcas.store');
    Route::put('/marcas/{id}', [MarcasController::class, 'update'])->name('marcas.update');

    // Tipo de arma
    Route::get('/tipoarma', [TipoArmaController::class, 'index'])->name('tipoarma.index');
    Route::get('/tipoarma/search', [TipoArmaController::class, 'search'])->name('tipoarma.search');
    Route::post('/tipoarma', [TipoArmaController::class, 'store'])->name('tipoarma.store');
    Route::put('/tipoarma/{id}', [TipoArmaController::class, 'update'])->name('tipoarma.update');

    // Modelos de armas
    Route::get('/modelos', [ProModeloController::class, 'index'])->name('modelos.index');
    Route::post('/modelos', [ProModeloController::class, 'store'])->name('modelos.crear');
    Route::put('/modelos/actualizar', [ProModeloController::class, 'edit'])->name('modelos.update');
    Route::delete('/modelos/eliminar', [ProModeloController::class, 'destroy'])->name('modelos.eliminar');
    Route::get('/modelos/marcas-activas', [ProModeloController::class, 'getMarcasActivas'])->name('modelos.marcas.activas');

    // ================================
    // INVENTARIO
    // ================================
    Route::get('/inventario', [InventarioController::class, 'index'])->name('inventario.index');
    Route::get('/inventario/productos-stock', [InventarioController::class, 'getProductosStock'])->name('inventario.productos-stock');
    Route::get('/inventario/buscar-productos', [InventarioController::class, 'buscarProductos'])->name('inventario.buscar-productos');
    Route::get('/inventario/productos/{id}/detalle', [InventarioController::class, 'getDetalleProducto'])->name('inventario.producto.detalle');
    Route::get('/inventario/productos/{id}', [InventarioController::class, 'getProducto'])->name('inventario.producto.get');
    Route::post('/inventario/productos', [InventarioController::class, 'store'])->name('inventario.productos.store');
    Route::put('/inventario/productos/{id}', [InventarioController::class, 'update'])->name('inventario.producto.update');
    Route::delete('/inventario/productos/{id}', [InventarioController::class, 'destroy'])->name('inventario.producto.delete');
    Route::get('/inventario/productos/{id}/movimientos', [InventarioController::class, 'getMovimientosProducto'])->name('inventario.producto.movimientos');
    Route::get('/inventario/productos/{id}/series', [InventarioController::class, 'getSeriesProducto'])->name('inventario.producto.series');
    Route::get('/inventario/productos/{id}/requiere-licencia', [InventarioController::class, 'verificarRequiereLicencia'])->name('inventario.producto.requiere-licencia');

    // Fotos de productos
    Route::get('/inventario/productos/{id}/fotos', [InventarioController::class, 'getFotosProducto'])->name('inventario.producto.fotos');
    Route::post('/inventario/productos/{id}/fotos', [InventarioController::class, 'subirFotos'])->name('inventario.producto.fotos.subir');
    Route::delete('/inventario/fotos/{id}', [InventarioController::class, 'eliminarFoto'])->name('inventario.foto.eliminar');
    Route::put('/inventario/fotos/{id}/principal', [InventarioController::class, 'establecerFotoPrincipal'])->name('inventario.foto.principal');

    // Movimientos e ingresos/egresos
    Route::post('/inventario/ingresar', [InventarioController::class, 'ingresar'])->name('inventario.ingresar');
    Route::post('/inventario/egresar', [InventarioController::class, 'egresar'])->name('inventario.egresar');
    Route::post('/inventario/egreso', [InventarioController::class, 'registrarEgreso'])->name('inventario.egreso');
    Route::get('/inventario/productos/{id}/stock-lotes', [InventarioController::class, 'getStockPorLotes'])->name('inventario.producto.stock-lotes');
    Route::get('/inventario/productos/{id}/series-disponibles', [InventarioController::class, 'getSeriesDisponibles'])->name('inventario.producto.series-disponibles');
    Route::get('/inventario/productos-excel', [InventarioController::class, 'getProductosExcel'])->name('inventario.productos-excel');
    Route::get('/inventario/movimientos', [InventarioController::class, 'getMovimientos'])->name('inventario.movimientos');

    // Estadísticas y alertas
    Route::get('/inventario/estadisticas', [InventarioController::class, 'getEstadisticas'])->name('inventario.estadisticas');
    Route::get('/inventario/alertas-stock', [InventarioController::class, 'getAlertasStock'])->name('inventario.alertas-stock');

    // APIs para selects
    Route::get('/categorias/activas', [InventarioController::class, 'getCategoriasActivas'])->name('categorias.activas');
    Route::get('/categorias/{categoria}/subcategorias', [InventarioController::class, 'getSubcategoriasPorCategoria'])->name('categorias.subcategorias');
    Route::get('/marcas/activas', [InventarioController::class, 'getMarcasActivas'])->name('marcas.activas');
    Route::get('/marcas/{marca}/modelos', [InventarioController::class, 'getModelosPorMarca'])->name('marcas.modelos');
    Route::get('/paises/activos', [InventarioController::class, 'getPaisesActivos'])->name('paises.activos');
    Route::get('/calibres/activos', [InventarioController::class, 'getCalibresActivos'])->name('calibres.activos');

    // Lotes
    Route::get('/inventario/lotes/buscar', [InventarioController::class, 'buscarLotes'])->name('inventario.lotes.buscar');
    Route::get('/inventario/lotes/{id}', [InventarioController::class, 'obtenerLote'])->name('inventario.lote.detalle');

    // Licencias
    Route::get('/licencias/buscar', [InventarioController::class, 'buscarLicencias'])->name('licencias.buscar');
    Route::get('/licencias/{id}', [InventarioController::class, 'getLicencia'])->name('licencias.get');

    // Precios
    Route::get('/inventario/productos/{id}/precios', [InventarioController::class, 'getHistorialPrecios'])->name('inventario.producto.precios');
    Route::put('/inventario/productos/{id}/precios', [InventarioController::class, 'actualizarPrecios'])->name('inventario.producto.precios.actualizar');

    // ================================
    // VENTAS
    // ================================
    Route::get('/ventas', [VentasController::class, 'index'])->name('ventas.index');
    Route::get('/ventas/search', [VentasController::class, 'search'])->name('ventas.search');
    Route::post('/ventas', [VentasController::class, 'store'])->name('ventas.store');
    Route::put('/ventas/{id}', [VentasController::class, 'update'])->name('ventas.update');

    //ventas batz
    Route::get('/ventas/pendientes', [VentasController::class, 'obtenerVentasPendientes'])->name('ventas.pendientes');
    ///

    // APIs de ventas
    Route::get('/api/ventas/subcategorias/{categoriaId}', [VentasController::class, 'getSubcategorias'])->name('ventas.api.subcategorias');
    Route::get('/api/ventas/marcas/{subcategoriaId}', [VentasController::class, 'getMarcas'])->name('ventas.api.marcas');
    Route::get('/api/ventas/modelos/{marcaId}', [VentasController::class, 'getModelos'])->name('ventas.api.modelos');
    Route::get('/api/ventas/calibres/{modeloId}', [VentasController::class, 'getCalibres'])->name('ventas.api.calibres');
    Route::get('/api/ventas/buscar-productos', [VentasController::class, 'buscarProductos'])->name('ventas.api.productos');
    Route::get('/api/ventas/buscar', [VentasController::class, 'buscarClientes'])->name('ventas.api.clientes.buscar');
    Route::post('/api/clientes/guardar', [VentasController::class, 'guardarCliente'])->name('ventas.api.clientes.guardar');
    Route::post('/api/ventas/procesar-venta', [VentasController::class, 'procesarVenta'])->name('ventas.api.ventas.procesar');

    // ================================
    // COMISIONES
    // ================================
    Route::get('/comisiones', [ComisionesController::class, 'index'])->name('comisiones.index');
    Route::get('/comisiones/search', [ComisionesController::class, 'search'])->name('comisiones.search');
    Route::get('/comisiones/resumen', [ComisionesController::class, 'getResumen'])->name('comisiones.resumen');
    Route::put('/comisiones', [ComisionesController::class, 'update'])->name('comisiones.update');
    Route::put('/comisiones/cancelar', [ComisionesController::class, 'cancelar'])->name('comisiones.cancelar');

    // ================================
    // UBICACIONES
    // ================================
    Route::prefix('api/ubicaciones')->name('ubicaciones.')->group(function () {
        Route::post('/', [UsersUbicacionController::class, 'create'])->name('ubi.create');
        Route::put('/', [UsersUbicacionController::class, 'update'])->name('ubi.update');
        Route::put('/{id}', [UsersUbicacionController::class, 'update'])->name('ubi.update.id');
        Route::get('/', [UsersUbicacionController::class, 'getDatos'])->name('ubi.getDatos');
        Route::get('/{user}/detalle', [UsersUbicacionController::class, 'getDetallesCliente'])->name('ubi.detalle');
        Route::delete('/{id}', [UsersUbicacionController::class, 'eliminarUbicacion'])->name('ubi.delete');
        Route::post('/visita', [UsersUbicacionController::class, 'agregarVisita']);
    });

    // Mapa de usuarios
    Route::get('/mapa', [UserController::class, 'indexMapa'])->name('mapa.index');

    // ================================
    // PAGOS
    // ================================
    Route::prefix('pagos')->group(function () {
        Route::get('/', [PagosController::class, 'index'])->name('mis.pagos');
        Route::get('subir', [PagosController::class, 'index2'])->name('subir.pago');
        Route::get('admin', [PagosController::class, 'index3'])->name('admin.pagos');
        Route::get('obtener/mispagos', [PagosController::class, 'MisFacturasPendientes'])->name('misfacturas.pendientes');
        Route::post('cuotas/pagar', [PagosController::class, 'pagarCuotas']);
    });


    // ================================
    // RUTAS PARA SISTEMA DE REPORTES
    // ================================

    // Vista principal de reportes
    Route::get('/reportes', [ReportesController::class, 'index'])->name('reportes.index');
    // ================================
    // APIs PARA DASHBOARD Y KPIS
    // ================================
    // Dashboard principal con KPIs y gráficos
    Route::get('/reportes/dashboard', [ReportesController::class, 'getDashboard'])->name('reportes.dashboard');
    // Filtros para formularios (vendedores, categorías, etc.)
    Route::get('/reportes/filtros', [ReportesController::class, 'getFiltros'])->name('reportes.filtros');

    // ================================
    // REPORTES ESPECÍFICOS
    // ================================
    // Reporte detallado de ventas
    Route::get('/reportes/ventas', [ReportesController::class, 'getReporteVentas'])->name('reportes.ventas');
    // Reporte de productos más vendidos
    Route::get('/reportes/productos', [ReportesController::class, 'getReporteProductos'])->name('reportes.productos');
    // Reporte de comisiones de vendedores
    Route::get('/reportes/comisiones', [ReportesController::class, 'getReporteComisiones'])->name('reportes.comisiones');
    // Reporte de pagos y cuotas
    Route::get('/reportes/pagos', [ReportesController::class, 'getReportePagos'])->name('reportes.pagos');
    // ================================
    // EXPORTACIÓN DE REPORTES
    // ================================
    // Exportar a PDF
    Route::get('/reportes/exportar-pdf', [ReportesController::class, 'exportarPDF'])->name('reportes.exportar.pdf');
    // Exportar a Excel
    Route::get('/reportes/exportar-excel', [ReportesController::class, 'exportarExcel'])->name('reportes.exportar.excel');

    // ================================
    // RUTAS ADICIONALES (OPCIONALES)
    // ================================
    // Detalle específico de una venta
    Route::get('/reportes/ventas/{id}/detalle', [ReportesController::class, 'getDetalleVenta'])->name('reportes.venta.detalle');

    Route::post('/ventas/autorizar', [VentasController::class, 'autorizarVenta']);
    Route::post('/ventas/cancelar', [VentasController::class, 'cancelarVenta']);
    Route::get('/ventas/pendientes', [VentasController::class, 'obtenerVentasPendientes']);
    Route::post('/ventas/actualizar-licencias', [VentasController::class, 'actualizarLicencias']);
   Route::post('/ventas/marcar-disponibles', [VentasController::class, 'marcarSeriesDisponibles'])
    ->name('ventas.marcar-disponibles');
    Route::post('/ventas/cancelarReserva', [VentasController::class, 'cancelarReserva'])->name('ventas.cancelar');


    Route::post('/reservas/procesar', [VentasController::class, 'procesarReserva'])
        ->middleware('auth')
        ->name('reservas.procesar');
    Route::get('/api/reservas/cliente/{clienteId}', [VentasController::class, 'buscarReservaPorCliente']);
    Route::post('/api/productos/stock', [InventarioController::class, 'obtenerStock']);


    // Imprimir comprobante de venta
    Route::get('/reportes/ventas/{id}/imprimir', [ReportesController::class, 'imprimirVenta'])->name('reportes.venta.imprimir');
    // Estadísticas avanzadas por período
    Route::get('/reportes/estadisticas-avanzadas', [ReportesController::class, 'getEstadisticasAvanzadas'])->name('reportes.estadisticas.avanzadas');
    // Comparación entre períodos
    Route::get('/reportes/comparacion-periodos', [ReportesController::class, 'getComparacionPeriodos'])->name('reportes.comparacion.periodos');
    // Búsqueda de clientes para autocomplete
    Route::get('/reportes/buscar-clientes', [ReportesController::class, 'buscarClientes'])->name('reportes.buscar.clientes');

    Route::get('/reportes/digecam/armas', [ReportesController::class, 'getReporteDigecamArmas'])->name('reportes.digecam.armas');
    Route::get('/reportes/digecam/municiones', [ReportesController::class, 'getReporteDigecamMuniciones'])->name('reportes.digecam.municiones');
    Route::get('/reportes/digecam/exportar-pdf', [ReportesController::class, 'exportarDigecamPDF'])->name('reportes.digecam.exportar.pdf');


    // Admin pagos
    Route::prefix('admin/pagos')->group(function () {
        Route::get('dashboard-stats', [AdminPagosController::class, 'stats']);
        Route::post('movs/upload', [AdminPagosController::class, 'estadoCuentaPreview']);
        Route::post('movs/procesar', [AdminPagosController::class, 'estadoCuentaProcesar']);
        Route::get('pendientes', [AdminPagosController::class, 'pendientes']);
        Route::post('aprobar', [AdminPagosController::class, 'aprobar']);
        Route::post('rechazar', [AdminPagosController::class, 'rechazar']);
        Route::get('movimientos', [AdminPagosController::class, 'movimientos']);
        Route::post('egresos', [AdminPagosController::class, 'registrarEgreso']);
        Route::post('conciliar', [AdminPagosController::class, 'conciliarAutomatico']);
        Route::post('movimientos/{id}/validar', [AdminPagosController::class, 'validarMovimiento']);
        Route::post('movimientos/{id}/rechazar', [AdminPagosController::class, 'rechazarMovimiento']);
        Route::post('ingresos', [AdminPagosController::class, 'registrarIngreso']);
    });

    // Rutas CRUD de Clientes
    Route::get('/clientes', [ClientesController::class, 'index'])->name('clientes.index');
    Route::post('/api/clientes/create', [VentasController::class, 'guardarCliente'])->name('ventas.api.clientes.guardar');
    Route::put('/clientes/{cliente}', [ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{cliente}', [ClientesController::class, 'destroy'])->name('clientes.destroy');

    // Ruta para ver PDF de licencia
    Route::get('/clientes/{cliente}/ver-pdf-licencia', [ClientesController::class, 'verPdfLicencia'])
        ->name('clientes.ver-pdf-licencia');




    //FACTURACIÓN
    Route::prefix('facturacion')->group(function () {
        Route::get('/', [FacturacionController::class, 'index'])->name('facturacion.index');
        Route::post('/buscarNit', [FacturacionController::class, 'buscarNIT'])->name('facturacion.buscarNit');
        Route::post('/certificar', [FacturacionController::class, 'certificar'])->name('facturacion.certificar');
        Route::get('/obtener-facturas', [FacturacionController::class, 'obtenerFacturas'])->name('facturacion.obtener');
        Route::get('/consultar-dte/{uuid}', [FacturacionController::class, 'consultarDte'])->name('facturacion.consultarDte');
        Route::get('/{id}/vista', [FacturacionController::class, 'vista'])->name('facturacion.vista');
        Route::post('/anular/{id}', [FacturacionController::class, 'anular'])->name('facturacion.anular');
        Route::post('/buscarCui', [FacturacionController::class, 'buscarCUI'])->name('buscarCui');
        Route::post('/certificar-cambiaria', [FacturacionController::class, 'certificarCambiaria'])
    ->name('facturacion.certificar.cambiaria');

Route::get('/{id}/vista-cambiaria', [FacturacionController::class, 'verFacturaCambiaria'])
    ->name('facturacion.vista.cambiaria');
    });
});

require __DIR__ . '/auth.php';
