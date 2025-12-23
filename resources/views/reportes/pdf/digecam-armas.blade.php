<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual de Venta de Armas de Fuego</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            padding: 15mm 10mm;
            line-height: 1.3;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo {
            width: 50px;
            height: 50px;
            margin: 0 auto 5px;
        }

        .header h1 {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 3px 0;
        }

        .header h2 {
            font-size: 10px;
            font-weight: bold;
            margin: 8px 0;
        }

        .info-section {
            margin: 10px 0;
            padding: 5px;
            background: #f0f0f0;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 7px;
        }

        table th {
            background-color: #333;
            color: white;
            padding: 4px 2px;
            text-align: center;
            border: 1px solid #000;
            font-weight: bold;
            font-size: 7px;
        }

        table td {
            border: 1px solid #666;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .highlight {
            background-color: #ffff99 !important;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="logo">
            <strong>DIGECAM</strong>
        </div>
        <h1>EJERCITO DE GUATEMALA</h1>
        <h1>MINISTERIO DE LA DEFENSA NACIONAL</h1>
        <h1>DIRECCIÓN GENERAL DE CONTROL DE ARMAS Y MUNICIONES</h1>
        <hr style="margin: 10px 0; border: 1px solid #000;">
        <h2>REPORTE MENSUAL DE VENTA DE ARMAS DE FUEGO</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div><strong>AÑO:</strong> {{ $anio }}</div>
            <div><strong>MES:</strong> {{ $mes }}</div>
        </div>
        <div class="info-row">
            <div><strong>OPERADOR:</strong> {{ $operador }}</div>
            <div><strong>EMPRESA:</strong> {{ $empresa }}</div>
        </div>
    </div>

    @if(count($data) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">No.</th>
                {{-- <th style="width: 8%;">TENENCIA<br>ANTERIOR</th> --}}
                {{-- <th style="width: 8%;">TENENCIA<br>NUEVA</th> --}}
                <th style="width: 8%;">TIPO</th>
                <th style="width: 12%;">SERIE</th>
                <th style="width: 10%;">MARCA</th>
                <th style="width: 8%;">MODELO</th>
                <th style="width: 8%;">CALIBRE</th>
                <th style="width: 15%;">COMPRADOR</th>
                <th style="width: 8%;">FECHA</th>
                <th style="width: 10%;">FACTURA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $venta)
            @php
            // Convertir array a objeto si es necesario
            $ventaObj = is_array($venta) ? (object)$venta : $venta;
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                {{-- <td>{{ $ventaObj->pro_tenencia_anterior ?? '' }}</td> --}}
                {{-- <td class="highlight">{{ $ventaObj->pro_tenencia_nueva ?? '' }}</td> --}}
                <td>{{ $ventaObj->tipo ?? 'N/A' }}</td>
                <td class="highlight">{{ $ventaObj->serie ?? 'SIN SERIE' }}</td>
                <td>{{ $ventaObj->marca ?? 'N/A' }}</td>
                <td>{{ $ventaObj->modelo ?? 'N/A' }}</td>
                <td>{{ $ventaObj->calibre ?? 'N/A' }}</td>
                <td style="text-align: left; padding-left: 5px;">
                    {{ strtoupper($ventaObj->comprador ?? 'CLIENTE GENERAL') }}
                </td>
                <td>
                    {{ isset($ventaObj->fecha) ? \Carbon\Carbon::parse($ventaObj->fecha)->format('Y-m-d') : '' }}
                </td>
                <td class="highlight">{{ $ventaObj->factura ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">
        <p>No se registraron ventas de armas de fuego en este período.</p>
    </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ $fecha_generacion }}</p>
        <p>Total de registros: {{ count($data) }}</p>
    </div>
</body>

</html>