@php
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

    // Contadores dinâmicos (opcional)
    $qtdPedidosPendentes = DB::table('apppedidovenda')->where('status', 'PENDENTE')->count();
    $qtdContasAbertas    = DB::table('appcontasreceber')->where('status', 'ABERTO')->count();

    // Helpers para saber se a rota atual pertence a um grupo
    $isCadastro   = request()->routeIs(
        'clientes.*',
        'revendedoras.*',
        'equiperevenda.*',
        'supervisores.*',
        'fornecedores.*',
        'categorias.*',
        'subcategorias.*',
        'produtos.*',
        'tabelapreco.*',
    );
    $isPedidos    = request()->routeIs('compras.*', 'vendas.*');
    $isEstoque    = request()->routeIs('estoque.*', 'movestoque.*');
    $isFinanceiro = request()->routeIs('formapagamento.*', 'planopagamento.*', 'contasreceber.*');
    $isCampanhas  = request()->routeIs('campanhas.*', 'campanhas.restricoes*', 'participacoes.*');
@endphp

<div x-data="{ openSidebar: true }"
     class="flex flex-col h-full border-r bg-white shadow-sm transition-all duration-300"
     :class="openSidebar ? 'w-64' : 'w-20'">

    <!-- Topo com logo e botão -->
    <div class="h-16 flex items-center justify-between px-4 border-b">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <template x-if="openSidebar">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-10 w-auto"
                     onerror="this.style.display='none'">
            </template>
            <span x-show="openSidebar" class="font-semibold text-gray-800">
                {{ config('app.name', 'appRevenda') }}
            </span>
        </a>

        <button @click="openSidebar = !openSidebar" class="p-2 rounded hover:bg-blue-100" title="Recolher/Expandir">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- MENU -->
    <nav class="flex-1 overflow-y-auto px-2 py-4 text-sm font-medium text-gray-700">

        <!-- INÍCIO -->
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-3 py-2 rounded hover:bg-blue-50 transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-700' }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7m-9 2v8" />
            </svg>
            <span x-show="openSidebar" class="ml-3">Início</span>
        </a>

        <!-- CADASTROS -->
        <div x-data="{ open: {{ $isCadastro ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-3 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none rounded transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span x-show="openSidebar" class="ml-3 flex-1 text-left">Cadastros</span>
                <svg x-show="openSidebar" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 pl-8">
                @php
                    $cadastros = [
                        ['Clientes', 'clientes.index', '<path d="M16 14a4 4 0 10-8 0 4 4 0 008 0zM12 14v7m-6-3h12" />'],
                        ['Revendedoras', 'revendedoras.index', '<path d="M15 10l4.55 9H4.45L9 10V5a3 3 0 116 0v5z" />'],
                        [
                            'Equipe de Revenda',
                            'equiperevenda.index',
                            '<path d="M17 20h5v-2a4 4 0 00-3-3.87V10a5 5 0 00-10 0v4.13A4 4 0 006 18v2h5" />',
                        ],
                        [
                            'Supervisores',
                            'supervisores.index',
                            '<path d="M12 12c2.28 0 4.28-1.72 4.75-4.02M12 12c-2.28 0-4.28-1.72-4.75-4.02M12 12v8m8-8a8 8 0 11-16 0" />',
                        ],
                        ['Fornecedores', 'fornecedores.index', '<path d="M3 3h18v18H3V3z" />'],
                        ['Categorias', 'categorias.index', '<path d="M4 6h16M4 10h16M4 14h16M4 18h16" />'],
                        ['Subcategorias', 'subcategororias.index', '<path d="M4 6h16M4 12h8m-8 6h16" />'],
                        ['Produtos', 'produtos.index', '<path d="M3 3h18v18H3V3zM7 3v18M17 3v18" />'],
                        ['Tabela de Preço', 'tabelapreco.index', '<path d="M4 6h16M4 10h16M4 14h16M4 18h16" />'],
                    ];
                @endphp

                @foreach ($cadastros as [$titulo, $rota, $svg])
                    @if (Route::has($rota))
                        <a href="{{ route($rota) }}"
                           class="flex items-center px-2 py-1 rounded hover:bg-blue-100 transition-all duration-150 {{ request()->routeIs(Str::before($rota, '.') . '.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">{!! $svg !!}</svg>
                            <span x-show="openSidebar" class="ml-2">{{ $titulo }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- PEDIDOS --}}
        @php
            $pedidosAbertosCompra = \App\Models\PedidoCompra::where('status', 'ABERTO')->count();
            $qtdPendentesVenda   = \App\Models\PedidoVenda::whereIn('status', ['PENDENTE', 'ABERTO'])->count();
        @endphp

        <div x-data="{ open: {{ $isPedidos ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-3 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none rounded transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h18l-2 13H5L3 3z" />
                </svg>
                <span x-show="openSidebar" class="ml-3 flex-1 text-left">Pedidos</span>
                <svg x-show="openSidebar" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 pl-8">

                {{-- Pedido de Compra --}}
                <div class="flex items-center justify-between px-2 py-1 rounded hover:bg-blue-100 transition-all">
                    <a href="{{ route('compras.index') }}"
                       class="flex items-center {{ request()->routeIs('compras.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 3h18l-2 13H5L3 3z" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Pedido de Compra</span>
                    </a>
                    @if ($pedidosAbertosCompra > 0)
                        <span x-show="openSidebar"
                              class="text-[11px] px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-700">
                            {{ $pedidosAbertosCompra }}
                        </span>
                    @endif
                </div>

                {{-- Pedido de Venda --}}
                <div class="flex w-full items-center justify-between px-2 py-1 rounded hover:bg-blue-100 transition-all">
                    <a href="{{ route('vendas.index') }}"
                       class="flex min-w-0 items-center {{ request()->routeIs('vendas.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v8m-4-4h8" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2 truncate">Pedido de Venda</span>
                    </a>
                </div>
                <div class="flex items-center">
                    @if ($qtdPendentesVenda > 0)
                        <a x-show="openSidebar" href="{{ route('vendas.index', ['status' => 'PENDENTES']) }}"
                           class="ml-2 shrink-0 whitespace-nowrap text-[11px] px-2 py-0.5 rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200"
                           title="Ver pedidos pendentes (PENDENTE + ABERTO)">
                            Pendentes
                            <span
                                class="ml-1 align-middle text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                {{ $qtdPendentesVenda }}
                            </span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- ESTOQUE -->
        <div x-data="{ open: {{ $isEstoque ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-3 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none rounded transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7l9-4 9 4v10l-9 4-9-4V7z" />
                </svg>
                <span x-show="openSidebar" class="ml-3 flex-1 text-left">Estoque</span>
                <svg x-show="openSidebar" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 pl-8">
                <a href="{{ route('estoque.index') }}"
                   class="flex items-center px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('estoque.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7h18M3 12h18M3 17h18" />
                    </svg>
                    <span x-show="openSidebar" class="ml-2">Estoque</span>
                </a>

                <a href="{{ route('movestoque.index') }}"
                   class="flex items-center px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('movestoque.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 4v5h.582a2 2 0 011.789 1.106L9 14l2-4 2 6 1-3h6" />
                    </svg>
                    <span x-show="openSidebar" class="ml-2">Movimentações</span>
                </a>
            </div>
        </div>

        <!-- FINANCEIRO -->
        <div x-data="{ open: {{ $isFinanceiro ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-3 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none rounded transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a5 5 0 00-10 0v2H5v12h14V9h-2z" />
                </svg>
                <span x-show="openSidebar" class="ml-3 flex-1 text-left">Financeiro</span>
                <svg x-show="openSidebar" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 pl-8">
                @php
                    $qtdFormas = class_exists(\App\Models\FormaPagamento::class)
                        ? \App\Models\FormaPagamento::count()
                        : 0;
                    $qtdPlanos = class_exists(\App\Models\PlanoPagamento::class)
                        ? \App\Models\PlanoPagamento::count()
                        : 0;
                    $qtdReceber = class_exists(\App\Models\ContasReceber::class)
                        ? \App\Models\ContasReceber::where('status', 'ABERTO')->count()
                        : 0;
                @endphp

                <a href="{{ route('formapagamento.index') }}"
                   class="flex items-center justify-between px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('formapagamento.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v8m-4-4h8" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Formas de Pagamento</span>
                    </div>
                    @if ($qtdFormas > 0)
                        <span x-show="openSidebar"
                              class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">{{ $qtdFormas }}</span>
                    @endif
                </a>

                <a href="{{ route('planopagamento.index') }}"
                   class="flex items-center justify-between px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('planopagamento.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7h18M3 12h18M3 17h18" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Planos de Pagamento</span>
                    </div>
                    @if ($qtdPlanos > 0)
                        <span x-show="openSidebar"
                              class="text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">{{ $qtdPlanos }}</span>
                    @endif
                </a>

                <a href="{{ route('contasreceber.index') }}"
                   class="flex items-center justify-between px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('contasreceber.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                    <div class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v8m-4-4h8" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Contas a Receber</span>
                    </div>
                    @if ($qtdReceber > 0)
                        <span x-show="openSidebar"
                              class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">{{ $qtdReceber }}</span>
                    @endif
                </a>
            </div>
        </div>

        <!-- CAMPANHAS -->
        <div x-data="{ open: {{ $isCampanhas ? 'true' : 'false' }} }" class="mt-2">
            <button @click="open = !open"
                    class="flex items-center w-full px-3 py-2 text-blue-600 hover:bg-blue-50 focus:outline-none rounded transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                </svg>
                <span x-show="openSidebar" class="ml-3 flex-1 text-left">Campanhas</span>
                <svg x-show="openSidebar" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg"
                     class="h-4 w-4 ml-auto transform transition-transform" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" x-collapse class="mt-1 space-y-1 pl-8">
                @if (Route::has('campanhas.index'))
                    <a href="{{ route('campanhas.index') }}"
                       class="flex items-center px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('campanhas.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h16" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Gerenciar Campanhas</span>
                    </a>
                @endif

                @if (Route::has('participacoes.index'))
                    <a href="{{ route('participacoes.index') }}"
                       class="flex items-center px-2 py-1 rounded hover:bg-blue-100 transition-all {{ request()->routeIs('participacoes.*') ? 'text-blue-700 font-semibold' : 'text-gray-600' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-500" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3" />
                        </svg>
                        <span x-show="openSidebar" class="ml-2">Participações</span>
                    </a>
                @endif
            </div>
        </div>
    </nav>

    <!-- RODAPÉ: Sair -->
    <div class="mt-auto border-t px-4 py-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center text-sm text-gray-600 hover:text-red-600 transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h6a2 2 0 012 2v1" />
                </svg>
                Sair
            </button>
        </form>
    </div>
</div>
