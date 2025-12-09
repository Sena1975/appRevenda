{{-- resources/views/whatsapp-config/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Configurações de WhatsApp
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">
                        Cadastre aqui as conexões de WhatsApp que esta empresa irá utilizar
                        (BotConversa, Z-API, etc). Você pode definir uma conexão padrão.
                    </p>
                </div>
                <div>
                    <a href="{{ route('whatsapp-config.create') }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        + Nova conexão
                    </a>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Nome / Descrição
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Provedor
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Número
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Padrão
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ativo
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ações
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($configs as $config)
                        @php
                            $providerLabels = [
                                'botconversa' => 'BotConversa',
                                'zapi'        => 'Z-API',
                                'other'       => 'Outro / Custom',
                            ];
                            $providerNome = $providerLabels[$config->provider] ?? ucfirst($config->provider);
                        @endphp
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <div class="font-semibold">
                                    {{ $config->nome_exibicao ?: 'Conexão #' . $config->id }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    ID: {{ $config->id }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">
                                    {{ $providerNome }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $config->phone_number ?: '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if ($config->is_default)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700">
                                        Padrão
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">
                                        Não
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                @if ($config->ativo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                        Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">
                                        Inativo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right">
                                <div class="inline-flex items-center space-x-2">
                                    <a href="{{ route('whatsapp-config.edit', $config) }}"
                                       class="text-xs px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                                        Editar
                                    </a>

                                    <form action="{{ route('whatsapp-config.destroy', $config) }}"
                                          method="POST"
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta configuração?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="text-xs px-3 py-1 rounded-md bg-red-50 text-red-700 hover:bg-red-100">
                                            Excluir
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">
                                Nenhuma configuração de WhatsApp cadastrada para esta empresa.
                                <br>
                                <a href="{{ route('whatsapp-config.create') }}" class="text-blue-600 hover:underline">
                                    Clique aqui para cadastrar a primeira conexão.
                                </a>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>

                @if ($configs->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $configs->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
