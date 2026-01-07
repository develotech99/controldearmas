<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
    <title>Nueva Venta Realizada — Folio #{{ $venta_id }}</title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;">
    <!-- preheader (oculto) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Nueva venta registrada por {{ $vendedor }}. Cliente: {{ $cliente['nombre'] }}. Total: Q {{ number_format((float) $total, 2) }}.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;">
        <tr>
            <td align="center" style="padding:28px 16px;">
                <!-- Card -->
                <table role="presentation" width="640" cellpadding="0" cellspacing="0"
                    style="max-width:640px;background:#ffffff;border:1px solid #eaecee;border-radius:14px;overflow:hidden;">

                    <tr>
                        <td style="padding:18px 22px;border-bottom:4px solid #3b82f6;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td valign="top" style="width:88px;">
                                        <img src="{{ $logoCid ?: asset('images/pro_armas.png') }}"
                                            alt="PRO ARMAS Y MUNICIONES" width="72"
                                            style="display:block;border:0;outline:none;border-radius:6px;height:auto;">
                                    </td>

                                    <td valign="top" style="padding-left:12px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="right"
                                                    style="font-family:Arial,Helvetica,sans-serif;color:#9aa1ad;font-size:12px;white-space:nowrap;">
                                                    Folio #{{ $venta_id }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:6px;font-family:Arial,Helvetica,sans-serif;">
                                                    <span
                                                        style="display:inline-block;background:#eff6ff;border:1px solid #3b82f6;color:#3b82f6;
                         padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;">
                                                        ✓ Nueva Venta Registrada
                                                    </span>
                                                    <div
                                                        style="color:#111827;font-size:20px;font-weight:800;margin:8px 0 2px;">
                                                        Pendiente de Autorización
                                                    </div>

                                                    <div style="color:#6b7280;font-size:12px;">
                                                        @if($metodo_pago_id == 1)
                                                            Venta en Efectivo. El dinero se encuentra en tienda física.
                                                        @else
                                                            Venta registrada. Se requiere validar el comprobante de pago.
                                                        @endif
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
                                        style="padding:12px 0 10px;color:#3b82f6;font-weight:700;font-size:12px;letter-spacing:.06em;
                             text-transform:uppercase;border-bottom:1px solid #edf0f2;">
                                        Detalles de la Venta
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Vendedor</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $vendedor ?? '—' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Cliente</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $cliente['nombre'] }}
                                        @if(!empty($cliente['email']) && $cliente['email'] !== 'No registrado')
                                        <br>
                                        <span style="color:#9aa1ad;font-size:12px;">{{ $cliente['email'] }}</span>
                                        @endif
                                    </td>
                                </tr>

                                @if(!empty($empresa_nombre))
                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Empresa / Sucursal</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $empresa_nombre }}
                                    </td>
                                </tr>
                                @endif

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Fecha</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $fecha ?? date('d/m/Y H:i') }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Monto Total</td>
                                    <td style="padding:10px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;background:#eff6ff;color:#111827;border:1px solid #dbeafe;
                                 padding:8px 12px;border-radius:8px;font-size:18px;font-weight:800;">
                                            Q {{ number_format((float) $total, 2) }}
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Método de Pago</td>
                                    <td style="padding:12px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;background:#f3f4f6;color:#111827;border-radius:999px;
                                 padding:6px 10px;font-size:12px;border:1px solid #e5e7eb;">
                                            {{ $metodo_pago_nombre ?? '—' }}
                                        </span>
                                    </td>
                                </tr>

                            </table>

                            <div
                                style="margin-top:14px;font-family:Arial,Helvetica,sans-serif;color:#6b7280;font-size:12px;">
                                La venta se encuentra en estado <strong>PENDIENTE</strong>. Debe ser autorizada por un administrador.
                            </div>

                            <!-- CTA ADMIN -->
                            @php
                                $adminCta = url('/reportes'); // O la URL correcta para autorizar ventas
                            @endphp
                            <div style="text-align:center;margin:22px 0 8px;">
                                <a href="{{ $adminCta }}"
                                    style="background:#3b82f6;border-radius:10px;color:#ffffff;display:inline-block;
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
                                © {{ date('Y') }} PRO ARMAS Y MUNICIONES · Notificación automática.<br>
                                Soporte: <a href="mailto:{{ config('mail.from.address') }}"
                                    style="color:#3b82f6;text-decoration:none;">{{ config('mail.from.address') }}</a>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
