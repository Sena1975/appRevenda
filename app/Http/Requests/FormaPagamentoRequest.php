<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormaPagamentoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('formapagamento')->id ?? null;

        return [
            'nome'          => ['required','string','max:60', Rule::unique('appformapagamento','nome')->ignore($id)],
            'gera_receber'  => ['required','boolean'],
            'max_parcelas'  => ['required','integer','min:1','max:120'],
            'ativo'         => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'         => 'Informe o nome da forma de pagamento.',
            'nome.unique'           => 'Já existe uma forma com este nome.',
            'max_parcelas.min'      => 'Máximo de parcelas deve ser ao menos 1.',
            'max_parcelas.max'      => 'Máximo de parcelas não deve ultrapassar 120.',
            'gera_receber.required' => 'Informe se gera Contas a Receber.',
            'ativo.required'        => 'Informe se a forma está ativa.',
        ];
    }
}
