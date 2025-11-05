<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use App\Models\FormaPagamento;

class PlanoPagamentoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        // Coerção para inteiros nulos ou >=0
        $int = fn($v) => is_null($v) || $v === '' ? null : (int)$v;

        $this->merge([
            'parcelas'   => $int($this->input('parcelas')),
            'prazo1'     => $int($this->input('prazo1')),
            'prazo2'     => $int($this->input('prazo2')),
            'prazo3'     => $int($this->input('prazo3')),
            'prazomedio' => $int($this->input('prazomedio')),
            'ativo'      => $this->boolean('ativo'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('planopagamento')->id ?? null;

        return [
            'codplano'          => ['required','string','max:20', Rule::unique('appplanopagamento','codplano')->ignore($id)],
            'descricao'         => ['required','string','max:100'],
            'formapagamento_id' => ['required','integer','exists:appformapagamento,id'],
            'parcelas'          => ['required','integer','min:1','max:120'],
            'prazo1'            => ['nullable','integer','min:0'],
            'prazo2'            => ['nullable','integer','min:0'],
            'prazo3'            => ['nullable','integer','min:0'],
            'prazomedio'        => ['nullable','integer','min:0'],
            'ativo'             => ['required','boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function(Validator $v){
            $forma = FormaPagamento::find($this->input('formapagamento_id'));
            if ($forma && $this->input('parcelas') > $forma->max_parcelas) {
                $v->errors()->add('parcelas', "Quantidade de parcelas excede o máximo ({$forma->max_parcelas}) da forma selecionada.");
            }

            // Regras simples para prazos conforme quantidade de parcelas
            $parcelas = (int)$this->input('parcelas');
            $p1 = $this->input('prazo1'); $p2 = $this->input('prazo2'); $p3 = $this->input('prazo3');

            if ($parcelas >= 1 && is_null($p1)) $v->errors()->add('prazo1','Informe o prazo da 1ª parcela.');
            if ($parcelas >= 2 && is_null($p2)) $v->errors()->add('prazo2','Informe o prazo da 2ª parcela.');
            if ($parcelas >= 3 && is_null($p3)) $v->errors()->add('prazo3','Informe o prazo da 3ª parcela.');

            // Se não informar prazomedio, calculo a média dos prazos informados
            if (is_null($this->input('prazomedio'))) {
                $ativos = array_filter([$p1,$p2,$p3], fn($n)=>$n!==null);
                if (count($ativos) > 0) {
                    $media = (int) floor(array_sum($ativos)/count($ativos));
                    $this->merge(['prazomedio' => $media]);
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'codplano.required'          => 'Informe o código do plano.',
            'codplano.unique'            => 'Já existe um plano com este código.',
            'descricao.required'         => 'Informe a descrição do plano.',
            'formapagamento_id.required' => 'Selecione a forma de pagamento.',
            'parcelas.required'          => 'Informe a quantidade de parcelas.',
            'prazo1.min'                 => 'Prazo não pode ser negativo.',
            'prazo2.min'                 => 'Prazo não pode ser negativo.',
            'prazo3.min'                 => 'Prazo não pode ser negativo.',
        ];
    }
}
