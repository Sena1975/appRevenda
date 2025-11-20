{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Painel Administrativo
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        {{-- GRID DE CARDS (RESPONSIVO) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Clientes --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Clientes</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            {{ $totClientes ?? 0 }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-blue-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zM8 11c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.67 0-8 1.34-8 4v2h10v-2c0-.74.2-1.43.55-2.06A9.08 9.08 0 008 13zm8 0c-.29 0-.61.02-.95.05 1.16.84 1.95 1.95 1.95 3.45v2h7v-2c0-2.66-5.33-4.5-8-4.5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Produtos com estoque --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Produtos com estoque</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            {{ $totProdutosEstoque ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Considera apenas produtos com quantidade &gt; 0
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-green-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 7H3L2 9h20l-1-2zM3 11h18v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9zM16 4V2H8v2H3v2h18V4h-5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Valor do estoque --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Valor do estoque</p>
                        <p class="text-3xl font-bold text-emerald-700 mt-1">
                            R$ {{ number_format(($valorEstoque ?? 0), 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Soma de (estoque x preço)
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-emerald-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1zm1 17.93V19h-2v-.07A6.002 6.002 0 016 13h2a4 4 0 007.874-1H18a6.002 6.002 0 01-5 5.93zM11 5v.07A6.002 6.002 0 016 11h2a4 4 0 017.874 1H18a6.002 6.002 0 01-5-5.93V5h-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Contas a Receber em aberto --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Títulos em Aberto</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            {{ $crEmAberto ?? 0 }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Valor em aberto:
                            <span class="font-semibold text-gray-700">
                                R$ {{ number_format(($valorAberto ?? 0), 2, ',', '.') }}
                            </span>
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-amber-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-amber-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm1 16h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- VENDAS DO MÊS --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Vendas no mês</p>
                        <p class="text-3xl font-bold text-emerald-700 mt-1">
                            R$ {{ number_format(($faturamentoMes ?? 0), 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Pedidos: <span class="font-semibold">{{ $totVendasMes ?? 0 }}</span>
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-emerald-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-emerald-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M5 4h14l-1 14H6L5 4zm4 16a3 3 0 006 0H9z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- COMPRAS DO MÊS --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Compras no mês</p>
                        <p class="text-3xl font-bold text-red-700 mt-1">
                            R$ {{ number_format(($valorComprasMes ?? 0), 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Pedidos de compra: <span class="font-semibold">{{ $totComprasMes ?? 0 }}</span>
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 4h10l1 4H6l1-4zm-2 6h14l-1.5 9h-11L5 10z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- VENDAS PENDENTES --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Vendas Pendentes</p>
                        <p class="text-3xl font-bold text-purple-700 mt-1">
                            R$ {{ number_format(($valorVendasPendentes ?? 0), 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Pedidos pendentes:
                            <span class="font-semibold">
                                {{ $totVendasPendentes ?? 0 }}
                            </span>
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-purple-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-purple-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1a11 11 0 100 22 11 11 0 000-22zm1 6v5.27l3.15 3.16-1.42 1.41L11 13V7h2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- COMPRAS PENDENTES --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Compras Pendentes</p>
                        <p class="text-3xl font-bold text-orange-700 mt-1">
                            R$ {{ number_format(($valorComprasPendentes ?? 0), 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Pedidos pendentes:
                            <span class="font-semibold">
                                {{ $totComprasPendentes ?? 0 }}
                            </span>
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-orange-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2 2 7v6c0 5.25 3.5 10.06 9 11 5.5-.94 9-5.75 9-11V7l-8-5zm0 4 5 2.5v1L12 7 7 9.5v-1L12 6zm0 4c2.21 0 4 1.79 4 4s-1.79 4-4 4-4-1.79-4-4 1.79-4 4-4z"/>
                        </svg>
                    </div>
                </div>
            </div>

        </div>

        {{-- VISÃO RÁPIDA: últimas vendas e compras --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Últimas vendas --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    🧾 Últimas vendas
                </h3>
                <p class="text-sm text-gray-500 mt-2">
                    Pedidos mais recentes cadastrados no sistema.
                </p>

                @if(($ultimasVendas ?? collect())->isEmpty())
                    <p class="text-sm text-gray-500 mt-4">Nenhuma venda cadastrada.</p>
                @else
                    <table class="w-full text-xs sm:text-sm mt-3">
                        <thead class="text-gray-500 border-b">
                            <tr>
                                <th class="py-1 text-left">Data</th>
                                <th class="py-1 text-right">Valor líquido</th>
                                <th class="py-1 text-right">Pedido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ultimasVendas as $venda)
                                <tr class="border-b last:border-0">
                                    <td class="py-1">
                                        {{ \Carbon\Carbon::parse($venda->data_pedido)->format('d/m/Y') }}
                                    </td>
                                    <td class="py-1 text-right text-emerald-700">
                                        R$ {{ number_format(($venda->valor_liquido ?? 0), 2, ',', '.') }}
                                    </td>
                                    <td class="py-1 text-right text-gray-500">
                                        #{{ $venda->id }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- Últimas compras --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                    📦 Últimas compras
                </h3>
                <p class="text-sm text-gray-500 mt-2">
                    Pedidos de compra mais recentes.
                </p>

                @if(($ultimasCompras ?? collect())->isEmpty())
                    <p class="text-sm text-gray-500 mt-4">Nenhuma compra cadastrada.</p>
                @else
                    <table class="w-full text-xs sm:text-sm mt-3">
                        <thead class="text-gray-500 border-b">
                            <tr>
                                <th class="py-1 text-left">Data</th>
                                <th class="py-1 text-right">Valor total</th>
                                <th class="py-1 text-right">Compra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ultimasCompras as $compra)
                                <tr class="border-b last:border-0">
                                    <td class="py-1">
                                        {{ \Carbon\Carbon::parse($compra->data_compra)->format('d/m/Y') }}
                                    </td>
                                    <td class="py-1 text-right text-red-700">
                                        R$ {{ number_format(($compra->valor_total ?? 0), 2, ',', '.') }}
                                    </td>
                                    <td class="py-1 text-right text-gray-500">
                                        #{{ $compra->id }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
