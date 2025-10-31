<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                {{-- Aqui você pode colocar o logo da sua empresa --}}
                <img src="{{ asset('images/logo-1024.png') }}" alt="Logo" class="w-24 h-24 mx-auto">
            </a>
        </x-slot>

        <!-- Mensagem de erro -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if ($errors->any())
            <div class="mb-4 font-medium text-sm text-red-600">
                {{ __('Verifique suas credenciais e tente novamente.') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- E-mail -->
            <div>
                <x-input-label for="email" :value="__('E-mail')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            </div>

            <!-- Senha -->
            <div class="mt-4">
                <x-input-label for="password" :value="__('Senha')" />
                <x-text-input id="password" class="block mt-1 w-full"
                              type="password"
                              name="password"
                              required autocomplete="current-password" />
            </div>

            <!-- Lembrar-me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                    <span class="ml-2 text-sm text-gray-600">{{ __('Lembrar-me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button class="ml-3">
                    {{ __('Entrar') }}
                </x-primary-button>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
