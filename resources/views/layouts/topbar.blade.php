<div class="flex items-center justify-between h-16 bg-white border-b px-6 shadow-sm">
    @php
        /** @var \App\Models\Usuario|null $user */
        $user = Auth::user();

        // empresa injetada pelo middleware EmpresaAtiva
        $empresa = app()->bound('empresa') ? app('empresa') : null;

        $nomeUsuario  = $user?->nome ?? 'Usuário';
        $emailUsuario = $user?->email ?? '';
        $nomeEmpresa  = $empresa?->nome_fantasia ?? 'Empresa não definida';

        $inicial = mb_strtoupper(mb_substr($nomeUsuario, 0, 1));
    @endphp

    <!-- Botão hamburguer (mobile) -->
    <button
        class="lg:hidden inline-flex items-center justify-center p-2 rounded-md border bg-white hover:bg-gray-50"
        @click="leftOpen = true"
        aria-label="Abrir menu"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
             viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>

    <!-- Título + empresa -->
    <div class="flex flex-col">
        <h1 class="text-lg font-semibold text-gray-700">
            Painel Adm
        </h1>
        <span class="text-xs text-gray-500">
            {{ $nomeEmpresa }}
        </span>
        
    </div>

    <!-- Usuário com dropdown -->
    <div x-data="{ open: false }" class="relative">
        <div @click="open = !open" class="flex items-center gap-4 cursor-pointer select-none">
            <div class="text-right">
                <p class="text-sm font-medium text-gray-700">
                    {{ $nomeUsuario }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ $emailUsuario }}
                </p>
                {{-- Se quiser mostrar a empresa também aqui, descomente:
                <p class="text-[0.7rem] text-gray-400">
                    {{ $nomeEmpresa }}
                </p>
                --}}
            </div>
            <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
                {{ $inicial }}
            </div>
        </div>

        <!-- Dropdown -->
        <div
            x-show="open"
            @click.outside="open = false"
            x-transition
            class="absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-lg shadow-lg z-50"
        >
            <a href="{{ route('profile.edit') }}"
               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                ⚙️ Perfil
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    🚪 Sair
                </button>
            </form>
        </div>
    </div>
</div>
