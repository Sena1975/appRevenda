@php
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

$mesAtual = Carbon::now()->month;
$aniversariantes = DB::table('appcliente')
    ->select('id','nome','data_nascimento')
    ->whereMonth('data_nascimento', $mesAtual)
    ->orderByRaw('DAY(data_nascimento)')
    ->get();

$meses = [
    1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
    7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];
@endphp

<div x-data="{ openAniv: false }">

    <!-- Botão flutuante -->
    <button
        @click="openAniv = !openAniv"
        class="fixed left-2 bottom-1/2 transform translate-y-1/2 md:top-1/2 md:bottom-auto md:-translate-y-1/2
               z-40 bg-indigo-600 text-white p-3 rounded-r-lg shadow-lg hover:bg-indigo-700 transition">
        🎂
    </button>

    <!-- Overlay (escurece fundo no mobile) -->
    <div
        x-show="openAniv"
        @click="openAniv = false"
        class="fixed inset-0 bg-black bg-opacity-40 z-40 md:hidden"
        x-transition.opacity>
    </div>

    <!-- Painel lateral -->
    <aside
        class="fixed top-0 left-0 h-full w-72 bg-white border-r border-gray-200 shadow-lg z-50
               transition-transform duration-300 will-change-transform"
        :class="openAniv ? 'translate-x-0' : '-translate-x-full'">

        <!-- Cabeçalho -->
        <div class="flex items-center justify-between px-4 py-3 bg-indigo-600 text-white">
            <h2 class="font-semibold text-sm">🎉 Aniversariantes</h2>
            <button @click="openAniv = false" class="hover:text-gray-200">✖</button>
        </div>

        <!-- Filtro de mês -->
        <div class="p-3 border-b">
            <label for="mesSelect" class="text-sm font-medium text-gray-700">Mês:</label>
            <select id="mesSelect" class="w-full mt-1 border-gray-300 rounded-md text-sm"
                    onchange="carregarAniversariantes(this.value)">
                @foreach($meses as $num => $nome)
                    <option value="{{ $num }}" {{ $num == $mesAtual ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Lista -->
        <div id="listaAniversariantes" class="flex-1 overflow-y-auto p-3">
            @forelse($aniversariantes as $aniv)
                @php
                    $data = Carbon::parse($aniv->data_nascimento);
                    $dia = $data->format('d');
                    $idade = $data->age;
                    $hoje = $data->isBirthday();
                @endphp
                <div class="flex items-center gap-2 p-2 rounded-md mb-2 border hover:bg-gray-50 transition">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-700 flex items-center justify-center rounded-full font-bold">
                        {{ strtoupper(substr($aniv->nome, 0, 1)) }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">{{ $aniv->nome }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $hoje ? '🎂 Hoje!' : $dia . '/' . str_pad($mesAtual, 2, '0', STR_PAD_LEFT) }}
                            <span class="text-gray-400">({{ $idade }} anos)</span>
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-sm text-center mt-6">Nenhum aniversariante neste mês 🎈</p>
            @endforelse
        </div>
    </aside>
</div>

<script>
function carregarAniversariantes(mes) {
    const lista = document.getElementById('listaAniversariantes');
    lista.innerHTML = '<p class="text-center text-gray-500 mt-6">⏳ Carregando...</p>';

    fetch(`/aniversariantes/${mes}`)
        .then(res => res.json())
        .then(data => {
            if (!data.length) {
                lista.innerHTML = '<p class="text-center text-gray-500 mt-6">Nenhum aniversariante neste mês 🎈</p>';
                return;
            }
            lista.innerHTML = data.map(item => `
                <div class="flex items-center gap-2 p-2 rounded-md mb-2 border hover:bg-gray-50 transition">
                    <div class="w-8 h-8 bg-indigo-100 text-indigo-700 flex items-center justify-center rounded-full font-bold">
                        ${item.nome.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-800">${item.nome}</p>
                        <p class="text-xs text-gray-500">${item.data_formatada} <span class="text-gray-400">(${item.idade} anos)</span></p>
                    </div>
                </div>
            `).join('');
        })
        .catch(() => {
            lista.innerHTML = '<p class="text-center text-red-500 mt-6">Erro ao carregar aniversariantes 😢</p>';
        });
}
</script>
