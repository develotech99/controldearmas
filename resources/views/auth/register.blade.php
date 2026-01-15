<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-slate-100 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="mx-auto w-16 h-16 bg-slate-800 rounded-xl flex items-center justify-center mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Registro de Empleado</h1>
                <p class="text-slate-600">Crear nueva cuenta en el sistema</p>
            </div>

            <!-- Register Form -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
                <form method="POST" action="{{ route('register') }}" class="space-y-6">
                    @csrf

                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-2">
                            Nombre Completo
                        </label>
                        <input id="name" 
                               type="text" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               autofocus 
                               autocomplete="name"
                               placeholder="Ingresa tu nombre completo"
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white text-slate-900 placeholder-slate-400">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">
                            Correo Electrónico
                        </label>
                        <input id="email" 
                               type="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autocomplete="username"
                               placeholder="empleado@controldearmas.com"
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white text-slate-900 placeholder-slate-400">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">
                            Contraseña
                        </label>
                        <input id="password" 
                               type="password" 
                               name="password" 
                               required 
                               autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres"
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white text-slate-900 placeholder-slate-400">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">
                            Confirmar Contraseña
                        </label>
                        <input id="password_confirmation" 
                               type="password" 
                               name="password_confirmation" 
                               required 
                               autocomplete="new-password"
                               placeholder="Repite tu contraseña"
                               class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white text-slate-900 placeholder-slate-400">
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            class="w-full bg-slate-800 text-white py-3 px-4 rounded-lg font-medium hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors duration-200">
                        Crear Cuenta
                    </button>
                </form>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-sm text-slate-600">
                    ¿Ya tienes cuenta? 
                    <a href="{{ route('login') }}" class="font-medium text-slate-800 hover:text-slate-600 transition-colors">
                        Iniciar sesión
                    </a>
                </p>
            </div>

            <!-- Back to Home -->
            <div class="text-center">
                <a href="{{ url('/') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>