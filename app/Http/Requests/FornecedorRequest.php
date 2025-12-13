<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FornecedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $soDigitos = fn($v) => $v ? preg_replace('/\D+/', '', $v) : $v;

        $this->merge([
            'cnpj'     => $soDigitos($this->input('cnpj')),
            'telefone' => $soDigitos($this->input('telefone')),
            'whatsapp' => $soDigitos($this->input('whatsapp')),
        ]);
    }

    public function rules(): array
    {
        // pega o model vindo da rota (seja {fornecedore} ou {fornecedor})
        $fornecedor = $this->route('fornecedore') ?? $this->route('fornecedor');
        $id = $fornecedor?->id;

        // empresa atual (pelo usuário logado)
        $empresaId = $this->user()?->empresa_id;

        return [
            'razaosocial'   => ['required', 'string', 'max:150'],
            'nomefantasia'  => ['nullable', 'string', 'max:150'],
            'cnpj'          => [
                'required',
                'string',
                'size:14', // CNPJ com apenas dígitos
                Rule::unique('appfornecedor', 'cnpj')
                    ->where(fn($q) => $q->where('empresa_id', $empresaId))
                    ->ignore($id),
            ],
            'pessoacontato' => ['nullable', 'string', 'max:100'],
            'telefone'      => ['nullable', 'string', 'max:20'],
            'whatsapp'      => ['nullable', 'string', 'max:20'],
            'telegram'      => ['nullable', 'string', 'max:50'],
            'instagram'     => ['nullable', 'string', 'max:100'],
            'facebook'      => ['nullable', 'string', 'max:100'],
            'email'         => ['nullable', 'string', 'email', 'max:120'],
            'endereco'      => ['nullable', 'string', 'max:200'],
            'status'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'razaosocial.required' => 'Informe a razão social.',
            'cnpj.required'        => 'Informe o CNPJ (apenas dígitos).',
            'cnpj.size'            => 'O CNPJ deve conter 14 dígitos.',
            'cnpj.unique'          => 'Já existe um fornecedor com este CNPJ.',
            'email.email'          => 'Informe um e-mail válido.',
            'status.required'      => 'Informe o status.',
        ];
    }
}
