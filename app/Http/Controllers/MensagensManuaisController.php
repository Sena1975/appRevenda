<?php

namespace App\Http\Controllers;

use App\Models\MensagemModelo;
use App\Models\Cliente;
use App\Services\MensageriaService;
use Illuminate\Http\Request;

class MensagensManuaisController extends Controller
{
    /**
     * Lista de modelos de mensagem.
     */
    public function index()
    {
        $modelos = MensagemModelo::orderBy('nome')->get();

        return view('mensageria.modelos_index', compact('modelos'));
    }

    /**
     * Formulário para criar um novo modelo de mensagem.
     */
    public function create()
    {
        return view('mensageria.modelos_create');
    }

    /**
     * Grava um novo modelo de mensagem.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nome'     => ['required', 'string', 'max:255'],
            'codigo'   => ['required', 'string', 'max:255', 'unique:appmensagem_modelo,codigo'],
            'canal'    => ['nullable', 'string', 'max:50'],
            'conteudo' => ['required', 'string'],
            'ativo'    => ['nullable', 'boolean'],
        ]);

        // Se canal não vier, padrão = whatsapp
        if (empty($data['canal'])) {
            $data['canal'] = 'whatsapp';
        }

        // Checkbox "ativo" vem como on/null
        $data['ativo'] = $request->boolean('ativo');

        MensagemModelo::create($data);

        return redirect()
            ->route('mensageria.modelos.index')
            ->with('success', 'Modelo de mensagem criado com sucesso!');
    }

    /**
     * Formulário para escolher clientes e enviar um modelo.
     */
    public function formEnviar(MensagemModelo $modelo)
    {
        $clientes = Cliente::orderBy('nome')->get();

        return view('mensageria.modelos_enviar', [
            'modelo'   => $modelo,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Envia o modelo selecionado para os clientes escolhidos.
     */
    public function enviar(Request $request, MensagemModelo $modelo)
    {
        $request->validate([
            'clientes' => ['required', 'array', 'min:1'],
        ]);

        $clienteIds = $request->input('clientes', []);

        /** @var MensageriaService $mensageria */
        $mensageria = app(MensageriaService::class);

        $clientes = Cliente::whereIn('id', $clienteIds)->get();

        $enviados = 0;

        foreach ($clientes as $cliente) {
            $texto = $modelo->conteudo;

            $mensageria->enviarWhatsapp(
                cliente: $cliente,
                conteudo: $texto,
                tipo: 'envio_manual_' . $modelo->codigo,
                pedido: null,
                campanha: null,
                payloadExtra: [
                    'origem'      => 'envio_manual',
                    'modelo_id'   => $modelo->id,
                    'modelo_nome' => $modelo->nome,
                ],
            );

            $enviados++;
        }

        return redirect()
            ->route('mensageria.modelos.index')
            ->with('success', "Mensagem '{$modelo->nome}' enviada para {$enviados} cliente(s).");
    }
}
