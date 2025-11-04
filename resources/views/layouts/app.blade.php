<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Painel de Revenda') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 font-sans antialiased min-h-screen">
    {{-- Estado global das sidebars --}}
    <div x-data="{ leftOpen: false, rightOpen: false }" class="flex min-h-screen">

        {{-- SIDEBAR ESQUERDA - desktop visível; mobile off-canvas --}}
        <aside
            class="fixed inset-y-0 left-0 z-40 w-64 transform bg-white border-r transition-transform duration-200 ease-out
                   -translate-x-full lg:translate-x-0 lg:static lg:z-auto"
            :class="{ 'translate-x-0': leftOpen }"
            @keydown.escape.window="leftOpen = false"
        >
            @include('layouts.sidebar')
        </aside>

        {{-- OVERLAY do mobile para sidebar esquerda left--}}
        <div
            class="fixed inset-0 z-30 bg-black/40 lg:hidden"
            x-show="leftOpen"
            x-transition.opacity
            @click="leftOpen = false"
        ></div>

        {{-- CONTEÚDO + SIDEBAR DIREITA rigth--}}
        <div class="flex flex-1 flex-col min-w-0">
            {{-- Topbar (terá o botão para abrir a esquerda) --}}
            @include('layouts.topbar')

            <div class="flex flex-1 min-h-0">
                {{-- MAIN: só aqui tem scroll vertical --}}
                <main class="flex-1 overflow-y-auto">
                    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        @isset($slot)
                            {{ $slot }}
                        @elseif (View::hasSection('content'))
                            @yield('content')
                        @endif
                    </div>
                </main>

                {{-- SIDEBAR DIREITA (opcional). Mostra no xl+, mobile como off-canvas se quiser ligar rightOpen --}}
                <aside
                    class="hidden xl:flex flex-col shrink-0 w-80 bg-white border-l"
                    :class="{ 'fixed inset-y-0 right-0 z-40 flex': rightOpen }"
                    @keydown.escape.window="rightOpen = false"
                >
                    @include('layouts.sidebar-right')
                </aside>
            </div>
        </div>
    </div>
</body>
</html>
