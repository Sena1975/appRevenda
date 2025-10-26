<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Fornecedores</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-7xl mx-auto">
        {{-- Mensagem de sucesso --}}
        @if (session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        {{-- Botão Novo Fornecedor --}}
        <div class="flex justify-end mb-4">
            <a href="{{ route('fornecedores.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Novo Fornecedor
            </a>
        </div>

        {{-- Tabela de Fornecedores --}}
        @if ($fornecedores->isEmpty())
            <p class="text-gray-500 text-sm">Nenhum fornecedor cadastrado ainda.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50 text-gray-700">
                        <tr>
                            <th class="px-4 py-2 border text-center w-12">#</th>
                            <th class="px-4 py-2 border text-left">Nome Fantasia</th>
                            <th class="px-4 py-2 border text-left">Razão Social</th>
                            <th class="px-4 py-2 border text-left">CNPJ</th>
                            <th class="px-4 py-2 border text-left">Pessoa Contato</th>
                            <th class="px-4 py-2 border text-left">Telefone</th>
                            <th class="px-4 py-2 border text-center w-24">Status</th>
                            <th class="px-4 py-2 border text-center w-28">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        @foreach ($fornecedores as $fornecedor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border text-center">{{ $fornecedor->id }}</td>
                                <td class="px-4 py-2 border">{{ $fornecedor->nomefantasia }}</td>
                                <td class="px-4 py-2 border">{{ $fornecedor->razaosocial }}</td>
                                <td class="px-4 py-2 border">{{ $fornecedor->cnpj }}</td>
                                <td class="px-4 py-2 border">{{ $fornecedor->pessoacontato }}</td>
                                <td class="px-4 py-2 border">{{ $fornecedor->telefone }}</td>
                                <td class="px-4 py-2 border text-center">
                                    @if($fornecedor->status)
                                        <span class="text-green-600 font-semibold">Ativo</span>
                                    @else
                                        <span class="text-red-600 font-semibold">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 border text-center">
                                    <div class="flex justify-center gap-3">
                                        <a href="{{ route('fornecedores.edit', $fornecedor->id) }}"
                                           class="text-blue-600 hover:text-blue-800" title="Editar">✏️</a>
                                        <form action="{{ route('fornecedores.destroy', $fornecedor->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('Deseja realmente excluir este fornecedor?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Excluir">🗑️</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
