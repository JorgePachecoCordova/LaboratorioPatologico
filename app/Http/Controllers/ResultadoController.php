<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PathologyRequest;
use App\Models\PathologyResult;
use App\Models\CytologyReport;
use App\Models\Physician;
use App\Models\Sample;
use App\Models\CytologyDiagnosis;
use App\Models\District;
use App\Models\Municipality;
use App\Models\HealthFacility;
use App\Support\PathologyResultExcelExporter;
use App\Support\PapReportGeography;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResultadoController extends Controller
{
    /**
     * Lista las solicitudes y su avance de muestras/resultados.
     */
    public function index(Request $request): View
    {
        return $this->resultsIndex($request, false);
    }

    /**
     * Lista exclusivamente las solicitudes que generan informe citológico.
     */
    public function cytologyIndex(Request $request): View
    {
        return $this->resultsIndex($request, true);
    }

    private function resultsIndex(Request $request, bool $cytologyOnly): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'batch' => ['nullable', 'integer', 'exists:reception_batches,id'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'classification' => ['nullable', Rule::in(['positive'])],
            'year' => ['nullable', 'integer', 'between:2025,2100'],
        ]);

        $search = trim((string) $request->query('q', ''));

        $selectedBatch = $request->filled('batch') ? \App\Models\ReceptionBatch::findOrFail($request->integer('batch')) : null;
        $pathologyRequests = PathologyRequest::query()
            ->with([
                'receptionBatch',
                'patient:id,identity_document,full_name',
                'healthFacility:id,name',
                'samples' => fn ($query) => $query
                    ->with('pathologyResult')
                    ->orderBy('sample_number'),
            ])
            ->when($request->filled('batch'), fn (Builder $query) => $query->where('reception_batch_id', $request->integer('batch')))
            ->when($cytologyOnly, function (Builder $query): void {
                $query->where(function (Builder $cytologyQuery): void {
                    $cytologyQuery
                        ->where('module', PathologyRequest::SEQUENCE_GYNECOLOGICAL)
                        ->orWhereRaw("LOWER(COALESCE(request_type, '')) LIKE ?", ['%citolog%'])
                        ->orWhereRaw("LOWER(COALESCE(study_type, '')) LIKE ?", ['%citolog%']);
                });
            })
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . mb_strtolower($search) . '%';

                $query->where(function (Builder $innerQuery) use ($like): void {
                    $innerQuery
                        ->whereRaw(
                            "LOWER(COALESCE(request_number, '')) LIKE ?",
                            [$like]
                        )
                        ->orWhereHas('patient', function (Builder $patientQuery) use ($like): void {
                            $patientQuery
                                ->whereRaw(
                                    "LOWER(COALESCE(identity_document, '')) LIKE ?",
                                    [$like]
                                )
                                ->orWhereRaw(
                                    "LOWER(COALESCE(full_name, '')) LIKE ?",
                                    [$like]
                                );
                        });
                });
            })
            ->when($request->filled('year'), fn (Builder $q) => $q->whereYear('request_date', $request->integer('year')))
            ->when($request->filled('month'), fn (Builder $q) => $q->whereMonth('request_date', $request->integer('month')))
            ->when($request->query('classification') === 'positive', fn (Builder $q) => $q->whereHas('samples.pathologyResult', fn (Builder $r) => $r->where('is_positive', true)))
            ->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('resultados.index', [
            'selectedBatch' => $selectedBatch,
            'pathologyRequests' => $pathologyRequests,
            'search' => $search,
            'cytologyOnly' => $cytologyOnly,
            'year' => $request->query('year'), 'month' => $request->query('month'), 'classification' => $request->query('classification'),
        ]);
    }

    /**
     * Muestra la trazabilidad de una solicitud y los formularios de muestra y
     * resultado, sin cambiar la ruta existente.
     */
    public function create(Request $request, PathologyRequest $solicitud): View
    {
        abort_unless(\App\Support\AccessRoles::writesResults($request->user()), 403);
        // Las solicitudes actuales crean sus muestras al guardarse. Esta
        // reparación idempotente permite gestionar también registros antiguos
        // que quedaron sin muestra y evita una pantalla intermedia ajena al
        // formulario de resultados.
        if ($solicitud->samples()->count() === 0 && $solicitud->sample_quantity > 0) {
            $solicitud->samples()->create([
                'sample_number' => 1,
                'sample_type' => match ($solicitud->module) {
                    PathologyRequest::SEQUENCE_RURAL => 'CITOLOGÍA RURAL',
                    PathologyRequest::SEQUENCE_GYNECOLOGICAL => 'CITOLOGÍA GINECOLÓGICA',
                    default => 'CITOLOGÍA URBANA',
                },
                'collection_date' => $solicitud->sample_collection_date,
                'received_at' => now(),
                'received_by' => Auth::user()?->name,
                'status' => Sample::STATUS_RECEIVED,
            ]);
        }

        $solicitud->load([
            'patient',
            'healthFacility',
            'regionalSecretariat',
            'municipality',
            'residenceMunicipality',
            'district',
            'area',
            'sector',
            'requestingPhysician',
            'samples' => fn ($query) => $query
                ->with(['pathologyResult.pathologist', 'pathologyResult.cytologyReport', 'pathologyResult.diagnoses'])
                ->orderBy('sample_number'),
        ]);

        $pathologists = Physician::query()
            ->active()
            ->orderBy('full_name')
            ->get();

        $pathologyTypes = CytologyDiagnosis::query()
            ->active()
            ->orderBy('description')
            ->get(['id', 'code', 'description'])
            ->map(fn (CytologyDiagnosis $diagnosis) => (object) [
                'id' => $diagnosis->id,
                'code' => $diagnosis->code,
                'name' => $diagnosis->description,
            ]);

        return view('resultados.create', [
            'pathologyRequest' => $solicitud,
            'pathologists' => $pathologists,
            'pathologyTypes' => $pathologyTypes,
            'newResult' => $request->boolean('nuevo'),
            'selectedSampleId' => $request->integer('sample_id'),
        ]);
    }

    /**
     * Crea o actualiza un resultado para una muestra perteneciente a la
     * solicitud indicada en la ruta.
     */
    public function store(
        Request $request,
        PathologyRequest $solicitud
    ): RedirectResponse {
        abort_unless(\App\Support\AccessRoles::writesResults($request->user()), 403);
        $validated = $request->validate([
            'expected_revision' => ['required', 'integer', 'min:0'],
            'correction_reason' => ['nullable', 'string', 'min:10', 'max:2000'],
            'sample_id' => [
                'required',
                'integer',
                Rule::exists('samples', 'id')->where(
                    fn ($query) => $query->where(
                        'pathology_request_id',
                        $solicitud->getKey()
                    )
                ),
            ],
            'pathologist_id' => [
                'nullable',
                'integer',
                Rule::exists('physicians', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'reader' => ['sometimes', 'required', Rule::in(array_keys(PathologyResult::READERS))],
            'rural_report_number' => ['nullable', 'string', 'max:50'],
            'visit_number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'insurance_number' => ['nullable', 'string', 'max:80'],
            'result_date' => ['required', 'date', 'after_or_equal:'.$solicitud->request_date->format('Y-m-d'), 'before_or_equal:today'],
            'macroscopic_description' => ['nullable', 'string', 'max:20000'],
            'microscopic_description' => ['nullable', 'string', 'max:20000'],
            'diagnosis' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'is_positive' => ['nullable', 'boolean'],
            'diagnosis_entries' => ['nullable', 'array', 'max:30'],
            'diagnosis_entries.*' => ['array'],
            'diagnosis_entries.*.pathology_type_id' => ['nullable', 'integer', Rule::exists('cytology_diagnoses', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'diagnosis_entries.*.code' => ['nullable', 'string', 'max:30'],
            'diagnosis_entries.*.description' => ['nullable', 'string', 'max:10000'],
            'status' => [
                'nullable',
                Rule::in([
                    PathologyResult::STATUS_DRAFT,
                    PathologyResult::STATUS_VALIDATED,
                ]),
            ],
            'sample_adequacy' => [
                'nullable',
                Rule::in([
                    CytologyReport::ADEQUACY_SATISFACTORY,
                    CytologyReport::ADEQUACY_UNSATISFACTORY,
                ]),
            ],
            'sample_adequacy_notes' => ['nullable', 'string', 'max:5000'],
            'transformation_zone_present' => ['nullable', 'boolean'],
            'general_category' => [
                'nullable',
                Rule::in([
                    CytologyReport::CATEGORY_NEGATIVE,
                    CytologyReport::CATEGORY_EPITHELIAL_ABNORMALITY,
                    CytologyReport::CATEGORY_OTHER,
                ]),
            ],
            'microorganisms' => ['nullable', 'array'],
            'microorganisms.*' => [Rule::in(['trichomonas', 'candida', 'flora_shift', 'actinomyces', 'herpes'])],
            'benign_findings' => ['nullable', 'array'],
            'benign_findings.*' => [Rule::in(['inflammation', 'radiation', 'iud', 'atrophy', 'glandular_post_hysterectomy'])],
            'epithelial_abnormality' => ['nullable', Rule::in(['asc_us', 'asc_h', 'lsil', 'hsil', 'squamous_carcinoma', 'agc', 'ais', 'adenocarcinoma'])],
            'epithelial_abnormality_details' => ['nullable', 'string', 'max:10000'],
            'hormonal_evaluation' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
        ], [
            'sample_id.required' => 'Seleccione una muestra.',
            'result_date.required' => 'La fecha del resultado es obligatoria.',
            'diagnosis.required' => 'El diagnóstico es obligatorio.',
            'status.required' => 'Seleccione el estado del resultado.',
        ]);

        $selectedTypes = CytologyDiagnosis::query()->active()
            ->whereIn('id', collect($validated['diagnosis_entries'] ?? [])->pluck('pathology_type_id')->filter())
            ->get(['id', 'code', 'description'])->keyBy('id');

        $entries = collect($validated['diagnosis_entries'] ?? [])
            ->map(function (array $entry) use ($selectedTypes): array {
                $type = $selectedTypes->get((int) ($entry['pathology_type_id'] ?? 0));
                return $type ? ['code' => $type->code, 'description' => $type->description] : [
                    'code' => trim((string) ($entry['code'] ?? '')),
                    'description' => trim((string) ($entry['description'] ?? '')),
                ];
            })
            ->filter(fn (array $entry): bool => $entry['code'] !== '' && $entry['description'] !== '')
            ->values();

        if ($entries->isEmpty() || $entries->every(fn (array $entry): bool => trim((string) ($entry['description'] ?? '')) === '')) {
            throw ValidationException::withMessages([
                'diagnosis_entries' => 'Seleccione al menos un diagnóstico.',
            ]);
        }

        $codes = $entries->map(fn ($entry) => mb_strtoupper(trim($entry['code']), 'UTF-8'));
        if ($codes->unique()->count() !== $codes->count()) {
            throw ValidationException::withMessages(['diagnosis_entries' => 'Hay códigos de diagnóstico repetidos. Conserve una sola fila por código.']);
        }
        $validated['diagnosis'] = $entries->pluck('description')->implode("\n");
        $validated['status'] = $validated['status'] ?? PathologyResult::STATUS_VALIDATED;

        DB::transaction(function () use ($validated, $solicitud, $entries, $request): void {
            $solicitud = PathologyRequest::query()->whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->status === PathologyRequest::STATUS_CANCELLED) {
                throw ValidationException::withMessages(['sample_id' => 'No se pueden registrar resultados en una solicitud anulada.']);
            }
            /** @var Sample $sample */
            $sample = Sample::query()
                ->whereKey($validated['sample_id'])
                ->where('pathology_request_id', $solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $isValidated = $validated['status'] === PathologyResult::STATUS_VALIDATED;

            // sample_id es único incluso cuando el informe fue eliminado de
            // forma recuperable. En ese caso se restaura el mismo registro
            // antes de actualizarlo, evitando una inserción duplicada.
            $result = PathologyResult::withTrashed()
                ->where('sample_id', $sample->getKey())
                ->lockForUpdate()
                ->first();

            $existing = $result !== null;
            $wasTrashed = $result?->trashed() ?? false;
            if ((int) ($result?->revision ?? 0) !== (int) $validated['expected_revision']) {
                throw ValidationException::withMessages(['expected_revision' => 'El resultado cambió desde que abrió el formulario. Recargue la página y revise la última versión antes de guardar.']);
            }
            $before = $result?->auditSnapshot();
            if ($result === null) {
                $result = new PathologyResult(['sample_id' => $sample->getKey()]);
            } elseif ($result->trashed()) {
                $result->restore();
            }

            $result->fill([
                'pathologist_id' => array_key_exists('pathologist_id', $validated) ? $validated['pathologist_id'] : $result->pathologist_id,
                'recorded_by' => $result->recorded_by ?? Auth::id(),
                'validated_by' => $isValidated ? Auth::id() : null,
                'reader' => $validated['reader'] ?? $result->reader ?? $solicitud->reader,
                'rural_report_number' => array_key_exists('rural_report_number', $validated) ? $validated['rural_report_number'] : $result->rural_report_number,
            'visit_number' => array_key_exists('visit_number', $validated) ? $validated['visit_number'] : $result->visit_number,
                'insurance_number' => array_key_exists('insurance_number', $validated) ? $validated['insurance_number'] : $result->insurance_number,
                'result_date' => $validated['result_date'],
                'macroscopic_description' => $this->nullableTrim(
                    array_key_exists('macroscopic_description', $validated) ? $validated['macroscopic_description'] : $result->macroscopic_description
                ),
                'microscopic_description' => $this->nullableTrim(
                    array_key_exists('microscopic_description', $validated) ? $validated['microscopic_description'] : $result->microscopic_description
                ),
                'diagnosis' => trim($validated['diagnosis']),
                'is_positive' => (bool) ($validated['is_positive'] ?? $result->is_positive ?? false),
                'notes' => ($notes = $this->nullableTrim(array_key_exists('notes', $validated) ? $validated['notes'] : $result->notes)) !== null
                    ? mb_strtoupper($notes, 'UTF-8')
                    : null,
                'status' => $validated['status'],
                'validated_at' => $isValidated ? now() : null,
            ]);
            // Re-saving unchanged clinical data must not create another version.
            $clinicalKeys = ['sample_adequacy','sample_adequacy_notes','transformation_zone_present','general_category','microorganisms','benign_findings','epithelial_abnormality','epithelial_abnormality_details','hormonal_evaluation','recommendations'];
            $dirty = array_diff_key($result->getDirty(), array_flip(['validated_by','validated_at','updated_at']));
            $storedEntries = $existing ? $result->diagnoses()->get(['code','description'])->map(fn ($item) => ['code'=>$item->code,'description'=>$item->description])->all() : [];
            $incomingEntries = $entries->map(fn ($item) => ['code'=>mb_strtoupper(trim($item['code']),'UTF-8'),'description'=>mb_strtoupper(trim($item['description']),'UTF-8')])->all();
            if ($existing && !$wasTrashed && $dirty === [] && $storedEntries === $incomingEntries && array_intersect_key($validated, array_flip($clinicalKeys)) === []) {
                return;
            }
            $result->revision = ((int) $result->revision) + 1;
            $result->save();

            $clinicalFields = ['sample_adequacy', 'sample_adequacy_notes', 'transformation_zone_present', 'general_category', 'microorganisms', 'benign_findings', 'epithelial_abnormality', 'epithelial_abnormality_details', 'hormonal_evaluation', 'recommendations'];
            if ($solicitud->isCytology() && array_intersect_key($validated, array_flip($clinicalFields)) !== []) {
                $cytologyData = [
                    'sample_adequacy' => $validated['sample_adequacy'] ?? CytologyReport::ADEQUACY_SATISFACTORY,
                    'sample_adequacy_notes' => $this->nullableTrim($validated['sample_adequacy_notes'] ?? null),
                    'transformation_zone_present' => (bool) ($validated['transformation_zone_present'] ?? false),
                    'general_category' => $validated['general_category'] ?? CytologyReport::CATEGORY_OTHER,
                    'microorganisms' => array_values($validated['microorganisms'] ?? []),
                    'benign_findings' => array_values($validated['benign_findings'] ?? []),
                    'epithelial_abnormality' => $validated['epithelial_abnormality'] ?? null,
                    'epithelial_abnormality_details' => $this->nullableTrim($validated['epithelial_abnormality_details'] ?? null),
                    'hormonal_evaluation' => $this->nullableTrim($validated['hormonal_evaluation'] ?? null),
                    'recommendations' => $this->nullableTrim($validated['recommendations'] ?? null),
                ];
                // El formulario compacto no envía los campos del anexo clínico.
                // Una corrección no debe borrarlos por omisión.
                if ($result->cytologyReport()->exists()) $cytologyData = array_intersect_key($cytologyData, $validated);
                if ($cytologyData !== []) $result->cytologyReport()->updateOrCreate([], $cytologyData);
            }

            $result->diagnoses()->delete();

            foreach ($entries as $position => $entry) {
                $result->diagnoses()->create([
                    'position' => $position + 1,
                    'code' => mb_strtoupper(trim((string) ($entry['code'] ?? '')), 'UTF-8') ?: '—',
                    'description' => mb_strtoupper(trim((string) ($entry['description'] ?? '')), 'UTF-8'),
                ]);
            }

            $sample->forceFill([
                'status' => $isValidated
                    ? Sample::STATUS_COMPLETED
                    : Sample::STATUS_PROCESSING,
            ])->save();

            $this->refreshRequestStatus($solicitud->getKey());
            \App\Models\ResultAudit::create([
                'pathology_result_id' => $result->id, 'actor_id' => $request->user()->id,
                'actor_name' => $request->user()->name, 'actor_username' => $request->user()->username,
                'action' => $existing ? 'corrected' : 'created', 'revision' => $result->revision,
                'reason' => $validated['correction_reason'] ?? null, 'before' => $before,
                'after' => $result->auditSnapshot(), 'created_at' => now(),
            ]);
        }, 5);

        return redirect()
            ->route('resultados.create', [
                'solicitud' => $solicitud,
                'sample_id' => $validated['sample_id'],
            ])
            ->with('result_viewport', substr((string) $request->input('_viewport', ''), 0, 12000))
            ->with(
                'success',
                $validated['status'] === PathologyResult::STATUS_VALIDATED
                    ? 'Resultado validado correctamente.'
                    : 'Borrador de resultado guardado correctamente.'
            );
    }

    /**
     * Presenta el informe. La URL existente se conserva.
     */
    public function show(PathologyResult $resultado): View
    {
        return $this->renderReport($resultado);
    }

    /**
     * Presenta la versión imprimible del informe.
     */
    public function imprimir(PathologyResult $resultado): View
    {
        return $this->renderReport($resultado);
    }

    public function excel(PathologyResult $resultado, PathologyResultExcelExporter $exporter): BinaryFileResponse
    {
        abort_unless(\App\Support\AccessRoles::readsResults(Auth::user()) && ($resultado->status === PathologyResult::STATUS_VALIDATED || \App\Support\AccessRoles::writesResults(Auth::user())), 403);
        $resultado->load([
            'sample.pathologyRequest.patient',
            'sample.pathologyRequest.healthFacility',
            'sample.pathologyRequest.requestingPhysician',
            'sample.pathologyRequest.regionalSecretariat',
            'sample.pathologyRequest.municipality',
            'sample.pathologyRequest.district',
            'sample.pathologyRequest.area',
            'sample.pathologyRequest.sector',
            'pathologist',
            'diagnoses',
        ]);

        $path = $exporter->create($resultado);
        $number = preg_replace('/[^A-Za-z0-9_-]+/', '-', $resultado->sample->pathologyRequest->request_number ?? (string) $resultado->id);

        return response()->download(
            $path,
            'Resultado-'.$number.'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    public function consolidatedExcel(Request $request, PathologyResultExcelExporter $exporter): BinaryFileResponse
    {
        $request->validate([
            'year' => ['nullable', 'required_with:month', 'integer', 'between:2025,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'classification' => ['nullable', Rule::in(['positive'])],
        ], [
            'year.required_with' => 'Seleccione también el año para generar el reporte de un mes.',
        ]);

        $results = PathologyResult::query()->with(['sample.pathologyRequest.patient', 'sample.pathologyRequest.healthFacility', 'diagnoses'])
            ->where('status', PathologyResult::STATUS_VALIDATED)
            ->whereHas('sample.pathologyRequest', function (Builder $q) use ($request): void {
                $q->when($request->filled('year'), fn (Builder $x) => $x->whereYear('request_date', $request->integer('year')))
                    ->when($request->filled('month'), fn (Builder $x) => $x->whereMonth('request_date', $request->integer('month')));
            })->when($request->query('classification') === 'positive', fn (Builder $q) => $q->where('is_positive', true))
            ->orderBy('result_date')->orderBy('id')->get();
        $period = $request->filled('year') ? 'Gestión '.$request->integer('year') : 'Todas las gestiones';
        $filename = 'Reporte-general-'.now()->format('Y-m-d').'.xlsx';
        if ($request->filled('month')) {
            $monthNames = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $period = $monthNames[$request->integer('month')].' '.$request->integer('year');
            $filename = sprintf('Reporte-resultados-%d-%02d.xlsx', $request->integer('year'), $request->integer('month'));
        }
        $path = $exporter->createConsolidated($results, $period, $request->query('classification') === 'positive' ? 'Solo positivos' : 'Todos los resultados');
        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function monthlyPositiveExcel(Request $request, PathologyResultExcelExporter $exporter)
    {
        $request->validate([
            'year' => ['sometimes', 'required', 'integer', 'between:2025,2100'],
            'month' => ['sometimes', 'required', 'integer', 'between:1,12'],
            'period' => ['sometimes', 'required', 'in:monthly,annual'],
            'format' => ['sometimes', 'required', 'in:excel,print'],
        ], ['year.between' => 'La gestión debe estar entre 2025 y 2100.']);
        $annual = $request->query('period', 'monthly') === 'annual';
        $month = max(1, min(12, $request->integer('month', (int) now()->format('n'))));
        $year = $request->integer('year', (int) now()->format('Y'));
        $results = $this->cytologyStatisticalResults()
            ->with(['sample.pathologyRequest.patient', 'sample.pathologyRequest.healthFacility', 'sample.pathologyRequest.healthFacility.municipality', 'sample.pathologyRequest.healthFacility.district', 'sample.pathologyRequest.municipality', 'diagnoses'])
            ->where('is_positive', true)
            ->whereYear('result_date', $year)
            ->when(!$annual, fn (Builder $query) => $query->whereMonth('result_date', $month))
            ->orderBy('result_date')->orderBy('id')->get();
        $months = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
        $reportRows = [];
        foreach ($results as $index => $result) {
            $pathologyRequest = $result->sample->pathologyRequest; $patient = $pathologyRequest->patient;
            $values = [$index + 1, $result->result_date?->format('d/m/Y'), $pathologyRequest->request_number,
                $pathologyRequest->municipality?->name ?? $pathologyRequest->healthFacility?->municipality?->name ?? 'SIN MUNICIPIO', $pathologyRequest->healthFacility?->name,
                $patient?->full_name, $patient?->ageAt($result->result_date),
                $result->diagnoses->pluck('description')->filter()->implode(' / ') ?: $result->diagnosis];
            $reportRows[] = $values;
        }
        $title = 'CONSOLIDADO MENSUAL DE CITOLOGÍA (PAP) CASOS POSITIVOS '.$months[$month].' '.$year;
        if ($annual) $title = 'CONSOLIDADO ANUAL DE CITOLOGÍA (PAP) CASOS POSITIVOS · ENERO A DICIEMBRE · GESTIÓN '.$year;
        if ($request->query('format') === 'print') {
            return response()->view('citologia.statistical-print', [
                'title' => $title,
                'headers' => ['N.º', 'FECHA', 'N.º DE PAP', 'RED O MUNICIPIO', 'ESTABLECIMIENTO', 'APELLIDOS Y NOMBRES', 'EDAD', 'DIAGNÓSTICO CITOLÓGICO'],
                'rows' => $reportRows,
            ])->header('Cache-Control', 'private, no-store');
        }
        $path = $exporter->createStatistical($title, ['N.º', 'FECHA', 'N.º DE PAP', 'RED O MUNICIPIO', 'ESTABLECIMIENTO', 'APELLIDOS Y NOMBRES', 'EDAD', 'DIAGNÓSTICO CITOLÓGICO'], $reportRows);
        return response()->download($path, 'Casos-positivos-'.$year.'-'.($annual ? 'anual' : str_pad((string) $month, 2, '0', STR_PAD_LEFT)).'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function cytologyStatisticalExcel(Request $request, PathologyResultExcelExporter $exporter)
    {
        $request->validate([
            'year' => ['sometimes', 'required', 'integer', 'between:2025,2100'],
            'month' => ['sometimes', 'required', 'integer', 'between:1,12'],
            'period' => ['sometimes', 'required', 'in:monthly,annual'],
            'format' => ['sometimes', 'required', 'in:excel,print'],
        ], ['year.between' => 'La gestión debe estar entre 2025 y 2100.']);
        $type = (string) $request->query('type', 'urban');
        abort_unless(in_array($type, ['urban', 'rural', 'age'], true), 404);
        $year = $request->integer('year', (int) now()->format('Y'));
        $annual = $request->query('period', 'monthly') === 'annual';
        $month = max(1, min(12, $request->integer('month', (int) now()->format('n'))));
        $monthNames = [1=>'ENERO',2=>'FEBRERO',3=>'MARZO',4=>'ABRIL',5=>'MAYO',6=>'JUNIO',7=>'JULIO',8=>'AGOSTO',9=>'SEPTIEMBRE',10=>'OCTUBRE',11=>'NOVIEMBRE',12=>'DICIEMBRE'];
        $results = $this->cytologyStatisticalResults()->with([
            'sample.pathologyRequest.patient', 'sample.pathologyRequest.healthFacility',
            'sample.pathologyRequest.healthFacility.municipality', 'sample.pathologyRequest.healthFacility.district', 'sample.pathologyRequest.municipality', 'sample.pathologyRequest.district',
        ])->whereYear('result_date', $year)->get();

        if ($annual) $month = 12;
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        if ($type === 'urban') {
            $filtered = $results->filter(fn ($r) => $r->sample->pathologyRequest->module === PathologyRequest::SEQUENCE_URBAN && (int) $r->result_date?->format('n') <= $month);
            $groups = $filtered->groupBy(fn ($r) => $r->sample->pathologyRequest->healthFacility?->name ?? 'SIN ESTABLECIMIENTO')->sortKeys();
            foreach (HealthFacility::query()->where('classification', 'U')->pluck('name') as $name) {
                if (!$groups->has($name)) $groups->put($name, collect());
            }
            $groups = $groups->sortKeys();
            $headers = array_merge(['ESTABLECIMIENTO'], $romanMonths, ['TOTAL']);
            $rows = $groups->map(function ($items, $name) {
                $months = collect(range(1, 12))->map(fn ($m) => $items->filter(fn ($r) => (int) $r->result_date?->format('n') === $m)->count())->all();
                return array_merge([$name], $months, [$items->count()]);
            })->values()->all();
            $title = 'INFORME MENSUAL DE PAP DE ÁREA URBANA · CORTE '.$monthNames[$month].' · GESTIÓN '.$year;
            $filename = 'Informe-PAP-urbano-'.$year.'-'.str_pad((string)$month, 2, '0', STR_PAD_LEFT).'.xlsx';
        } elseif ($type === 'rural') {
            $filtered = $results->filter(fn ($r) => $r->sample->pathologyRequest->module === PathologyRequest::SEQUENCE_RURAL && (int) $r->result_date?->format('n') <= $month);
            $groups = $filtered->groupBy(function ($r) {
                $request = $r->sample->pathologyRequest;
                return implode('|', PapReportGeography::pair(
                    $request->district?->name ?? $request->healthFacility?->district?->name,
                    $request->municipality?->name ?? $request->healthFacility?->municipality?->name));
            })->sortKeys();
            // Include catalog relationships even when no sample has a result in the period.
            $catalogPairs = collect();
            foreach (PapReportGeography::RURAL as $network => $municipalities) {
                foreach ($municipalities as $municipality) $catalogPairs->push([$network, $municipality]);
            }
            foreach (District::query()->with('municipality')->get() as $district) {
                if ($district->municipality) {
                    $catalogPairs->push(PapReportGeography::pair($district->name, $district->municipality->name));
                }
            }
            foreach (HealthFacility::query()->with(['district', 'municipality'])->get() as $facility) {
                if ($facility->classification !== 'U' && $facility->municipality) {
                    $catalogPairs->push(PapReportGeography::pair($facility->district?->name, $facility->municipality->name));
                }
            }
            foreach (Municipality::query()->pluck('name') as $name) {
                $pair = PapReportGeography::pair(null, $name);
                if ($pair[0] !== 'SIN RED') {
                    $catalogPairs->push($pair);
                }
            }
            foreach ($catalogPairs as [$network, $municipality]) {
                $key = $network.'|'.$municipality;
                if (!$groups->has($key)) $groups->put($key, collect());
            }
            $ordered = collect();
            foreach ($catalogPairs as [$network, $municipality]) {
                $key = $network.'|'.$municipality;
                $ordered->put($key, $groups->get($key));
            }
            $groups = $ordered->union($groups);
            $headers = array_merge(['RED', 'MUNICIPIO'], $romanMonths, ['TOTAL']);
            $rows = $groups->map(function ($items, $key) {
                [$network, $municipality] = explode('|', $key, 2);
                $months = collect(range(1, 12))->map(fn ($m) => $items->filter(fn ($r) => (int) $r->result_date?->format('n') === $m)->count())->all();
                return array_merge([$network, $municipality], $months, [$items->count()]);
            })->values()->all();
            $title = 'INFORME MENSUAL DE PAP POR REDES Y MUNICIPIOS · ÁREA RURAL · CORTE '.$monthNames[$month].' · GESTIÓN '.$year;
            $filename = 'Informe-PAP-rural-'.$year.'-'.str_pad((string)$month, 2, '0', STR_PAD_LEFT).'.xlsx';
        } else {
            $filtered = $results->filter(fn ($r) => $annual || (int) $r->result_date?->format('n') === $month);
            $headers = ['ÁREA', 'CÓDIGO', 'INDICADOR', '0-9', '10-14', '15-19', '20-34', '35-49', '50 Y +', 'SIN EDAD', 'TOTAL'];
            $rows = [];
            foreach (['G'=>'GINECOLOGÍA H.D.B.', 'U'=>'ÁREA URBANA', 'R'=>'ÁREA RURAL'] as $module => $label) {
                $area = $filtered->filter(fn ($r) => $r->sample->pathologyRequest->module === $module);
                foreach (['MUESTRAS DE PAP TOMADAS' => false, 'RESULTADOS PAP POSITIVOS' => true] as $indicator => $positiveOnly) {
                    $items = $positiveOnly ? $area->where('is_positive', true) : $area;
                    $counts = [0,0,0,0,0,0,0];
                    foreach ($items as $result) {
                        $age = $result->sample->pathologyRequest->patient?->ageAt($result->result_date);
                        $band = $age === null ? 6 : ($age < 10 ? 0 : ($age < 15 ? 1 : ($age < 20 ? 2 : ($age < 35 ? 3 : ($age < 50 ? 4 : 5)))));
                        $counts[$band]++;
                    }
                    $rows[] = array_merge([$label, $positiveOnly ? 25 : 24, $indicator], $counts, [$items->count()]);
                }
            }
            $title = 'INFORME MENSUAL DE CITOLOGÍA · GRUPOS ETARIOS · '.$monthNames[$month].' '.$year;
            $filename = 'Informe-citologia-grupos-etarios-'.$year.'-'.str_pad((string)$month, 2, '0', STR_PAD_LEFT).'.xlsx';
        }
        if ($annual) {
            $title = match ($type) {
                'urban' => 'INFORME ANUAL DE PAP · ÁREA URBANA',
                'rural' => 'INFORME ANUAL DE PAP · REDES Y MUNICIPIOS · ÁREA RURAL',
                'age' => 'INFORME ANUAL DE CITOLOGÍA · GRUPOS ETARIOS',
            };
            $title .= ' · ENERO A DICIEMBRE · GESTIÓN '.$year;
            $filename = 'Informe-citologia-'.$type.'-'.$year.'-anual.xlsx';
        }
        if ($type !== 'age') {
            $offset = $type === 'rural' ? 2 : 1;
            $totals = array_fill(0, 13, 0);
            foreach ($rows as $row) {
                foreach (array_slice($row, $offset) as $i => $value) $totals[$i] += $value;
            }
            $rows[] = array_merge($type === 'rural' ? ['TOTAL', ''] : ['TOTAL'], $totals);
        }
        if ($request->query('format') === 'print') {
            return response()->view('citologia.statistical-print', compact('title', 'headers', 'rows', 'type'))
                ->header('Cache-Control', 'private, no-store');
        }
        $path = $exporter->createStatistical($title, $headers, $rows);
        return response()->download($path, $filename, ['Content-Type'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function cytologyStatisticalResults(): Builder
    {
        return PathologyResult::query()
            ->where('status', PathologyResult::STATUS_VALIDATED)
            ->whereHas('sample.pathologyRequest', function (Builder $query): void {
                $query->where('status', '!=', PathologyRequest::STATUS_CANCELLED)
                    ->whereIn('module', [PathologyRequest::SEQUENCE_URBAN, PathologyRequest::SEQUENCE_RURAL, PathologyRequest::SEQUENCE_GYNECOLOGICAL]);
            });
    }

    /**
     * Conserva los resultados clínicos y dirige los cambios al flujo auditado.
     */
    public function destroy(PathologyResult $resultado): RedirectResponse
    {
        throw ValidationException::withMessages(['result' => 'Los resultados se corrigen conservando su historial; no se eliminan.']);
    }

    private function renderReport(PathologyResult $result): View
    {
        abort_unless(\App\Support\AccessRoles::readsResults(Auth::user()) && ($result->status === PathologyResult::STATUS_VALIDATED || \App\Support\AccessRoles::writesResults(Auth::user())), 403, 'El resultado aún no está validado para su entrega.');
        $result->load([
            'sample.pathologyRequest.patient',
            'sample.pathologyRequest.healthFacility',
            'sample.pathologyRequest.requestingPhysician',
            'sample.pathologyRequest.regionalSecretariat',
            'sample.pathologyRequest.municipality',
            'sample.pathologyRequest.district',
            'sample.pathologyRequest.area',
            'sample.pathologyRequest.sector',
            'sample.pathologyRequest.residenceMunicipality',
            'pathologist',
            'recorder',
            'validator',
            'cytologyReport',
            'diagnoses',
        ]);

        return view('resultados.informe', [
            'pathologyResult' => $result,
        ]);
    }

    /**
     * Recalcula el estado general de la solicitud según todos sus resultados.
     */
    private function refreshRequestStatus(int $pathologyRequestId): void
    {
        /** @var PathologyRequest $pathologyRequest */
        $pathologyRequest = PathologyRequest::query()
            ->whereKey($pathologyRequestId)
            ->lockForUpdate()
            ->firstOrFail();

        $samples = $pathologyRequest->samples()
            ->with('pathologyResult')
            ->get();

        $totalSamples = $samples->count();
        $resultsCount = $samples
            ->filter(fn (Sample $sample): bool => $sample->pathologyResult !== null)
            ->count();
        $validatedCount = $samples
            ->filter(
                fn (Sample $sample): bool =>
                    $sample->pathologyResult?->status
                    === PathologyResult::STATUS_VALIDATED
            )
            ->count();

        $status = PathologyRequest::STATUS_PROCESSING;

        if ($totalSamples > 0 && $validatedCount === $totalSamples) {
            $status = PathologyRequest::STATUS_VALIDATED;
        } elseif ($resultsCount > 0) {
            $status = PathologyRequest::STATUS_REPORTED;
        }

        $pathologyRequest->forceFill([
            'status' => $status,
            'updated_by' => Auth::id(),
        ])->save();
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
