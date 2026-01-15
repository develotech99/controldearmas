<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="format-detection" content="telephone=no,date=no,address=no,email=no,url=no">
    <title>
        @if(($tipo ?? 'VENTA') === 'PREVENTA')
            Nuevo comprobante de Preventa
        @elseif(($tipo ?? 'VENTA') === 'DEUDA')
            Nuevo pago de Deuda
        @else
            Nuevo comprobante recibido — Venta #{{ $venta_id }}
        @endif
    </title>
</head>

<body style="margin:0;padding:0;background:#f4f6f8;">
    <!-- preheader (oculto) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Se recibió un comprobante del cliente {{ $cliente['nombre'] }} para la venta #{{ $venta_id }}. Acción
        requerida: validar.
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
                                                @if(($tipo ?? 'VENTA') === 'PREVENTA')
                                                    Comprobante de Preventa
                                                @elseif(($tipo ?? 'VENTA') === 'DEUDA')
                                                    Pago de Deuda
                                                @else
                                                    Venta #{{ $venta_id }}
                                                @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding-top:6px;font-family:Arial,Helvetica,sans-serif;">
                                                    <span
                                                        style="display:inline-block;background:#fff7ed;border:1px solid #f97316;color:#f97316;
                         padding:4px 10px;border-radius:999px;font-size:12px;font-weight:800;">
                                                        ✓ Comprobante recibido
                                                    </span>
                                                    <div
                                                        style="color:#111827;font-size:20px;font-weight:800;margin:8px 0 2px;">
                                                        Revisión y validación requerida
                                                    </div>

                                                    <div style="color:#6b7280;font-size:12px;">
                                                        Verifica el monto, referencia y aplica la validación
                                                        correspondiente.
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
                                        Resumen del envío
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
                                        <a href="mailto:{{ $cliente['email'] }}"
                                            style="color:#f97316;text-decoration:none;">
                                            ({{ $cliente['email'] }})
                                        </a>
                                        @endif
                                        @if(!empty($empresa))
                                        <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                                            Empresa: {{ $empresa }}
                                        </div>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Fecha del comprobante</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $fecha ?? '—' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Monto del comprobante</td>
                                    <td style="padding:10px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;background:#fff1e6;color:#111827;border:1px solid #ffd7ba;
                                 padding:8px 12px;border-radius:8px;font-size:18px;font-weight:800;">
                                            Q {{ number_format((float) $monto, 2) }}
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Banco</td>
                                    <td style="padding:12px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;background:#f3f4f6;color:#111827;border-radius:999px;
                                 padding:6px 10px;font-size:12px;border:1px solid #e5e7eb;">
                                            {{ $banco_nombre ?? '—' }} <span style="color:#9aa1ad;">(ID:
                                                {{ $banco_id ?? '—' }})</span>
                                        </span>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Referencia</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $referencia }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Concepto</td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        {{ $concepto ?? '—' }}</td>
                                </tr>

                                <tr>
                                    <td style="padding:12px 0;color:#6b7280;font-size:13px;">Cuotas pagadas (este envío)
                                    </td>
                                    <td style="padding:12px 0;color:#111827;font-size:14px;text-align:right;">
                                        @php
                                            $cuotasCount = is_array($cuotas ?? null)
                                                ? count($cuotas)
                                                : (is_numeric($cuotas ?? null)
                                                    ? (int) $cuotas
                                                    : 0);
                                        @endphp
                                        {{ $cuotasCount }}
                                    </td>
                                </tr>
                            </table>

                            @if(($tipo ?? 'VENTA') === 'PREVENTA' && !empty($productos))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin-top:10px;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;border-top:1px solid #edf0f2;">
                                <tr>
                                    <td colspan="3"
                                        style="padding:12px 0 10px;color:#f97316;font-weight:700;font-size:12px;letter-spacing:.06em;
                                     text-transform:uppercase;">
                                        Detalle de Productos
                                    </td>
                                </tr>
                                <tr style="background:#f9fafb;color:#6b7280;font-size:11px;text-transform:uppercase;">
                                    <th style="padding:8px;text-align:left;">Producto</th>
                                    <th style="padding:8px;text-align:center;">Cant.</th>
                                    <th style="padding:8px;text-align:right;">Precio</th>
                                </tr>
                                @foreach($productos as $prod)
                                <tr>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;">
                                        {{ $prod['nombre'] }}
                                    </td>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;text-align:center;">
                                        {{ $prod['cantidad'] }}
                                    </td>
                                    <td style="padding:8px;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;text-align:right;">
                                        Q {{ number_format($prod['precio'], 2) }}
                                    </td>
                                </tr>
                                @endforeach
                            </table>
                            @endif

                            <!-- Comparativa (opcional, se mantiene) -->
                            @php
                                $montoFront = (float) ($monto_total ?? 0);
                                $montoComp = (float) ($monto ?? 0);
                                $diff = $montoComp - $montoFront;
                            @endphp
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin-top:10px;border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;">
                                <tr>
                                    <td colspan="2"
                                        style="padding:12px 0 10px;color:#9aa1ad;font-weight:700;font-size:12px;letter-spacing:.06em;
                             text-transform:uppercase;border-bottom:1px solid #edf0f2;">
                                        Comparativa rápida
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Total cuotas (front)</td>
                                    <td style="padding:10px 0;color:#111827;font-size:14px;text-align:right;">
                                        Q {{ number_format($montoFront, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Monto comprobante</td>
                                    <td style="padding:10px 0;color:#111827;font-size:14px;text-align:right;">
                                        Q {{ number_format($montoComp, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;color:#6b7280;font-size:13px;">Diferencia</td>
                                    <td style="padding:10px 0;text-align:right;">
                                        <span
                                            style="display:inline-block;
                                 background:{{ $diff == 0 ? '#ecfdf5' : ($diff > 0 ? '#fff1e6' : '#fee2e2') }};
                                 color:#111827;border:1px solid {{ $diff == 0 ? '#a7f3d0' : ($diff > 0 ? '#ffd7ba' : '#fecaca') }};
                                 padding:6px 10px;border-radius:8px;font-size:13px;font-weight:700;">
                                            {{ $diff == 0 ? '0.00' : ($diff > 0 ? '+' : '') }}{{ number_format($diff, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <div
                                style="margin-top:14px;font-family:Arial,Helvetica,sans-serif;color:#6b7280;font-size:12px;">
                                Se adjunta la imagen del comprobante si fue proporcionada.
                            </div>

                            <!-- CTA ADMIN -->
                            @php
                                $adminCta = $cta_admin ?? url('/pagos/admin');
                            @endphp
                            <div style="text-align:center;margin:22px 0 8px;">
                                <a href="{{ $adminCta }}"
                                    style="background:#f97316;border-radius:10px;color:#ffffff;display:inline-block;
                          font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;
                          line-height:44px;text-align:center;text-decoration:none;width:260px;">
                                    Revisar y validar pago
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
