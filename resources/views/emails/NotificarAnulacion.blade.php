<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Anulada</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.05);overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#ef4444;padding:24px;text-align:center;">
                            @if(!empty($logoBase64))
                                <img src="{{ $logoBase64 }}" alt="Logo" style="max-width:150px;height:auto;display:block;margin:0 auto;">
                            @else
                                <h1 style="margin:0;color:#ffffff;font-size:24px;">Pro Armas</h1>
                            @endif
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 24px;">
                            <h2 style="margin:0 0 16px;color:#111827;font-size:20px;text-align:center;">Factura Anulada</h2>
                            
                            <p style="margin:0 0 24px;color:#4b5563;font-size:16px;line-height:1.5;text-align:center;">
                                Se ha anulado una factura asociada a la <strong>Venta #{{ $payload['venta_id'] }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;border-collapse:collapse;">
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px;">Factura</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;text-align:right;">
                                        {{ $payload['factura_serie'] }} - {{ $payload['factura_numero'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px;">Cliente</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;text-align:right;">
                                        {{ $payload['cliente_nombre'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px;">Anulado por</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;text-align:right;">
                                        {{ $payload['usuario_anulo'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px;">Fecha de Anulación</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;text-align:right;">
                                        {{ $payload['fecha_anulacion'] }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#6b7280;font-size:14px;">Motivo</td>
                                    <td style="padding:12px 0;border-bottom:1px solid #e5e7eb;color:#111827;font-size:14px;font-weight:600;text-align:right;">
                                        {{ $payload['motivo'] }}
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color:#fff1f2;border-left:4px solid #ef4444;padding:16px;margin-bottom:24px;">
                                <p style="margin:0;color:#991b1b;font-size:14px;line-height:1.5;">
                                    <strong>Atención:</strong> Deberá de volver a autorizar esta venta para que el vendedor pueda facturar nuevamente.
                                </p>
                            </div>

                            <div style="text-align:center;">
                                <a href="{{ url('/reportes') }}" style="display:inline-block;background-color:#ef4444;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:6px;font-weight:600;font-size:16px;">
                                    Ir a Autorizar Venta
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f9fafb;padding:24px;text-align:center;border-top:1px solid #e5e7eb;">
                            <p style="margin:0;color:#9ca3af;font-size:12px;">
                                &copy; {{ date('Y') }} Armería Control Pro. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
