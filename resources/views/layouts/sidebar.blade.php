<div class="flex flex-col w-64 bg-white border-r shadow-sm">
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-10">
    </div>

    <!-- Links -->
    <nav class="flex-1 px-4 py-4 space-y-2 text-sm font-medium text-gray-700">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            🏠 <span>Início</span>
        </a>

        <a href="{{ route('produtos.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('produtos.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            🧴 <span>Produtos</span>
        </a>

        <a href="{{ route('categorias.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('categorias.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            🧩 <span>Categorias</span>
        </a>

        <a href="{{ route('revendedoras.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('revendedoras.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            👩‍💼 <span>Revendedoras</span>
        </a>

        <a href="{{ route('equiperevenda.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('equiperevenda.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            👥 <span>Equipes</span>
        </a>

        <a href="{{ route('supervisores.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('supervisores.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            🧑‍🏫 <span>Supervisores</span>
        </a>

        <a href="{{ route('clientes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded hover:bg-indigo-100 {{ request()->routeIs('clientes.*') ? 'bg-indigo-50 text-indigo-700' : '' }}">
            🧾 <span>Clientes</span>
        </a>
    </nav>

    <!-- Usuário -->
    <div class="border-t px-4 py-3 bg-gray-50 text-sm">
        <p class="font-semibold text-gray-800">{{ Auth::user()->nome }}</p>
        <p class="text-gray-500">{{ Auth::user()->email }}</p>

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="text-red-500 hover:text-red-700 text-xs">
                Sair
            </button>
        </form>
    </div>
</div>
