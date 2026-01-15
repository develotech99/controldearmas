<x-guest-layout>
    <div
        class="relative w-full max-w-md mx-auto
               rounded-2xl bg-white/10 backdrop-blur-xl p-8
               border border-orange-500/50
               shadow-[0_20px_70px_-20px_rgba(255,120,40,0.45),0_40px_120px_-30px_rgba(0,0,0,0.6)]">

        {{-- HEADER --}}
        <div class="text-center mb-6">
            <img src="{{ asset('images/controlarmasdev.png') }}" alt="CONTROL DE ARMAS"
                class="mx-auto mb-4 h-20 w-20 rounded-xl bg-black/40 p-2
                        ring-2 ring-orange-500/70
                        shadow-[0_0_55px_rgba(255,115,0,0.55)]">
            <h1 class="text-2xl font-extrabold tracking-tight text-white">
                Restablecer Contraseña
            </h1>
            <p class="text-slate-300/85 text-sm">
                Crea una nueva contraseña segura para tu cuenta
            </p>
        </div>

        {{-- FORM --}}
        <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
            @csrf
            {{-- Token --}}
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            {{-- Email --}}
            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-slate-100">
                    Correo Electrónico
                </label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required
                    autofocus readonly
                    class="w-full rounded-lg border border-white/25 bg-black/30 px-4 py-3 text-slate-300
                              cursor-not-allowed">
                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nueva contraseña --}}
            <div>
                <label for="password" class="mb-2 block text-sm font-medium text-slate-100">
                    Nueva Contraseña
                </label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    class="w-full rounded-lg border border-white/25 bg-black/30 px-4 py-3 text-white
                              placeholder-white/60 outline-none transition
                              focus:border-orange-500 focus:ring-2 focus:ring-orange-500/60">
                @error('password')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmación --}}
            <div>
                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-100">
                    Confirmar Nueva Contraseña
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    autocomplete="new-password" placeholder="Repite tu nueva contraseña"
                    class="w-full rounded-lg border border-white/25 bg-black/30 px-4 py-3 text-white
                              placeholder-white/60 outline-none transition
                              focus:border-orange-500 focus:ring-2 focus:ring-orange-500/60">
                @error('password_confirmation')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notas de seguridad --}}
            <div class="rounded-lg border border-white/10 bg-black/20 p-4">
                <div class="flex items-start gap-3">
                    <svg class="h-5 w-5 text-orange-400 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-slate-200">Recomendaciones de seguridad:</p>
                        <ul class="mt-1 space-y-1 text-xs text-slate-400">
                            <li>• Usa al menos 8 caracteres</li>
                            <li>• Incluye números y símbolos</li>
                            <li>• No compartas tu contraseña</li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Botón --}}
            <button type="submit"
                class="relative inline-flex w-full items-center justify-center gap-2 rounded-lg
                           bg-gradient-to-r from-orange-600 to-orange-500 px-4 py-3 font-semibold text-black
                           shadow-lg hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-orange-400/70">
                <span class="absolute inset-0 -z-10 rounded-lg bg-orange-500/40 blur-md"></span>
                Restablecer Contraseña
            </button>
        </form>

        {{-- Volver --}}
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}"
                class="inline-flex items-center text-sm text-slate-300 hover:text-white transition">
                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al login
            </a>
        </div>
    </div>
</x-guest-layout>
