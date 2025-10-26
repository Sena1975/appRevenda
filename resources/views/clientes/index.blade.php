<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Clientes</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6">
        <!-- Mensagem de sucesso -->
        @if(session('success'))
            <div class="mb-4 text-green-600 font-medium">
                {{ session('success') }}
            </div>
        @endif

        <!-- Botão Novo Cliente -->
        <div class="flex justify-end mb-4">
            <a href="{{ route('clientes.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Novo Cliente
            </a>
        </div>

        <!-- Tabela -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-200 rounded-lg">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">FOTO</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">NOME</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">E-MAIL</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">TELEFONE</th>
                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr class="border-b hover:bg-gray-50 transition">
                            <!-- FOTO -->
                            <td class="px-4 py-2">
                                @if($cliente->foto && Storage::exists('public/'.$cliente->foto))
                                    <img src="{{ asset('storage/'.$cliente->foto) }}"
                                         alt="Foto"
                                         class="w-12 h-12 rounded-full object-cover border border-gray-300 shadow-sm">
                                @else
                                    <img src="{{ asset('storage/clientes/default.png') }}"
                                         alt="Sem foto"
                                         class="w-12 h-12 rounded-full object-cover border border-gray-200 opacity-70">
                                @endif
                            </td>

                            <!-- NOME -->
                            <td class="px-4 py-2 text-gray-800 font-medium">
                                {{ $cliente->nome }}
                            </td>

                            <!-- EMAIL -->
                            <td class="px-4 py-2 text-gray-700">
                                {{ $cliente->email ?? '—' }}
                            </td>

                            <!-- TELEFONE -->
                            <td class="px-4 py-2 text-gray-700">
                                {{ $cliente->telefone ?? '—' }}
                            </td>

                            <!-- AÇÕES -->
                            <td class="px-4 py-2">
                                <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-blue-600 hover:underline">Editar</a>
                                <span class="mx-1 text-gray-400">|</span>
                                <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline"
                                            onclick="return confirm('Deseja realmente excluir este cliente?')">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-4 text-center text-gray-500">Nenhum cliente cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <div class="mt-4">
            {{ $clientes->links() }}
        </div>
    </div>
</x-app-layout>
