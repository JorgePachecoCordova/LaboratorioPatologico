<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PacienteController extends Controller
{
    /**
     * Registra un paciente desde el modal AJAX.
     *
     * El nombre del controlador se conserva porque las rutas actuales ya lo usan,
     * pero el modelo, la tabla y los campos internos están en inglés.
     */
    public function store(Request $request): JsonResponse
    {
        // Normaliza antes de comprobar duplicados; nunca elimina números del nombre.
        foreach (['identity_document', 'full_name', 'phone', 'email', 'address'] as $field) {
            if (is_string($request->input($field))) {
                $value = trim($request->input($field));
                if ($field === 'full_name') $value = preg_replace('/\s+/u', ' ', $value);
                if ($field === 'identity_document') $value = mb_strtoupper($value, 'UTF-8');
                $request->merge([$field => $value]);
            }
        }
        $validated = $request->validate([
            'identity_document' => [
                'required',
                'string',
                'max:30',
                'regex:/\A[\p{L}\p{N}]+(?:[ .\/-][\p{L}\p{N}]+)*\z/u',
                Rule::unique('patients', 'identity_document'),
            ],
            'full_name' => ['bail', 'required', 'string', 'max:180', new \App\Rules\PersonName()],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:'.now()->subYears(130)->format('Y-m-d')],
            'sex' => ['nullable', Rule::in(['M', 'F'])],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/\A\+?[0-9() .-]+\z/', function ($attribute, $value, $fail) {
                $digits = preg_replace('/\D/', '', (string) $value);
                if (strlen($digits) < 6 || strlen($digits) > 15) $fail('El teléfono debe contener entre 6 y 15 dígitos.');
            }],
            'email' => ['nullable', 'email', 'max:180'],
        ], [
            'identity_document.required' => 'El CI es obligatorio.',
            'identity_document.unique' => 'Ya existe un paciente registrado con ese CI.',
            'identity_document.regex' => 'El CI solo puede contener letras, números y separadores de identificación (espacio, punto, guion o barra).',
            'full_name.required' => 'Los nombres completos son obligatorios.',
            'birth_date.required' => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before_or_equal' => 'La fecha de nacimiento no puede ser futura.',
            'birth_date.date_format' => 'Ingrese una fecha de nacimiento válida.',
            'birth_date.after_or_equal' => 'Revise la fecha de nacimiento: la edad no puede superar los 130 años.',
            'phone.regex' => 'Ingrese un teléfono válido, sin letras.',
            'sex.in' => 'Seleccione un sexo válido.',
            'email.email' => 'El correo electrónico no es válido.',
        ]);

        $patient = DB::transaction(function () use ($validated): Patient {
            return Patient::query()->create([
                'identity_document' => trim($validated['identity_document']),
                'full_name' => $this->normalizeFullName($validated['full_name']),
                'birth_date' => $validated['birth_date'],
                'sex' => $validated['sex'] ?? null,
                'address' => $this->nullableTrim($validated['address'] ?? null),
                'phone' => $this->nullableTrim($validated['phone'] ?? null),
                'email' => $this->nullableTrim($validated['email'] ?? null),
                'is_active' => true,
            ]);
        }, 5);

        return response()->json([
            'success' => true,
            'message' => 'Paciente registrado correctamente.',
            'patient' => [
                'id' => $patient->id,
                'identity_document' => $patient->identity_document,
                'full_name' => $patient->full_name,
                'birth_date' => $patient->birth_date?->format('Y-m-d'),
                'current_age' => $patient->current_age,
                'sex' => $patient->sex,
                'address' => $patient->address,
                'phone' => $patient->phone,
                'email' => $patient->email,
                'last_request_number' => null,
                'last_sequence_year' => null,
            ],
        ], 201);
    }

    private function normalizeFullName(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?: trim($value);
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
