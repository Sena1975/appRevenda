{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Painel de Revenda') }}</title>

    {{-- Alpine (se já incluir em outro lugar, pode remover) --}}
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Tailwind/Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="bg-gray-100 min-h-screen antialiased"
    x-data="{
        /* MENU LATERAL */
        sidebarOpen:false,

        /* ANIVERSARIANTES */
        aniversOpen:false,
        mesSel:(new Date().getMonth()+1),
        lista:[],
        carregando:false,
        async carregarAniversariantes(){
            this.carregando = true;
            try{
                const r = await fetch(`/aniversariantes/${this.mesSel}/json`);
                const j = await r.json();
                this.lista = j.data ?? [];
            }catch(e){
                this.lista = [];
            }finally{
                this.carregando = false;
            }
        }
    }"
    @keydown.window.escape="sidebarOpen=false; aniversOpen=false"
>

    {{-- MOBILE: Overlay + Drawer da Sidebar --}}
    <div class="relative z-40 lg:hidden" x-show="sidebarOpen" x-cloak>
        <div class="fixed inset-0 bg-gray-900/50" @click="sidebarOpen=false" x-show="sidebarOpen" x-transition.opacity></div>

        <div class="fixed inset-y-0 left-0 max-w-full flex">
            <div class="w-72 bg-white shadow-xl"
                 x-show="sidebarOpen"
                 x-transition:enter="transform transition ease-out duration-200"
                 x-transition:enter-start="-translate-x-full"
                 x-transition:enter-end="translate-x-0"
                 x-transition:leave="transform transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0"
                 x-transition:leave-end="-translate-x-full">
                @includeIf('layouts.partials.sidebar')
            </div>
        </div>
    </div>

    {{-- DESKTOP: Sidebar fixa --}}
    <div class="hidden lg:fixed lg:inset-y-0 lg:z-30 lg:flex lg:w-64 lg:flex-col lg:border-r lg:bg-white">
        @includeIf('layouts.partials.sidebar')
    </div>

    {{-- ÁREA PRINCIPAL (deslocada no desktop) --}}
    <div class="lg:pl-64 flex flex-col min-h-screen">

        {{-- TOPBAR --}}
        <header class="sticky top-0 z-20 bg-white border-b">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    {{-- Botão hamburguer (mobile) --}}
                    <button type="button"
                            class="lg:hidden inline-flex items-center justify-center rounded-md p-2 border border-gray-200 hover:bg-gray-50 focus:outline-none"
                            @click="sidebarOpen = true" aria-label="Abrir menu">
                        <svg class="h-5 w-5 text-gray-700" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z"/>
                        </svg>
                    </button>

                    {{-- Header: compatível com slot OU section --}}
                    <div class="truncate">
                        @hasSection('header')
                            @yield('header')
                        @else
                            {{ $header ?? '' }}
                        @endif
                    </div>

                    {{-- Botão Aniversariantes --}}
                    <button
                        type="button"
                        class="ml-2 inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50"
                        @click="aniversOpen = true; carregarAniversariantes()"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2a2 2 0 00-2 2c0 1.5 2 3 2 3s2-1.5 2-3a2 2 0 00-2-2zm7 10H5a3 3 0 00-3 3v5h20v-5a3 3 0 00-3-3zM3 18h18v2H3v-2z"/>
                        </svg>
                        Aniversariantes
                    </button>
                </div>

                {{-- À direita: topbar customizada, se existir --}}
                @includeIf('layouts.partials.topbar')
            </div>
        </header>

        {{-- PAINEL ANIVERSARIANTES (off-canvas lateral do conteúdo) --}}
        <div class="relative z-30" x-cloak x-show="aniversOpen">
            {{-- overlay que respeita a sidebar fixa no desktop --}}
            <div class="fixed inset-0 lg:pl-64 bg-gray-900/40" @click="aniversOpen=false" x-transition.opacity></div>

            <div class="fixed inset-y-0 left-0 lg:left-64 flex">
                <div class="w-[22rem] bg-white shadow-xl flex flex-col"
                     x-show="aniversOpen"
                     x-transition:enter="transform transition ease-out duration-200"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transform transition ease-in duration-200"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full">

                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold">Aniversariantes</span>
                            <select class="border rounded-md text-sm px-2 py-1"
                                    x-model.number="mesSel"
                                    @change="carregarAniversariantes()">
                                <template x-for="m in 12" :key="m">
                                    <option :value="m" x-text="new Date(2000,m-1,1).toLocaleString('pt-BR',{month:'long'})"></option>
                                </template>
                            </select>
                        </div>
                        <button class="rounded-md p-1 hover:bg-gray-100" @click="aniversOpen=false" aria-label="Fechar">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.3 5.71L12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.29 19.7 2.88 18.29 9.17 12 2.88 5.71 4.29 4.3l6.3 6.29 6.29-6.3z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="p-3">
                        <div class="relative">
                            <input type="text" placeholder="Filtrar por nome..."
                                   class="w-full border rounded-md px-3 py-2 text-sm"
                                   @input="
                                     const q = $event.target.value.toLowerCase();
                                     document.querySelectorAll('.aniv-item').forEach(el=>{
                                        const nome = el.getAttribute('data-nome');
                                        el.style.display = nome.includes(q) ? '' : 'none';
                                     });
                                   ">
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <template x-if="carregando">
                            <div class="p-4 text-sm text-gray-500">Carregando...</div>
                        </template>

                        <template x-if="!carregando && lista.length===0">
                            <div class="p-4 text-sm text-gray-500">Nenhum aniversariante neste mês.</div>
                        </template>

                        <ul class="divide-y">
                            <template x-for="p in lista" :key="p.id">
                                <li class="aniv-item px-4 py-3 flex items-center gap-3" :data-nome="(p.nome||'').toLowerCase()">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center font-semibold text-blue-700" x-text="p.iniciais"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" x-text="p.nome"></p>
                                        <p class="text-xs text-gray-500">
                                            <span x-text="p.dia_mes"></span> •
                                            <span x-text="p.idade + ' anos'"></span>
                                        </p>
                                        <p class="text-xs text-gray-500" x-text="p.telefone"></p>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- CONTEÚDO: $slot (component) OU @section('content') --}}
        <main class="flex-1">
            <div class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    @isset($slot)
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endisset
                </div>
            </div>
        </main>

        {{-- <footer class="py-4 text-center text-xs text-gray-400">© {{ date('Y') }} - appRevenda</footer> --}}
    </div>
</body>
</html>
