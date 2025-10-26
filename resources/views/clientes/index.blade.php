<x-app-layout>
    <div class="bg-white shadow rounded-lg p-6">
        <!-- Cabeçalho -->
        <div class="flex justify-between items-center mb-6 border-b pb-3">
            <h2 class="text-2xl font-semibold text-gray-700">Clientes</h2>

            <a href="{{ route('clientes.create') }}"
            class="flex items-center gap-2 bg-blue-600 text-white font-medium px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Novo Cliente
            </a>

        </div>

        <!-- Mensagem de sucesso -->
        @if (session('success'))
            <div class="mb-4 text-green-600 text-sm">{{ session('success') }}</div>
        @endif

        <!-- Tabela -->
        <table class="w-full border border-gray-200 rounded text-sm">
            <thead class="bg-gray-100 text-gray-700 uppercase">
                <tr>
                    <th class="px-4 py-2 text-left w-20">Foto</th>
                    <th class="px-4 py-2 text-left">Nome</th>
                    <th class="px-4 py-2 text-left">E-mail</th>
                    <th class="px-4 py-2 text-left">Telefone</th>
                    <th class="px-4 py-2 text-center w-32">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($clientes as $cliente)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-2">
                            @if ($cliente->foto)
                                <img src="{{ asset('storage/' . $cliente->foto) }}" alt="Foto"
                                     class="w-10 h-10 rounded-full object-cover">
                            @else
                                <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
                                    {{ strtoupper(substr($cliente->nome, 0, 1)) }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $cliente->nome }}</td>
                        <td class="px-4 py-2">{{ $cliente->email }}</td>
                        <td class="px-4 py-2">{{ $cliente->telefone }}</td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-blue-600 hover:underline">Editar</a> |
                            <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Deseja realmente excluir este cliente?')"
                                        class="text-red-600 hover:underline">
                                    Excluir
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                            Nenhum cliente cadastrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($clientes->count() > 0)
            <div class="mt-4">
                {{ $clientes->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
