<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Venta #{{ $venta->ven_id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { font-size: 24px; color: #2c3e50; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        
        .info-section { margin-bottom: 30px; display: table; width: 100%; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-label { font-weight: bold; color: #555; }
        .info-value { margin-bottom: 5px; }
        
        .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #3498db; color: white; font-weight: bold; text-align: center; }
        .table tr:nth-child(even) { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .totals { float: right; width: 300px; }
        .totals-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid #eee; }
        .totals-row.final { border-top: 2px solid #333; border-bottom: none; font-weight: bold; font-size: 14px; margin-top: 10px; padding-top: 10px; }
        
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        
        .status-badge { 
            padding: 5px 10px; 
            border-radius: 15px; 
            font-weight: bold; 
            font-size: 10px;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PRO ARMAS</h1>
        <p>Comprobante de Venta #{{ $venta->ven_id }}</p>
        <p>Fecha: {{ \Carbon\Carbon::parse($venta->ven_fecha)->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="info-section">
        <div class="info-col">
            <h3 style="margin-top: 0; color: #2c3e50;">Datos del Cliente</h3>
            <div><span class="info-label">Nombre:</span> {{ $venta->cliente->cliente_nombre1 }} {{ $venta->cliente->cliente_apellido1 }}</div>
            <div><span class="info-label">DPI:</span> {{ $venta->cliente->cliente_dpi ?? 'N/A' }}</div>
            <div><span class="info-label">NIT:</span> {{ $venta->cliente->cliente_nit ?? 'C/F' }}</div>
            <div><span class="info-label">Dirección:</span> {{ $venta->cliente->cliente_direccion ?? 'Ciudad' }}</div>
        </div>
        <div class="info-col">
            <h3 style="margin-top: 0; color: #2c3e50;">Información de Venta</h3>
            <div><span class="info-label">Vendedor:</span> {{ $venta->vendedor->user_primer_nombre }} {{ $venta->vendedor->user_primer_apellido }}</div>
            <div><span class="info-label">Estado Venta:</span> {{ $venta->ven_situacion }}</div>
            <div><span class="info-label">Estado Pago:</span> {{ $venta->estado_pago }}</div>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Cant.</th>
                <th>SKU</th>
                <th>Producto</th>
                <th>Precio Unit.</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalleVentas as $detalle)
            <tr>
                <td class="text-center">{{ $detalle->det_cantidad }}</td>
                <td class="text-center">{{ $detalle->producto->pro_codigo_sku ?? 'N/A' }}</td>
                <td>{{ $detalle->producto->producto_nombre ?? 'Producto no encontrado' }}</td>
                <td class="text-right">Q {{ number_format($detalle->det_precio, 2) }}</td>
                <td class="text-right">Q {{ number_format($detalle->det_cantidad * $detalle->det_precio, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>Q {{ number_format($venta->ven_total_vendido, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Descuento:</span>
            <span>Q {{ number_format($venta->ven_descuento ?? 0, 2) }}</span>
        </div>
        <div class="totals-row final">
            <span>Total a Pagar:</span>
            <span>Q {{ number_format($venta->ven_total_vendido - ($venta->ven_descuento ?? 0), 2) }}</span>
        </div>
        
        <div style="margin-top: 20px; border-top: 1px dashed #ccc; padding-top: 10px;">
            <div class="totals-row">
                <span>Total Pagado:</span>
                <span>Q {{ number_format($venta->pagos->sum('pago_monto_abonado'), 2) }}</span>
            </div>
            <div class="totals-row" style="color: #e74c3c; font-weight: bold;">
                <span>Saldo Pendiente:</span>
                <span>Q {{ number_format(($venta->ven_total_vendido - ($venta->ven_descuento ?? 0)) - $venta->pagos->sum('pago_monto_abonado'), 2) }}</span>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Este documento es un comprobante interno y no sustituye a una factura contable.</p>
        <p>Generado por Sistema ProArmas el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
