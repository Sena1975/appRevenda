<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nome'      => ['required','string','max:100'],
            'categoria' => ['nullable','string','max:100'],
            'status'    => ['required','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'   => 'Informe o nome da categoria.',
            'status.required' => 'Informe o status.',
        ];
    }
}
