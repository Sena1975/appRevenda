@php
    use Illuminate\Support\Str;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">
            Controle de Pagamentos - Indicações
        </h2>

    </x-slot>

    <div class="max-w-6xl mx-auto bg-white shadow rounded-lg p-6">

        {{-- Mensagens de feedback --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="mb-4 p-3 rounded bg-blue-100 text-blue-700 text-sm">
                {{ session('info') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-3 rounded bg-red-100 text-red-700 text-sm">
                <strong>Ops! Verifique os erros abaixo:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Filtro por status --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex gap-2 text-sm">
                <a href="{{ route('indicacoes.index', ['status' => 'pendente']) }}"
                    class="px-3 py-1 rounded border
                    {{ $status === 'pendente' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-100 text-gray-700' }}">
                    Pendentes
                </a>

                <a href="{{ route('indicacoes.index', ['status' => 'pago']) }}"
                    class="px-3 py-1 rounded border
                    {{ $status === 'pago' ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-100 text-gray-700' }}">
                    Pagas
                </a>
            </div>

            <div class="text-sm text-gray-700">
                @if ($status === 'pendente')
                    <span class="font-semibold">Total a pagar:</span>
                @else
                    <span class="font-semibold">Total já pago:</span>
                @endif
                R$ {{ number_format($totalPremio, 2, ',', '.') }}
            </div>
        </div>

        {{-- Tabela de indicações --}}
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100 text-left">
                        <th class="px-3 py-2 border">ID</th>
                        <th class="px-3 py-2 border">Indicador (quem recebe)</th>
                        <th class="px-3 py-2 border">Indicado (cliente)</th>
                        <th class="px-3 py-2 border">Pedido</th>
                        <th class="px-3 py-2 border text-right">Valor Pedido</th>
                        <th class="px-3 py-2 border text-right">Prêmio (PIX)</th>
                        <th class="px-3 py-2 border text-center">Status</th>
                        <th class="px-3 py-2 border text-center w-40">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($indicacoes as $ind)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2 border">
                                {{ $ind->id }}
                            </td>

                            <td class="px-3 py-2 border">
                                {{ $ind->indicador->nome ?? '—' }}
                            </td>

                            <td class="px-3 py-2 border">
                                {{ $ind->indicado->nome ?? '—' }}
                            </td>

                            <td class="px-3 py-2 border">
                                @if ($ind->pedido)
                                    #{{ $ind->pedido->id }}
                                    <span class="text-xs text-gray-500">
                                        ({{ optional($ind->pedido->data_pedido)->format('d/m/Y') }})
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-3 py-2 border text-right">
                                R$ {{ number_format((float) $ind->valor_pedido, 2, ',', '.') }}
                            </td>

                            <td class="px-3 py-2 border text-right font-semibold">
                                R$ {{ number_format((float) $ind->valor_premio, 2, ',', '.') }}
                            </td>

                            {{-- Status com badge --}}
                            <td class="px-3 py-2 border text-center">
                                @php
                                    $statusLinha = strtolower($ind->status ?? '');
                                    $badgeClasses = 'bg-gray-100 text-gray-800';
                                    $label = $statusLinha ? ucfirst($statusLinha) : '—';

                                    if ($statusLinha === 'pendente') {
                                        $badgeClasses = 'bg-yellow-100 text-yellow-800';
                                        $label = 'Pendente';
                                    } elseif ($statusLinha === 'pago') {
                                        $badgeClasses = 'bg-green-100 text-green-800';
                                        $label = 'Pago';
                                    } elseif ($statusLinha === 'cancelado') {
                                        $badgeClasses = 'bg-red-100 text-red-800';
                                        $label = 'Cancelado';
                                    }
                                @endphp

                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold {{ $badgeClasses }}">
                                    {{ $label }}
                                </span>
                            </td>

                            <td class="px-3 py-2 border text-center">
                                @if ($ind->status === 'pendente')
                                    <form action="{{ route('indicacoes.pagar', $ind->id) }}" method="POST"
                                        onsubmit="return confirm('Confirmar pagamento desta indicação?');"
                                        class="inline-block">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1 text-xs bg-emerald-600 text-white rounded shadow hover:bg-emerald-700">
                                            Confirmar Pagamento
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-500">Já pago</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">
                                Nenhuma indicação encontrada para este filtro.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginação --}}
        <div class="mt-4">
            {{ $indicacoes->links() }}
        </div>
    </div>
</x-app-layout>
