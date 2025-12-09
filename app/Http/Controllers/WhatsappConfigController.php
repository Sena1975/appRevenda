<?php

namespace App\Http\Controllers;

use App\Models\WhatsappConfig;
use Illuminate\Http\Request;

class WhatsappConfigController extends Controller
{
    protected function autorizarEmpresa(Request $request, WhatsappConfig $config): void
    {
        $user = $request->user();

        if (!$user || $user->empresa_id !== $config->empresa_id) {
            abort(403, 'Configuração não pertence à sua empresa.');
        }
    }

    public function index(Request $request)
    {
        $configs = WhatsappConfig::daEmpresa()
            ->orderBy('is_default', 'desc')
            ->orderBy('nome_exibicao')
            ->paginate(10);

        return view('whatsapp-config.index', compact('configs'));
    }

    public function create()
    {
        // opções de provider que você vai suportar
        $providers = [
            'botconversa' => 'BotConversa',
            'zapi'        => 'Z-API',
            'other'       => 'Outro / Custom',
        ];

        return view('whatsapp-config.create', compact('providers'));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $dados = $request->validate([
            'provider'      => 'required|string|in:botconversa,zapi,other',
            'phone_number'  => 'nullable|string|max:30',
            'nome_exibicao' => 'nullable|string|max:100',

            'api_url'       => 'nullable|string|max:255',
            'api_key'       => 'nullable|string|max:255',
            'token'         => 'nullable|string|max:255',
            'instance_id'   => 'nullable|string|max:255',

            'is_default'    => 'nullable|boolean',
            'ativo'         => 'nullable|boolean',
        ]);

        $dados['empresa_id'] = $user->empresa_id;
        $dados['is_default'] = $request->boolean('is_default');
        $dados['ativo']      = $request->boolean('ativo', true);

        // se marcar como padrão, limpa os outros padrões da empresa
        if ($dados['is_default']) {
            WhatsappConfig::where('empresa_id', $user->empresa_id)
                ->update(['is_default' => false]);
        }

        WhatsappConfig::create($dados);

        return redirect()
            ->route('whatsapp-config.index')
            ->with('success', 'Configuração de WhatsApp salva com sucesso!');
    }

    public function edit(Request $request, WhatsappConfig $whatsappConfig)
    {
        $this->autorizarEmpresa($request, $whatsappConfig);

        $providers = [
            'botconversa' => 'BotConversa',
            'zapi'        => 'Z-API',
            'other'       => 'Outro / Custom',
        ];

        return view('whatsapp-config.edit', [
            'config'    => $whatsappConfig,
            'providers' => $providers,
        ]);
    }

    public function update(Request $request, WhatsappConfig $whatsappConfig)
    {
        $this->autorizarEmpresa($request, $whatsappConfig);

        $dados = $request->validate([
            'provider'      => 'required|string|in:botconversa,zapi,other',
            'phone_number'  => 'nullable|string|max:30',
            'nome_exibicao' => 'nullable|string|max:100',

            'api_url'       => 'nullable|string|max:255',
            'api_key'       => 'nullable|string|max:255',
            'token'         => 'nullable|string|max:255',
            'instance_id'   => 'nullable|string|max:255',

            'is_default'    => 'nullable|boolean',
            'ativo'         => 'nullable|boolean',
        ]);

        $dados['is_default'] = $request->boolean('is_default');
        $dados['ativo']      = $request->boolean('ativo', true);

        if ($dados['is_default']) {
            WhatsappConfig::where('empresa_id', $whatsappConfig->empresa_id)
                ->where('id', '!=', $whatsappConfig->id)
                ->update(['is_default' => false]);
        }

        $whatsappConfig->update($dados);

        return redirect()
            ->route('whatsapp-config.index')
            ->with('success', 'Configuração de WhatsApp atualizada com sucesso!');
    }

    public function destroy(Request $request, WhatsappConfig $whatsappConfig)
    {
        $this->autorizarEmpresa($request, $whatsappConfig);

        $whatsappConfig->delete();

        return redirect()
            ->route('whatsapp-config.index')
            ->with('success', 'Configuração de WhatsApp excluída com sucesso!');
    }
}
