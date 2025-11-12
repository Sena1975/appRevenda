{{-- resources/views/fornecedores/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Novo Fornecedor</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-4xl mx-auto">
        @if ($errors->any())
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-2 text-red-800">
                <ul class="list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('fornecedores.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Razão Social</label>
                    <input type="text" name="razaosocial" value="{{ old('razaosocial') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                    <input type="text" name="nomefantasia" value="{{ old('nomefantasia') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">CNPJ (apenas dígitos)</label>
                    <input type="text" name="cnpj" value="{{ old('cnpj') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="14" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Pessoa Contato</label>
                    <input type="text" name="pessoacontato" value="{{ old('pessoacontato') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm">
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

                <div>
                    <label class="block text-sm font-medium text-gray-700">Telegram</label>
                    <input type="text" name="telegram" value="{{ old('telegram') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="50">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" value="{{ old('instagram') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" value="{{ old('facebook') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="100">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="120">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" value="{{ old('endereco') }}"
                           class="w-full border-gray-300 rounded-md shadow-sm" maxlength="200">
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
                <a href="{{ route('fornecedores.index') }}" class="rounded border px-4 py-2 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Salvar</button>
            </div>
        </form>
    </div>
</x-app-layout>
