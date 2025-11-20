<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            QR Code - Cadastro de Cliente
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-xl mx-auto text-center">
        <p class="mb-4 text-sm text-gray-600">
            Escaneie o QR Code abaixo para fazer seu cadastro como cliente:
        </p>

        {!! QrCode::size(280)->margin(1)->generate(route('clientes.public.create')) !!}

        <p class="mt-4 text-xs text-gray-500">
            Link: {{ route('clientes.public.create') }}
        </p>
    </div>
</x-app-layout>
