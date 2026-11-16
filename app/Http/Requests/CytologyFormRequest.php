<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CytologyFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) return false;

        // Conserva las reglas de acceso de ModuleAccess para cuentas anteriores.
        return !in_array($user->role, ['laboratory_office', 'anatomopathology_office'], true)
            || $user->hasRole('administrator')
            || $user->can('manage cytology reports');
    }

    protected function normalizeText(array $fields): void
    {
        foreach ($fields as $field) {
            if (!is_string($this->input($field)) && $this->input($field) !== null) continue;
            $value = trim((string) $this->input($field, ''));
            $this->merge([$field => $value === '' ? null : mb_strtoupper(
                iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value, 'UTF-8'
            )]);
        }
    }
}
