<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Factura Cambiaria {{ $factura->fac_serie }}-{{ $factura->fac_numero }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
            font-size: 13px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            max-width: 21cm;
            margin: 0 auto;
            background: white;
            padding: 2cm;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
        }

        .logo-datos {
            display: flex;
            gap: 20px;
            align-items: center;
            flex: 1;
        }

        .logo {
            width: 100px;
            height: 100px;
            flex-shrink: 0;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .datos-empresa {
            flex: 1;
        }

        .datos-empresa h1 {
            font-size: 18px;
            margin-bottom: 8px;
            color: #000;
            font-weight: bold;
        }

        .datos-empresa p {
            margin: 3px 0;
            font-size: 12px;
            color: #555;
            line-height: 1.4;
        }

        .tipo-doc {
            text-align: right;
            padding: 15px;
            background: #f0f0f0;
            border: 2px solid #333;
            border-radius: 4px;
        }

        .tipo-doc h2 {
            font-size: 20px;
            margin-bottom: 5px;
            color: #000;
        }

        .tipo-doc p {
            font-size: 11px;
            color: #666;
        }

        /* Info documento y receptor */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
            background: #fafafa;
        }

        .info-box h3 {
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 12px;
            color: #000;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 12px;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #000;
            text-align: right;
        }

        .uuid {
            font-family: Courier, monospace;
            font-size: 10px;
            word-break: break-all;
            background: white;
            padding: 8px;
            border: 1px solid #ddd;
            margin-top: 8px;
        }

        /* Tabla */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 12px;
        }

        table thead {
            background: #333;
            color: white;
        }

        table th {
            padding: 10px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
        }

        table th.right,
        table td.right {
            text-align: right;
        }

        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        table tbody tr:hover {
            background: #f9f9f9;
        }

        /* Totales */
        .totales-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .totales-box {
            width: 300px;
            border: 1px solid #ddd;
            background: #fafafa;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 15px;
            font-size: 12px;
            border-bottom: 1px solid #eee;
        }

        .total-row.final {
            background: #333;
            color: white;
            font-weight: bold;
            font-size: 14px;
            border-bottom: none;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }

        .footer p {
            margin: 5px 0;
            line-height: 1.5;
        }

        .certificado {
            background: #e8f5e9;
            border: 1px solid #4caf50;
            padding: 12px;
            margin: 20px 0;
            font-size: 11px;
            text-align: center;
        }

        .certificado strong {
            color: #2e7d32;
            display: block;
            margin-bottom: 5px;
        }

        /* Botones */
        .btn-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .btn-print {
            background: #333;
            color: white;
        }

        .btn-print:hover {
            background: #555;
        }

        .btn-download {
            background: white;
            color: #333;
            border: 1px solid #ddd;
        }

        .btn-download:hover {
            background: #f5f5f5;
        }

        @media print {
            @page {
                size: letter;
                margin: 0;
            }

            html,
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                height: 100% !important;
                font-size: 12px !important;
            }

            .page {
                box-shadow: none !important;
                padding: 1cm !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            table thead {
                background: #333 !important;
                -webkit-print-color-adjust: exact !important;
            }

            table thead th {
                background: #333 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
            }

            .total-row.final {
                background: #333 !important;
                color: white !important;
                -webkit-print-color-adjust: exact !important;
            }

            .header,
            .info-grid,
            table,
            .totales-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            table {
                page-break-inside: auto !important;
            }

            table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            table thead {
                display: table-header-group !important;
            }

            .btn-toolbar {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                height: 0 !important;
                width: 0 !important;
                position: absolute !important;
                left: -9999px !important;
            }

            body>*:not(.page) {
                display: none !important;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
            }

            .tipo-doc {
                margin-top: 15px;
                text-align: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .btn-toolbar {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <!-- Botones -->
    <div class="btn-toolbar">
        <button onclick="imprimirFactura()" class="btn btn-print">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
            Imprimir
        </button>
        @if ($factura->fac_xml_certificado_path)
            <a href="{{ asset('storage/' . $factura->fac_xml_certificado_path) }}" download class="btn btn-download">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                XML
            </a>
        @endif
    </div>

    <div class="page">
        <!-- Header -->
        <div class="header">
            <div class="logo-datos">
                <div class="logo">
                    <img src="{{ asset('images/pro_armas.png') }}" alt="Logo ProArmas & Municiones">
                </div>
                <div class="datos-empresa">
                    <h1>{{ $emisor['comercial'] ?? $emisor['nombre'] }}</h1>
                    <p><strong>NIT:</strong> {{ $emisor['nit'] }}</p>
                    <p>{{ $emisor['direccion'] }}</p>
                </div>
            </div>

            <div class="tipo-doc">
                <h2>FACTURA CAMBIARIA</h2>
                <p>Documento Tributario Electrónico FEL</p>
                <p>Título de crédito con abonos pactados</p>
            </div>
        </div>

        <!-- Info documento y receptor -->
        <div class="info-grid">
            <div class="info-box">
                <h3>Información del Documento</h3>
                <div class="info-row">
                    <span class="info-label">Serie - Número:</span>
                    <span class="info-value">{{ $factura->fac_serie }}-{{ $factura->fac_numero }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Emisión:</span>
                    <span class="info-value">{{ $factura->fac_fecha_emision?->format('d/m/Y') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Certificación:</span>
                    <span class="info-value">
                        {{ optional($factura->fac_fecha_certificacion)->format('d/m/Y H:i') }}
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Moneda:</span>
                    <span class="info-value">{{ $factura->fac_moneda }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Autorización:</span>
                    <span class="info-value">{{ $factura->fac_serie }}</span>
                </div>
                <div style="margin-top: 10px;">
                    <div class="info-label" style="margin-bottom: 5px;">UUID:</div>
                    <div class="uuid"><b>{{ $factura->fac_uuid }}</b></div>
                </div>
            </div>
            <div class="info-box">
                <h3>Cliente</h3>
                <div class="info-row">
                    <span class="info-label">NIT:</span>
                    <span class="info-value">{{ $factura->fac_nit_receptor }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value">{{ $factura->fac_receptor_nombre }}</span>
                </div>
                @if ($factura->fac_receptor_direccion)
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value">{{ $factura->fac_receptor_direccion }}</span>
                    </div>
                @endif
                @if ($factura->fac_receptor_telefono)
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value">{{ $factura->fac_receptor_telefono }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabla de productos -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Descripción</th>
                    <th class="right" style="width: 70px;">Cant.</th>
                    <th class="right" style="width: 80px;">P. Unit.</th>
                    <th class="right" style="width: 70px;">Desc.</th>
                    <th class="right" style="width: 90px;">Subtotal</th>
                    <th class="right" style="width: 70px;">IVA</th>
                    <th class="right" style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($factura->detalle as $i => $d)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $d->det_fac_producto_desc }}</td>
                        <td class="right">{{ number_format($d->det_fac_cantidad, 2) }}</td>
                        <td class="right">Q {{ number_format($d->det_fac_precio_unitario, 2) }}</td>
                        <td class="right">Q {{ number_format($d->det_fac_descuento, 2) }}</td>
                        <td class="right">Q {{ number_format($d->det_fac_monto_gravable, 2) }}</td>
                        <td class="right">Q {{ number_format($d->det_fac_impuesto, 2) }}</td>
                        <td class="right"><strong>Q {{ number_format($d->det_fac_total, 2) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totales -->
        <div class="totales-section">
            <div class="totales-box">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <strong>Q {{ number_format($factura->fac_subtotal, 2) }}</strong>
                </div>
                @if ($factura->fac_descuento > 0)
                    <div class="total-row">
                        <span>Descuento:</span>
                        <strong>Q {{ number_format($factura->fac_descuento, 2) }}</strong>
                    </div>
                @endif
                <div class="total-row">
                    <span>IVA (12%):</span>
                    <strong>Q {{ number_format($factura->fac_impuestos, 2) }}</strong>
                </div>
                <div class="total-row final">
                    <span>TOTAL:</span>
                    <span>Q {{ number_format($factura->fac_total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- PLAN DE ABONOS / FACTURA CAMBIARIA --}}
        @if (!empty($abonos ?? []))
            <div style="margin-top: 30px;">
                <h3 style="font-size: 13px; margin-bottom: 10px; text-transform: uppercase; color:#000;">
                    Plan de pagos / Abonos factura cambiaria
                </h3>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px;"># Abono</th>
                            <th style="width: 140px;">Fecha vencimiento</th>
                            <th class="right" style="width: 120px;">Monto abono</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalAbonos = 0;
                        @endphp
                        @foreach ($abonos as $abono)
                            @php
                                // Ajusta nombres de campos según tu modelo
                                $numero = $abono['numero'] ?? $abono->numero ?? $loop->iteration;
                                $fecha  = $abono['fecha'] ?? $abono['fecha_vencimiento'] ?? $abono->fecha ?? $abono->fecha_vencimiento ?? null;
                                $monto  = $abono['monto'] ?? $abono->monto ?? 0;
                                $totalAbonos += $monto;
                            @endphp
                            <tr>
                                <td>{{ $numero }}</td>
                                <td>
                                    @if ($fecha)
                                        {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="right">Q {{ number_format($monto, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="2" class="right" style="font-weight:bold;">Total abonos:</td>
                            <td class="right" style="font-weight:bold;">Q {{ number_format($totalAbonos, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Información adicional -->
        <div
            style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <div style="font-size: 12px;">
                <p style="margin: 5px 0;"><strong>Atendido por:</strong> {{ $factura->fac_vendedor }}</p>
                <p style="margin: 5px 0; color: #666;">Fecha operación:
                    {{ $factura->fac_fecha_operacion?->format('d/m/Y H:i') }}
                </p>
            </div>
            <div style="text-align: right; font-size: 11px; color: #666;">
                <p>Factura Cambiaria Electrónica en Línea (FEL)</p>
                <p>Este documento constituye título de crédito por el total indicado.</p>
            </div>
        </div>
    </div>

    <script>
        function imprimirFactura() {
            const tituloOriginal = document.title;

            document.body.classList.add('printing');
            document.title = `Factura-Cambiaria-{{ $factura->fac_serie }}-{{ $factura->fac_numero }}`;

            document.body.offsetHeight;

            setTimeout(() => {
                window.print();

                setTimeout(() => {
                    document.title = tituloOriginal;
                    document.body.classList.remove('printing');
                }, 500);
            }, 100);
        }

        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
            document.querySelectorAll('table, .header, .totales-section').forEach(el => {
                el.style.visibility = 'visible';
            });
        });

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
    </script>
</body>

</html>
