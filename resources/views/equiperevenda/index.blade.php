<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Equipes de Revenda</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Equipes</h3>
            <a href="{{ route('equipes.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Nova Equipe</a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Revendedora Responsável</th>
                    <th class="px-4 py-2 text-left">Descrição</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipes as $eq)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $eq->nome }}</td>
                        <td class="px-4 py-2">{{ $eq->revendedora?->nome ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $eq->descricao }}</td>
                        <td class="px-4 py-2">
                            @if($eq->status)
                                <span class="text-green-600 font-medium">Ativa</span>
                            @else
                                <span class="text-red-600 font-medium">Inativa</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('equipes.edit', $eq->id) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('equipes.destroy', $eq->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $equipes->links() }}
        </div>
    </div>
</x-app-layout>
