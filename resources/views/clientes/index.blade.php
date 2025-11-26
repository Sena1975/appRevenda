{{-- resources/views/clientes/index.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex items-center justify-between gap-2">
            <h1 class="text-xl font-semibold text-gray-800">Clientes</h1>
            <div class="flex gap-2">
                <a href="{{ route('clientes.merge.form') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Mesclar cadastros
                </a>
                <a href="{{ route('clientes.create') }}"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    + Novo Cliente
                </a>
            </div>
        </div>


        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 rounded px-4 py-2">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 rounded px-4 py-2">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            {{-- Filtros --}}
            <form method="GET" action="{{ route('clientes.index') }}" class="mb-4 flex flex-wrap gap-4 items-end">

                {{-- Filtro por Origem --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Origem do cadastro</label>
                    <select name="origem_cadastro" class="mt-1 block w-52 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todas</option>
                        @php
                            $origemSelecionada = $filtroOrigem ?? '';
                        @endphp
                        <option value="Interno" @selected($origemSelecionada === 'Interno')>Interno</option>
                        <option value="Cadastro Público" @selected($origemSelecionada === 'Cadastro Público')>Cadastro Público</option>
                        <option value="Importação" @selected($origemSelecionada === 'Importação')>Importação</option>
                        <option value="WhatsApp" @selected($origemSelecionada === 'WhatsApp')>WhatsApp</option>
                        <option value="Instagram" @selected($origemSelecionada === 'Instagram')>Instagram</option>
                    </select>
                </div>

                {{-- Filtro por Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-40 border-gray-300 rounded-md shadow-sm">
                        <option value="">Todos</option>
                        @php
                            $statusSelecionado = $filtroStatus ?? '';
                        @endphp
                        <option value="Ativo" @selected($statusSelecionado === 'Ativo')>Ativo</option>
                        <option value="Inativo" @selected($statusSelecionado === 'Inativo')>Inativo</option>
                        <option value="Em Aprovação" @selected($statusSelecionado === 'Em Aprovação')>Em Aprovação</option>
                    </select>
                </div>

                <div>
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        Filtrar
                    </button>
                </div>

                {{-- Botão para limpar filtros, opcional --}}
                @if (!empty($filtroOrigem) || !empty($filtroStatus))
                    <div>
                        <a href="{{ route('clientes.index') }}"
                            class="inline-flex items-center px-3 py-2 bg-gray-200 rounded-md text-xs text-gray-700 hover:bg-gray-300">
                            Limpar filtros
                        </a>
                    </div>
                @endif

            </form>

            {{-- ⚠️ Legenda discreta para cadastros públicos --}}
            <div class="px-4 py-2 text-xs text-gray-600 flex items-center gap-2 border-b bg-sky-50/40">
                <span class="inline-block w-3 h-3 rounded-full bg-blue-500"></span>
                <span>
                    Linhas destacadas em azul indicam clientes cadastrados via <strong>link público</strong>
                    (origem: <span class="font-semibold">Cadastro Público</span>).
                </span>
            </div>

            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Foto</th>
                        <th class="px-3 py-2 text-left">Nome</th>
                        <th class="px-3 py-2 text-left">E-mail</th>
                        <th class="px-3 py-2 text-left">WhatsApp</th>
                        <th class="px-3 py-2 text-right">MIX</th>
                        <th class="px-3 py-2 text-right">Compras (R$)</th>
                        <th class="px-3 py-2 text-right">Ticket Médio (R$)</th>
                        <th class="px-3 py-2 text-right">Origem</th>
                        <th class="px-3 py-2 text-right"> Indicador </th>
                        <th class="px-3 py-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        @php
                            $isPublic = $cliente->origem_cadastro === 'Cadastro Público';
                        @endphp
                        <tr
                            class="border-t hover:bg-gray-50 {{ $isPublic ? 'bg-blue-50 border-l-4 border-blue-500' : '' }}">
                            <td class="px-3 py-2">
                                @if ($cliente->foto && Storage::exists('public/' . $cliente->foto))
                                    <img src="{{ asset('storage/' . $cliente->foto) }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-300 shadow-sm"
                                        alt="">
                                @else
                                    <img src="{{ asset('storage/clientes/default.png') }}"
                                        class="w-12 h-12 rounded-full object-cover border border-gray-200 opacity-70"
                                        alt="">
                                @endif
                            </td>
                            <td class="px-3 py-2 font-medium text-gray-800">{{ $cliente->nome }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $cliente->email ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-700">
                                @if ($cliente->whatsapp && $cliente->whatsapp_link)
                                    <a href="{{ $cliente->whatsapp_link }}" target="_blank"
                                        class="inline-flex items-center gap-1 text-green-600 hover:text-green-800">
                                        <span>📲</span>
                                        <span>{{ $cliente->whatsapp }}</span>
                                    </a>
                                @else
                                    —
                                @endif
                                @if ($cliente->whatsapp_indicacao_link)
                                    <a href="{{ $cliente->whatsapp_indicacao_link }}" target="_blank"
                                        class="inline-flex items-center px-2 py-1 text-xs rounded bg-green-600 text-white hover:bg-green-700 mt-1">
                                        🔗 link de indicação
                                    </a>
                                @endif
                            </td>

                            {{-- MIX (qtde de itens diferentes comprados) --}}
                            <td class="px-3 py-2 text-right text-gray-800">
                                {{ (int) ($cliente->mix ?? 0) }}
                            </td>

                            {{-- COMPRAS (total líquido) --}}
                            <td class="px-3 py-2 text-right text-blue-700">
                                R$ {{ number_format((float) ($cliente->total_compras ?? 0), 2, ',', '.') }}
                            </td>

                            {{-- TICKET MÉDIO --}}
                            <td class="px-3 py-2 text-right text-emerald-700">
                                R$ {{ number_format((float) ($cliente->ticket_medio ?? 0), 2, ',', '.') }}
                            </td>

                            {{-- Origem --}}
                            <td class="px-4 py-2 text-right text-gray-700">
                                {{ $cliente->origem_cadastro ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-sm text-gray-700">
                                @if ($cliente->indicador_id == 1)
                                    ID-1 (Padrão)
                                @else
                                    ID-{{ $cliente->indicador_id }}
                                    @if (!empty($cliente->indicador))
                                        – {{ $cliente->indicador->nome }}
                                    @endif
                                @endif
                            </td>

                            {{-- bloco de ações (com ícones) --}}
                            <td class="px-3 py-2 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">

                                    {{-- Detalhes (Visualizar) --}}
                                    <a href="{{ route('clientes.show', $cliente->id) }}"
                                        class="text-blue-600 hover:text-blue-800" title="Detalhes do cliente">
                                        🔍
                                    </a>

                                    {{-- Editar --}}
                                    <a href="{{ route('clientes.edit', $cliente->id) }}"
                                        class="text-orange-500 hover:text-orange-700" title="Editar cliente">
                                        ✏️
                                    </a>

                                    {{-- Dropdown de Extratos (ícone 📊) --}}
                                    <div x-data="{ open: false }" class="relative inline-block text-left">
                                        <button type="button" @click="open = !open"
                                            class="text-purple-600 hover:text-purple-800 focus:outline-none"
                                            title="Extratos do cliente">
                                            📊
                                        </button>

                                        <div x-show="open" @click.away="open = false" x-cloak
                                            class="origin-top-right absolute right-0 mt-1 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20">
                                            <div class="py-1 text-xs">

                                                {{-- Extrato Financeiro --}}
                                                <a href="{{ route('relatorios.recebimentos.extrato_cliente', ['cliente_id' => $cliente->id]) }}"
                                                    class="block px-3 py-1 hover:bg-blue-50 text-gray-700">
                                                    💰 Extrato Financeiro
                                                </a>

                                                {{-- Extrato de Pedidos --}}
                                                <a href="{{ route('relatorios.clientes.extrato_pedidos', ['cliente' => $cliente->id]) }}"
                                                    class="block px-3 py-1 hover:bg-blue-50 text-gray-700">
                                                    🧾 Extrato de Pedidos
                                                </a>

                                                {{-- Produtos Comprados --}}
                                                <a href="{{ route('relatorios.clientes.extrato_produtos', ['cliente' => $cliente->id]) }}"
                                                    class="block px-3 py-1 hover:bg-blue-50 text-gray-700">
                                                    📦 Produtos Comprados
                                                </a>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Excluir Cliente (ícone lixeira) --}}
                                    <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST"
                                        onsubmit="return confirm('Confirma excluir o cliente {{ addslashes($cliente->nome) }}? Esta ação não poderá ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800"
                                            title="Excluir Cliente">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-gray-500">
                                Nenhum cliente cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="px-3 py-2">
                {{ $clientes->links() }}
            </div>
        </div>
    </div>
@endsection
