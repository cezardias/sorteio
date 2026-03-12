<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Rules\V1\StripTags;
use App\Rules\V1\RifaMinNumbers;

class StoreRifasRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true; // Adapte conforme sua lógica de autorização
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            "title" => ["required", "bail", "min:1", "max:255"],
            "description_resume" => ["required", "bail", "min:1", "max:20000"],
            "show_site" => ["required", "bail"],
            "emphasis" => ["required", "bail"],
            "show_top" => ["required", "bail", "max:255"],
            "video" => ["nullable", "bail", "max:255"],
            // "img" => ["required", "bail", "image", "mimes:jpeg,png,jpg,webp", "max:10000"],
            "status" => ["nullable", "bail", "max:255"],
            "price" => ["required", "bail", "min:0"],
            "description_sortition" => ["required", "bail", "min:1"],
            "description_product" => ["required", "bail", "min:1"],
            "description_role" => ["required", "bail", "min:1"],
            "data_sortition" => ["nullable", "bail", "date"],
            "initial_sale" => ["nullable", "bail", "date"],
            "end_sale" => ["nullable", "bail", "date"],
            "cota.qntd_cota" => ["required", "bail", "min:0"],
            "cota.qntd_cota_min_order" => ["required", "bail", "min:0"],
            "cota.qntd_cota_max_order" => ["required", "bail", "min:0"],
            "cota.qntd_cota_max_client" => ["required", "bail", "min:0"],
            "rifa_payment.time_pay" => ["required", "bail", "min:0"],
            "gateway" => ["nullable", "bail", "max:255"],
            "service_charge" => ["nullable",  "bail", "max:255"],
            "text_service_charge" => ["nullable", "bail", "max:255"],
            "cotas_double" => ["nullable", "bail", "max:255"],
            "text_cotas_double" => ["nullable", "bail", "max:255"],
            "title_cotas_awarded" => ["nullable", "bail", "max:255"],
            "description_cotas_awarded" => ["nullable", "bail", "max:255"],
            "title_upsell" => ["nullable", "bail", "max:255"],
            "description_upsell" => ["nullable", "bail", "max:255"],
            "facebook_pixel" => ["nullable", "bail", "max:255"],
            "facebook_token" => ["nullable", "bail", "max:255"],
            "tiktok_pixel" => ["nullable", "bail", "max:255"],
            "whatsapp_group" => ["nullable", "bail", "max:255"],
            "link_ebook" => ["nullable", "bail", "max:255"],
            "nota_fiscal" => ["nullable", "bail", "max:255"],
            'package_awarded_number' => ['nullable', 'array', "bail"],
            'qntd_number' => ['nullable', 'integer', "bail"],
            'price_packet' => ['nullable', "bail"],
        ];
    }

    public function messages() {
        return [
            "required" => "O campo :attribute é obrigatório.",
            "min" => [
                "string" => "O campo :attribute deve ter pelo menos :min caracteres.",
                "integer" => "O campo :attribute deve ser no mínimo :min.",
            ],
            "max" => [
                "string" => "O campo :attribute não deve ter mais que :max caracteres.",
            ],
            "image" => "O campo :attribute deve ser uma imagem.",
            "date" => "O campo :attribute deve ser uma data válida.",
            "numeric" => "O campo :attribute deve ser numérico.",
            "integer" => "O campo :attribute deve ser um número inteiro.",
            "in" => "O valor selecionado para o campo :attribute é inválido.",
        ];
    }

    public function attributes() {
        return [
            "title" => "Título",
            "description_resume" => "Resumo da Descrição",
            "show_site" => "Mostrar no Site",
            "emphasis" => "Ênfase",
            "show_top" => "Mostrar no Topo",
            "video" => "Vídeo",
            "img" => "Imagem",
            "status" => "Status",
            "price" => "Preço do Número",
            "description_sortition" => "Descrição do Sorteio",
            "description_product" => "Descrição do Produto",
            "description_role" => "Descrição das Regras",
            "data_sortition" => "Data do Sorteio",
            "initial_sale" => "Início das Vendas",
            "end_sale" => "Fim das Vendas",
            "qntd_cota" => "Quantidade de Cotas",
            "qntd_cota_digit" => "Quantidade de Dígitos das Cotas",
            "value_unit" => "Valor Unitário",
            "qntd_cota_max_order" => "Quantidade Máxima de Cotas por Pedido",
            "qntd_cota_max_client" => "Quantidade Máxima de Cotas por Cliente",
            "time_pay" => "Tempo de Pagamento",
            "gateway" => "Tipo de Pagamento",
            "service_charge" => "Taxa de Serviço",
            "text_service_charge" => "Texto da Taxa de Serviço",
            "cotas_double" => "Cotas Duplas",
            "text_cotas_double" => "Texto das Cotas Duplas",
            "title_cotas_awarded" => "Título das Cotas Premiados",
            "description_cotas_awarded" => "Descrição das Cotas Premiados",
            "title_upsell" => "Título do Upsell",
            "description_upsell" => "Descrição do Upsell",
            "facebook_pixel" => "Pixel do Facebook",
            "facebook_token" => "Token do Facebook",
            "tiktok_pixel" => "Pixel do TikTok",
            "whatsapp_group" => "Grupo do WhatsApp",
            "flink_ebook" => "Link do eBook",
            "nota_fiscal" => "Nota Fiscal",
            "rifaNumbers" => "Quantidade de Números",
        ];
    }
}
