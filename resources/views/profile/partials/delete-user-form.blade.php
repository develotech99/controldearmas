<section class="space-y-6">
    <header>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
            Eliminar Cuenta
        </h2>

        <p class="mt-2 text-sm text-slate-600 dark:text-gray-400 leading-relaxed">
            Una vez que se elimine tu cuenta, todos los recursos y datos se eliminarán permanentemente. 
            Antes de eliminar tu cuenta, descarga cualquier dato o información que desees conservar.
        </p>
    </header>

    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <p class="text-sm font-medium text-red-800 dark:text-red-300">Acción Irreversible</p>
                <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                    Esta acción no se puede deshacer. Todos tus datos se perderán permanentemente.
                </p>
            </div>
        </div>
    </div>

    <button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
    >
        Eliminar Cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8">
            @csrf
            @method('delete')

            <div class="flex items-center space-x-4 mb-6">
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-slate-900 dark:text-white">
                        ¿Confirmar eliminación de cuenta?
                    </h2>
                    <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                        Esta acción es irreversible
                    </p>
                </div>
            </div>

            <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                <p class="text-sm text-red-800 dark:text-red-300 leading-relaxed">
                    Una vez que se elimine tu cuenta, todos los recursos y datos se eliminarán permanentemente. 
                    Por favor, ingresa tu contraseña para confirmar que deseas eliminar permanentemente tu cuenta.
                </p>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-2">
                    Confirma tu contraseña
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full px-4 py-3 border border-slate-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-colors bg-white dark:bg-gray-700 text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-gray-400"
                    placeholder="Ingresa tu contraseña actual"
                />

                @error('password', 'userDeletion')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center justify-end space-x-4">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="bg-slate-100 dark:bg-gray-600 text-slate-700 dark:text-white px-6 py-3 rounded-lg font-medium hover:bg-slate-200 dark:hover:bg-gray-500 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="bg-red-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
                >
                    Eliminar Cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>