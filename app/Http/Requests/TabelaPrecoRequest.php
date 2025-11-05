<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\TabelaPreco;
use Illuminate\Support\Facades\DB;

class TabelaPrecoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajuste se usar policies
    }

    public function rules(): array
    {
        return [
            'produto_id'    => ['required','integer','exists:appproduto,id'],
            'codfab'        => ['nullable','string','max:50'],
            'preco_compra'  => ['nullable','numeric','min:0'],
            'preco_revenda' => ['required','numeric','min:0.01'],
            'pontuacao'     => ['required','integer','min:0'],
            'data_inicio'   => ['required','date'],
            'data_fim'      => ['required','date','after_or_equal:data_inicio'],
            'status'        => ['required','boolean'], // 0 ou 1
        ];
    }

    public function messages(): array
    {
        return [
            'produto_id.required'    => 'Selecione o produto.',
            'produto_id.exists'      => 'Produto inválido.',
            'preco_revenda.required' => 'Informe o preço de revenda.',
            'preco_revenda.min'      => 'Preço de revenda deve ser maior que zero.',
            'pontuacao.required'     => 'Informe a pontuação.',
            'data_inicio.required'   => 'Informe a data de início.',
            'data_fim.required'      => 'Informe a data de fim.',
            'data_fim.after_or_equal'=> 'A data fim deve ser igual ou posterior à data início.',
            'status.required'        => 'Informe o status (Ativo/Inativo).',
        ];
    }

    /**
     * Validação extra: não permitir sobreposição de vigência
     * para o mesmo produto quando status=1 (ativo).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $status = (int) $this->input('status', 0);
            if ($status !== 1) {
                return; // só bloqueia sobreposição para ativos
            }

            $produtoId  = (int) $this->input('produto_id');
            $inicioNovo = $this->input('data_inicio');
            $fimNovo    = $this->input('data_fim');

            if (!$produtoId || !$inicioNovo || !$fimNovo) {
                return;
            }

            $ignoreId = $this->route('tabelapreco')?->id ?? $this->route('id') ?? null;

            // Regra de interseção de intervalos:
            // Sobrepõe se NÃO (novo_fim < inicio_existente OU novo_inicio > fim_existente)
            $sobrepoe = TabelaPreco::query()
                ->where('produto_id', $produtoId)
                ->where('status', 1)
                ->when($ignoreId, fn($q) => $q->where('id', '<>', $ignoreId))
                ->where(function ($q) use ($inicioNovo, $fimNovo) {
                    $q->where(function ($q2) use ($inicioNovo, $fimNovo) {
                        $q2->where('data_inicio', '<=', $fimNovo)
                           ->where('data_fim',   '>=', $inicioNovo);
                    });
                })
                ->exists();

            if ($sobrepoe) {
                $v->errors()->add('data_inicio', 'Já existe uma Tabela de Preço ativa que se sobrepõe a este período para o produto selecionado.');
                $v->errors()->add('data_fim',    'Ajuste as datas ou inative a outra vigência.');
            }
        });
    }
}
