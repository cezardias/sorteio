<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Rules\V1\StripTags;
use App\Rules\V1\RifaMinNumbers;

class UpdateRifasRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        $adminUser = Auth::user();

        $isAllowed = true;
        $isNotAllowed = false;

        if (!$adminUser) {
            return $isNotAllowed;
        }

        if (!$adminUser->tokenCan("update")) {
            return $isNotAllowed;
        }

        return $isAllowed;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array {

        $method = $this->method();

        if ($method === "PUT") {
            return [
                "title" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "description" => ["required", "bail", "min:1", new StripTags(), "max:20000"],
                "rifaStatus" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "rifaDate" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "price" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "firstPacoteNumbers" => ["sometimes", "required", "bail", new StripTags(), "max:255"],
                "firstPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "secondPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "secondPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "thirdPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "thirdPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "fourthPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "fourthPacoteDiscount" => ["bail", "max:255", "nullable"],
                "thumbnail.*" => ["required", "bail", "image", "mimes:jpeg,png,jpg,webp", "max:10000"],
                "rifaNumbers" => ["required", "bail", "numeric", "integer", new RifaMinNumbers()],
            ];
        } else if ($method === "PATCH" || $method === "POST") {
            return [
                "title" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "description" => ["required", "bail", "min:1", new StripTags(), "max:20000"],
                "rifaStatus" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "rifaDate" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "price" => ["required", "bail", "min:1", new StripTags(), "max:255"],
                "firstPacoteNumbers" => ["sometimes", "required", "bail", new StripTags(), "max:255"],
                "firstPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "secondPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "secondPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "thirdPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "thirdPacoteDiscount" => ["bail",  "max:255", "nullable"],
                "fourthPacoteNumbers" => ["sometimes", "required", "bail",  new StripTags(), "max:255"],
                "fourthPacoteDiscount" => ["bail", "max:255", "nullable"],
                // "thumbnail.*" => ["sometimes", "required", "bail", "image", "mimes:jpeg,png,jpg,webp", "max:10000"],
                "rifaNumbers" => ["required", "bail", "numeric", "integer", new RifaMinNumbers()],
            ];
        }
    }

    public function messages() {
        return [
            "required" => "O campo :attribute é obrigatório.",
            "min" => [
                "string" => "O campo :attribute deve ter pelo menos :min caracteres.",
            ],
            "max" => [
                "string" => "O campo :attribute não deve ter mais que :max caracteres.",
            ],
            "image" => "O campo :attribute deve ser uma imagem.",
        ];
    }

    public function attributes() {
        return [
            "title" => "Título",
            "description" => "Descrição",
            "thumbnail" => "Thumbnail",
            "rifaStatus" => "Status",
            "rifaDate" => "Data do sorteio",
            "price" => "Preço do Número",
            "firstPacoteNumbers" => "Números do primeiro pacote",
            "firstPacoteDiscount" => "Desconto do primeiro pacote",
            "secondPacoteNumbers" => "Números do segundo pacote",
            "secondPacoteDiscount" => "Desconto do segundo pacote",
            "thirdPacoteNumbers" => "Números do terceiro pacote",
            "thirdPacoteDiscount" => "Desconto do terceiro pacote",
            "fourthPacoteNumbers" => "Números do quarto pacote",
            "fourthPacoteDiscount" => "Desconto do quarto pacote",
            "rifaNumbers" => "Quantidade de Números",
        ];
    }
}
