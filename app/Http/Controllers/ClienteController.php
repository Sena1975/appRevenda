<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $empresaId = $user?->empresa_id;

        if (!$empresaId) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        // filtros vindos da tela (GET)
        $filtroOrigem = $request->input('origem_cadastro');
        $filtroStatus = $request->input('status');

        // Subquery com estatísticas por cliente (APENAS da empresa)
        $statsSub = DB::table('apppedidovenda as p')
            ->join('appitemvenda as i', 'i.pedido_id', '=', 'p.id')
            ->selectRaw('
            p.cliente_id                                   as cliente_id,
            COUNT(DISTINCT i.produto_id)                  as mix,
            COALESCE(SUM(p.valor_liquido), 0)             as total_compras,
            COUNT(DISTINCT p.id)                          as qtd_pedidos,
            COALESCE(
                SUM(p.valor_liquido) / NULLIF(COUNT(DISTINCT p.id), 0),
                0
            )                                             as ticket_medio
        ')
            ->whereNotNull('p.cliente_id')
            ->where('p.empresa_id', $empresaId) // 👈 só pedidos da empresa do usuário
            ->groupBy('p.cliente_id');

        // Clientes da empresa + join com as stats
        $clientesQuery = Cliente::query()
            ->from('appcliente as c')
            ->where('c.empresa_id', $empresaId) // 👈 só clientes da empresa
            ->leftJoinSub($statsSub, 's', 's.cliente_id', '=', 'c.id')
            ->selectRaw('
            c.*,
            COALESCE(s.mix, 0)            as mix,
            COALESCE(s.total_compras, 0)  as total_compras,
            COALESCE(s.ticket_medio, 0)   as ticket_medio
        ');

        // === aplica filtro por origem_cadastro, se informado ===
        if (!empty($filtroOrigem)) {
            $clientesQuery->where('c.origem_cadastro', $filtroOrigem);
        }

        // === aplica filtro por status, se informado ===
        if (!empty($filtroStatus)) {
            $clientesQuery->where('c.status', $filtroStatus);
        }

        $clientes = $clientesQuery
            ->orderBy('c.nome')
            ->paginate(15)
            ->appends($request->only('origem_cadastro', 'status'));

        // ==========================
        // ESTATÍSTICAS POR ORIGEM (APENAS da empresa)
        // ==========================
        $origensStats = DB::table('appcliente as c')
            ->selectRaw("
            COALESCE(c.origem_cadastro, 'Não informada') as origem_cadastro,
            COUNT(*)                                      as total,
            SUM(CASE WHEN c.status = 'Ativo' THEN 1 ELSE 0 END) as ativos
        ")
            ->where('c.empresa_id', $empresaId) // 👈 só clientes da empresa
            ->groupBy(DB::raw("COALESCE(c.origem_cadastro, 'Não informada')"))
            ->get();

        $totalClientes = $origensStats->sum('total');
        $totalAtivos   = $origensStats->sum('ativos');

        return view('clientes.index', [
            'clientes'      => $clientes,
            'filtroOrigem'  => $filtroOrigem,
            'filtroStatus'  => $filtroStatus,
            'origensStats'  => $origensStats,
            'totalClientes' => $totalClientes,
            'totalAtivos'   => $totalAtivos,
        ]);
    }

    public function create()
    {
        // Lista de clientes para servir como possíveis indicadores
        // (excluindo o ID 1 se você quiser deixar só como "Vendedor")
        $indicadores = Cliente::where('id', '!=', 1)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('clientes.create', compact('indicadores'));
    }


    public function createPublic(Request $request)
    {
        $ufs = DB::table('appuf')->orderBy('nome')->get();

        // pega ?indicador=ID da URL, padrão 1 (vendedor)
        $indicadorId = (int) $request->query('indicador', 1);

        $indicadorCliente = null;

        if ($indicadorId !== 1) {
            $indicadorCliente = Cliente::find($indicadorId);

            // se não achar, volta pro padrão 1
            if (!$indicadorCliente) {
                $indicadorId = 1;
            }
        }

        return view('clientes.cadastro-publico', compact('ufs', 'indicadorId', 'indicadorCliente'));
    }


    public function storePublic(Request $request)
    {
        // Validação pública
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',

            // AGORA OBRIGATÓRIOS
            'email'           => [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
            ],
            'whatsapp'        => 'required|string|max:30',
            'telefone'        => 'nullable|string|max:20',
            'cep'             => 'nullable|string|max:9',
            'endereco'        => 'nullable|string|max:255',
            'uf_id'           => 'nullable|integer',
            'cidade_id'       => 'nullable|integer',
            'bairro_id'       => 'nullable',
            'bairro_nome'     => 'nullable|string|max:100',
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',
            'data_nascimento' => ['nullable', 'date', 'before:today'],
            'sexo'            => 'nullable|string|max:20',
            'filhos'          => 'nullable|integer|min:0',
            // Time do coração
            'timecoracao'     => 'nullable|string|max:60',
            // Foto
            'foto'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'origem_cadastro' => 'nullable|string|max:50',

            // se quiser mandar empresa_id via formulário/link público
            'empresa_id'      => 'nullable|integer|exists:appempresas,id',
        ]);

        /*
    |--------------------------------------------------------------------------
    | DEFINE EMPRESA E CÓDIGO SEQUENCIAL POR EMPRESA
    |--------------------------------------------------------------------------
    */
        // 1) tenta pegar empresa do container (middleware EmpresaAtiva)
        $empresaAtiva = app()->bound('empresa') ? app('empresa') : null;

        // 2) prioridade: empresa do request > empresa ativa > fallback = 1
        $empresaId = (int) ($request->input('empresa_id')
            ?: ($empresaAtiva->id ?? 1));

        // 3) próximo código sequencial por empresa
        $ultimoCodigo = \App\Models\Cliente::where('empresa_id', $empresaId)
            ->max('codigo_empresa');

        $proximoCodigo = $ultimoCodigo ? ($ultimoCodigo + 1) : 1;

        // --- Normaliza data de nascimento (mesma lógica do store) ---
        $dn = $request->input('data_nascimento');
        if ($dn) {
            $dn = trim($dn);
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dn)) {
                try {
                    $dn = \Carbon\Carbon::createFromFormat('d/m/Y', $dn)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $dn = null;
                }
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dn)) {
                $dn = null;
            }
        } else {
            $dn = null;
        }

        // --- UF/Cidade/Bairro por ID ---
        $ufSigla = $request->filled('uf_id')
            ? DB::table('appuf')->where('id', $request->uf_id)->value('sigla')
            : null;

        $cidadeNome = ($request->filled('cidade_id') && $request->cidade_id !== '__keep__')
            ? DB::table('appcidade')->where('id', $request->cidade_id)->value('nome')
            : null;

        $bairroNome = null;
        $bairroId = $request->input('bairro_id');
        if ($bairroId === 'custom') {
            $bairroNome = $request->input('bairro_nome');
        } elseif (is_numeric($bairroId)) {
            $bairroNome = DB::table('appbairro')->where('id', (int) $bairroId)->value('nome');
        }

        $telefone = $request->input('telefone') ?: $request->input('whatsapp');

        // --- Monta dados para gravar ---
        $dados = [
            'nome'            => $request->nome,
            'email'           => $request->email,
            'telefone'        => $telefone,
            'cep'             => $request->cep,
            'endereco'        => $request->endereco,
            'uf'              => $ufSigla ?? $request->uf,
            'cidade'          => $cidadeNome ?? $request->cidade,
            'bairro'          => $bairroNome ?? $request->bairro,
            'whatsapp'        => $request->whatsapp,
            'telegram'        => null,
            'instagram'       => null,
            'facebook'        => null,
            'cpf'             => null,
            'data_nascimento' => $dn,
            'sexo'            => $request->sexo,
            'filhos'          => $request->filhos,
            'timecoracao'     => $request->timecoracao,

            // ⚠ status fixo para cadastros públicos
            'status'          => 'Em Aprovação',
            'origem_cadastro' => 'Público',
            'indicador_id'    => (int) $request->input('indicador_id', 1),

            // 🔹 multiempresa
            'empresa_id'      => $empresaId,
            'codigo_empresa'  => $proximoCodigo,
        ];

        // Foto (se enviada)
        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        $cliente = \App\Models\Cliente::create($dados);

        // Número da Dani em formato internacional (55 + DDD + número)
        $daniNumber = '5571993420874'; // 71 99342-0874

        // 👉 Agora usa o código da empresa, não o ID global:
        $texto = "Olá Dani, já fiz meu cadastro, segue ID-{$cliente->codigo_empresa}";

        // Monta o link do WhatsApp com a mensagem
        $whatsappLink = 'https://wa.me/' . $daniNumber . '?' . http_build_query([
            'text' => $texto,
        ]);

        return view('clientes.cadastro-publico-ok', [
            'cliente'      => $cliente,
            'whatsappLink' => $whatsappLink,
        ]);
    }

    public function edit(Cliente $cliente)
    {
        // Indicadores possíveis: outros clientes, exceto ele mesmo
        $indicadores = Cliente::where('id', '!=', $cliente->id)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return view('clientes.edit', compact('cliente', 'indicadores'));
    }


