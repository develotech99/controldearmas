{{-- resources/views/emails/verificacion.blade.php --}}
@php
    $brand = [
        'primary' => '#ED6A00', // Naranja Pro Armas
        'dark' => '#111827', // Texto principal
        'muted' => '#6B7280', // Texto secundario
        'bg' => '#F3F4F6', // Fondo general
        'white' => '#FFFFFF',
    ];
@endphp
<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Verifica tu correo - Control de Armas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Preheader (texto corto que verán antes de abrir el email) -->
    <style>
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            mso-hide: all;
            overflow: hidden;
        }

        a {
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .px {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background: {{ $brand['bg'] }};">

    <div class="preheader">
        Confirma tu dirección de correo para activar tu cuenta en Control de Armas.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0"
        style="background: {{ $brand['bg'] }}; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" class="container" cellspacing="0" cellpadding="0"
                    border="0"
                    style="width:600px; max-width:600px; background: {{ $brand['white'] }}; border-radius: 12px; overflow:hidden; box-shadow:0 6px 18px rgba(0,0,0,.08);">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background: {{ $brand['white'] }}; padding:24px;">
                            @php
                                $logoUrl = secure_asset('images/controlarmasdev.png'); 
                                $useImage = str_starts_with($logoUrl, 'https://');
                            @endphp

                            @if ($useImage)
                                <img src="{{ $logoUrl }}" alt="Control de Armas" width="160" height="60"
                                    style="display:block;border:0;outline:none;text-decoration:none;">
                            @else
     
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0"
                                    style="border-collapse:collapse;">
                                    <tr>
                                        <td align="center"
                                            style="border:2px solid #ED6A00;border-radius:12px;padding:10px 16px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                                <tr>
                                                    <td align="center"
                                                        style="font-family:Arial,Helvetica,sans-serif;font-size:24px;line-height:32px;font-weight:800;color:#111827;">
                                                        CONTROL DE <span style="color:#ED6A00;">ARMAS</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center"
                                                        style="font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:16px;color:#6B7280;letter-spacing:.6px;text-transform:uppercase;">
                                                        by Develotech
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="px" style="padding: 0 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="padding-top: 8px; padding-bottom: 8px;">
                                        <h1
                                            style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:24px; line-height:1.3; color: {{ $brand['dark'] }};">
                                            Confirma tu correo electrónico
                                        </h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 16px;">
                                        <p
                                            style="margin: 0; font-family: Arial, Helvetica, sans-serif; font-size:15px; line-height:1.6; color: {{ $brand['muted'] }};">
                                            Hola
                                            {{ trim(($user->user_primer_nombre ?? '') . ' ' . ($user->user_primer_apellido ?? '')) }},<br>
                                            Gracias por registrarte en <strong>Control de Armas</strong>. Para activar tu
                                            cuenta, por favor haz clic en el botón:
                                        </p>
                                    </td>
                                </tr>

                                <!-- Botón bulletproof (incluye VML para Outlook) -->
                                <tr>
                                    <td align="center" style="padding: 8px 0 24px;">
                                        <!--[if mso]>
                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $link }}" style="height:48px;v-text-anchor:middle;width:280px;" arcsize="10%" stroke="f" fillcolor="{{ $brand['primary'] }}">
                      <w:anchorlock/>
                      <center style="color:#ffffff;font-family:Arial, Helvetica, sans-serif;font-size:16px;font-weight:bold;">
                        Validar correo
                      </center>
                    </v:roundrect>
                    <![endif]-->
                                        <![if !mso]>
                                        <a href="{{ $link }}" target="_blank"
                                            style="background: {{ $brand['primary'] }}; color:#fff; display:inline-block; padding:14px 24px; border-radius:8px; font-family: Arial, Helvetica, sans-serif; font-size:16px; font-weight:bold;">
                                            Validar correo
                                        </a>
                                        <![endif]>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding-bottom: 8px;">
                                        <p
                                            style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:13px; line-height:1.6; color: {{ $brand['muted'] }};">
                                            Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                                            <span style="word-break: break-all;">
                                                <a href="{{ $link }}"
                                                    style="color: {{ $brand['primary'] }};">{{ $link }}</a>
                                            </span>
                                        </p>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding: 16px 0 24px;">
                                        <p
                                            style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:13px; line-height:1.6; color: {{ $brand['muted'] }};">
                                            Si no solicitaste esta cuenta, puedes ignorar este correo con confianza.
                                        </p>
                                    </td>
                                </tr>
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
                                        <p
                                            style="margin:0; font-family: Arial, Helvetica, sans-serif; font-size:12px; color: {{ $brand['muted'] }};">
                                            © {{ now()->year }} <strong>Control de Armas</strong>. Todos los derechos
                                            reservados.
                                        </p>
                                        <p
                                            style="margin:4px 0 0; font-family: Arial, Helvetica, sans-serif; font-size:12px; color: {{ $brand['muted'] }};">
                                            <em>Desarrollado por <strong>Develotech</strong></em>
                                        </p>
                                    </td>
                                    <td align="right">
                                        <a href="{{ config('app.url') }}"
                                            style="font-family: Arial, Helvetica, sans-serif; font-size:12px; color: {{ $brand['primary'] }};">
                                            Visitar sitio
                                        </a>
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
