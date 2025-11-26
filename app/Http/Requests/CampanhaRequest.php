<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampanhaRequest extends FormRequest
{
    /**
     * Autorizar esta request (por enquanto libera geral).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para criação/edição de campanha.
     */
    public function rules(): array
    {
        return [
            'nome'                   => 'required|string|max:100',
            'tipo_id'                => 'required|integer|exists:appcampanhatipo,id',

            'data_inicio'            => 'required|date',
            'data_fim'               => 'required|date|after_or_equal:data_inicio',

            'prioridade'             => 'required|integer|min:1',

            'produto_brinde_id'      => 'nullable|integer|exists:appproduto,id',
            'descricao'              => 'nullable|string',

            // Regras de cupom (usadas dependendo do tipo)
            'valor_base_cupom'       => 'nullable|numeric|min:0',
            'quantidade_minima_cupom'=> 'nullable|integer|min:1',

            // Flags booleanas
            'acumulativa_por_valor'      => 'sometimes|boolean',
            'acumulativa_por_quantidade' => 'sometimes|boolean',
            'ativa'                      => 'sometimes|boolean',
            'cumulativa'                 => 'sometimes|boolean',
            'aplicacao_automatica'       => 'sometimes|boolean',

            // Será sobrescrito no controller, mas deixo aqui para não dar erro se vier no form
            'tipo_acumulacao'        => 'nullable|string|in:valor,quantidade',
        ];
    }

    /**
     * Nomes mais amigáveis para aparecer nas mensagens de erro.
     */
    public function attributes(): array
    {
        return [
            'nome'                    => 'nome da campanha',
            'tipo_id'                 => 'tipo de campanha',
            'data_inicio'             => 'data de início',
            'data_fim'                => 'data de término',
            'prioridade'              => 'prioridade',
            'produto_brinde_id'       => 'produto brinde',
            'descricao'               => 'descrição',
            'valor_base_cupom'        => 'valor base do cupom',
            'quantidade_minima_cupom' => 'quantidade mínima para cupom',
            'acumulativa_por_valor'   => 'acumular por valor',
            'acumulativa_por_quantidade' => 'acumular por quantidade',
            'ativa'                   => 'ativa',
            'cumulativa'              => 'cumulativa',
            'aplicacao_automatica'    => 'aplicação automática',
            'tipo_acumulacao'         => 'tipo de acumulação',
        ];
    }
}
