<?php

namespace App\Http\Requests\v1;

use Illuminate\Foundation\Http\FormRequest;

class OrderRifaRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'numbers' => 'nullable',
            'status' => 'nullable|integer|in:0,1,2',
            'rifas_id' => 'required|exists:rifas,id',
            'pay_id' => 'nullable',
            'client_id' => 'required|exists:clients,id',
            'value' => 'required|numeric'
        ];
    }
    public function messages() {
        return [
            'numbers.json' => 'O campo números deve ser um JSON válido.',
            'status.required' => 'O campo status é obrigatório.',
            'status.integer' => 'O campo status deve ser um número inteiro.',
            'status.in' => 'O campo status deve ser um dos seguintes valores: 0 (pendente), 1 (pago), 2 (não aprovado).',
            'rifas_id.required' => 'O campo rifas_id é obrigatório.',
            'rifas_id.exists' => 'O rifas_id fornecido não existe.',
            'pay_id.required' => 'O campo pay_id é obrigatório.',
            'pay_id.exists' => 'O pay_id fornecido não existe.',
            'client_id.required' => 'O campo client_id é obrigatório.',
            'client_id.exists' => 'O client_id fornecido não existe.',
            'value.required' => 'O campo value é obrigatório.',
            'value.integer' => 'O campo value deve ser um número inteiro.',
        ];
    }
}
