<x-guest-layout>
    <div
        class="relative w-full max-w-md mx-auto
               rounded-2xl bg-white/10 backdrop-blur-xl p-8
               border border-orange-500/50
               shadow-[0_20px_70px_-20px_rgba(255,120,40,0.45),0_40px_120px_-30px_rgba(0,0,0,0.6)]">

        {{-- LOGO PRINCIPAL --}}
        <div class="text-center mb-6">
            <img src="{{ asset('images/controlarmasdev.png') }}" alt="CONTROL DE ARMAS"
                class="mx-auto mb-4 h-36 w-36 rounded-xl bg-black/40 p-2
                        ring-2 ring-orange-500/70
                        shadow-[0_0_55px_rgba(255,115,0,0.55)]">
            <h1 class="text-2xl font-extrabold tracking-tight text-white leading-none">CONTROL DE ARMAS</h1>
            <p class="text-slate-400 text-xs font-medium tracking-wider uppercase mt-1">by Develotech</p>
            <p class="text-slate-300/85 text-sm mt-2">Ingreso al Sistema de Inventario</p>
        </div>

        {{-- Status --}}
        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- FORMULARIO --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-slate-100 mb-2">Correo Electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    autocomplete="username"
                    class="w-full rounded-lg border border-white/25 bg-black/30 px-4 py-3 text-white
                              placeholder-white/60 outline-none transition
                              focus:border-orange-500 focus:ring-2 focus:ring-orange-500/60">
                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
            {{-- Password --}}
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <label for="password" class="block text-sm font-medium text-slate-100">Contraseña</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                            class="text-xs font-medium text-orange-400 hover:text-orange-300">¿Olvidaste tu
                            contraseña?</a>
                    @endif
                </div>

                {{-- Campo con toggle --}}
                <div x-data="{ show: false }" class="relative">
                    <input :type="show ? 'text' : 'password'" id="password" name="password" required
                        autocomplete="current-password"
                        class="w-full rounded-lg border border-white/25 bg-black/30 px-4 py-3 pr-10 text-white
                  placeholder-white/60 outline-none transition
                  focus:border-orange-500 focus:ring-2 focus:ring-orange-500/60">

                    <button type="button" @click="show = !show"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-white focus:outline-none"
                        :aria-pressed="show.toString()" :title="show ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                        aria-label="Alternar visibilidad de contraseña">
                        {{-- ojo abierto --}}
                        <svg x-show="!show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" x-transition.opacity.duration.100>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12z" />
                        </svg>
                        {{-- ojo tachado --}}
                        <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" x-transition.opacity.duration.100>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.61-4.33" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6.228 6.228A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.478 5.568" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 00-4.243-2.829M9.88 9.88A3 3 0 0012 15a3 3 0 002.12-.879" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18" />
                        </svg>
                    </button>
                </div>

                @error('password')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>


            {{-- Remember + Volver --}}
            <div class="flex items-center justify-between">
                <label for="remember_me" class="flex items-center gap-2">
                    <input id="remember_me" type="checkbox" name="remember"
                        class="h-4 w-4 rounded border-white/20 bg-white/10 text-orange-500 focus:ring-orange-500/50">
                    <span class="text-sm text-slate-200">Recordarme</span>
                </label>
            </div>

            {{-- Botón --}}
            <button type="submit"
                class="relative inline-flex w-full items-center justify-center gap-2 rounded-lg
                           bg-gradient-to-r from-orange-600 to-orange-500 px-4 py-3 font-semibold text-black
                           shadow-lg hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-orange-400/70">
                <span class="absolute inset-0 -z-10 rounded-lg bg-orange-500/40 blur-md"></span>
                Ingresar al Sistema
            </button>

            <p class="text-center text-xs text-slate-400 mt-2">
                Acceso restringido. Intentos no autorizados serán registrados.
            </p>
        </form>
    </div>
</x-guest-layout>
