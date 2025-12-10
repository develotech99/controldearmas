<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .container { background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { max-height: 60px; }
        .details { margin-bottom: 20px; }
        .details p { margin: 5px 0; }
        .product-list { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .product-list th, .product-list td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .product-list th { background-color: #f8f9fa; }
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
        
        <h3>Nueva Venta Realizada</h3>
        <p>Se ha registrado una nueva venta que requiere su atención.</p>
        
        <div class="details">
            <p><strong>Venta ID:</strong> #{{ $ventaData['ven_id'] }}</p>
            <p><strong>Cliente:</strong> {{ $ventaData['cliente'] }}</p>
            <p><strong>Vendedor:</strong> {{ $ventaData['vendedor'] }}</p>
            <p><strong>Fecha:</strong> {{ $ventaData['fecha'] }}</p>
            <p><strong>Total:</strong> <span style="font-size: 1.2em; color: #28a745; font-weight: bold;">Q{{ number_format($ventaData['total'], 2) }}</span></p>
        </div>

        <h4>Detalle de Productos:</h4>
        <table class="product-list">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th>Producto</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ventaData['productos'] as $prod)
                <tr>
                    <td>{{ $prod['cantidad'] }}</td>
                    <td>{{ $prod['nombre'] }}</td>
                    <td>Q{{ number_format($prod['subtotal'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="text-align: center;">
            <p style="margin-top: 20px; font-weight: bold; color: #d9534f;">Esta venta necesita autorizarla</p>
            <a href="{{ url('/reportes') }}" class="btn">Ir a autorizar venta</a>
        </div>

        <div class="footer">
            <p>Este es un correo automático, por favor no responder.</p>
        </div>
    </div>
</body>
</html>
