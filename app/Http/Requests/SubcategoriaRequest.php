<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubcategoriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nome'         => ['required','string','max:100'],
            'categoria_id' => ['required','integer','exists:appcategoria,id'],
            'subcategoria' => ['nullable','string','max:100'],
            'status'       => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'         => 'Informe o nome da subcategoria.',
            'categoria_id.required' => 'Selecione a categoria.',
            'categoria_id.exists'   => 'Categoria inválida.',
            'status.required'       => 'Informe o status.',
        ];
    }
}
