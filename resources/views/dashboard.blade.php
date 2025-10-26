<x-app-layout>
    <x-slot name="header">
        {{ __('Painel de Controle') }}
    </x-slot>

    <div class="grid grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold">Produtos</h3>
            <p class="text-gray-600 mt-2">Em breve: total de produtos cadastrados.</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold">Revendedoras</h3>
            <p class="text-gray-600 mt-2">Em breve: total de revendedoras ativas.</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold">Clientes</h3>
            <p class="text-gray-600 mt-2">Em breve: total de clientes cadastrados.</p>
        </div>
    </div>
</x-app-layout>
