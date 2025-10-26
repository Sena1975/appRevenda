<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Supervisores</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Supervisores</h3>
            <a href="{{ route('supervisores.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Novo Supervisor
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">Telefone</th>
                    <th class="px-4 py-2 text-left">WhatsApp</th>
                    <th class="px-4 py-2 text-left">Cidade</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($supervisores as $supervisor)
                    <tr>
                        <td class="px-4 py-2">{{ $supervisor->nome }}</td>
                        <td class="px-4 py-2">{{ $supervisor->telefone }}</td>
                        <td class="px-4 py-2">{{ $supervisor->whatsapp }}</td>
                        <td class="px-4 py-2">{{ $supervisor->cidade }}</td>
                        <td class="px-4 py-2">
                            @if($supervisor->status)
                                <span class="text-green-600 font-semibold">Ativo</span>
                            @else
                                <span class="text-red-600 font-semibold">Inativo</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('supervisores.show', $supervisor) }}" class="text-gray-600 hover:underline">Ver</a>
                            <a href="{{ route('supervisores.edit', $supervisor) }}" class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('supervisores.destroy', $supervisor) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Deseja excluir este supervisor?')">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-center text-gray-500">Nenhum supervisor cadastrado.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
