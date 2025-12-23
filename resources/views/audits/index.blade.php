@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-history text-blue-600 dark:text-blue-400"></i>
                </div>
                Historial de Auditoría
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Registro detallado de cambios y actividades en el sistema.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-100 dark:border-blue-800">
                Total: {{ $audits->total() }} registros
            </span>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 mb-6 overflow-hidden">
        <div class="p-6">
            <form action="{{ route('audits.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-6">
                <!-- Search -->
                <div class="lg:col-span-3">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" name="search" id="search" value="{{ request('search') }}" 
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                            placeholder="Palabra clave...">
                    </div>
                </div>

                <!-- User -->
                <div class="lg:col-span-3">
                    <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Usuario</label>
                    <select name="user_id" id="user_id" 
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Todos los usuarios</option>
                        @foreach($users as $user)
                            <option value="{{ $user->user_id }}" {{ request('user_id') == $user->user_id ? 'selected' : '' }}>
                                {{ $user->user_primer_nombre }} {{ $user->user_primer_apellido }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Event -->
                <div class="lg:col-span-2">
                    <label for="event" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Evento</label>
                    <select name="event" id="event" 
                        class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <option value="">Todos</option>
                        <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Creación</option>
                        <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Actualización</option>
                        <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Eliminación</option>
                        <option value="restored" {{ request('event') == 'restored' ? 'selected' : '' }}>Restauración</option>
                    </select>
                </div>

                <!-- Model -->
                <div class="lg:col-span-2">
                    <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Modelo</label>
                    <input type="text" name="model" id="model" value="{{ request('model') }}" 
                        class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out"
                        placeholder="Ej: Producto">
                </div>

                <!-- Actions -->
                <div class="lg:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-150 ease-in-out flex items-center justify-center gap-2 shadow-sm hover:shadow">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('audits.index') }}" class="bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium py-2 px-3 rounded-lg border border-gray-300 dark:border-gray-600 transition duration-150 ease-in-out shadow-sm" title="Limpiar filtros">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Evento</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Modelo / Descripción</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($audits as $audit)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-user text-xs"></i>
                                    </div>
                                    <div class="ml-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $audit->user ? $audit->user->user_primer_nombre . ' ' . $audit->user->user_primer_apellido : 'Sistema/Desconocido' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $audit->user ? $audit->user->email : '' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full border {{ $badgeClasses }}">
                                    {{ $eventLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
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
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $friendlyModel }}</span>
                                    @if($description)
                                        <span class="text-xs text-gray-500 dark:text-gray-400 italic">{{ Str::limit($description, 30) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-mono text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                    #{{ $audit->auditable_id }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $audit->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $audit->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $audit->ip_address }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('audits.show', $audit->id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 px-3 py-1.5 rounded-lg transition-colors duration-150 inline-flex items-center gap-1">
                                    <i class="fas fa-eye"></i> Ver Detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-16 w-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-search text-gray-400 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No se encontraron registros</h3>
                                    <p class="text-gray-500 dark:text-gray-400">Intenta ajustar los filtros de búsqueda.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
            {{ $audits->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
