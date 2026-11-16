<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PathologyRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailyRegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:2025-01-01'],
            'module' => ['sometimes', 'required', Rule::in(['R', 'U', 'G'])],
            'print' => ['sometimes', 'boolean'],
        ], ['date.after_or_equal' => 'El registro diario está disponible desde la gestión 2025.']);

        $date = $validated['date'] ?? now()->format('Y-m-d');
        $module = $validated['module'] ?? PathologyRequest::SEQUENCE_RURAL;
        $areas = ['R' => 'ÁREA RURAL', 'U' => 'ÁREA URBANA', 'G' => 'GINECOLOGÍA'];
        $printing = $request->boolean('print');
        $recordsQuery = PathologyRequest::query()
            ->with(['patient', 'municipality', 'residenceMunicipality', 'healthFacility', 'samples.pathologyResult.diagnoses'])
            ->where('module', $module)
            ->whereDate('request_date', $date)
            ->orderBy('sequence_number')->orderBy('id');
        $records = $printing ? $recordsQuery->get() : $recordsQuery->paginate(25)->withQueryString();

        return response()->view('solicitudes.daily-register', compact('records', 'date', 'module', 'areas', 'printing'))
            ->header('Cache-Control', 'private, no-store');
    }
}
