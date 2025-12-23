@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-info-circle me-2"></i>Detalle de Auditoría #{{ $audit->id }}
                    </h5>
                    <a href="{{ route('audits.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Volver al listado
                    </a>
                </div>
                <div class="card-body bg-light">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted text-uppercase text-xs font-weight-bolder mb-3">Información General</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <span class="text-sm font-weight-bold">Usuario:</span>
                                            <span class="text-sm">{{ $audit->user ? $audit->user->user_primer_nombre . ' ' . $audit->user->user_primer_apellido : 'Sistema/Desconocido' }}</span>
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <span class="text-sm font-weight-bold">Evento:</span>
                                            <span class="badge bg-secondary">{{ strtoupper($audit->event) }}</span>
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <span class="text-sm font-weight-bold">Fecha:</span>
                                            <span class="text-sm">{{ $audit->created_at->format('d/m/Y H:i:s') }}</span>
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2 d-flex justify-content-between align-items-center">
                                            <span class="text-sm font-weight-bold">IP Address:</span>
                                            <span class="text-sm">{{ $audit->ip_address }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="text-muted text-uppercase text-xs font-weight-bolder mb-3">Contexto Técnico</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item bg-transparent px-0 py-2">
                                            <span class="d-block text-sm font-weight-bold mb-1">Modelo Afectado:</span>
                                            @php
                                                $modelMap = [
                                                    'App\Models\Alerta' => 'Alerta',
                                                    'App\Models\AlertaRol' => 'Asignación Alerta-Rol',
                                                    'App\Models\Banco' => 'Banco',
                                                    'App\Models\CajaSaldo' => 'Saldo de Caja',
                                                    'App\Models\Calibre' => 'Calibre',
                                                    'App\Models\Categoria' => 'Categoría',
                                                    'App\Models\ClienteDocumento' => 'Documento de Cliente',
                                                    'App\Models\ClienteEmpresa' => 'Empresa de Cliente',
                                                    'App\Models\ClienteSaldo' => 'Saldo de Cliente',
                                                    'App\Models\ClienteSaldoHistorial' => 'Historial Saldo Cliente',
                                                    'App\Models\Clientes' => 'Cliente',
                                                    'App\Models\Facturacion' => 'Factura',
                                                    'App\Models\FacturacionDetalle' => 'Detalle de Factura',
                                                    'App\Models\FelToken' => 'Token FEL',
                                                    'App\Models\LicenciaAsignacionProducto' => 'Asignación Licencia-Producto',
                                                    'App\Models\Lote' => 'Lote',
                                                    'App\Models\Marcas' => 'Marca',
                                                    'App\Models\MetodoPago' => 'Método de Pago',
                                                    'App\Models\Movimiento' => 'Movimiento de Inventario',
                                                    'App\Models\PagoLicencia' => 'Pago de Licencia',
                                                    'App\Models\PagoMetodo' => 'Método Pago Licencia',
                                                    'App\Models\PagoComprobante' => 'Comprobante Pago Licencia',
                                                    'App\Models\PagoSubido' => 'Pago Subido',
                                                    'App\Models\Pais' => 'País',
                                                    'App\Models\Precio' => 'Precio',
                                                    'App\Models\Preventa' => 'Preventa',
                                                    'App\Models\PreventaDetalle' => 'Detalle de Preventa',
                                                    'App\Models\ProArmaLicenciada' => 'Arma Licenciada',
                                                    'App\Models\ProCliente' => 'Cliente (Pro)',
                                                    'App\Models\ProCuota' => 'Cuota',
                                                    'App\Models\ProDetallePago' => 'Detalle de Pago',
                                                    'App\Models\ProDetalleVenta' => 'Detalle de Venta',
                                                    'App\Models\ProDocumentacionLicImport' => 'Doc. Licencia Importación',
                                                    'App\Models\ProEmpresaDeImportacion' => 'Empresa Importadora',
                                                    'App\Models\ProLicencia' => 'Licencia (Pro)',
                                                    'App\Models\ProPagoLicencia' => 'Pago Licencia (Pro)',
                                                    'App\Models\ProPagoLicMetodo' => 'Método Pago Licencia (Pro)',
                                                    'App\Models\ProLicenciaTotalPagado' => 'Total Pagado Licencia',
                                                    'App\Models\ProLicenciaParaImportacion' => 'Licencia de Importación',
                                                    'App\Models\ProMetodoPago' => 'Método de Pago (Pro)',
                                                    'App\Models\ProModelo' => 'Modelo de Arma',
                                                    'App\Models\ProPago' => 'Pago',
                                                    'App\Models\ProPorcentajeVendedor' => 'Porcentaje Vendedor',
                                                    'App\Models\ProVenta' => 'Venta (Pro)',
                                                    'App\Models\Producto' => 'Producto',
                                                    'App\Models\ProductoFoto' => 'Foto de Producto',
                                                    'App\Models\Promocion' => 'Promoción',
                                                    'App\Models\Rol' => 'Rol',
                                                    'App\Models\SerieProducto' => 'Serie de Producto',
                                                    'App\Models\StockActual' => 'Stock Actual',
                                                    'App\Models\Subcategoria' => 'Subcategoría',
                                                    'App\Models\TipoArma' => 'Tipo de Arma',
                                                    'App\Models\UnidadMedida' => 'Unidad de Medida',
                                                    'App\Models\User' => 'Usuario',
                                                    'App\Models\UsersHistorialVisita' => 'Historial Visita Usuario',
                                                    'App\Models\UsersUbicacion' => 'Ubicación Usuario',
                                                    'App\Models\UsersVisita' => 'Visita Usuario',
                                                    'App\Models\Ventas' => 'Venta',
                                                ];
                                                $friendlyModel = $modelMap[$audit->auditable_type] ?? $audit->auditable_type;
                                            @endphp
                                            <code class="text-primary">{{ $friendlyModel }}</code>
                                            @if($audit->auditable_type != $friendlyModel)
                                                <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">({{ $audit->auditable_type }})</small>
                                            @endif
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2">
                                            <span class="d-block text-sm font-weight-bold mb-1">ID del Registro:</span>
                                            <code class="text-dark">{{ $audit->auditable_id }}</code>
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2">
                                            <span class="d-block text-sm font-weight-bold mb-1">URL:</span>
                                            <small class="text-muted text-break">{{ $audit->url }}</small>
                                        </li>
                                        <li class="list-group-item bg-transparent px-0 py-2">
                                            <span class="d-block text-sm font-weight-bold mb-1">User Agent:</span>
                                            <small class="text-muted text-break">{{ $audit->user_agent }}</small>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-white border-bottom-0">
                                    <h6 class="mb-0 text-danger"><i class="fas fa-minus-circle me-2"></i>Valores Anteriores</h6>
                                </div>
                                <div class="card-body bg-light rounded-bottom p-3">
                                    @if(empty($audit->old_values))
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-ban mb-2"></i>
                                            <p class="mb-0 text-sm">No hay valores anteriores (Creación)</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tbody>
                                                    @foreach($audit->old_values as $key => $value)
                                                        <tr>
                                                            <td class="text-sm font-weight-bold text-end pe-3" style="width: 40%;">{{ $key }}:</td>
                                                            <td class="text-sm text-break text-muted">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-header bg-white border-bottom-0">
                                    <h6 class="mb-0 text-success"><i class="fas fa-plus-circle me-2"></i>Valores Nuevos</h6>
                                </div>
                                <div class="card-body bg-light rounded-bottom p-3">
                                    @if(empty($audit->new_values))
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-ban mb-2"></i>
                                            <p class="mb-0 text-sm">No hay valores nuevos (Eliminación)</p>
                                        </div>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-sm table-borderless mb-0">
                                                <tbody>
                                                    @foreach($audit->new_values as $key => $value)
                                                        <tr>
                                                            <td class="text-sm font-weight-bold text-end pe-3" style="width: 40%;">{{ $key }}:</td>
                                                            <td class="text-sm text-break text-dark">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
