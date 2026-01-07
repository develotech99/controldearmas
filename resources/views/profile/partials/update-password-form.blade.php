<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Actualizar Contraseña
        </h2>

        <p class="mt-2 text-sm text-slate-600 dark:text-gray-400">
            Asegúrate de que tu cuenta use una contraseña larga y aleatoria para mantenerte seguro.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <!-- Current Password -->
        <div>
            <label for="update_password_current_password" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                Contraseña Actual
            </label>
            <input
                id="update_password_current_password"
                name="current_password"
                type="password"
                class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                autocomplete="current-password"
                placeholder="Ingresa tu contraseña actual"
            />
            @error('current_password', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- New Password -->
        <div>
            <label for="update_password_password" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                Nueva Contraseña
            </label>
            <input
                id="update_password_password"
                name="password"
                type="password"
                class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                autocomplete="new-password"
                placeholder="Ingresa tu nueva contraseña"
            />
            @error('password', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div>
            <label for="update_password_password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                Confirmar Nueva Contraseña
            </label>
            <input
                id="update_password_password_confirmation"
                name="password_confirmation"
                type="password"
                class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                autocomplete="new-password"
                placeholder="Confirma tu nueva contraseña"
            />
            @error('password_confirmation', 'updatePassword')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Security Tips -->
        <div class="bg-slate-50 dark:bg-gray-700 border border-slate-200 dark:border-gray-600 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-slate-600 dark:text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="text-sm font-medium text-slate-800 dark:text-white">Recomendaciones de seguridad:</p>
                    <ul class="text-xs text-slate-600 dark:text-gray-400 mt-1 space-y-1">
                        <li>• Usa al menos 8 caracteres</li>
                        <li>• Incluye números y símbolos</li>
                        <li>• No uses información personal</li>
                        <li>• No compartas tu contraseña</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center space-x-4">
            <button
                type="submit"
                class="bg-slate-800 dark:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-slate-700 dark:hover:bg-gray-500 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors duration-200"
            >
                Guardar Contraseña
            </button>

            @if (session('status') === 'password-updated')
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Contraseña actualizada correctamente',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                </script>
            @endif
        </div>
    </form>
</section>