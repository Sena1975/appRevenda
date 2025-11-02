@php
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Mês atual
$mesAtual = Carbon::now()->month;

// Busca aniversariantes do mês atual
$aniversariantes = DB::table('appcliente')
    ->select('nome', 'data_nascimento')
    ->whereMonth('data_nascimento', $mesAtual)
    ->orderByRaw('DAY(data_nascimento)')
    ->get();
@endphp

<div x-data="{ open: true }" class="border-l bg-white shadow-sm w-64 flex flex-col transition-all duration-300"
     :class="open ? 'w-64' : 'w-10'">
    
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between px-3 py-2 bg-indigo-600 text-white cursor-pointer"
         @click="open = !open">
        <div class="flex items-center gap-2">
            <span>🎂</span>
            <span x-show="open" class="font-semibold">Feliz Aniversário</span>
        </div>
        <button class="focus:outline-none">
            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M6 18L18 6M6 6l12 12" />
            </svg>
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- Lista -->
    <div x-show="open" x-transition class="flex-1 overflow-y-auto p-3">
        @if($aniversariantes->isEmpty())
            <p class="text-gray-500 text-sm text-center mt-4">Nenhum aniversariante este mês 🎈</p>
        @else
            @foreach($aniversariantes as $aniv)
                @php
                    $dia = Carbon::parse($aniv->data_nascimento)->format('d');
                    $mes = Carbon::parse($aniv->data_nascimento)->format('m');
                    $diaFormatado = Carbon::parse($aniv->data_nascimento)->format('d \d\e M');
                    $idade = Carbon::parse($aniv->data_nascimento)->age;
                    $hoje = Carbon::now()->isSameDay(Carbon::parse($aniv->data_nascimento));
                @endphp
                <div class="flex items-center gap-2 p-2 rounded-md mb-2 border hover:bg-gray-50 transition">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-700 flex items-center justify-center rounded-full font-bold">
                        {{ strtoupper(substr($aniv->nome, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">{{ $aniv->nome }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $hoje ? '🎉 Hoje!' : $diaFormatado }} 
                            <span class="text-gray-400">({{ $idade }} anos)</span>
                        </p>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>
