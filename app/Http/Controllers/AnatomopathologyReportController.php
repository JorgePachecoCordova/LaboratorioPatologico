<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AnatomopathologyReport;
use App\Models\HospitalService;
use App\Models\Physician;
use App\Support\AnatomopathologyCapProtocol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnatomopathologyReportController extends Controller
{
    public function storeCatalogOption(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:service,physician,protocol'],
            'name' => ['required', 'string', 'max:200'],
        ]);
        if ($data['type'] === 'protocol') {
            $name = \App\Models\CapProtocolField::normalize($data['name']);
            validator(['name' => $name], ['name' => ['required', 'string', 'max:180']])->validate();
            $option = \App\Models\CapProtocolField::query()->firstOrCreate(
                ['normalized_name' => $name],
                ['name' => $name, 'is_active' => true],
            );
            if (!$option->is_active) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'name' => 'Este protocolo está desactivado. Solicite su activación en Configuración.',
                ]);
            }
            return response()->json(['option' => ['id' => $option->id, 'name' => $option->name]], $option->wasRecentlyCreated ? 201 : 200);
        }
        $name = mb_strtoupper(Str::ascii(trim($data['name'])), 'UTF-8');
        $service = $data['type'] === 'service';
        if (!$service) validator(['name' => $data['name']], ['name' => [new \App\Rules\PersonName()]])->validate();
        $table = $service ? 'hospital_services' : 'physicians';
        $column = $service ? 'normalized_name' : 'full_name';
        validator(['name' => $name], ['name' => ['required', \Illuminate\Validation\Rule::unique($table, $column)]],
            ['name.unique' => 'Ya existe una opción con ese nombre.'])->validate();
        $option = $service
            ? HospitalService::create(['name' => $name, 'normalized_name' => $name, 'is_active' => true])
            : Physician::create(['full_name' => $name, 'is_active' => true]);

        return response()->json(['option' => ['id' => $option->id, 'name' => $name]], 201);
    }

    public function index(Request $request): View
    {
        $query = mb_strtoupper(Str::ascii(trim((string) $request->query('q', ''))), 'UTF-8');
        $base = AnatomopathologyReport::query();
        $report = $request->integer('registro') ? (clone $base)->find($request->integer('registro')) : null;
        $newPatientSource = $request->integer('nuevo_para')
            ? (clone $base)->findOrFail($request->integer('nuevo_para')) : null;
        if ($newPatientSource) {
            $report = null;
            $query = '';
        }

        $matches = null;
        if ($query !== '') {
            $like = '%' . mb_strtolower($query) . '%';
            $matches = (clone $base)->where(function ($builder) use ($like): void {
                $exact = trim($like, '%');
                $builder->whereRaw('LOWER(report_number) = ?', [$exact])
                    ->orWhereRaw("LOWER(COALESCE(registration_number, '')) = ?", [$exact])
                    ->orWhereRaw('LOWER(patient_name) LIKE ?', [$like]);
            })->latest('id')->paginate(10)->withQueryString();
            if (!$report && $matches->total() === 1) {
                $report = $matches->first();
            }
        }
        $total = (clone $base)->count();
        $position = $report ? AnatomopathologyReport::query()->where('id', '<=', $report->id)->count() : 0;
        $previous = $report ? AnatomopathologyReport::query()->where('id', '<', $report->id)->latest('id')->first(['id']) : null;
        $next = $report ? AnatomopathologyReport::query()->where('id', '>', $report->id)->oldest('id')->first(['id']) : null;
        $services = HospitalService::query()->active()->orderBy('name')->get(['id','name']);
        $physicians = Physician::query()->active()->orderBy('full_name')->get(['id','full_name']);

        return view('anatomopatologia.form', compact('matches','report','query','total','position','previous','next','services','physicians','newPatientSource'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $report = AnatomopathologyReport::query()->create($validated + ['status' => 'completed']);
        if ($request->input('action') === 'print') {
            return redirect()->route('anatomopatologia.imprimir', ['anatomopatologia' => $report, 'auto' => 1]);
        }
        return redirect()->route('anatomopatologia.index', ['registro' => $report->id])
            ->with('success', "Informe {$report->report_number} guardado correctamente. Disponible para consulta e impresión.")
            ->with('ap_viewport', substr((string) $request->input('_viewport', ''), 0, 12000))
            ->with('saved_report_id', $report->id)
            ->with('saved_report_number', $report->report_number);
    }

    public function update(Request $request, AnatomopathologyReport $anatomopatologia): RedirectResponse
    {
        abort(403, 'Los informes guardados son de solo lectura. Cree un nuevo informe para otra atención.');
    }

    public function print(AnatomopathologyReport $anatomopatologia): View
    {
        return view('anatomopatologia.print', ['report'=>$anatomopatologia]);
    }

    private function validated(Request $request, ?AnatomopathologyReport $report = null): array
    {
        $fields = ['registration_number','patient_name','clinical_diagnosis','anatomical_data','important_data','macroscopic_description','microscopic_description','diagnosis','notes'];
        $request->merge(collect($fields)->mapWithKeys(fn (string $field): array => [$field => is_string($request->input($field)) || $request->input($field) === null ? $this->uppercaseAscii($request->input($field)) : $request->input($field)])->all());
        $capRules = [
            'cap_protocol_enabled' => ['sometimes', 'boolean'],
            'cap_protocol' => ['sometimes', 'nullable', 'array:' . implode(',', [...array_keys(AnatomopathologyCapProtocol::FIELDS), 'custom'])],
            'cap_protocol.custom' => ['nullable', 'array', 'max:50'],
            'cap_protocol.custom.*' => ['array:title,value'],
            'cap_protocol.custom.*.title' => ['required', 'string', 'max:180'],
            'cap_protocol.custom.*.value' => ['required', 'string', 'max:10000'],
        ];
        foreach (AnatomopathologyCapProtocol::FIELDS as $key => $label) {
            $capRules['cap_protocol.' . $key] = ['nullable', 'string', 'max:10000'];
        }
        $activeCapFields = AnatomopathologyCapProtocol::activeFields();
        $data = $request->validate($capRules + [
            'registration_number'=>['required','string','max:40','regex:/^\d+(?:-(?:\d{2}|\d{4}))?$/'], 'report_date'=>['required','date'],
            'patient_name'=>['bail','required','string','max:180',new \App\Rules\PersonName()], 'patient_age'=>['nullable','integer','min:0','max:130'],
            'hospital_service_id'=>['nullable','integer','exists:hospital_services,id'],
            'requesting_physician_id'=>['nullable','integer','exists:physicians,id'],
            'clinical_diagnosis'=>['nullable','string','max:10000'], 'anatomical_data'=>['nullable','string','max:10000'],
            'important_data'=>['nullable','string','max:10000'], 'macroscopic_description'=>['nullable','string','max:30000'],
            'microscopic_description'=>['nullable','string','max:30000'], 'diagnosis'=>['nullable','string','max:30000'],
            'notes'=>['nullable','string','max:10000'],
        ], ['registration_number.required' => 'Ingrese el número de registro asignado al informe.', 'registration_number.regex' => 'Escriba el número de registro, por ejemplo 125 o 125-26.']);
        if (!$report && $request->boolean('cap_protocol_enabled')) {
            foreach (AnatomopathologyCapProtocol::FIELDS as $key => $label) {
                if (!isset($activeCapFields[$key]) && filled($data['cap_protocol'][$key] ?? null)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cap_protocol.'.$key => 'Este campo fue desactivado por administración. Quite su contenido antes de guardar un informe nuevo.',
                    ]);
                }
            }
            $inactiveTitles = \App\Models\CapProtocolField::query()->where('is_active', false)
                ->pluck('normalized_name')->all();
            foreach ($data['cap_protocol']['custom'] ?? [] as $index => $item) {
                if (in_array(\App\Models\CapProtocolField::normalize($item['title']), $inactiveTitles, true)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'cap_protocol.custom.'.$index.'.title' => 'Este protocolo fue desactivado por administración. Quítelo del informe nuevo o solicite su activación.',
                    ]);
                }
            }
        }
        // Legacy clients omit CAP fields: preserve the stored annex when updating.
        if ($request->has('cap_protocol_enabled') || $request->has('cap_protocol')) {
            $data['cap_protocol_enabled'] = $request->boolean('cap_protocol_enabled');
            $custom = $data['cap_protocol']['custom'] ?? [];
            $data['cap_protocol'] = collect($data['cap_protocol'] ?? [])->except('custom')
                ->map(fn ($value) => filled(trim((string) $value))
                    ? mb_strtoupper(Str::ascii(trim((string) $value)), 'UTF-8') : null)->all();
            $data['cap_protocol']['custom'] = array_values(array_map(fn ($item) => [
                'title' => mb_strtoupper(Str::ascii(trim($item['title'])), 'UTF-8'),
                'value' => mb_strtoupper(Str::ascii(trim($item['value'])), 'UTF-8'),
            ], $custom));
        }
        if (preg_match('/^(\d+)(?:-(\d{2}|\d{4}))?$/', $data['registration_number'] ?? '', $parts)) {
            $year = date('y', strtotime($data['report_date']));
            $sequence = ltrim($parts[1], '0') ?: '0';
            $data['registration_number'] = str_pad($sequence, 3, '0', STR_PAD_LEFT).'-'.$year;
        }
        validator(['registration_number' => $data['registration_number']], [
            'registration_number' => ['max:40', \Illuminate\Validation\Rule::unique('anatomopathology_reports', 'report_number')->ignore($report?->id)],
        ], ['registration_number.unique' => 'Ya existe un informe con ese número de registro y gestión.'])->validate();
        $data['report_number'] = $data['registration_number'];
        $service = !empty($data['hospital_service_id']) ? HospitalService::find($data['hospital_service_id']) : null;
        $physician = !empty($data['requesting_physician_id']) ? Physician::find($data['requesting_physician_id']) : null;
        $data['service']=$service?->name; $data['physician_name']=$physician?->full_name;
        return $data;
    }

    private function uppercaseAscii(mixed $value): ?string
    {
        $value=trim((string)($value??''));
        if ($value==='') return null;
        return mb_strtoupper(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value,'UTF-8');
    }
}
