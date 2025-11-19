{{-- resources/views/clientes/index.blade.php --}}
@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Facades\Storage;
    @endphp

    <div class="max-w-7xl mx-auto space-y-4">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold text-gray-800">Clientes</h1>
            <a href="{{ route('clientes.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Novo Cliente
            </a>
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
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 text-left">Foto</th>
                        <th class="px-3 py-2 text-left">Nome</th>
                        <th class="px-3 py-2 text-left">E-mail</th>
                        <th class="px-3 py-2 text-left">Telefone</th>
                        <th class="px-3 py-2 text-right">MIX</th>
                        <th class="px-3 py-2 text-right">Compras (R$)</th>
                        <th class="px-3 py-2 text-right">Ticket Médio (R$)</th>
                        <th class="px-3 py-2 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr class="border-t hover:bg-gray-50">
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
                            <td class="px-3 py-2 text-gray-700">{{ $cliente->telefone ?? '—' }}</td>

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

                                    {{-- Dropdown de Extratos (ícone 📊, igual espírito do compras) --}}
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
                            <td colspan="8" class="px-3 py-6 text-center text-gray-500">
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
