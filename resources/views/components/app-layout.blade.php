{{-- resources/views/components/app-layout.blade.php --}}
@props(['header' => null])

<!DOCTYPE html> 
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Painel de Revenda') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar esquerda --}}
        @include('layouts.sidebar')

        {{-- Conteúdo principal + Sidebar direita --}}
        <div class="flex flex-1 flex-col">
            {{-- Topbar --}}
            @include('layouts.topbar')

            <div class="flex flex-1 overflow-hidden">
                {{-- Área de conteúdo --}}
                <main class="flex-1 overflow-y-auto bg-gray-100 p-6">
                    {{-- Header opcional vindo de <x-slot name="header"> --}}
                    @isset($header)
                        <div class="mb-4">
                            {{ $header }}
                        </div>
                    @endisset

                    {{ $slot }}
                </main>

                {{-- Sidebar direita (opcional) --}}
                @include('layouts.sidebar-right')
            </div>
        </div>
    </div>
</body>
</html>
