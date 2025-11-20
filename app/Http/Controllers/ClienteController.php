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
    // filtros vindos da tela (GET)
    $filtroOrigem = $request->input('origem_cadastro');
    $filtroStatus = $request->input('status');

    // Subquery com estatísticas por cliente
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
        ->groupBy('p.cliente_id');

    $clientesQuery = DB::table('appcliente as c')
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
    // ESTATÍSTICAS POR ORIGEM
    // ==========================
    $origensStats = DB::table('appcliente')
        ->selectRaw("
            COALESCE(origem_cadastro, 'Não informada') as origem_cadastro,
            COUNT(*)                                    as total,
            SUM(CASE WHEN status = 'Ativo' THEN 1 ELSE 0 END) as ativos
        ")
        ->groupBy(DB::raw("COALESCE(origem_cadastro, 'Não informada')"))
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
        return view('clientes.create');
    }

    public function createPublic()
    {
        // Se quiser simplificar, podemos mandar menos campos pra tela pública.
        // Aqui vou reaproveitar UF/Cidade/Bairro igual seu create normal.
        $ufs = DB::table('appuf')->orderBy('nome')->get();

        return view('clientes.cadastro-publico', compact('ufs'));
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
                'lowercase',
                'email:rfc,dns',
                'max:255',
                'unique:appcliente,email',
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
        ]);

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
            $bairroNome = DB::table('appbairro')->where('id', (int)$bairroId)->value('nome');
        }

        // --- Monta dados para gravar ---
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
        ];

        // Foto (se enviada)
        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        Cliente::create($dados);

        return view('clientes.cadastro-publico-ok');
    }


    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',
            'email'           => ['nullable', 'string', 'lowercase', 'email:rfc,dns', 'max:255', 'unique:appcliente,email'],
            'telefone'        => 'nullable|string|max:20',

            'cep'             => 'nullable|string|max:9',
            'endereco'        => 'nullable|string|max:255',
            'uf_id'           => 'nullable|integer',
            'cidade_id'       => 'nullable|integer',
            'bairro_id'       => 'nullable', // pode ser numérico ou "custom"
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
            $bairroNome = DB::table('appbairro')->where('id', (int)$bairroId)->value('nome');
        }

        // Monta dados finais (usa $dn!)
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
        ];

        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        Cliente::create($dados);

        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }
    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',
            'email' => [
                'nullable',
                'string',
                'lowercase',
                'email:rfc,dns',
                'max:255',
                Rule::unique('appcliente', 'email')->ignore($cliente->id),
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
}
