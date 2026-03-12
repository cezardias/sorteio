<?php

namespace App\Rules\V1;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RifaMinNumbers implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = strip_tags($value);

        if ($value < 0) {
            $fail("O campo $attribute tem que ser maior ou igual a 0.");
        }


        if ($value > 1000000) {
            $fail("O campo $attribute não pode ser maior do que 1000000.");
        }

        return;
    }
}
