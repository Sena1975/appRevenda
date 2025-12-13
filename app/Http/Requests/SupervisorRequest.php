<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Supervisor;

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
        // suporta resource com parâmetro {supervisore} ou {supervisor}
        $supervisor = $this->route('supervisore') ?? $this->route('supervisor');
        $id        = $supervisor?->id;

        $empresaId = $this->user()?->empresa_id;

        // pega o nome real da tabela sem “chute”
        $table = (new Supervisor())->getTable();

        return [
            'nome' => ['required','string','max:150'],

            // CPF único por empresa (se informado)
            'cpf' => [
                'nullable',
                'string',
                'size:11',
                Rule::unique($table, 'cpf')
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                    ->ignore($id),
            ],

            'telefone'       => ['nullable','string','max:20'],
            'whatsapp'       => ['nullable','string','max:20'],

            // Email único por empresa (se informado)
            'email' => [
                'nullable',
                'email',
                'max:120',
                Rule::unique($table, 'email')
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                    ->ignore($id),
            ],

            'instagram'      => ['nullable','string','max:255'],
            'facebook'       => ['nullable','string','max:255'],

            // Como você remove máscara e fica só dígitos, o CEP vira 8
            'cep'            => ['nullable','string','size:8'],

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
            'cpf.unique'      => 'Já existe um supervisor com este CPF nesta empresa.',
            'email.email'     => 'Informe um e-mail válido.',
            'email.unique'    => 'Já existe um supervisor com este e-mail nesta empresa.',
            'estado.size'     => 'Estado (UF) deve ter 2 letras.',
            'cep.size'        => 'CEP deve conter 8 dígitos.',
            'status.required' => 'Informe o status.',
        ];
    }
}
