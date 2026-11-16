<?php

namespace App\Http\Controllers;

use App\Models\CytologyDiagnosis;
use App\Support\AccessRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class ResultDiagnosisController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_active && AccessRoles::writesResults($request->user()), 403);
        $request->merge([
            'code' => mb_strtoupper(trim((string) $request->input('code'))),
            'description' => mb_strtoupper(trim((string) $request->input('description'))),
        ]);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9.\-\/]*$/', 'unique:cytology_diagnoses,code'],
            'description' => ['required', 'string', 'max:10000'],
        ], [
            'code.required' => 'Ingrese el código del diagnóstico.',
            'code.unique' => 'Este código ya existe en el catálogo. Selecciónelo en el buscador.',
            'code.regex' => 'Use letras, números, punto, guion o barra, sin espacios.',
            'description.required' => 'Ingrese la descripción del diagnóstico.',
        ]);
        try {
            $diagnosis = CytologyDiagnosis::create($data + ['is_active' => true]);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['code' => 'Este código ya existe en el catálogo.']);
        }
        return response()->json($diagnosis->only(['id', 'code', 'description']), 201);
    }
}
