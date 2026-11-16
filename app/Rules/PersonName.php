<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class PersonName implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Admite tildes, ñ, nombres compuestos, iniciales, apóstrofos y guiones.
        if (!is_string($value) || !preg_match('/\A[\p{L}\p{M} .\x{0027}\x{2019}-]+\z/u', trim($value)) || preg_match_all('/\p{L}/u', $value) < 2) {
            $fail('El nombre debe contener al menos dos letras y no puede incluir números ni símbolos ajenos a un nombre.');
        }
    }
}
