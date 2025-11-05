{{-- resources/views/formapagamento/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Nova Forma de Pagamento</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-3xl mx-auto">
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-red-800">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('formapagamento.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required maxlength="60">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Gera Contas a Receber?</label>
                    <select name="gera_receber" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('gera_receber','1')==='1'?'selected':'' }}>Sim</option>
                        <option value="0" {{ old('gera_receber')==='0'?'selected':'' }}>Não</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Máximo de Parcelas</label>
                    <input type="number" min="1" max="120" name="max_parcelas" value="{{ old('max_parcelas', 1) }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Ativa?</label>
                    <select name="ativo" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('ativo','1')==='1'?'selected':'' }}>Ativa</option>
                        <option value="0" {{ old('ativo')==='0'?'selected':'' }}>Inativa</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('formapagamento.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">Cancelar</a>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
