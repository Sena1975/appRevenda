<x-guest-layout>
    <div class="w-full max-w-sm sm:max-w-md mx-auto">
        <div class="bg-white/90 backdrop-blur rounded-xl shadow-xl p-6 md:p-7 border border-slate-200">
            {{-- Logo + Título --}}
            <div class="text-center mb-6">
                <img src="{{ asset('images/logo-1024.png') }}" alt="Logo"
                     class="h-48 w-48 rounded-full mx-auto shadow-md object-contain">
                <h1 class="mt-3 text-xl font-semibold text-slate-800">appRevenda</h1>
                <p class="text-slate-500 text-sm">Use suas credenciais para continuar</p>
            </div>

            {{-- Status de sessão --}}
            <x-auth-session-status class="mb-3" :status="session('status')" />

            {{-- Erros gerais --}}
            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-red-700 text-sm">
                    {{ __('Verifique suas credenciais e tente novamente.') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" id="formLogin">
                @csrf

  {{-- E-mail --}}
<div class="mb-4">
  <x-input-label for="email" :value="__('E-mail')" />

  <div class="mt-1 flex items-center rounded-lg overflow-hidden
              border border-slate-300 bg-white
              focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
    {{-- ícone à esquerda (fora do input) --}}
    <div class="h-11 w-11 shrink-0 grid place-items-center bg-slate-50 border-r border-slate-300">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M21.75 7.5v9a2.25 2.25 0 0 1-2.25 2.25H4.5A2.25 2.25 0 0 1 2.25 16.5v-9A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5zm0 0L12 13.5 2.25 7.5" />
      </svg>
    </div>

    {{-- input (sem borda própria) --}}
    <x-text-input id="email" type="email" name="email"
                  :value="old('email')" required autocomplete="username"
                  class="h-11 flex-1 border-0 px-3 focus:ring-0 focus:outline-none" />
  </div>

  <x-input-error :messages="$errors->get('email')" class="mt-1" />
</div>

{{-- Senha --}}
<div class="mb-1">
  <x-input-label for="password" :value="__('Senha')" />

  <div class="mt-1 flex items-center rounded-lg overflow-hidden
              border border-slate-300 bg-white
              focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500">
    {{-- ícone cadeado à esquerda --}}
    <div class="h-11 w-11 shrink-0 grid place-items-center bg-slate-50 border-r border-slate-300">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M16.5 10.5V7.5a4.5 4.5 0 1 0-9 0v3m-.75 0h10.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-6a1.5 1.5 0 0 1 1.5-1.5z" />
      </svg>
    </div>

    {{-- input --}}
    <x-text-input id="password" type="password" name="password"
                  required autocomplete="current-password"
                  class="h-11 flex-1 border-0 px-3 focus:ring-0 focus:outline-none" />

    {{-- olho à direita (fora do input) --}}
    <button type="button" id="btnTogglePassword"
            class="h-11 w-11 grid place-items-center border-l border-slate-300 text-slate-500 hover:text-slate-700"
            aria-label="Mostrar/ocultar senha">
      <svg id="iconEye" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 110-10 5 5 0 010 10z"/>
      </svg>
      <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 24 24" fill="currentColor">
        <path d="M2.3 1.2l20.5 20.5-1.1 1.1-3-3A13.1 13.1 0 0112 19c-7 0-10-7-10-7a20.8 20.8 0 014.6-5.7L1.2 2.3 2.3 1.2zM12 7a5 5 0 013.9 8.1l-1.5-1.5A3 3 0 0012 9a2.9 2.9 0 00-1.6.5L8.8 7.9A5 5 0 0112 7z"/>
      </svg>
    </button>
  </div>

  <x-input-error :messages="$errors->get('password')" class="mt-1" />
</div>


  <x-input-error :messages="$errors->get('password')" class="mt-1" />
</div>


                {{-- Aviso Caps Lock --}}
                <div id="capsWarning" class="hidden text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-2 py-1 mb-3">
                    Caps Lock ligado
                </div>

                {{-- Lembrar-me + Esqueci senha --}}
                <div class="mt-3 flex items-center justify-between">
                    <label for="remember_me" class="inline-flex items-center select-none">
                        <input id="remember_me" type="checkbox"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               name="remember">
                        <span class="ml-2 text-sm text-gray-600">{{ __('Lembrar-me') }}</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm text-indigo-600 hover:text-indigo-700" href="{{ route('password.request') }}">
                            {{ __('Esqueci minha senha') }}
                        </a>
                    @endif
                </div>

                {{-- Botão --}}
                <div class="mt-5">
                    <x-primary-button class="w-full justify-center">
                        {{ __('Entrar') }}
                    </x-primary-button>
                </div>
            </form>

            <p class="mt-5 text-center text-xs text-slate-500">
                © {{ date('Y') }} {{ config('app.name', 'Painel de Revenda') }}
            </p>
        </div>
    </div>

    {{-- Script: toggle senha + caps lock --}}
    <script>
        (function () {
            const pwd  = document.getElementById('password');
            const btn  = document.getElementById('btnTogglePassword');
            const eye  = document.getElementById('iconEye');
            const off  = document.getElementById('iconEyeOff');
            const caps = document.getElementById('capsWarning');

            btn?.addEventListener('click', () => {
                const isPwd = pwd.type === 'password';
                pwd.type = isPwd ? 'text' : 'password';
                eye.classList.toggle('hidden', !isPwd);
                off.classList.toggle('hidden',  isPwd);
                pwd.focus();
            });

            pwd?.addEventListener('keyup', (e) => {
                if ('getModifierState' in e) {
                    caps.classList.toggle('hidden', !e.getModifierState('CapsLock'));
                }
            });
        })();
    </script>
</x-guest-layout>
