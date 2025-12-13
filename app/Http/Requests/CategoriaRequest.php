<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $categoria  = $this->route('categoria'); // model binding
        $id         = $categoria?->id;
        $empresaId  = $this->user()?->empresa_id;

        return [
            'nome' => [
                'required','string','max:100',
                Rule::unique('appcategoria', 'nome')
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                    ->ignore($id),
            ],
            'categoria' => ['nullable','string','max:100'],
            'status'    => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'   => 'Informe o nome da categoria.',
            'nome.unique'     => 'Já existe uma categoria com este nome nesta empresa.',
            'status.required' => 'Informe o status.',
        ];
    }
}
