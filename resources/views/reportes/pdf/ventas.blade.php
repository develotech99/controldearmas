<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 0; padding: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #2c3e50; margin: 0; }
        .info { display: flex; justify-content: space-between; margin-bottom: 20px; background: #f8f9fa; padding: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table th { background-color: #3498db; color: white; font-weight: bold; text-align: center; }
        .table tr:nth-child(even) { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
        .no-data { text-align: center; padding: 40px; color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $titulo ?? 'Reporte de Ventas' }}</h1>
        <p>Generado: {{ $fecha_generacion ?? now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info">
        <div><strong>Período:</strong> {{ $filtros['fecha_inicio'] ?? 'N/A' }} - {{ $filtros['fecha_fin'] ?? 'N/A' }}</div>
        <div><strong>Total Registros:</strong> {{ is_countable($data) ? count($data) : (isset($data['data']) ? count($data['data']) : 0) }}</div>
    </div>

    @if(isset($data['data']) && count($data['data']) > 0)
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @php $totalGeneral = 0; @endphp
                @foreach($data['data'] as $venta)
                    @php $totalGeneral += $venta->ven_total_vendido; @endphp
                    <tr>
                        <td class="text-center">{{ $venta->ven_id }}</td>
                        <td class="text-center">{{ $venta->ven_fecha ? \Carbon\Carbon::parse($venta->ven_fecha)->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $venta->cliente->nombre_completo ?? 'Cliente no encontrado' }}</td>
                        <td>{{ isset($venta->vendedor) ? ($venta->vendedor->user_primer_nombre . ' ' . $venta->vendedor->user_primer_apellido) : 'N/A' }}</td>
                        <td class="text-right">Q {{ number_format($venta->ven_total_vendido, 2) }}</td>
                        <td class="text-center">{{ $venta->ven_situacion }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 20px; padding: 10px; background: #ecf0f1; border-radius: 5px;">
            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 14px;">
                <span>Total General:</span>
                <span>Q {{ number_format($totalGeneral, 2) }}</span>
            </div>
        </div>
    @else
        <div class="no-data">
            <h3>No se encontraron registros</h3>
            <p>No hay ventas que mostrar para los criterios seleccionados.</p>
        </div>
    @endif

    <div class="footer">
        <p>Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i:s') }} - Sistema de Gestión</p>
    </div>
</body>
</html>

