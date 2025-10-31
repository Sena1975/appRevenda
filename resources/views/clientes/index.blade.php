{{-- resources/views/clientes/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="max-w-7xl mx-auto space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Clientes</h1>
        <a href="{{ route('clientes.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Novo Cliente
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded px-4 py-2">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left">Foto</th>
                    <th class="px-3 py-2 text-left">Nome</th>
                    <th class="px-3 py-2 text-left">E-mail</th>
                    <th class="px-3 py-2 text-left">Telefone</th>
                    <th class="px-3 py-2 text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
            @forelse($clientes as $cliente)
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-3 py-2">
                        @if($cliente->foto && Storage::exists('public/'.$cliente->foto))
                            <img src="{{ asset('storage/'.$cliente->foto) }}"
                                 class="w-12 h-12 rounded-full object-cover border border-gray-300 shadow-sm" alt="">
                        @else
                            <img src="{{ asset('storage/clientes/default.png') }}"
                                 class="w-12 h-12 rounded-full object-cover border border-gray-200 opacity-70" alt="">
                        @endif
                    </td>
                    <td class="px-3 py-2 font-medium text-gray-800">{{ $cliente->nome }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $cliente->email ?? '—' }}</td>
                    <td class="px-3 py-2 text-gray-700">{{ $cliente->telefone ?? '—' }}</td>
                    <td class="px-3 py-2 text-right space-x-2">
                        <a href="{{ route('clientes.edit', $cliente->id) }}" class="text-blue-600 hover:underline">Editar</a>
                        <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" class="inline"
                              onsubmit="return confirm('Excluir cliente?');">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-6 text-center text-gray-500">
                        Nenhum cliente cadastrado.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="px-3 py-2">
            {{ $clientes->links() }}
        </div>
    </div>
</div>
@endsection
