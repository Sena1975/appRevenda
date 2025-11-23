{{-- resources/views/clientes/cadastro-publico-ok.blade.php --}}
<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white shadow rounded-lg p-6 w-full max-w-md text-center">
            <h1 class="text-2xl font-bold mb-3 text-gray-800">
                Cadastro enviado com sucesso! 🎉
            </h1>

            <p class="text-sm text-gray-600 mb-4">
                Obrigado por se cadastrar. Em breve entraremos em contato pelo WhatsApp ou e-mail informado.
            </p>

            @isset($whatsappLink)
                <p class="text-sm text-gray-700 mb-4">
                    Estamos abrindo o WhatsApp para você avisar que já fez o cadastro...
                </p>

                {{-- Link/botão de fallback, caso o redirecionamento automático não funcione --}}
                <a href="{{ $whatsappLink }}" target="_blank"
                   class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md shadow hover:bg-green-700">
                    📲 Enviar mensagem
                </a>

                <p class="text-xs text-gray-500 mt-2">
                    A mensagem será:
                    <br>
                    <span class="italic">
                        "Olá Dani, já fiz meu cadastro, segue ID-{{ $cliente->id }}"
                    </span>
                </p>

                {{-- Redirecionamento automático para o WhatsApp --}}
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        // Pequeno delay só para garantir que a página carregou
                        setTimeout(function () {
                            window.location.href = @json($whatsappLink);
                        }, 800);
                    });
                </script>
            @endisset

            <p class="text-xs text-gray-400 mt-6">
                Você já pode fechar esta página após enviar a mensagem.
            </p>
        </div>
    </div>
</x-guest-layout>
