<?php

namespace App\Http\Controllers;

use App\Models\MensagemModelo;
use App\Models\Cliente;
use App\Services\MensageriaService;
use Illuminate\Http\Request;

class MensagensManuaisController extends Controller
{
    public function index()
    {
        $modelos = MensagemModelo::where('ativo', true)->orderBy('nome')->get();

        return view('mensageria.modelos_index', compact('modelos'));
    }

    public function formEnviar(MensagemModelo $modelo)
    {
        // Você pode filtrar clientes, paginar, etc.
        $clientes = Cliente::orderBy('nome')->get();

        return view('mensageria.modelos_enviar', [
            'modelo'   => $modelo,
            'clientes' => $clientes,
        ]);
    }

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

            // Aqui você poderia fazer replace de placeholders, se quiser
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
