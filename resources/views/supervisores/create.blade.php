{{-- resources/views/supervisores/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Supervisor</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-red-800">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('supervisores.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" name="nome" value="{{ old('nome') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required maxlength="150">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">CPF (11 dígitos)</label>
                    <input type="text" name="cpf" value="{{ old('cpf') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="11">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" value="{{ old('telefone') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="20">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="120">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" value="{{ old('endereco') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="150">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bairro</label>
                    <input type="text" name="bairro" value="{{ old('bairro') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Cidade</label>
                    <input type="text" name="cidade" value="{{ old('cidade') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="100">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">UF</label>
                    <input type="text" name="estado" value="{{ old('estado') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CEP</label>
                    <input type="text" name="cep" value="{{ old('cep') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="9">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" value="{{ old('instagram') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="255">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" value="{{ old('facebook') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="255">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Nascimento</label>
                    <input type="date" name="datanascimento" value="{{ old('datanascimento') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                        <option value="1" {{ old('status','1')==='1'?'selected':'' }}>Ativo</option>
                        <option value="0" {{ old('status')==='0'?'selected':'' }}>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-6 gap-3">
                <a href="{{ route('supervisores.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">Cancelar</a>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
