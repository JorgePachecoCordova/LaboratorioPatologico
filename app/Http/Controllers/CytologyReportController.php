<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CytologyReport;
use App\Services\CytologyNumberService;
use App\Http\Requests\SaveCytologyRequest;
use App\Http\Requests\SaveCytologyReportRequest;
use App\Models\HospitalService;
use App\Models\Physician;
use App\Support\CytologyExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CytologyReportController extends Controller
{
    public function storeCatalogOption(Request $request): \Illuminate\Http\JsonResponse
    {
        $admin = \App\Support\AccessRoles::admin($request->user());
        abort_unless($admin || ($request->user()?->role === 'cytology_office' && in_array($request->input('type'), ['service', 'physician', 'care', 'sample'], true)), 403, 'Solo puede añadir servicios, médicos solicitantes, tipos de muestra y de atención desde este módulo.');
        if (in_array($request->input('type'), ['service', 'physician'], true)) {
            return app(AnatomopathologyReportController::class)->storeCatalogOption($request);
        }
        $data = $request->validate([
            'type' => ['required', 'in:sample,care'],
            'name' => ['required', 'string', 'max:60'],
        ]);
        $name = mb_strtoupper(\Illuminate\Support\Str::ascii(trim($data['name'])), 'UTF-8');
        validator(['name' => $name], ['name' => [
            'required', 'max:'.($data['type'] === 'care' ? 30 : 60),
            \Illuminate\Validation\Rule::unique('cytology_catalog_options', 'name')->where('type', $data['type']),
        ]], ['name.unique' => 'Ya existe una opción con ese nombre.'])->validate();
        \App\Models\CytologyCatalogOption::create(['type' => $data['type'], 'name' => $name]);
        return response()->json(['option' => ['id' => $name, 'name' => $name]], 201);
    }

    public function index(Request $request): View
    {
        $consultationOnly = $request->user()?->role === 'anatomopathology_office';
        abort_if($consultationOnly && ($request->has('nuevo') || $request->has('nueva_para')), 403);
        $query = trim((string) $request->query('q', ''));
        $selectedId = $request->integer('registro');
        $newPatientSource = $request->integer('nueva_para')
            ? CytologyReport::query()->whereNotNull('request_number')->findOrFail($request->integer('nueva_para'))
            : null;
        if ($newPatientSource) {
            $selectedId = 0;
            $query = '';
        }

        $reports = CytologyReport::query()->whereNotNull('request_number');
        if ($consultationOnly) $reports->where('status', 'validated')->whereNotNull('report_number');
        $total = (clone $reports)->count();

        $report = $selectedId
            ? (clone $reports)->findOrFail($selectedId)
            : null;

        $matches = null;
        if ($query !== '' && !$selectedId) {
            $like = '%' . mb_strtolower($query) . '%';
            $matches = (clone $reports)
                ->where(function ($builder) use ($like): void {
                    $builder->whereRaw("LOWER(request_number) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(report_number, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(registration_number, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(patient_name, '')) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(COALESCE(sample_type, '')) LIKE ?", [$like]);
                })
                ->latest('id')
                ->paginate(10)->withQueryString();
        }

        $position = $report
            ? CytologyReport::query()->whereNotNull('request_number')->where('id', '<=', $report->id)->count()
            : 0;

        $previous = $report
            ? CytologyReport::query()->whereNotNull('request_number')->where('id', '<', $report->id)->latest('id')->first(['id'])
            : null;
        $next = $report
            ? CytologyReport::query()->whereNotNull('request_number')->where('id', '>', $report->id)->oldest('id')->first(['id'])
            : null;

        $physicians = Physician::query()->active()->orderBy('full_name')->get(['id', 'access_code', 'full_name']);
        $services = HospitalService::query()->active()->orderBy('name')->get(['id', 'access_code', 'name']);

        $catalogOptions = DB::table('cytology_catalog_options')->orderBy('name')->get()->groupBy('type');
        return view($consultationOnly ? 'citologia.consultation' : 'citologia.form', compact(
            'report', 'matches', 'query', 'total', 'position', 'previous', 'next', 'physicians', 'services', 'catalogOptions', 'newPatientSource'
        ));
    }

    public function store(SaveCytologyRequest $request): RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $report = DB::transaction(function () use ($validated): CytologyReport {
            $year = (int) substr($validated['request_date'], 0, 4);
            $completeReport = ($validated['report_action'] ?? null) === 'validate';
            unset($validated['report_action']);
            if ($completeReport) {
                $validated['report_number'] = app(CytologyNumberService::class)->next('report', (int) substr($validated['report_date'], 0, 4));
                $validated['registration_number'] = app(CytologyNumberService::class)->next('registration', (int) substr($validated['report_date'], 0, 4));
                $validated['status'] = 'validated';
            }
            return CytologyReport::query()->create($validated + [
                'request_number' => app(CytologyNumberService::class)->next('request', $year),
                'status' => 'requested',
            ]);
        }, 5);

        return redirect()->route('citologia.index', ['registro' => $report->id])
            ->with('cytology_saved', true)
            ->with('success', 'Registro citológico guardado correctamente.');
    }

    public function updateRequest(SaveCytologyRequest $request, CytologyReport $citologia): RedirectResponse
    {
        $citologia->update($this->validateRequest($request));
        if ($request->input('request_action') === 'print') return redirect()->route('citologia.solicitud.imprimir', $citologia);

        return redirect()->route('citologia.index', ['registro' => $citologia->id, 'tab' => 'solicitud'])
            ->with('success', 'Solicitud citológica actualizada correctamente.');
    }

    public function updateReport(SaveCytologyReportRequest $request, CytologyReport $citologia): RedirectResponse
    {
        if ($errors = $this->savedRequestErrors($citologia)) return $this->incompleteRequestRedirect($citologia, $errors);
        $validated = $request->validated();

        DB::transaction(function () use ($citologia, $validated, $request): void {
            $citologia = CytologyReport::query()->whereKey($citologia->id)->lockForUpdate()->firstOrFail();
            if (!$citologia->report_number) {
                $year = (int) substr($validated['report_date'], 0, 4);
                $validated['report_number'] = app(CytologyNumberService::class)->next('report', $year);
            }
            if (!$citologia->registration_number) {
                $validated['registration_number'] = app(CytologyNumberService::class)->next('registration', (int) substr($validated['report_date'], 0, 4));
            }
            $validated['status'] = $request->input('report_action') === 'validate' ? 'validated' : 'reported';
            $citologia->update($validated);
        }, 5);

        return redirect()->route('citologia.index', ['registro' => $citologia->id, 'tab' => 'informe'])
            ->with('cytology_saved', true)
            ->with('success', $request->input('report_action') === 'validate'
                ? 'Informe validado correctamente.' : 'Borrador del informe guardado.');
    }

    public function print(CytologyReport $citologia): View|RedirectResponse
    {
        abort_if(!$citologia->request_number, 404);
        if ($errors = $this->savedRequestErrors($citologia)) return $this->incompleteRequestRedirect($citologia, $errors);
        if ($errors = $this->savedReportErrors($citologia)) return redirect()->route('citologia.index', ['registro'=>$citologia->id, 'tab'=>'informe'])->withErrors($errors);

        return view('citologia.print', ['report' => $citologia]);
    }

    public function excel(CytologyReport $citologia, CytologyExcelExporter $exporter): BinaryFileResponse|RedirectResponse
    {
        abort_if(!$citologia->request_number, 404);
        if ($errors = $this->savedRequestErrors($citologia)) return $this->incompleteRequestRedirect($citologia, $errors);
        if ($errors = $this->savedReportErrors($citologia)) return redirect()->route('citologia.index', ['registro'=>$citologia->id, 'tab'=>'informe'])->withErrors($errors);

        $path = $exporter->create($citologia);
        $number = preg_replace('/[^A-Za-z0-9_-]+/', '-', $citologia->report_number ?? $citologia->request_number);

        return response()->download(
            $path,
            'Informe-citologico-'.$number.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function printRequest(CytologyReport $citologia): View|RedirectResponse
    {
        // La solicitud puede imprimirse desde que fue guardada; el número de
        // informe recién se asigna cuando se completa el resultado.
        abort_if(!$citologia->request_number, 404);
        if ($errors = $this->savedRequestErrors($citologia)) return $this->incompleteRequestRedirect($citologia, $errors);

        return view('citologia.request-print', ['report' => $citologia]);
    }

    private function savedRequestErrors(CytologyReport $report): array
    {
        $data = $report->attributesToArray();
        foreach ($data as $key => $value) if (is_string($value)) $data[$key] = trim($value);
        foreach (['request_date'] as $key) $data[$key] = $report->$key?->format('Y-m-d');
        foreach (['sample_taken_at', 'sample_received_at'] as $key) if (filled($data[$key] ?? null)) $data[$key] = substr($data[$key], 0, 5);
        $form = new SaveCytologyRequest();
        $rules = $form->rules();
        $rules['registration_number'] = ['nullable', 'string', 'max:50'];
        $validator = validator($data, $rules, $form->messages(), [
            'patient_name'=>'paciente', 'patient_age'=>'edad', 'care_type'=>'atención', 'bed_number'=>'cama',
            'registration_number'=>'número de registro', 'clinical_history_number'=>'historia clínica',
            'hospital_service_id'=>'servicio', 'requesting_physician_id'=>'médico solicitante',
            'sample_type_code'=>'tipo de muestra', 'sample_type_other'=>'especifique otros',
            'sample_taken_at'=>'hora de toma', 'sample_received_at'=>'hora de recepción',
            'clinical_diagnosis'=>'diagnóstico presuntivo', 'clinical_data'=>'datos clínicos', 'request_date'=>'fecha de solicitud',
        ]);
        return $validator->errors()->messages();
    }

    private function incompleteRequestRedirect(CytologyReport $report, array $errors): RedirectResponse
    {
        return redirect()->route('citologia.index', ['registro'=>$report->id, 'tab'=>'solicitud'])
            ->withErrors(['print'=>'Complete y guarde los campos obligatorios de la solicitud antes de emitir documentos.'] + $errors);
    }

    private function savedReportErrors(CytologyReport $report): array
    {
        $data = ['report_date'=>$report->report_date?->format('Y-m-d'), 'description'=>trim((string)$report->description), 'conclusion'=>trim((string)$report->conclusion)];
        $errors = validator($data, ['report_date'=>['required','date'], 'description'=>['nullable','string'], 'conclusion'=>['nullable','string']], [], ['description'=>'descripción','conclusion'=>'conclusión','report_date'=>'fecha del informe'])->errors()->messages();
        if ($report->status !== 'validated' || !$report->report_number) $errors['status'] = ['Guarde y valide el informe antes de imprimirlo o descargarlo.'];
        return $errors;
    }

    private function validateRequest(SaveCytologyRequest $request): array
    {
        $validated = $request->validated();

        $service = !empty($validated['hospital_service_id'])
            ? HospitalService::query()->find($validated['hospital_service_id'])
            : null;
        $physician = !empty($validated['requesting_physician_id'])
            ? Physician::query()->find($validated['requesting_physician_id'])
            : null;

        $validated['service'] = $service?->name;
        $validated['physician_name'] = $physician?->full_name;
        $validated['sample_type'] = $validated['sample_type_code'] === 'OTROS'
            ? ($validated['sample_type_other'] ?? 'OTROS')
            : $validated['sample_type_code'];

        return $validated;
    }

}
