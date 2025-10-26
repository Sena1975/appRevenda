<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Detalhes do Supervisor
        </h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">

        {{-- Dados do Supervisor --}}
        <h3 class="text-lg font-semibold text-gray-800 mb-4">
            Informações do Supervisor
        </h3>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div>
                <p><strong>Nome:</strong> {{ $supervisor->nome }}</p>
                <p><strong>CPF:</strong> {{ $supervisor->cpf ?? '-' }}</p>
                <p><strong>Email:</strong> {{ $supervisor->email ?? '-' }}</p>
                <p><strong>Telefone:</strong> {{ $supervisor->telefone ?? '-' }}</p>
                <p><strong>WhatsApp:</strong> {{ $supervisor->whatsapp ?? '-' }}</p>
            </div>

            <div>
                <p><strong>Endereço:</strong> {{ $supervisor->endereco ?? '-' }}</p>
                <p><strong>Bairro:</strong> {{ $supervisor->bairro ?? '-' }}</p>
                <p><strong>Cidade:</strong> {{ $supervisor->cidade ?? '-' }}</p>
                <p><strong>Estado:</strong> {{ $supervisor->estado ?? '-' }}</p>
                <p><strong>Status:</strong>
                    @if($supervisor->status)
                        <span class="text-green-600 font-semibold">Ativo</span>
                    @else
                        <span class="text-red-600 font-semibold">Inativo</span>
                    @endif
                </p>
            </div>
        </div>

        {{-- Equipes relacionadas --}}
        <h3 class="text-lg font-semibold text-gray-800 mb-3 border-t pt-4">
            Equipes vinculadas
        </h3>

        @if($supervisor->equipes->isEmpty())
            <p class="text-gray-500">Nenhuma equipe vinculada a este supervisor.</p>
        @else
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Nome da Equipe</th>
                        <th class="px-4 py-2 text-left">Descrição</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($supervisor->equipes as $equipe)
                        <tr>
                            <td class="px-4 py-2">{{ $equipe->nome }}</td>
                            <td class="px-4 py-2">{{ $equipe->descricao ?? '-' }}</td>
                            <td class="px-4 py-2">
                                @if($equipe->status)
                                    <span class="text-green-600 font-semibold">Ativa</span>
                                @else
                                    <span class="text-red-600 font-semibold">Inativa</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right">
                                <a href="{{ route('equipes.edit', $equipe) }}" class="text-blue-600 hover:underline">Editar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="flex justify-end mt-6">
            <a href="{{ route('supervisores.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">
                Voltar
            </a>
        </div>
    </div>
</x-app-layout>
