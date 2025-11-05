{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Painel Administrativo
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto">
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
                        {{-- ícone --}}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-blue-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 11c1.657 0 3-1.79 3-4s-1.343-4-3-4-3 1.79-3 4 1.343 4 3 4zM8 11c1.657 0 3-1.79 3-4S9.657 3 8 3 5 4.79 5 7s1.343 4 3 4zm0 2c-2.67 0-8 1.34-8 4v2h10v-2c0-.74.2-1.43.55-2.06A9.08 9.08 0 008 13zm8 0c-.29 0-.61.02-.95.05 1.16.84 1.95 1.95 1.95 3.45v2h7v-2c0-2.66-5.33-4.5-8-4.5z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Revendedoras --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Revendedoras</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            {{ $totRevendedoras ?? 0 }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-pink-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-pink-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.761 0 5-2.686 5-6S14.761 0 12 0 7 2.686 7 6s2.239 6 5 6zm0 2c-3.866 0-12 1.933-12 5.8V24h24v-4.2C24 15.933 15.866 14 12 14z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Produtos --}}
            <div class="bg-white rounded-xl shadow-sm p-5 hover:shadow md:transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Produtos</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">
                            {{ $totProdutos ?? 0 }}
                        </p>
                    </div>
                    <div class="p-3 rounded-full bg-green-50">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M21 7H3L2 9h20l-1-2zM3 11h18v9a2 2 0 01-2 2H5a2 2 0 01-2-2v-9zM16 4V2H8v2H3v2h18V4h-5z"/>
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
        </div>

        {{-- Espaço para seções futuras (aniversariantes retrátil, últimos pedidos, etc.) --}}
        <div class="mt-6">
            {{-- Exemplo de placeholder simples --}}
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-lg font-semibold text-gray-800">Visão rápida</h3>
                <p class="text-sm text-gray-500 mt-2">
                    Aqui colocaremos: Aniversariantes do mês, últimos pedidos, alertas de estoque e campanhas ativas.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
