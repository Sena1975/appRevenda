@extends('layouts.app')

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                    <h1 class="text-lg sm:text-xl font-semibold text-gray-800">
                        Relatório de Mensagens
                    </h1>
                </div>

                {{-- FILTROS --}}
                <form method="GET" action="{{ route('mensagens.index') }}"
                    class="mb-6 border border-gray-200 rounded-lg p-4 sm:p-5 bg-gray-50">

                    {{-- 1ª linha: datas, status, tipo, campanha --}}
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Data de (envio)
                            </label>
                            <input type="date" name="data_de" value="{{ $filtros['data_de'] ?? '' }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Data até
                            </label>
                            <input type="date" name="data_ate" value="{{ $filtros['data_ate'] ?? '' }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Status
                            </label>
                            <select name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- todos --</option>
                                @foreach (['queued', 'sent', 'failed'] as $st)
                                    <option value="{{ $st }}" @selected(($filtros['status'] ?? '') === $st)>
                                        {{ strtoupper($st) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Tipo
                            </label>
                            <select name="tipo"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- todos --</option>
                                @foreach ($tiposConhecidos as $tipo)
                                    <option value="{{ $tipo }}" @selected(($filtros['tipo'] ?? '') === $tipo)>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Campanha
                            </label>
                            <select name="campanha_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- todas --</option>
                                @foreach ($campanhas as $camp)
                                    <option value="{{ $camp->id }}" @selected(($filtros['campanha_id'] ?? '') == $camp->id)>
                                        {{ $camp->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 2ª linha: cliente, pedido, canal, direção, busca --}}
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Cliente
                            </label>
                            <select name="cliente_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- todos --</option>
                                @foreach ($clientes as $cli)
                                    <option value="{{ $cli->id }}" @selected(($filtros['cliente_id'] ?? '') == $cli->id)>
                                        {{ $cli->nome }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Pedido #
                            </label>
                            <input type="number" name="pedido_id" value="{{ $filtros['pedido_id'] ?? '' }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Canal
                            </label>
                            <select name="canal"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- todos --</option>
                                @foreach (['whatsapp', 'sms', 'email'] as $can)
                                    <option value="{{ $can }}" @selected(($filtros['canal'] ?? '') === $can)>
                                        {{ strtoupper($can) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Direção
                            </label>
                            <select name="direcao"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                <option value="">-- ambas --</option>
                                @foreach (['outbound', 'inbound'] as $dir)
                                    <option value="{{ $dir }}" @selected(($filtros['direcao'] ?? '') === $dir)>
                                        {{ strtoupper($dir) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">
                                Busca (texto / cliente)
                            </label>
                            <input type="text" name="busca" value="{{ $filtros['busca'] ?? '' }}"
                                placeholder="parte do texto, nome do cliente..."
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                      focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        </div>
                    </div>

                    {{-- Botões --}}
                    <div class="mt-4 flex flex-col sm:flex-row gap-2">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 border border-transparent
                                   text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600
                                   hover:bg-indigo-700 focus:outline-none focus:ring-2
                                   focus:ring-offset-2 focus:ring-indigo-500">
                            Filtrar
                        </button>

                        <a href="{{ route('mensagens.index') }}"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300
                              text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white
                              hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2
                              focus:ring-indigo-500">
                            Limpar
                        </a>
                    </div>
                </form>

                {{-- TABELA --}}
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs sm:text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Data
                                    envio</th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Cliente
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Pedido
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Campanha
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Status
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Canal
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Direção
                                </th>
                                <th class="px-2 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Preview
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($mensagens as $msg)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        <a href="{{ route('mensagens.show', $msg) }}"
                                            class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                            {{ $msg->id }}
                                        </a>
                                    </td>

                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ optional($msg->sent_at ?? $msg->created_at)->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ $msg->cliente?->nome ?? '-' }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        @if ($msg->pedido_id)
                                            #{{ $msg->pedido_id }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ $msg->campanha?->nome ?? '-' }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ $msg->tipo ?? '-' }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap">
                                        @php
                                            $badgeClass = match ($msg->status) {
                                                'sent' => 'bg-green-100 text-green-800',
                                                'failed' => 'bg-red-100 text-red-800',
                                                'queued' => 'bg-yellow-100 text-yellow-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            };
                                        @endphp
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClass }}">
                                            {{ strtoupper($msg->status ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ strtoupper($msg->canal ?? '-') }}
                                    </td>
                                    <td class="px-2 py-2 whitespace-nowrap text-gray-700">
                                        {{ strtoupper($msg->direcao ?? '-') }}
                                    </td>
                                    <td class="px-2 py-2 max-w-xs">
                                        <span class="block text-gray-700 text-xs sm:text-sm truncate"
                                            title="{{ $msg->conteudo }}">
                                            {{ \Illuminate\Support\Str::limit($msg->conteudo, 80) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-4 py-6 text-center text-sm text-gray-500">
                                        Nenhuma mensagem encontrada para os filtros informados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- PAGINAÇÃO --}}
                <div class="mt-4">
                    {{ $mensagens->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
