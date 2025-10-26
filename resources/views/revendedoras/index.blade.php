<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Revendedoras</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-700">Lista de Revendedoras</h3>
            <a href="{{ route('revendedoras.create') }}" 
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                + Nova Revendedora
            </a>
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
                    <th class="px-4 py-2 text-left">CPF</th>
                    <th class="px-4 py-2 text-left">Telefone</th>
                    <th class="px-4 py-2 text-left">WhatsApp</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach($revendedoras as $rev)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $rev->nome }}</td>
                        <td class="px-4 py-2">{{ $rev->cpf }}</td>
                        <td class="px-4 py-2">{{ $rev->telefone }}</td>
                        <td class="px-4 py-2">{{ $rev->whatsapp }}</td>
                        <td class="px-4 py-2">{{ $rev->email }}</td>
                        <td class="px-4 py-2">
                            @if($rev->status)
                                <span class="text-green-600 font-medium">Ativa</span>
                            @else
                                <span class="text-red-600 font-medium">Inativa</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('revendedoras.edit', $rev->id) }}" 
                               class="text-blue-600 hover:underline">Editar</a>
                            <form action="{{ route('revendedoras.destroy', $rev->id) }}" 
                                  method="POST" 
                                  class="inline-block"
                                  onsubmit="return confirm('Tem certeza que deseja excluir esta revendedora?')">
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
            {{ $revendedoras->links() }}
        </div>
    </div>
</x-app-layout>
