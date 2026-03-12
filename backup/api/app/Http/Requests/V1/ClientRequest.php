<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
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
            "name" => ["required", "max:255"],
            "surname" => ["required", "max:255"],
            "cellphone" => ["required", "max:255"],
        ];
    }

    public function messages(): array {
        return [
            'name.required' => 'Nome é obrigatório',
            'surname.required' => 'Telefone é obrigatório',
            'cellphone.required' => 'CPF é obrigatório',
        ];
    }
}
