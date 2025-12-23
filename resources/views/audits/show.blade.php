@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('audits.index') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
                </div>
                Detalle de Auditoría #{{ $audit->id }}
            </h1>
        </div>
        <div>
            @php
                $badgeClasses = match($audit->event) {
                    'created' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-green-200 dark:border-green-800',
                    'updated' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800',
                    'deleted' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 border-red-200 dark:border-red-800',
                    'restored' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600'
                };
                $eventLabel = match($audit->event) {
                    'created' => 'CREADO',
                    'updated' => 'ACTUALIZADO',
                    'deleted' => 'ELIMINADO',
                    'restored' => 'RESTAURADO',
                    default => strtoupper($audit->event)
                };
            @endphp
            <span class="px-4 py-2 inline-flex text-sm font-bold rounded-full border {{ $badgeClasses }}">
                {{ $eventLabel }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- General Info -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-user-circle text-gray-400"></i> Información General
                </h3>
            </div>
            <div class="p-6">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuario Responsable</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">
                            {{ $audit->user ? $audit->user->user_primer_nombre . ' ' . $audit->user->user_primer_apellido : 'Sistema/Desconocido' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $audit->user ? $audit->user->email : '-' }}
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha y Hora</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $audit->created_at->format('d/m/Y H:i:s') }}
                        </dd>
                    </div>
                    <div class="sm:col-span-1">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dirección IP</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded inline-block">
                            {{ $audit->ip_address }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Technical Context -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/30">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-code text-gray-400"></i> Contexto Técnico
                </h3>
            </div>
            <div class="p-6">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Modelo Afectado</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
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
                            <span class="font-mono text-blue-600 dark:text-blue-400 font-semibold">{{ $friendlyModel }}</span>
                            @if($audit->auditable_type != $friendlyModel)
                                <span class="text-xs text-gray-500 dark:text-gray-400 block mt-0.5">({{ $audit->auditable_type }})</span>
                            @endif
                        </dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID Registro</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">
                                #{{ $audit->auditable_id }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</dt>
                            <dd class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $audit->user_agent }}">
                                {{ Str::limit($audit->user_agent, 40) }}
                            </dd>
                        </div>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">URL Solicitada</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white break-all font-mono bg-gray-50 dark:bg-gray-700/50 p-2 rounded border border-gray-100 dark:border-gray-600 text-xs">
                            {{ $audit->url }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Changes Comparison -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Old Values -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-red-100 dark:border-red-900/30 bg-red-50 dark:bg-red-900/10">
                <h3 class="text-lg font-semibold text-red-700 dark:text-red-400 flex items-center gap-2">
                    <i class="fas fa-minus-circle"></i> Valores Anteriores
                </h3>
            </div>
            <div class="p-0 flex-grow">
                @if(empty($audit->old_values))
                    <div class="flex flex-col items-center justify-center h-full py-12 text-gray-400 dark:text-gray-500">
                        <i class="fas fa-ban text-4xl mb-3 opacity-50"></i>
                        <p class="text-sm">No hay valores anteriores (Creación)</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($audit->old_values as $key => $value)
                                    <tr class="hover:bg-red-50/50 dark:hover:bg-red-900/10 transition-colors">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-500 dark:text-gray-400 w-1/3 text-right bg-gray-50 dark:bg-gray-800/50">
                                            {{ $key }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white font-mono break-all">
                                            {{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- New Values -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col h-full">
            <div class="px-6 py-4 border-b border-green-100 dark:border-green-900/30 bg-green-50 dark:bg-green-900/10">
                <h3 class="text-lg font-semibold text-green-700 dark:text-green-400 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> Valores Nuevos
                </h3>
            </div>
            <div class="p-0 flex-grow">
                @if(empty($audit->new_values))
                    <div class="flex flex-col items-center justify-center h-full py-12 text-gray-400 dark:text-gray-500">
                        <i class="fas fa-ban text-4xl mb-3 opacity-50"></i>
                        <p class="text-sm">No hay valores nuevos (Eliminación)</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($audit->new_values as $key => $value)
                                    <tr class="hover:bg-green-50/50 dark:hover:bg-green-900/10 transition-colors">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-500 dark:text-gray-400 w-1/3 text-right bg-gray-50 dark:bg-gray-800/50">
                                            {{ $key }}
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-900 dark:text-white font-mono break-all">
                                            {{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value }}
                                        </td>
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
@endsection
