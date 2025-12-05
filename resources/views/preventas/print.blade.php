<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventa #{{ $preventa->prev_id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white p-8 shadow-lg print:shadow-none print:p-0">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8 border-b pb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">ProArmas y Municiones</h1>
                <p class="text-gray-600">Comprobante de Preventa</p>
            </div>
            <div class="text-right">
                <h2 class="text-xl font-bold text-gray-700">Preventa #{{ $preventa->prev_id }}</h2>
                <p class="text-sm text-gray-500">Fecha: {{ $preventa->prev_fecha->format('d/m/Y') }}</p>
            </div>
        </div>

        <!-- Client Info -->
        <div class="mb-8 bg-gray-50 p-4 rounded-lg print:bg-transparent print:p-0">
            <h3 class="text-lg font-bold text-gray-700 mb-2">Datos del Cliente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p><span class="font-semibold">Nombre:</span> {{ $preventa->cliente->cliente_nombre1 }} {{ $preventa->cliente->cliente_apellido1 }}</p>
                    @if($preventa->empresa)
                        <p><span class="font-semibold">Empresa:</span> {{ $preventa->empresa->emp_nombre }}</p>
                    @elseif($preventa->cliente->cliente_nom_empresa)
                        <p><span class="font-semibold">Empresa:</span> {{ $preventa->cliente->cliente_nom_empresa }}</p>
                    @endif
                </div>
                <div>
                    <p><span class="font-semibold">NIT:</span> {{ $preventa->cliente->cliente_nit }}</p>
                    <p><span class="font-semibold">Estado:</span> {{ $preventa->prev_estado }}</p>
                </div>
            </div>
        </div>

        <!-- Products -->
        <table class="w-full mb-8">
            <thead>
                <tr class="bg-gray-800 text-white print:bg-gray-200 print:text-black">
                    <th class="py-2 px-4 text-left">Descripción</th>
                    <th class="py-2 px-4 text-center">Cantidad</th>
                    <th class="py-2 px-4 text-right">Precio Ref.</th>
                    <th class="py-2 px-4 text-right">Subtotal Ref.</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @foreach($preventa->detalles as $detalle)
                <tr class="border-b">
                    <td class="py-2 px-4">{{ $detalle->producto->producto_nombre }}</td>
                    <td class="py-2 px-4 text-center">{{ $detalle->det_cantidad }}</td>
                    <td class="py-2 px-4 text-right">Q{{ number_format($detalle->det_precio_unitario, 2) }}</td>
                    <td class="py-2 px-4 text-right font-semibold">Q{{ number_format($detalle->det_subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="flex justify-end mb-8">
            <div class="w-1/2">
                <div class="flex justify-between py-2 border-b">
                    <span class="font-bold text-gray-600">Total Referencia:</span>
                    <span class="font-bold text-gray-800">Q{{ number_format($preventa->prev_total, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="font-bold text-emerald-600">Monto Abonado:</span>
                    <span class="font-bold text-emerald-600">Q{{ number_format($preventa->prev_monto_pagado, 2) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b bg-gray-100 print:bg-transparent">
                    <span class="font-bold text-gray-800">Saldo Pendiente (Ref):</span>
                    <span class="font-bold text-gray-800">Q{{ number_format($preventa->prev_total - $preventa->prev_monto_pagado, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Disclaimer -->
        <div class="border-t-2 border-gray-300 pt-4 mt-8 text-center">
            <p class="text-xs text-gray-500 mb-2">Observaciones: {{ $preventa->prev_observaciones ?? 'Ninguna' }}</p>
            <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg print:border-gray-300 print:bg-transparent">
                <p class="font-bold text-sm text-gray-700 uppercase">
                    *** IMPORTANTE: LOS PRECIOS MOSTRADOS SON DE REFERENCIA Y PUEDEN VARIAR AL MOMENTO DE LA VENTA FINAL ***
                </p>
                <p class="text-xs text-gray-500 mt-1">Este documento es un comprobante de preventa y no constituye una factura final.</p>
            </div>
        </div>

        <!-- Print Button (Hidden when printing) -->
        <div class="mt-8 text-center no-print">
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-lg transition duration-150">
                <i class="fas fa-print mr-2"></i>Imprimir Comprobante
            </button>
            <button onclick="window.close()" class="ml-4 bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded shadow-lg transition duration-150">
                Cerrar
            </button>
        </div>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>
