<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Support\Facades\DB;

class SaveCytologyRequest extends CytologyFormRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && !$this->route('citologia')?->request_number;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('report_action') === 'validate') $this->merge(['report_date' => now()->format('Y-m-d')]);
        $this->normalizeText(['patient_name', 'clinical_diagnosis', 'clinical_data', 'sample_type_other', 'description', 'conclusion', 'note']);
    }

    public function rules(): array
    {
        return [
            'report_action' => ['sometimes', 'required', 'in:validate'],
            'report_date' => ['required_with:report_action', 'nullable', 'date'],
            'description' => ['nullable', 'string', 'max:20000'],
            'conclusion' => ['nullable', 'string', 'max:20000'],
            'note' => ['nullable', 'string', 'max:10000'],
            'request_date' => ['required', 'date', 'after_or_equal:2025-01-01'],
            'patient_name' => ['bail', 'required', 'string', 'max:180', new \App\Rules\PersonName()],
            'patient_age' => ['required', 'integer', 'min:0', 'max:130'],
            'care_type' => ['nullable', \Illuminate\Validation\Rule::in(array_merge(['external', 'hospitalized'], DB::table('cytology_catalog_options')->where('type', 'care')->pluck('name')->all()))],
            'bed_number' => ['nullable', 'string', 'max:30'],
            'registration_number' => ['prohibited'],
            'clinical_history_number' => ['nullable', 'string', 'max:50'],
            'hospital_service_id' => ['required', 'integer', 'exists:hospital_services,id'],
            'requesting_physician_id' => ['nullable', 'integer', 'exists:physicians,id'],
            'sample_type_code' => ['required', 'string', 'max:60'],
            'sample_type_other' => ['nullable', 'string', 'max:180'],
            'sample_taken_at' => ['nullable', 'date_format:H:i'],
            'sample_received_at' => ['nullable', 'date_format:H:i'],
            'clinical_diagnosis' => ['nullable', 'string', 'max:10000'],
            'clinical_data' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_date.after_or_equal' => 'La fecha de solicitud debe ser del 1 de enero de 2025 en adelante.',
            'patient_name.required' => 'El nombre del paciente es obligatorio.',
            'sample_type_code.required' => 'Seleccione el tipo de muestra.',
        ];
    }
}
