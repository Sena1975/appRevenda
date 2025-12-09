<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FormaPagamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user      = $this->user();
        $empresaId = $user?->empresa_id;

        // Quando for edição, vem o model tipado na rota: formapagamento/{formapagamento}
        $forma = $this->route('formapagamento');
        $id    = $forma?->id ?? null;

        return [
            'nome' => [
                'required',
                'string',
                'max:100',
                Rule::unique('appformapagamento', 'nome')
                    ->ignore($id) // ignora o próprio registro na edição
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],

            // mantenha as demais regras que você já tinha:
            'ativo'         => ['nullable', 'boolean'],
            'gera_receber'  => ['nullable', 'boolean'],
            'gera_pagar'    => ['nullable', 'boolean'],
            // ... (outros campos que você já usa)
        ];
    }

    public function messages(): array
    {
        return [
            'nome.unique' => 'Já existe uma forma com este nome para esta empresa.',
            // demais mensagens se quiser...
        ];
    }
}
