{{-- resources/views/layouts/partials/topbar.blade.php --}}
<div class="flex items-center gap-3">
    <span class="hidden sm:block text-sm text-gray-500">{{ auth()->user()->name ?? '' }}</span>
    <a href="{{ route('profile.edit') }}" class="text-sm text-gray-600 hover:underline">Perfil</a>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="rounded-md border px-3 py-1 text-sm hover:bg-gray-50" type="submit">Sair</button>
    </form>
</div>
