<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual de Venta de Munición</title>
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

        .subtitle {
            text-align: center;
            font-size: 9px;
            margin: 8px 0;
            font-weight: bold;
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
        <h2>REPORTE MENSUAL DE VENTA DE MUNICIÓN CORRESPONDIENTE DE LA FECHA {{ \Carbon\Carbon::parse($fecha_inicio)->format('Y-m-d') }}</h2>
        <h2>A LA FECHA {{ \Carbon\Carbon::parse($fecha_fin)->format('Y-m-d') }}</h2>
    </div>

    <div class="subtitle">
        <strong>{{ $empresa }}</strong>
    </div>

    @if(count($data) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th style="width: 10%;">DOCUMENTO</th>
                <th style="width: 20%;">NOMBRE</th>
                <th style="width: 10%;">FACTURA</th>
                <th style="width: 8%;">FECHA</th>
                <th style="width: 10%;">SERIE ARMA</th>
                <th style="width: 8%;">CLASE<br>ARMA</th>
                <th style="width: 8%;">CALIBRE<br>ARMA</th>
                <th style="width: 8%;">CALIBRE<br>VENDIDO</th>
                <th style="width: 6%;">CANTIDAD</th>
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
                <td>{{ $ventaObj->documento_tipo ?? '' }} {{ $ventaObj->documento_numero ?? '' }} {{ $ventaObj->documento_secundario ?? '' }}</td>
                <td style="text-align: left; padding-left: 5px;">
                    {{ strtoupper($ventaObj->nombre ?? 'CLIENTE GENERAL') }}
                </td>
                <td class="highlight">{{ $ventaObj->factura ?? '' }}</td>
                <td>
                    {{ isset($ventaObj->fecha) ? \Carbon\Carbon::parse($ventaObj->fecha)->format('Y-m-d') : '' }}
                </td>
                <td>{{ $ventaObj->serie_arma ?? 'N/A' }}</td>
                <td>{{ $ventaObj->clase_arma ?? 'N/A' }}</td>
                <td>{{ $ventaObj->calibre_arma ?? 'N/A' }}</td>
                <td>{{ $ventaObj->calibre_vendido ?? 'N/A' }}</td>
                <td class="highlight"><strong>{{ $ventaObj->cantidad ?? 0 }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 15px; padding: 8px; background: #e8e8e8; border: 1px solid #333;">
        <strong>Total de municiones vendidas: {{ collect($data)->sum('cantidad') }} unidades</strong>
    </div>
    @else
    <div class="no-data">
        <p>No se registraron ventas de munición en este período.</p>
    </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ $fecha_generacion }}</p>
        <p>Total de registros: {{ count($data) }}</p>
    </div>
</body>

</html>