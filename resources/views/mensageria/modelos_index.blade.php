<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-700">
                Modelos de Mensagens
            </h2>

            @if (Route::has('mensageria.modelos.create'))
                <a href="{{ route('mensageria.modelos.create') }}"
                   class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded bg-indigo-600 text-white hover:bg-indigo-700">
                    + Novo modelo
                </a>
            @endif
        </div>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2">Nome</th>
                    <th class="text-left py-2">Código</th>
                    <th class="text-left py-2">Canal</th>
                    <th class="text-right py-2">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($modelos as $modelo)
                    <tr class="border-b">
                        <td class="py-2">{{ $modelo->nome }}</td>
                        <td class="py-2 text-xs text-gray-500">{{ $modelo->codigo }}</td>
                        <td class="py-2">{{ $modelo->canal }}</td>
                        <td class="py-2 text-right">
                            <a href="{{ route('mensageria.modelos.form_enviar', $modelo) }}"
                               class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded bg-indigo-600 text-white hover:bg-indigo-700">
                                Enviar...
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-4 text-center text-gray-500">
                            Nenhum modelo cadastrado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>
</x-app-layout>
