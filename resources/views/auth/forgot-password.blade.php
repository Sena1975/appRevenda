<x-guest-layout>
    <div class="w-full max-w-sm sm:max-w-md mx-auto">
        <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl p-6 md:p-7 border border-slate-200">

            {{-- Logo + Título --}}
            <div class="text-center mb-6">
                <img src="{{ asset('images/logo-1024.png') }}" alt="Logo"
                     class="h-40 w-40 rounded-full mx-auto shadow-md object-contain">
                <h1 class="mt-3 text-xl font-semibold text-slate-800">Esqueceu sua senha?</h1>
                <p class="text-slate-500 text-sm">
                    Informe seu e-mail e enviaremos um link para redefinir a senha.
                </p>
            </div>

            {{-- Status de sessão (mensagem de link enviado) --}}
            <x-auth-session-status class="mb-3" :status="session('status')" />

            {{-- Erros gerais --}}
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700 text-sm">
                    {{ __('Verifique os dados e tente novamente.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                {{-- E-mail --}}
                <div class="mb-4">
                    <x-input-label for="email" :value="__('E-mail')" />
                    <div class="relative mt-1">
                        {{-- ícone e-mail --}}
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M21.75 7.5v9a2.25 2.25 0 0 1-2.25 2.25H4.5A2.25 2.25 0 0 1 2.25 16.5v-9A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5zm0 0L12 13.5 2.25 7.5" />
                          </svg>
                        </span>

                        <x-text-input
                            id="email"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="username"
                            class="block w-full h-11 pl-12 rounded-lg"
                        />
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                {{-- Botões --}}
                <div class="mt-5 space-y-3">
                    <x-primary-button class="w-full justify-center">
                        {{ __('Enviar link de redefinição') }}
                    </x-primary-button>

                    <a href="{{ route('login') }}"
                       class="block text-center text-sm text-slate-600 hover:text-slate-800">
                        {{ __('Voltar para o login') }}
                    </a>
                </div>
            </form>

            {{-- Rodapé --}}
            <p class="mt-5 text-center text-xs text-slate-500">
                © {{ date('Y') }} {{ config('app.name', 'Painel de Revenda') }}
            </p>
        </div>
    </div>
</x-guest-layout>
