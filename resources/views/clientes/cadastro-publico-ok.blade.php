<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white shadow rounded-lg p-6 w-full max-w-md text-center">
            <h1 class="text-2xl font-bold mb-3 text-green-700">
                Cadastro enviado! ✅
            </h1>
            <p class="text-sm text-gray-700 mb-4">
                Seus dados foram cadastrados com sucesso. Em breve entraremos em contato pelo WhatsApp ou e-mail.
            </p>

            <a href="{{ url('/') }}"
               class="inline-block mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                Voltar para o início
            </a>
        </div>
    </div>
</x-guest-layout>