public function store(Request $request)
{
    $validated = $request->validate([
        'nome'            => 'required|string|max:255',
        'email'           => ['nullable', 'string', 'email:rfc,dns', 'max:255'],
        'telefone'        => 'nullable|string|max:20',
        'cep'             => 'nullable|string|max:9',
        'endereco'        => 'nullable|string|max:255',
        'uf_id'           => 'nullable|integer',
        'cidade_id'       => 'nullable|integer',
        'bairro_id'       => 'nullable',
        'bairro_nome'     => 'nullable|string|max:100',
        'bairro'          => 'nullable|string|max:100',
        'cidade'          => 'nullable|string|max:100',
        'uf'              => 'nullable|string|max:2',
        'whatsapp'        => 'nullable|string|max:30',
        'telegram'        => 'nullable|string|max:50',
        'instagram'       => 'nullable|string|max:50',
        'facebook'        => 'nullable|string|max:100',
        'cpf'             => 'nullable|string|max:20',
        'data_nascimento' => ['nullable', 'date', 'before:today'],
        'sexo'            => 'nullable|string|max:20',
        'filhos'          => 'nullable|integer|min:0',
        'timecoracao'     => 'nullable|string|max:60',
        'status'          => ['nullable', 'in:Ativo,Inativo'],
        'foto'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        'origem_cadastro' => 'nullable|string|max:50',
    ]);

    // Normaliza data para YYYY-MM-DD ou null
    $dn = $request->input('data_nascimento');
    if ($dn) {
        $dn = trim($dn);
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dn)) {
            try {
                $dn = \Carbon\Carbon::createFromFormat('d/m/Y', $dn)->format('Y-m-d');
            } catch (\Throwable $e) {
                $dn = null;
            }
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dn)) {
            $dn = null;
        }
    } else {
        $dn = null;
    }

    // Traduz UF/Cidade/Bairro por ID
    $ufSigla = $request->filled('uf_id')
        ? DB::table('appuf')->where('id', $request->uf_id)->value('sigla')
        : null;

    $cidadeNome = ($request->filled('cidade_id') && $request->cidade_id !== '__keep__')
        ? DB::table('appcidade')->where('id', $request->cidade_id)->value('nome')
        : null;

    $bairroNome = null;
    $bairroId = $request->input('bairro_id');
    if ($bairroId === 'custom') {
        $bairroNome = $request->input('bairro_nome');
    } elseif (is_numeric($bairroId)) {
        $bairroNome = DB::table('appbairro')->where('id', (int) $bairroId)->value('nome');
    }

    /*
    |--------------------------------------------------------------------------
    | EMPRESA + CÓDIGO SEQUENCIAL POR EMPRESA
    |--------------------------------------------------------------------------
    */
    $usuario      = $request->user();
    $empresaAtiva = app()->bound('empresa') ? app('empresa') : null;

    // prioridade: usuário logado > empresa ativa do middleware > fallback 1
    $empresaId = (int) ($usuario?->empresa_id ?? $empresaAtiva?->id ?? 1);

    // pega o último codigo_empresa daquela empresa e soma 1
    $ultimoCodigo = \App\Models\Cliente::where('empresa_id', $empresaId)
        ->max('codigo_empresa');

    $proximoCodigo = $ultimoCodigo ? ($ultimoCodigo + 1) : 1;

    // Monta dados finais
    $dados = [
        'nome'            => $request->nome,
        'email'           => $request->email,
        'telefone'        => $request->telefone,
        'cep'             => $request->cep,
        'endereco'        => $request->endereco,
        'uf'              => $ufSigla ?? $request->uf,
        'cidade'          => $cidadeNome ?? $request->cidade,
        'bairro'          => $bairroNome ?? $request->bairro,
        'whatsapp'        => $request->whatsapp,
        'telegram'        => $request->telegram,
        'instagram'       => $request->instagram,
        'facebook'        => $request->facebook,
        'cpf'             => $request->cpf,
        'data_nascimento' => $dn,
        'sexo'            => $request->sexo,
        'filhos'          => $request->filhos,
        'timecoracao'     => $request->timecoracao,
        'status'          => $request->status,
        'origem_cadastro' => $request->input('origem_cadastro', 'Interno'),
        'indicador_id'    => (int) $request->input('indicador_id', 1),

        // 🔹 multiempresa
        'empresa_id'      => $empresaId,
        'codigo_empresa'  => $proximoCodigo,
    ];

    if ($request->hasFile('foto')) {
        $dados['foto'] = $request->file('foto')->store('clientes', 'public');
    }

    \App\Models\Cliente::create($dados);

    return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
}

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',
            'email' => [
                'nullable',
                'string',
                'email:rfc,dns',
                'max:255',
            ],
            'telefone'        => 'nullable|string|max:20',
            'cep'             => 'nullable|string|max:9',
            'endereco'        => 'nullable|string|max:255',
            'uf_id'           => 'nullable|integer',
            'cidade_id'       => 'nullable|integer',
            'bairro_id'       => 'nullable',
            'bairro_nome'     => 'nullable|string|max:100',
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',
            'whatsapp'        => ['nullable', 'string', 'max:20'],
            'telegram'        => ['nullable', 'string', 'max:64'],
            'instagram'       => 'nullable|string|max:50',
            'facebook'        => 'nullable|string|max:100',
            'cpf'             => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'sexo'            => 'nullable|string|max:20',
            'filhos'          => 'nullable|integer|min:0',
            'timecoracao'     => 'nullable|string|max:60',
            'status'          => ['nullable', 'in:Ativo,Inativo'],
            'foto'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'origem_cadastro' => 'nullable|string|max:50',
        ]);

        // Normaliza data para YYYY-MM-DD ou null
        $dn = $request->input('data_nascimento');
        if ($dn) {
            $dn = trim($dn);
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dn)) {
                try {
                    $dn = \Carbon\Carbon::createFromFormat('d/m/Y', $dn)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $dn = null;
                }
            } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dn)) {
                $dn = null;
            }
        } else {
            $dn = null;
        }

        // Traduz UF/Cidade/Bairro por ID
        $ufSigla = $request->filled('uf_id')
            ? DB::table('appuf')->where('id', $request->uf_id)->value('sigla')
            : null;

        $cidadeNome = ($request->filled('cidade_id') && $request->cidade_id !== '__keep__')
            ? DB::table('appcidade')->where('id', $request->cidade_id)->value('nome')
            : null;

        $bairroNome = null;
        $bairroId = $request->input('bairro_id');
        if ($bairroId === 'custom') {
            $bairroNome = $request->input('bairro_nome');
        } elseif (is_numeric($bairroId)) {
            $bairroNome = DB::table('appbairro')->where('id', (int)$bairroId)->value('nome');
        }

        $dados = [
            'nome'            => $request->nome,
            'email'           => $request->email,
            'telefone'        => $request->telefone,
            'cep'             => $request->cep,
            'endereco'        => $request->endereco,
            'uf'              => $ufSigla ?? $request->uf,
            'cidade'          => $cidadeNome ?? $request->cidade,
            'bairro'          => $bairroNome ?? $request->bairro,
            'whatsapp'        => $request->whatsapp,
            'telegram'        => $request->telegram,
            'instagram'       => $request->instagram,
            'facebook'        => $request->facebook,
            'cpf'             => $request->cpf,
            'data_nascimento' => $dn,
            'sexo'            => $request->sexo,
            'filhos'          => $request->filhos,
            'timecoracao'     => $request->timecoracao,
            'status'          => $request->status,
            'origem_cadastro' => $request->input('origem_cadastro', $cliente->origem_cadastro ?? 'Interno'),
            'indicador_id'    => (int) $request->input('indicador_id', 1),
        ];

        // Foto (substitui a antiga)
        if ($request->hasFile('foto')) {
            if ($cliente->foto && Storage::exists('public/' . $cliente->foto)) {
                Storage::delete('public/' . $cliente->foto);
            }
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        $cliente->update($dados);

        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(\App\Models\Cliente $cliente)
    {
        try {
            // Remove foto, se houver
            if ($cliente->foto && Storage::exists('public/' . $cliente->foto)) {
                Storage::delete('public/' . $cliente->foto);
            }

            $cliente->delete();

            return redirect()
                ->route('clientes.index')
                ->with('success', 'Cliente excluído com sucesso!');
        } catch (QueryException $e) {

            // Código MySQL 1451 = violação de chave estrangeira (registro filho existente)
            if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1451) {
                return redirect()
                    ->route('clientes.index')
                    ->with('error', 'Não é possível excluir este cliente, pois existem registros financeiros ou pedidos vinculados a ele.');
            }

            // Outros erros: se quiser, pode tratar diferente, mas por enquanto só relança
            throw $e;
        }
    }
    /**
     * Formulário para mesclar dois cadastros de cliente
     */
    public function mergeForm()
    {
        // Você pode filtrar aqui só os "Cadastro Público" se quiser
        $clientes = Cliente::orderBy('nome')
            ->get(['id', 'nome', 'email', 'whatsapp', 'origem_cadastro']);

        return view('clientes.merge', compact('clientes'));
    }

    /**
     * Executa a mesclagem de dois clientes:
     * - Cliente principal: será mantido
     * - Cliente secundário: fornece dados complementares e depois é inativado ou excluído
     */
    public function mergeStore(Request $request)
    {
        $data = $request->validate([
            'principal_id' => ['required', 'integer', 'exists:appcliente,id', 'different:secundario_id'],
            'secundario_id' => ['required', 'integer', 'exists:appcliente,id'],
        ]);

        $principal = Cliente::findOrFail($data['principal_id']);
        $secundario = Cliente::findOrFail($data['secundario_id']);

        // Lista de campos que queremos tentar completar no cadastro principal
        $campos = [
            'cpf',
            'telefone',
            'whatsapp',
            'telegram',
            'cep',
            'endereco',
            'bairro',
            'cidade',
            'uf',
            'instagram',
            'facebook',
            'email',
            'data_nascimento',
            'timecoracao',
            'sexo',
            'filhos',
            'foto',
        ];

        foreach ($campos as $campo) {
            $valorPrincipal = $principal->{$campo} ?? null;
            $valorSecundario = $secundario->{$campo} ?? null;

            $principalVazio = ($valorPrincipal === null || $valorPrincipal === '' || $valorPrincipal === 0);

            if ($principalVazio && $valorSecundario !== null && $valorSecundario !== '') {
                $principal->{$campo} = $valorSecundario;
            }
        }

        // Se quiser, você pode preferir SEMPRE o dado do secundário:
        // $principal->{$campo} = $valorSecundario ?? $valorPrincipal;

        // Mantém status/origem_cadastro do principal como estão
        $principal->save();

        // Agora trata o cliente secundário
        // Opção 1: marcar como inativo + origem "Mesclado"
        $secundario->status = 'Inativo';
        $secundario->origem_cadastro = $secundario->origem_cadastro
            ? $secundario->origem_cadastro . ' (Mesclado)'
            : 'Mesclado';
        $secundario->save();

        // Se tiver certeza que o secundário não tem pedidos/financeiro,
        // poderia tentar deletar. Aqui vou só inativar para ser mais seguro.
        /*
        try {
            $secundario->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            // Se der erro de FK, ele continua como Inativo (já salvo acima)
        }
        */

        return redirect()
            ->route('clientes.index')
            ->with('success', "Clientes mesclados com sucesso! O cadastro de '{$principal->nome}' foi atualizado.");
    }

    public function indicadorInfo(Cliente $cliente)
    {
        // ID do indicador gravado no cliente (padrão = 1)
        $indicadorId = (int) ($cliente->indicador_id ?? 1);

        // Nome do indicador (se diferente de 1)
        $indicadorNome = null;

        if ($indicadorId !== 1) {
            $indicador = Cliente::find($indicadorId);
            $indicadorNome = $indicador?->nome;
        }

        // Texto amigável pra mostrar na tela
        if ($indicadorId === 1) {
            $texto = 'ID-1 (Vendedor padrão / sem indicação)';
        } elseif ($indicadorNome) {
            $texto = "ID-{$indicadorId} - {$indicadorNome}";
        } else {
            $texto = "ID-{$indicadorId} (cliente indicador não encontrado)";
        }

        return response()->json([
            'indicador_id'   => $indicadorId,
            'indicador_nome' => $indicadorNome,
            'texto'          => $texto,
        ]);
    }
}
