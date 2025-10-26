<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Editar Cliente</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-lg mx-auto">
        <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label class="block text-gray-700">Nome</label>
                <input type="text" name="nome" value="{{ $cliente->nome }}" class="w-full border-gray-300 rounded mt-1" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">E-mail</label>
                <input type="email" name="email" value="{{ $cliente->email }}" class="w-full border-gray-300 rounded mt-1">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">Telefone</label>
                <input type="text" name="telefone" value="{{ $cliente->telefone }}" class="w-full border-gray-300 rounded mt-1">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700">Foto</label>
                @if ($cliente->foto)
                    <img src="{{ asset('storage/' . $cliente->foto) }}" alt="Foto" class="w-16 h-16 rounded-full mb-2">
                @endif
                <input type="file" name="foto" accept="image/*" class="w-full border-gray-300 rounded mt-1">
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">Atualizar</button>
            </div>
        </form>
    </div>
</x-app-layout>
