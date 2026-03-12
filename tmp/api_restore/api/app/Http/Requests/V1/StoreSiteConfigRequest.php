<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Rules\V1\StripTags;
use App\Rules\V1\RifaMinNumbers;

class StoreSiteConfigRequest extends FormRequest
{
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

        if (!$adminUser->tokenCan("create")) {
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
        return [
        ];
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
            "plataforma" => "Nome da plataforma",
            "nomesite" => "Nome",
            "thumbnail" => "Thumbnail",
            "logosite" => "Logo do site",
            "faviconsite" => "Favicon do site",
            "linkwppsite" => "Link do WhatsApp",
            "linkinstasite" => "Link do Instagram",
            "email" => "Email de login",
            "password" => "Senha de login",
            "publickeymercado" => "Acesso Token public (Mercado Pago)",
            "secretmercadopago" => "Acesso Token (Mercado Pago)",
            "pixel" => "Pixel"
        ];
    }
}
