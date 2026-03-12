<?php

namespace App\Rules\V1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StripTags implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = strip_tags($value);

        if ($attribute === "content") {
            $attribute = "Conteúdo";
        } else if ($attribute === "title") {
            $attribute = "Título";
        }

        if (!$value) {
            $fail("O campo $attribute é obrigatório.");
        }
    }
}
