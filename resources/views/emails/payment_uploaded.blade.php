<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .container { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .details { margin-bottom: 20px; }
        .details p { margin: 5px 0; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .btn:hover { background-color: #0056b3; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ $logoCid ?: asset('images/pro_armas.png') }}" alt="Armería Control Pro" style="max-height: 80px; width: auto;">
        </div>
        
        <h3>Comprobante de Pago Subido</h3>
        <p>Se ha subido un comprobante de pago para la siguiente venta:</p>
        
        <div class="details">
            <p><strong>Venta ID:</strong> #{{ $pagoData['venta_id'] }}</p>
            <p><strong>Monto:</strong> <span style="font-size: 1.1em; font-weight: bold;">Q{{ number_format($pagoData['monto'], 2) }}</span></p>
            <p><strong>Banco:</strong> {{ $pagoData['banco'] }}</p>
            <p><strong>No. Autorización:</strong> {{ $pagoData['no_autorizacion'] }}</p>
            <p><strong>Fecha Pago:</strong> {{ $pagoData['fecha_pago'] }}</p>
        </div>

        <div style="text-align: center;">
            <a href="{{ url('/pagos') }}" class="btn">Ir a Administrar Pagos</a>
        </div>

        <div style="text-align: center;">
            <a href="{{ url('/reportes') }}" class="btn">Sí no ha sido autorizada,  Ir a Autorizar venta</a>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
