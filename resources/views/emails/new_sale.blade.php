<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
    <title>Nueva Venta Realizada — #{{ $ventaData['ven_id'] }}</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;">
    <!-- preheader (oculto) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Nueva venta registrada #{{ $ventaData['ven_id'] }} por {{ $ventaData['vendedor'] }}. Total: Q{{ number_format($ventaData['total'], 2) }}. Acción requerida: Autorizar.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:28px 16px;">
                <!-- Card -->
                <table role="presentation" width="640" cellpadding="0" cellspacing="0"
                    style="max-width:640px;background:#ffffff;border:1px solid #eaecee;border-radius:14px;overflow:hidden;">

                    <tr>
                        <td style="padding:18px 22px;border-bottom:4px solid #f97316;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:88px;">
                                        <img src="{{ $logoCid ?: asset('images/controlarmasdev.png') }}"
                                            alt="CONTROL DE ARMAS" width="72"
                                            style="display:block;border:0;outline:none;border-radius:6px;height:auto;">
                                    </td>

                                    <td valign="top" style="padding-left:12px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="right"
                                                    style="font-family:Arial,Helvetica,sans-serif;color:#9aa1ad;font-size:12px;white-space:nowrap;">
                                                    Venta #{{ $ventaData['ven_id'] }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:6px;font-family:Arial,Helvetica,sans-serif;">
                                                    <span
                                                        style="display:inline-block;background:#ecfdf5;border:1px solid #10b981;color:#059669;
                         padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;">
                                                        ✓ Nueva Venta Registrada
                                                    </span>
                                                    <div
                                                        style="color:#111827;font-size:20px;font-weight:800;margin:8px 0 2px;">
                                                        Autorización Requerida
                                                    </div>

                                                    <div style="color:#6b7280;font-size:12px;">
                                                        Se ha generado una nueva venta que necesita ser revisada y autorizada.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>


                    <!-- Resumen -->
                    <tr>
                        <td style="padding:8px 28px 8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
                                <tr>
                                    <td colspan="2"
                                        style="padding:12px 0 10px;color:#f97316;font-weight:700;font-size:12px;letter-spacing:.06em;
                             text-transform:uppercase;border-bottom:1px solid #edf0f2;">
                                        Detalles de la Venta
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Cliente</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $ventaData['cliente'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Vendedor</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $ventaData['vendedor'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Fecha</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $ventaData['fecha'] }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Total Venta</td>
                                    <td style="padding:10px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;background:#fff1e6;color:#111827;border:1px solid #ffd7ba;
                                 padding:8px 12px;border-radius:8px;font-size:18px;font-weight:800;">
                                            Q {{ number_format($ventaData['total'], 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Detalle de Productos -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin-top:20px;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
                                <tr>
                                    <td colspan="3"
                                        style="padding:12px 0 10px;color:#9aa1ad;font-weight:700;font-size:12px;letter-spacing:.06em;
                             text-transform:uppercase;border-bottom:1px solid #edf0f2;">
                                        Productos
                                    </td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <th style="padding:8px;text-align:left;font-size:12px;color:#6b7280;">Cant.</th>
                                    <th style="padding:8px;text-align:left;font-size:12px;color:#6b7280;">Producto</th>
                                    <th style="padding:8px;text-align:right;font-size:12px;color:#6b7280;">Subtotal</th>
                                </tr>
                                @foreach($ventaData['productos'] as $prod)
                                <tr>
                                    <td style="padding:8px;font-size:13px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $prod['cantidad'] }}</td>
                                    <td style="padding:8px;font-size:13px;color:#111827;border-bottom:1px solid #f3f4f6;">{{ $prod['nombre'] }}</td>
                                    <td style="padding:8px;text-align:right;font-size:13px;color:#111827;border-bottom:1px solid #f3f4f6;">Q{{ number_format($prod['subtotal'], 2) }}</td>
                                </tr>
                                @endforeach
                            </table>

                            <!-- CTA ADMIN -->
                            <div style="text-align:center;margin:22px 0 8px;">
                                <a href="{{ url('/reportes') }}"
                                    style="background:#f97316;border-radius:10px;color:#ffffff;display:inline-block;
                          font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;
                          line-height:44px;text-align:center;text-decoration:none;width:260px;">
                                    Ir a Autorizar Venta
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background:#fafafa;padding:16px 22px;border-top:1px solid #edf0f2;text-align:center;">
                            <div
                                style="font-family:Arial,Helvetica,sans-serif;color:#9aa1ad;font-size:12px;line-height:18px;">
                                © {{ date('Y') }} CONTROL DE ARMAS · Notificación automática.<br>
                                Soporte: <a href="mailto:{{ config('mail.from.address') }}"
                                    style="color:#f97316;text-decoration:none;">{{ config('mail.from.address') }}</a>
                            </div>
                        </td>
                    </tr>
                </table>
                <!-- /Card -->
            </td>
        </tr>
    </table>
</body>

</html>
