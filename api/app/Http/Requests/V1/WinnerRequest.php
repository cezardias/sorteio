<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class WinnerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ticket' => 'required',
            'draw_day' => 'required',
            'img' => 'required',
            'rifas_id' => ['required', 'exists:rifas,id'],
            'client_id' => ['required', 'exists:clients,id'],
        ];
    }
}
