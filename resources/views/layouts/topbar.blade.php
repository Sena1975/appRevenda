
<div class="flex items-center justify-between h-16 bg-white border-b px-6 shadow-sm">
    <!-- Botão menu (para mobile ou futuro recolhimento) -->
    <button class="p-2 rounded-md bg-gray-100 hover:bg-gray-200">
        ☰
    </button>

    <!-- Título -->
    <h1 class="text-lg font-semibold text-gray-700">Painel Administrativo</h1>

    <!-- Usuário -->
    <div class="flex items-center gap-4">
        <div class="text-right">
            <p class="text-sm font-medium text-gray-700">{{ Auth::user()->nome }}</p>
            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
        </div>
        <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
            {{ strtoupper(substr(Auth::user()->nome, 0, 1)) }}
        </div>
    </div>
</div>
