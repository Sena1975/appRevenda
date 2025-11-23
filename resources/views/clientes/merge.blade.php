{{-- resources/views/clientes/merge.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="max-w-3xl mx-auto space-y-4">
        <h1 class="text-xl font-semibold text-gray-800">
            Mesclar cadastros de clientes
        </h1>

        <p class="text-sm text-gray-600">
            Use esta tela quando uma mesma cliente tiver <strong>dois cadastros</strong>, por exemplo:
            um cadastro antigo criado por você e um novo cadastro feito pelo link público
            (<code>/cadastro-cliente</code>).
        </p>

        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 rounded px-4 py-2 text-sm">
                <strong>Ops! Verifique os campos abaixo:</strong>
                <ul class="mt-1 list-disc list-inside">
                    @foreach ($errors->all() as $erro)
                        <li>{{ $erro }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('clientes.merge.store') }}" class="bg-white rounded-lg shadow p-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Cliente principal --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cliente principal (vai ficar)
                    </label>
                    <select name="principal_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}" @selected(old('principal_id') == $c->id)>
                                {{ $c->nome }}
                                @if ($c->email)
                                    - {{ $c->email }}
                                @endif
                                @if ($c->whatsapp)
                                    - {{ $c->whatsapp }}
                                @endif
                                @if ($c->origem_cadastro)
                                    ({{ $c->origem_cadastro }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Este é o cadastro que será mantido. O ID e os relacionamentos (pedidos, financeiro, etc.)
                        continuam nele.
                    </p>
                </div>

                {{-- Cliente secundário --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Cliente secundário (será mesclado)
                    </label>
                    <select name="secundario_id" class="w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                        <option value="">Selecione...</option>
                        @foreach ($clientes as $c)
                            <option value="{{ $c->id }}" @selected(old('secundario_id') == $c->id)>
                                {{ $c->nome }}
                                @if ($c->email)
                                    - {{ $c->email }}
                                @endif
                                @if ($c->whatsapp)
                                    - {{ $c->whatsapp }}
                                @endif
                                @if ($c->origem_cadastro)
                                    ({{ $c->origem_cadastro }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        Os dados deste cadastro serão usados para completar o principal.
                        Depois ele será marcado como <strong>Inativo (Mesclado)</strong>.
                    </p>
                </div>
            </div>

            <div class="border-t pt-3 flex items-center justify-between">
                <a href="{{ route('clientes.index') }}" class="text-sm text-gray-600 hover:underline">
                    ← Voltar para lista de clientes
                </a>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700"
                    onclick="return confirm('Confirma mesclar estes dois cadastros? Esta ação não poderá ser desfeita.')">
                    Mesclar cadastros
                </button>
            </div>
        </form>
    </div>
@endsection
