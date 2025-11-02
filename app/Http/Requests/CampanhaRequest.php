<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampanhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'ativa' => (bool) $this->boolean('ativa'),
            'cumulativa' => (bool) $this->boolean('cumulativa'),
            'aplicacao_automatica' => (bool) $this->boolean('aplicacao_automatica'),
            'acumulativa_por_valor' => (bool) $this->boolean('acumulativa_por_valor'),
            'acumulativa_por_quantidade' => (bool) $this->boolean('acumulativa_por_quantidade'),
        ]);
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string',
            'tipo_id' => 'required|integer|exists:appcampanha_tipo,id',
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'ativa' => 'required|boolean',
            'cumulativa' => 'required|boolean',
            'aplicacao_automatica' => 'required|boolean',
            'prioridade' => 'required|integer|min:1',
            'valor_base_cupom' => 'nullable|numeric|min:0',
            'acumulativa_por_valor' => 'required|boolean',
            'acumulativa_por_quantidade' => 'required|boolean',
            'quantidade_minima_cupom' => 'nullable|integer|min:1',
            'tipo_acumulacao' => 'nullable|in:valor,quantidade',
            'produto_brinde_id' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_id.exists' => 'Tipo de campanha inválido.',
            'data_fim.after_or_equal' => 'Data fim deve ser maior ou igual à data início.',
        ];
    }
}
