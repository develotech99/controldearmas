@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary fw-bold">
                        <i class="fas fa-history me-2"></i>Historial de Auditoría
                    </h5>
                    <div>
                        <span class="badge bg-light text-dark border">
                            Total: {{ $audits->total() }} registros
                        </span>
                    </div>
                </div>
                <div class="card-body bg-light">
                    <form action="{{ route('audits.index') }}" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search" class="form-label small text-muted">Buscar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0 ps-0" id="search" name="search" value="{{ request('search') }}" placeholder="Palabra clave...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="user_id" class="form-label small text-muted">Usuario</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">Todos los usuarios</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->user_id }}" {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                        {{ $user->user_primer_nombre }} {{ $user->user_primer_apellido }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="event" class="form-label small text-muted">Evento</label>
                            <select class="form-select" id="event" name="event">
                                <option value="">Todos</option>
                                <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Creación</option>
                                <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Actualización</option>
                                <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Eliminación</option>
                                <option value="restored" {{ request('event') == 'restored' ? 'selected' : '' }}>Restauración</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="model" class="form-label small text-muted">Modelo</label>
                            <input type="text" class="form-control" id="model" name="model" value="{{ request('model') }}" placeholder="Ej: Producto">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 me-2">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                            <a href="{{ route('audits.index') }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                <i class="fas fa-undo"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Usuario</th>
                                    <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Evento</th>
                                    <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Modelo</th>
                                    <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID Registro</th>
                                    <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha</th>
                                    <th class="py-3 text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">IP</th>
                                    <th class="pe-4 py-3 text-end text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($audits as $audit)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $audit->user ? $audit->user->user_primer_nombre . ' ' . $audit->user->user_primer_apellido : 'Sistema/Desconocido' }}</h6>
                                                    <p class="text-xs text-secondary mb-0">{{ $audit->user ? $audit->user->email : '' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            @php
                                                $badgeClass = match($audit->event) {
                                                    'created' => 'bg-success',
                                                    'updated' => 'bg-warning text-dark',
                                                    'deleted' => 'bg-danger',
                                                    'restored' => 'bg-info',
                                                    default => 'bg-secondary'
                                                };
                                                $eventLabel = match($audit->event) {
                                                    'created' => 'CREADO',
                                                    'updated' => 'ACTUALIZADO',
                                                    'deleted' => 'ELIMINADO',
                                                    'restored' => 'RESTAURADO',
                                                    default => strtoupper($audit->event)
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }} border-0">{{ $eventLabel }}</span>
                                        </td>
                                        <td>
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
                                                $friendlyModel = $modelMap[$audit->auditable_type] ?? class_basename($audit->auditable_type);
                                                
                                                // Intentar obtener un nombre descriptivo
                                                $description = '';
                                                if ($audit->auditable) {
                                                    if ($audit->auditable_type == 'App\Models\Producto') {
                                                        $description = $audit->auditable->producto_nombre;
                                                    } elseif ($audit->auditable_type == 'App\Models\User') {
                                                        $description = $audit->auditable->user_primer_nombre . ' ' . $audit->auditable->user_primer_apellido;
                                                    } elseif ($audit->auditable_type == 'App\Models\Clientes' || $audit->auditable_type == 'App\Models\ProCliente') {
                                                        $description = $audit->auditable->cliente_nombre1 . ' ' . $audit->auditable->cliente_apellido1;
                                                    } elseif ($audit->auditable_type == 'App\Models\Ventas') {
                                                        $description = 'Total: Q' . number_format($audit->auditable->ven_total_vendido, 2);
                                                    }
                                                }
                                                
                                                // Si no hay objeto (eliminado) o no se encontró, buscar en old_values
                                                if (empty($description) && !empty($audit->old_values)) {
                                                     if ($audit->auditable_type == 'App\Models\Producto') {
                                                        $description = $audit->old_values['producto_nombre'] ?? '';
                                                    } elseif ($audit->auditable_type == 'App\Models\User') {
                                                        $description = ($audit->old_values['user_primer_nombre'] ?? '') . ' ' . ($audit->old_values['user_primer_apellido'] ?? '');
                                                    } elseif ($audit->auditable_type == 'App\Models\Clientes' || $audit->auditable_type == 'App\Models\ProCliente') {
                                                        $description = ($audit->old_values['cliente_nombre1'] ?? '') . ' ' . ($audit->old_values['cliente_apellido1'] ?? '');
                                                    }
                                                }
                                            @endphp
                                            <div class="d-flex flex-column">
                                                <span class="text-secondary text-xs font-weight-bold">{{ $friendlyModel }}</span>
                                                @if($description)
                                                    <span class="text-xs text-muted fst-italic">{{ Str::limit($description, 30) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold">#{{ $audit->auditable_id }}</span>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold">{{ $audit->created_at->format('d/m/Y H:i:s') }}</span>
                                            <p class="text-xs text-secondary mb-0">{{ $audit->created_at->diffForHumans() }}</p>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold">{{ $audit->ip_address }}</span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#auditModal{{ $audit->id }}">
                                                <i class="fas fa-eye"></i> Ver Detalles
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" id="auditModal{{ $audit->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Detalle de Auditoría #{{ $audit->id }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <strong>Modelo:</strong> 
                                                                    @php
                                                                        $friendlyModelModal = $modelMap[$audit->auditable_type] ?? $audit->auditable_type;
                                                                    @endphp
                                                                    {{ $friendlyModelModal }}
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <strong>ID:</strong> {{ $audit->auditable_id }}
                                                                </div>
                                                            </div>
                                                            <div class="row mb-3">
                                                                <div class="col-md-6">
                                                                    <strong>URL:</strong> <span class="text-break">{{ $audit->url }}</span>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <strong>User Agent:</strong> <span class="text-break">{{ $audit->user_agent }}</span>
                                                                </div>
                                                            </div>
                                                            
                                                            <hr>
                                                            
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6 class="text-danger border-bottom pb-2">Valores Anteriores</h6>
                                                                    <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                                                        @if(empty($audit->old_values))
                                                                            <em class="text-muted">No hay valores anteriores (Creación)</em>
                                                                        @else
                                                                            <ul class="list-unstyled mb-0">
                                                                                @foreach($audit->old_values as $key => $value)
                                                                                    <li class="mb-1">
                                                                                        <strong>{{ $key }}:</strong> 
                                                                                        <span class="text-break">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6 class="text-success border-bottom pb-2">Valores Nuevos</h6>
                                                                    <div class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                                                                        @if(empty($audit->new_values))
                                                                            <em class="text-muted">No hay valores nuevos (Eliminación)</em>
                                                                        @else
                                                                            <ul class="list-unstyled mb-0">
                                                                                @foreach($audit->new_values as $key => $value)
                                                                                    <li class="mb-1">
                                                                                        <strong>{{ $key }}:</strong> 
                                                                                        <span class="text-break">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                                                    </li>
                                                                                @endforeach
                                                                            </ul>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-search fa-3x text-secondary mb-3 opacity-50"></i>
                                                <h5 class="text-secondary">No se encontraron registros de auditoría</h5>
                                                <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white py-3">
                    {{ $audits->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
