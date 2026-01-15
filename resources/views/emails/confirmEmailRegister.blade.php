<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación Exitosa - Control de Armas</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card {
            transition: all 0.3s ease;
        }

        .countdown {
            font-size: 1.2rem;
            font-weight: bold;
            color: #3b82f6;
        }
    </style>
</head>

<body>
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden mx-4 card">
        <div class="p-8">
            <div class="text-center">
                <div class="w-24 h-24 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-circle text-green-500 text-5xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">¡Confirmación Exitosa!</h1>
                <p class="text-gray-600 mb-6">Tu correo electrónico ha sido verificado correctamente. Ahora puedes
                    iniciar sesión en tu cuenta.</p>

                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <p class="text-blue-800">Serás redirigido automáticamente al login en <span id="countdown"
                            class="countdown">5</span> segundos</p>
                </div>

                <div class="flex justify-center space-x-4">
                    <a href="/login"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition duration-200">
                        <i class="fas fa-sign-in-alt mr-2"></i>Ir al Login
                    </a>
                    <a href="/"
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                        <i class="fas fa-home mr-2"></i>Inicio
                    </a>
                </div>
            </div>
        </div>
        <div class="py-4 bg-gray-100 text-center text-gray-600 text-sm">
            <p>© 2023 Control de Armas. Todos los derechos reservados.</p>
        </div>
    </div>

    <script>
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');

        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = '/login';
            }
        }, 1000);
    </script>
</body>

</html>
