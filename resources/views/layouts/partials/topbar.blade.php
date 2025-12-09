{{-- resources/views/layouts/partials/topbar.blade.php --}}
<div class="flex items-center gap-3">

    @if (Route::has('mensageria.modelos.index'))
        <a href="{{ route('mensageria.modelos.index') }}"
            class="hidden sm:inline-flex items-center rounded-md border border-green-200 bg-green-50 px-3 py-1 text-sm text-green-700 hover:bg-green-100 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                {{-- Ícone "balão de mensagem" --}}
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 8h10M7 12h6M5 20l2.5-2.5H17a2 2 0 002-2V8a2 2 0 00-2-2H7A2 2 0 005 8v12z" />
            </svg>
            <span class="ml-1">Mensagens</span>
        </a>
    @endif

    <span class="hidden sm:block text-sm text-gray-500">
        {{ auth()->user()->name ?? '' }}
    </span>

    <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:underline">
        Perfil
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="rounded-md border px-3 py-1 text-sm hover:bg-gray-50" type="submit">
            Sair
        </button>
    </form>
</div>
