<div class="w-72 bg-white border-l shadow-inner flex flex-col">
    <div class="p-4 border-b bg-indigo-600 text-white font-semibold text-lg flex items-center justify-between">
        🎂 <span>Feliz Aniversário</span>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-3">
        <!-- Exemplo de aniversariantes (dados estáticos por enquanto) -->
        @php
            $aniversariantes = [
                ['nome' => 'Maria Silva', 'cargo' => 'Cliente Ouro', 'data' => '25 de Out', 'foto' => null],
                ['nome' => 'João Santos', 'cargo' => 'Cliente Prata', 'data' => '26 de Out', 'foto' => null],
                ['nome' => 'Carla Souza', 'cargo' => 'Cliente Bronze', 'data' => '28 de Out', 'foto' => null],
                ['nome' => 'Paulo Oliveira', 'cargo' => 'Cliente Ouro', 'data' => '29 de Out', 'foto' => null],
            ];
        @endphp

        @foreach ($aniversariantes as $a)
            <div class="flex items-center gap-3 bg-gray-50 hover:bg-indigo-50 rounded-lg p-3 shadow-sm">
                @if ($a['foto'])
                    <img src="{{ asset('images/' . $a['foto']) }}" alt="{{ $a['nome'] }}" class="w-10 h-10 rounded-full">
                @else
                    <div class="w-10 h-10 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold">
                        {{ strtoupper(substr($a['nome'], 0, 1)) }}
                    </div>
                @endif

                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">{{ $a['nome'] }}</p>
                    <p class="text-xs text-gray-500">{{ $a['cargo'] }}</p>
                </div>
                <span class="text-xs font-medium text-indigo-600">{{ $a['data'] }}</span>
            </div>
        @endforeach
    </div>
</div>
