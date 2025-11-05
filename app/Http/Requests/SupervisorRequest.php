<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $dig = fn($v) => $v ? preg_replace('/\D+/', '', $v) : $v;
        $this->merge([
            'cpf'      => $dig($this->input('cpf')),
            'telefone' => $dig($this->input('telefone')),
            'whatsapp' => $dig($this->input('whatsapp')),
            'cep'      => $this->input('cep') ? preg_replace('/\D+/', '', $this->input('cep')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nome'           => ['required','string','max:150'],
            'cpf'            => ['nullable','string','size:11'], // 11 dígitos
            'telefone'       => ['nullable','string','max:20'],
            'whatsapp'       => ['nullable','string','max:20'],
            'email'          => ['nullable','email','max:120'],
            'instagram'      => ['nullable','string','max:255'],
            'facebook'       => ['nullable','string','max:255'],
            'cep'            => ['nullable','string','max:9'],
            'endereco'       => ['nullable','string','max:150'],
            'bairro'         => ['nullable','string','max:100'],
            'cidade'         => ['nullable','string','max:100'],
            'estado'         => ['nullable','string','size:2'],
            'datanascimento' => ['nullable','date'],
            'status'         => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'   => 'Informe o nome.',
            'cpf.size'        => 'CPF deve conter 11 dígitos.',
            'email.email'     => 'Informe um e-mail válido.',
            'estado.size'     => 'Estado (UF) deve ter 2 letras.',
            'status.required' => 'Informe o status.',
        ];
    }
}
