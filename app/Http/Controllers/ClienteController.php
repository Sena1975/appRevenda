<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::orderBy('nome')->paginate(10);
        return view('clientes.index', compact('clientes'));
    }

    public function create()
    {
        return view('clientes.create');
    }

    public function store(Request $request)
    {
        // 1) Validação – mantive os campos que você já validava e acrescentei
        // os do formulário (cep, endereco, ids, e redes/whatsapp etc. como opcionais)
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',
            'email'           => 'nullable|email',
            'telefone'        => 'nullable|string|max:20',

            // campos provenientes do formulário atual
            'cep'             => 'nullable|string|max:9',   // 00000-000
            'endereco'        => 'nullable|string|max:255',
            'uf_id'           => 'nullable|integer',
            'cidade_id'       => 'nullable|integer',
            'bairro_id'       => 'nullable', // pode ser número ou "custom"
            'bairro_nome'     => 'nullable|string|max:100', // usado quando bairro_id = "custom"

            // manter compatibilidade com seu schema atual (você já validava estes):
            'bairro'          => 'nullable|string|max:100',
            'cidade'          => 'nullable|string|max:100',
            'uf'              => 'nullable|string|max:2',

            // sociais/opcionais do seu form:
            'whatsapp'        => 'nullable|string|max:30',
            'telegram'        => 'nullable|string|max:50',
            'instagram'       => 'nullable|string|max:50',
            'facebook'        => 'nullable|string|max:100',
            'cpf'             => 'nullable|string|max:20',
            'data_nascimento' => 'nullable|date',
            'sexo'            => 'nullable|string|max:20',
            'filhos'          => 'nullable|integer|min:0',
            'timecoracao'     => 'nullable|string|max:60',
            'status'          => 'nullable|string|max:20',

            // foto
            'foto'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // 2) Traduz UF/Cidade/Bairro (IDs) em nomes/sigla para salvar nos campos texto
        //    (compatível com sua tabela atual que possui 'uf', 'cidade', 'bairro')
        $ufSigla     = null;
        $cidadeNome  = null;
        $bairroNome  = null;

        if ($request->filled('uf_id')) {
            $uf = DB::table('appuf')->where('id', $request->uf_id)->first(['id','sigla','nome']);
            if ($uf) {
                $ufSigla = $uf->sigla; // você salva 'uf' como sigla (SP, RJ, ...)
            }
        }

        if ($request->filled('cidade_id')) {
            $cidade = DB::table('appcidade')->where('id', $request->cidade_id)->first(['id','nome']);
            if ($cidade) {
                $cidadeNome = $cidade->nome;
            }
        }

        // bairro pode vir:
        // - como ID numérico => buscamos o nome em appbairro
        // - como "custom" => usamos bairro_nome do form (inserido pelo JS)
        // - como vazio => fica null
        $bairroId = $request->input('bairro_id');
        if ($bairroId === 'custom') {
            $bairroNome = $request->input('bairro_nome'); // precisa do hidden no form quando custom
        } elseif (is_numeric($bairroId)) {
            $bairro = DB::table('appbairro')->where('id', intval($bairroId))->first(['id','nome']);
            if ($bairro) {
                $bairroNome = $bairro->nome;
            }
        }

        // 3) Monta o array a ser salvo
        //    -> mantive seus campos originais (nome, email, telefone, bairro, cidade, uf, foto)
        //    -> e incluí campos do form que geralmente existem (cep, endereco, sociais...)
        //       OBS: se sua tabela 'clientes' ainda não tem essas colunas, te passo a migration.
        $dados = [
            'nome'            => $request->nome,
            'email'           => $request->email,
            'telefone'        => $request->telefone,

            'cep'             => $request->cep,
            'endereco'        => $request->endereco,

            'uf'              => $ufSigla ?? $request->uf,          // prioridade para a sigla via ID
            'cidade'          => $cidadeNome ?? $request->cidade,   // prioridade para nome via ID
            'bairro'          => $bairroNome ?? $request->bairro,   // prioridade para nome via ID/custom

            'whatsapp'        => $request->whatsapp,
            'telegram'        => $request->telegram,
            'instagram'       => $request->instagram,
            'facebook'        => $request->facebook,
            'cpf'             => $request->cpf,
            'data_nascimento' => $request->data_nascimento,
            'sexo'            => $request->sexo,
            'filhos'          => $request->filhos,
            'timecoracao'     => $request->timecoracao,
            'status'          => $request->status,
        ];

        // 4) Upload da foto
        if ($request->hasFile('foto')) {
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        // 5) Persistência
        Cliente::create($dados);

        return redirect()->route('clientes.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente)
    {
        $validated = $request->validate([
            'nome'            => 'required|string|max:255',
            'email'           => 'nullable|email',
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
            'data_nascimento' => 'nullable|date',
            'sexo'            => 'nullable|string|max:20',
            'filhos'          => 'nullable|integer|min:0',
            'timecoracao'     => 'nullable|string|max:60',
            'status'          => 'nullable|string|max:20',

            'foto'            => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Repete a lógica de tradução de IDs
        $ufSigla     = null;
        $cidadeNome  = null;
        $bairroNome  = null;

        if ($request->filled('uf_id')) {
            $uf = DB::table('appuf')->where('id', $request->uf_id)->first(['id','sigla','nome']);
            if ($uf) $ufSigla = $uf->sigla;
        }
        if ($request->filled('cidade_id')) {
            $cidade = DB::table('appcidade')->where('id', $request->cidade_id)->first(['id','nome']);
            if ($cidade) $cidadeNome = $cidade->nome;
        }

        $bairroId = $request->input('bairro_id');
        if ($bairroId === 'custom') {
            $bairroNome = $request->input('bairro_nome');
        } elseif (is_numeric($bairroId)) {
            $bairro = DB::table('appbairro')->where('id', intval($bairroId))->first(['id','nome']);
            if ($bairro) $bairroNome = $bairro->nome;
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
            'data_nascimento' => $request->data_nascimento,
            'sexo'            => $request->sexo,
            'filhos'          => $request->filhos,
            'timecoracao'     => $request->timecoracao,
            'status'          => $request->status,
        ];

        // Foto (apaga a antiga se existir)
        if ($request->hasFile('foto')) {
            if ($cliente->foto && Storage::exists('public/' . $cliente->foto)) {
                Storage::delete('public/' . $cliente->foto);
            }
            $dados['foto'] = $request->file('foto')->store('clientes', 'public');
        }

        $cliente->update($dados);

        return redirect()->route('clientes.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Cliente $cliente)
    {
        if ($cliente->foto && Storage::exists('public/' . $cliente->foto)) {
            Storage::delete('public/' . $cliente->foto);
        }

        $cliente->delete();

        return redirect()->route('clientes.index')->with('success', 'Cliente excluído com sucesso!');
    }
}
