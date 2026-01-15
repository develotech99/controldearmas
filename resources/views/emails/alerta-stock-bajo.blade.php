@php
    $brand = [
        'primary' => '#ED6A00',
        'dark' => '#111827',
        'muted' => '#6B7280',
        'bg' => '#F3F4F6',
        'white' => '#FFFFFF',
    ];
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Alerta de Stock - Control de Armas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        .preheader { display: none !important; visibility: hidden; opacity: 0; color: transparent; height: 0; width: 0; }
        a { text-decoration: none; }
        @media (max-width: 600px) {
            .container { width: 100% !important; }
            .px { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background: {{ $brand['bg'] }};">

    <div class="preheader">
        Tienes alertas de stock bajo que requieren atención.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background: {{ $brand['bg'] }}; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" class="container" cellspacing="0" cellpadding="0" border="0"
                    style="width:600px; max-width:600px; background: {{ $brand['white'] }}; border-radius: 12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: {{ $brand['white'] }}; padding:24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center" style="border:2px solid #ED6A00;border-radius:12px;padding:10px 16px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:32px;font-weight:800;color:#111827;">
                                                    CONTROL DE <span style="color:#ED6A00;">ARMAS</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td align="center" style="font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:16px;color:#6B7280;letter-spacing:.6px;text-transform:uppercase;">
                                                    by Develotech
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="px" style="padding: 0 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-top: 8px; padding-bottom: 8px;">
                                        <h1 style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:24px; line-height:1.3; color: {{ $brand['dark'] }};">
                                            ⚠️ Reporte Diario de Stock
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 16px;">
                                        <p style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color: {{ $brand['muted'] }};">
                                            Hola <strong>{{ $nombreAdmin }}</strong>,<br>
                                            {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                                        </p>
                                    </td>
                                </tr>

                                @php
                                    $totalAlertas = $alertasAgrupadas['critica']->count() + 
                                                    $alertasAgrupadas['alta']->count() + 
                                                    $alertasAgrupadas['media']->count();
                                @endphp

                                @if($totalAlertas > 0)
                                    <tr>
                                        <td style="padding-bottom: 16px;">
                                            <p style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color: {{ $brand['dark'] }};">
                                                Tienes <strong style="color: #DC2626;">{{ $totalAlertas }} alerta(s)</strong> que requieren atención:
                                            </p>
                                        </td>
                                    </tr>

                                    <!-- Alertas Críticas -->
                                    @if($alertasAgrupadas['critica']->isNotEmpty())
                                        <tr>
                                            <td style="padding-bottom: 12px;">
                                                <div style="background: #FEF2F2; border-left: 4px solid #DC2626; border-radius: 6px; padding: 12px;">
                                                    <p style="margin:0 0 8px 0; font-family: Arial, Helvetica, sans-serif; font-size:14px; font-weight:600; color: #DC2626;">
                                                        🔴 CRÍTICAS ({{ $alertasAgrupadas['critica']->count() }})
                                                    </p>
                                                    @foreach($alertasAgrupadas['critica'] as $alerta)
                                                        <div style="background: white; border-radius: 4px; padding: 10px; margin-bottom: 8px;">
                                                            <p style="margin:0 0 4px 0; font-family: Arial, Helvetica, sans-serif; font-size:14px; font-weight:600; color: #111827;">
                                                                {{ $alerta->producto_nombre }}
                                                            </p>
                                                            <p style="margin:0 0 6px 0; font-family: Arial, Helvetica, sans-serif; font-size:13px; color: #6B7280;">
                                                                {{ $alerta->alerta_mensaje }}
                                                            </p>
                                                            <span style="background: #E5E7EB; color: #374151; padding: 2px 8px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px;">
                                                                SKU: {{ $alerta->pro_codigo_sku }}
                                                            </span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endif

                                    <!-- Botón de acción -->
                                    <tr>
                                        <td align="center" style="padding: 16px 0 24px;">
                                            <a href="{{ url('/inventario') }}" style="background: {{ $brand['primary'] }}; color:#fff; display:inline-block; padding:14px 24px; border-radius:8px; font-family: Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">
                                                Ver Panel de Inventario
                                            </a>
                                        </td>
                                    </tr>
                                @else
                                    <tr>
                                        <td align="center" style="padding: 40px 20px;">
                                            <p style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:16px; color: #10B981;">
                                                ✅ No hay alertas pendientes
                                            </p>
                                        </td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:0 32px;">
                            <hr style="border:0; height:1px; background:#E5E7EB; margin:0;">
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="px" style="padding: 16px 32px 28px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="left">
                                        <p style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:12px; color: {{ $brand['muted'] }};">
                                            © {{ now()->year }} <strong>Control de Armas</strong>. Todos los derechos reservados.
                                        </p>
                                        <p style="margin:4px 0 0; font-family: Arial, Helvetica, sans-serif; font-size:12px; color: {{ $brand['muted'] }};">
                                            <em>Desarrollado por <strong>Develotech</strong></em>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>