<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Información del Perfil
        </h2>

        <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">
            Actualiza la información de tu cuenta y dirección de correo electrónico.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                Nombre Completo
            </label>
            <input
                id="name"
                name="name"
                type="text"
                class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                value="{{ old('name', $user->name) }}"
                required
                autofocus
                autocomplete="name"
                placeholder="Ingresa tu nombre completo"
            />
            @error('name')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                Correo Electrónico
            </label>
            <input
                id="email"
                name="email"
                type="email"
                class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
                placeholder="empleado@proarmas.com"
            />
            @error('email')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-start space-x-3">
                        <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">
                                Tu dirección de correo electrónico no está verificada.
                            </p>
                            <button 
                                form="send-verification" 
                                type="submit"
                                class="mt-2 text-sm text-yellow-700 hover:text-yellow-600 underline font-medium"
                            >
                                Haz clic aquí para reenviar el correo de verificación.
                            </button>
                        </div>
                    </div>

                    @if (session('status') === 'verification-link-sent')
                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm font-medium text-green-800">
                                Se ha enviado un nuevo enlace de verificación a tu dirección de correo electrónico.
                            </p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- User Role Info -->
        <div class="bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-slate-600 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-slate-800 dark:text-white">Empleado del Sistema</p>
                    <p class="text-xs text-slate-600 dark:text-gray-400 mt-1">
                        Tienes acceso al sistema de inventario de ProArmas y Municiones
                    </p>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center space-x-4">
            <button
                type="submit"
                class="bg-slate-800 dark:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-slate-700 dark:hover:bg-gray-500 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors duration-200"
            >
                Guardar Cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm font-medium text-green-600"
                >
                    Perfil actualizado correctamente
                </p>
            @endif
        </div>
    </form>
</section>