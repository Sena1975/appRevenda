<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-700">Cadastrar Fornecedor</h2>
    </x-slot>

    <div class="bg-white shadow rounded-lg p-6 max-w-5xl mx-auto">
        <form action="{{ route('fornecedores.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <!-- Razão Social -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Razão Social</label>
                    <input type="text" name="razaosocial" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Nome Fantasia -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome Fantasia</label>
                    <input type="text" name="nomefantasia" class="w-full border-gray-300 rounded-md shadow-sm" required>
                </div>

                <!-- CNPJ -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">CNPJ</label>
                    <input type="text" name="cnpj" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="00.000.000/0000-00">
                </div>

                <!-- Pessoa de Contato -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Pessoa de Contato</label>
                    <input type="text" name="pessoacontato" class="w-full border-gray-300 rounded-md shadow-sm">
                </div>

                <!-- Telefone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telefone</label>
                    <input type="text" name="telefone" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="(00) 0000-0000">
                </div>

                <!-- WhatsApp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input type="text" name="whatsapp" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="(00) 00000-0000">
                </div>

                <!-- Telegram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telegram</label>
                    <input type="text" name="telegram" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="@usuario">
                </div>

                <!-- Instagram -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Instagram</label>
                    <input type="text" name="instagram" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="@perfil">
                </div>

                <!-- Facebook -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook</label>
                    <input type="text" name="facebook" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="facebook.com/empresa">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" name="email" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="contato@empresa.com">
                </div>

                <!-- Endereço -->
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700">Endereço</label>
                    <input type="text" name="endereco" class="w-full border-gray-300 rounded-md shadow-sm" placeholder="Rua, número, bairro, cidade, UF">
                </div>

                <!-- Status -->
                <div class="col-span-2 flex items-center mt-2">
                    <input type="checkbox" name="status" value="1" checked class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    <label class="ml-2 text-sm text-gray-700">Ativo</label>
                </div>
            </div>

            <!-- Botões -->
            <div class="mt-8 flex justify-end">
                <a href="{{ route('fornecedores.index') }}" 
                   class="px-5 py-2 mr-6 bg-gray-500 text-white font-semibold rounded-md shadow hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-1 transition">
                    ❌ Cancelar
                </a>

                <button type="submit" 
                    class="px-5 py-2 bg-blue-600 text-white font-semibold rounded-md shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition">
                    💾 Salvar
                </button>
            </div>
        </form>
    </div>

    {{-- Script de máscaras e preenchimento automático via CNPJ --}}
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const cnpjInput = document.querySelector('input[name="cnpj"]');
        const telInput = document.querySelector('input[name="telefone"]');
        const whatsappInput = document.querySelector('input[name="whatsapp"]');

        // --- MÁSCARA DE CNPJ ---
        cnpjInput.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            this.value = value;
        });

        // --- MÁSCARA DE TELEFONE ---
        function aplicarMascaraTelefone(campo) {
            campo.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length > 11) value = value.slice(0, 11);

                if (value.length <= 10) {
                    value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
                }

                this.value = value;
            });
        }

        aplicarMascaraTelefone(telInput);
        aplicarMascaraTelefone(whatsappInput);

        // --- CONSULTA DE CNPJ ---
        cnpjInput.addEventListener('blur', async function() {
            let cnpj = this.value.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                alert('CNPJ inválido!');
                return;
            }

            cnpjInput.style.backgroundColor = '#fef9c3'; // amarelo (carregando)

            try {
                const response = await fetch(`https://publica.cnpj.ws/cnpj/${cnpj}`);
                if (!response.ok) throw new Error('Erro ao consultar o CNPJ');
                const data = await response.json();

                const nomeFantasia = data.nome_fantasia && data.nome_fantasia.trim() !== ''
                    ? data.nome_fantasia
                    : data.razao_social;

                const est = data.estabelecimento ?? {};
                const endereco = [
                    est.logradouro,
                    est.numero,
                    est.bairro,
                    est.cidade?.nome,
                    est.estado?.sigla
                ].filter(Boolean).join(', ');

                document.querySelector('input[name="razaosocial"]').value = data.razao_social ?? '';
                document.querySelector('input[name="nomefantasia"]').value = nomeFantasia ?? '';
                document.querySelector('input[name="email"]').value = est.email ?? '';
                document.querySelector('input[name="telefone"]').value = est.telefone1 ?? '';
                document.querySelector('input[name="endereco"]').value = endereco ?? '';
                document.querySelector('input[name="status"]').checked = true;

                cnpjInput.style.backgroundColor = '#dcfce7'; // verde (sucesso)
            } catch (error) {
                console.error(error);
                alert('Erro ao buscar CNPJ ou CNPJ não encontrado.');
                cnpjInput.style.backgroundColor = '#fee2e2'; // vermelho (erro)
            }
        });
    });
    </script>
</x-app-layout>
