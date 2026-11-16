<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordCharacters implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/\p{Lu}/u', $value) || !preg_match('/[\p{P}\p{S}]/u', $value)) {
            $fail('La contraseña debe incluir al menos una letra mayúscula y un carácter especial, como . o _.');
        }
    }
}
