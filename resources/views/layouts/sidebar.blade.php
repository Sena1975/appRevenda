<div class="flex flex-col w-64 bg-white border-r shadow-sm">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-10">
    </div>

    <!-- Menu -->
    <nav class="flex-1 px-2 py-4 text-sm font-medium text-gray-700">

        <!-- Início -->
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-4 py-2 rounded hover:bg-blue-50 {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8m0-8l-2 2m0 0l-7 7h18" />
            </svg>
            Início
        </a>

        <!-- CADASTRO -->
        <div x-data="{ open: false }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-4 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Cadastro
                <svg :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="pl-8 mt-1 space-y-1">
                @php
                    $cadastros = [
                        ['Clientes', 'clientes.index'],
                        ['Revendedoras', 'revendedoras.index'],
                        ['Equipe Revenda', 'equipes.index'],
                        ['Supervisores', 'supervisores.index'],
                        ['Categorias', 'categorias.index'],
                        ['Subcategorias', 'subcategorias.index'],
                        ['Fornecedores', 'fornecedores.index'],
                        ['Produtos', 'produtos.index'],
                        ['Tabela de Preço', 'tabelapreco.index'],
                    ];
                @endphp

                @foreach($cadastros as [$titulo, $rota])
                    @if(Route::has($rota))
                        <a href="{{ route($rota) }}"
                           class="block px-2 py-1 rounded hover:bg-blue-100 {{ request()->routeIs(Str::before($rota, '.').'.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                            {{ $titulo }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- PEDIDO -->
        <div x-data="{ open: false }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-4 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3z" />
                </svg>
                Pedido
                <svg :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="pl-8 mt-1 space-y-1">
                @php
                    $pedidos = [
                        ['Compra', 'compras.index'],
                        ['Venda', 'vendas.index'],
                    ];
                @endphp

                @foreach($pedidos as [$titulo, $rota])
                    @if(Route::has($rota))
                        <a href="{{ route($rota) }}"
                           class="block px-2 py-1 rounded hover:bg-blue-100 {{ request()->routeIs(Str::before($rota, '.').'.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                            {{ $titulo }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        <!-- CONSULTA -->
        <div x-data="{ open: false }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-4 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM19 19l-3.5-3.5" />
                </svg>
                Consulta
                <svg :class="{'rotate-180': open}" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="pl-8 mt-1 space-y-1">
                @php
                    $consultas = [
                        ['Aniversariantes', 'aniversariantes.index'],
                        ['Estoque', 'estoque.index'],
                        ['Revistas', 'revistas.index'],
                        ['Extrato Pontuação', 'pontuacoes.index'],
                        ['Contas a Pagar', 'contaspagar.index'],
                        ['Contas a Receber', 'contasreceber.index'],
                        ['Vendas', 'consultavendas.index'],
                        ['Compras', 'consultacompras.index'],
                    ];
                @endphp

                @foreach($consultas as [$titulo, $rota])
                    @if(Route::has($rota))
                        <a href="{{ route($rota) }}"
                           class="block px-2 py-1 rounded hover:bg-blue-100 {{ request()->routeIs(Str::before($rota, '.').'.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                            {{ $titulo }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </nav>
</div>
