<?php

declare(strict_types=1);

namespace App\Http\Requests;

class SaveCytologyReportRequest extends CytologyFormRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && !$this->route('citologia')?->report_number;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['report_date' => $this->route('citologia')?->report_date?->format('Y-m-d') ?? now()->format('Y-m-d')]);
        $this->normalizeText(['description', 'conclusion', 'note']);
    }

    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:20000'],
            'conclusion' => ['nullable', 'string', 'max:20000'],
            'note' => ['nullable', 'string', 'max:10000'],
            'report_action' => ['required', 'in:draft,validate'],
        ];
    }
}
