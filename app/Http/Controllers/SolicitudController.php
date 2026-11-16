<?php

declare(strict_types=1);


namespace App\Http\Controllers;


use App\Models\Area;
use App\Models\AnatomopathologyReport;
use App\Models\CytologyReport;
use App\Models\District;
use App\Models\HealthFacility;
use App\Models\Municipality;
use App\Models\PathologyRequest;
use App\Models\PathologyType;
use App\Models\Patient;
use App\Models\Physician;
use App\Models\RegionalSecretariat;
use App\Models\Sector;
use App\Services\RequestNumberService;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;



class SolicitudController extends Controller
{

    public function storeCatalogOption(Request $request): JsonResponse
    {
        abort_unless(\App\Support\AccessRoles::addsRequestCatalogs($request->user()), 403);
        $validated = $request->validate([

            'type' => ['required', Rule::in(['municipality', 'district', 'area', 'sector', 'facility'])],
            'name' => ['required', 'string', 'max:220'],
            'regional_secretariat_id' => ['nullable', 'integer', 'exists:regional_secretariats,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'classification' => ['nullable', Rule::in(['U', 'R'])],
        ]);

        $name = trim($validated['name']);
        $normalizedName = mb_strtoupper(Str::ascii($name), 'UTF-8');
        $table = [
            'municipality' => 'municipalities',
            'district' => 'districts',
            'area' => 'areas',
            'sector' => 'sectors',
            'facility' => 'health_facilities',
        ][$validated['type']];
        validator(
            ['normalized_name' => $normalizedName],
            ['normalized_name' => [Rule::unique($table, 'normalized_name')]],
            ['normalized_name.unique' => 'Ya existe una opción con ese nombre.']
        )->validate();
        $common = ['name' => $name, 'normalized_name' => $normalizedName, 'is_active' => true];

        $option = match ($validated['type']) {
            'municipality' => Municipality::query()->create($common + [
                'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
            ]),
            'district' => District::query()->create($common + [
                'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
                'municipality_id' => $validated['municipality_id'] ?? null,
            ]),
            'area' => Area::query()->create($common + [
                'district_id' => $request->validate(['district_id' => ['required', 'integer', 'exists:districts,id']])['district_id'],
            ]),
            'sector' => Sector::query()->create($common + [
                'area_id' => $request->validate(['area_id' => ['required', 'integer', 'exists:areas,id']])['area_id'],
            ]),
            'facility' => HealthFacility::query()->create($common + [
                'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
                'municipality_id' => $request->validate(['municipality_id' => ['required', 'integer', 'exists:municipalities,id']])['municipality_id'],
                'district_id' => $validated['district_id'] ?? null,
                'area_id' => $validated['area_id'] ?? null,
                'sector_id' => $validated['sector_id'] ?? null,
                'classification' => $validated['classification'] ?? 'R',
            ]),
        };

        return response()->json([
            'message' => 'Opción añadida correctamente.',
            'option' => ['id' => $option->getKey(), 'name' => $option->name],
        ], 201);
    }

    /**
     * Retira una solicitud y sus muestras/resultados del flujo de trabajo.
     * Los registros usan SoftDeletes, de modo que la operación es recuperable.
     */
    public function destroy(PathologyRequest $solicitud): RedirectResponse
    {
        abort_unless(\App\Support\AccessRoles::admin(Auth::user()), 403);
        DB::transaction(function () use ($solicitud): void {
            $solicitud = PathologyRequest::query()->whereKey($solicitud->id)->lockForUpdate()->firstOrFail();
            if ($solicitud->samples()->withTrashed()->whereHas('pathologyResult', fn ($q) => $q->withTrashed())->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['result' => 'No se puede eliminar una solicitud con resultados clínicos. Corrija el resultado para conservar su historial.']);
            }
            $solicitud->load('samples.pathologyResult');

            foreach ($solicitud->samples as $sample) {
                $sample->pathologyResult?->delete();
                $sample->delete();
            }

            $solicitud->delete();
        }, 5);

        return redirect()
            ->route('resultados.index')
            ->with('success', 'Solicitud eliminada correctamente. El paciente se conservó.');
    }


    /*
    |--------------------------------------------------------------------------
    | PANTALLA PRINCIPAL
    |--------------------------------------------------------------------------
    */


   public function index(Request $request): View
{
        if (!\App\Support\AccessRoles::admin($request->user()) && !\App\Support\AccessRoles::reception($request->user())) {
            abort_unless(\App\Support\AccessRoles::consultsLaboratory($request->user()), 403);
            return app(ResultadoController::class)->index($request);
        }
    $rawPatientQuery = preg_replace(
        '/\s+/u',
        ' ',
        trim((string) $request->query('patient_query', ''))
    ) ?: trim((string) $request->query('patient_query', ''));

    $patientQuery = mb_strtoupper(
        $rawPatientQuery,
        'UTF-8'
    );

    $selectedPatient = null;
    $patientMatches = collect();
    $selectedPatientId = (int) $request->query('selected_patient_id', 0);

    if ($selectedPatientId > 0) {
        $selectedPatient = Patient::query()->find($selectedPatientId);
    } elseif ($patientQuery !== '') {
        $isIdentitySearch = preg_match('/^\d[\d\s.\-]*$/u', $patientQuery) === 1;
        $searchTerms = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($patientQuery, 'UTF-8')) ?: []
        ));

        $patientMatches = Patient::query()
            ->where('is_active', true)
            ->when($isIdentitySearch, function (Builder $query) use ($patientQuery): void {
                $query->whereRaw(
                    'LOWER(identity_document) LIKE ?',
                    ['%' . mb_strtolower($patientQuery, 'UTF-8') . '%']
                );
            }, function (Builder $query) use ($searchTerms): void {
                foreach ($searchTerms as $term) {
                    $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . $term . '%']);
                }
            })
            ->orderByRaw(
                "CASE WHEN identity_document = ? THEN 0 ELSE 1 END",
                [$patientQuery]
            )
            ->orderBy('full_name')
            ->paginate(20, [
                'id',
                'identity_document',
                'full_name',
                'birth_date',
                'sex',
                'phone',
            ], 'patients_page')->withQueryString();

        if ($isIdentitySearch) {
            $selectedPatient = $patientMatches
                ->firstWhere('identity_document', $patientQuery);
        } else {
            $exactNameMatches = $patientMatches->getCollection()->filter(
                fn (Patient $patient): bool => mb_strtoupper(
                    preg_replace('/\s+/u', ' ', trim($patient->full_name)) ?: trim($patient->full_name),
                    'UTF-8'
                ) === $patientQuery
            );

            if (!$patientMatches->hasPages() && $exactNameMatches->count() === 1) {
                $selectedPatient = $exactNameMatches->first();
            }
        }
    }

    return view('solicitudes.index',[

        'patientQuery' => $patientQuery,

        'selectedPatient' => $selectedPatient,

        'patientMatches' => $patientMatches,

        'regionalSecretariats' =>
            RegionalSecretariat::query()
            ->active()
            ->orderBy('name')
            ->get(),


        'districts' =>
            District::query()
            ->active()
            ->orderBy('name')
            ->get(),


        'municipalities' =>
            Municipality::query()
            ->active()
            ->orderBy('name')
            ->get(),

        'areas' =>
            Area::query()
            ->active()
            ->orderBy('name')
            ->get(),

        'sectors' =>
            Sector::query()
            ->active()
            ->orderBy('name')
            ->get(),

        'healthFacilities' =>
            HealthFacility::query()
            ->active()
            ->orderBy('name')
            ->get(),

        'physicians' =>
            Physician::query()
            ->active()
            ->orderBy('full_name')
            ->get(),

        'pathologyTypes' =>
            PathologyType::query()
            ->active()
            ->orderBy('name')
            ->get([
                'code',
                'name'
            ]),


    ]);

}

    /*
    |--------------------------------------------------------------------------
    | CREAR
    |--------------------------------------------------------------------------
    */


    public function create(?int $registro = null): RedirectResponse
    {

        return redirect()
            ->route('solicitudes.index');

    }

    /*
    |--------------------------------------------------------------------------
    | GUARDAR SOLICITUD
    |
    | U = Urbano
    | R = Rural
    | G = Ginecología
    |--------------------------------------------------------------------------
    */


    public function store(
        Request $request,
        RequestNumberService $requestNumberService

    ): RedirectResponse {


        $validated = $request->validate([



            /*
            DATOS GENERALES
            */


            'reception_batch_id' => ['nullable', 'integer', 'exists:reception_batches,id'],
            'patient_category' => ['nullable', Rule::in(['insured', 'private'])],

            'patient_id'=>[
                'required',
                'integer',
                'exists:patients,id'
            ],


            'module'=>[
                'required',
                Rule::in([
                    'U',
                    'R',
                    'G'
                ])
            ],

            'request_date'=>[
                'required',
                'date',
                'after_or_equal:2025-01-01'
            ],



            'sample_collection_date'=>[
                'nullable',
                'date',
                'after_or_equal:2025-01-01'
            ],

            'sample_quantity'=>[
                'required',
                'integer',
                'min:1',
                'max:100'
            ],

            /*
            DATOS UBICACION
            */


            'municipality_id'=>[
                'nullable',
                'exists:municipalities,id'
            ],

            'regional_secretariat_id'=>[
                'nullable',
                'exists:regional_secretariats,id'
            ],

            'district_id'=>[
                'nullable',
                'exists:districts,id'
            ],

            'area_id'=>[
                'nullable',
                'exists:areas,id'
            ],



            'sector_id'=>[
                'nullable',
                'exists:sectors,id'
            ],


            'health_facility_id'=>[
                'nullable',
                'exists:health_facilities,id'
            ],


            /*
            GINECOLOGIA
            */


            'residence_municipality_id'=>[
                'nullable',
                'exists:municipalities,id'
            ],


        ], [
        ]);


        $pathologyRequest = DB::transaction(

            function() use(
                $validated,
                $requestNumberService

            ){


                /*
                Año del correlativo
                */


                $year = (int)
                    date(
                        'Y',
                        strtotime(
                            $validated['request_date']
                        )
                    );


                /*
                Generación automática:
                U / R / G
                */


                $batch = app(\App\Services\ReceptionNumberService::class)->assign($validated, Auth::id());

                $number =
                    $requestNumberService->next(

                        $validated['module'],

                        $year

                    );


                $pathologyRequest = PathologyRequest::create([

                    'reception_batch_id' => $batch->id,
                    'patient_category' => $validated['module'] === 'G'
                        ? ($validated['patient_category'] ?? 'insured') : null,

                    /*
                    PACIENTE
                    */


                    'patient_id'=>
                        $validated['patient_id'],


                    /*
                    MODULO
                    */


                    'module'=>
                        $validated['module'],

                    /*
                    CORRELATIVO
                    */

                    'sequence_type'=>
                        $number['sequence_type'],

                    'sequence_year'=>
                        $number['sequence_year'],

                    'sequence_number'=>
                        $number['sequence_number'],

                    'request_number'=>
                        $number['request_number'],

                    'request_type'=>'CITOLOGIA',

                    'study_type'=>'CITOLOGIA',

                    /*
                    UBICACION
                    */


                    'municipality_id'=>
                        $validated['municipality_id'] ?? null,

                    'regional_secretariat_id'=>
                        $validated['regional_secretariat_id'] ?? null,

                    'district_id'=>
                        $validated['district_id'] ?? null,

                    'area_id'=>
                        $validated['area_id'] ?? null,

                    'sector_id'=>
                        $validated['sector_id'] ?? null,

                    'health_facility_id'=>
                        $validated['health_facility_id'] ?? null,

                    /*
                    GINECOLOGIA
                    */


                    'residence_municipality_id'=>
                        $validated['residence_municipality_id'] ?? null,

                    /*
                    USUARIO
                    */

                    'created_by'=>
                        Auth::id(),

                    'updated_by'=>
                        Auth::id(),

                    /*
                    SOLICITUD
                    */

                    'sample_quantity'=>
                        $validated['sample_quantity'],

                    'request_date'=>
                        $validated['request_date'],

                    'sample_collection_date'=>
                        $validated['sample_collection_date'] ?? null,

                    'status'=>
                        PathologyRequest::STATUS_REGISTERED,

                ]);

                for ($sampleNumber = 1; $sampleNumber <= $validated['sample_quantity']; $sampleNumber++) {
                    $pathologyRequest->samples()->create([
                        'sample_number' => $sampleNumber,
                        'sample_type' => 'MUESTRA CITOLOGICA',
                        'collection_date' => $validated['sample_collection_date'] ?? null,
                        'received_at' => now(),
                        'status' => \App\Models\Sample::STATUS_RECEIVED,
                    ]);
                }

                $pathologyRequest->forceFill([
                    'status' => PathologyRequest::STATUS_RECEIVED,
                ])->save();

                return $pathologyRequest;

            },

            5

        );


        return redirect()

            ->route('solicitudes.index')

            ->with(

                'success',

                'Registro guardado. Examen: '

                .$pathologyRequest->request_number.' · Solicitud: '.$pathologyRequest->receptionBatch->display_number

            );

    }
        /*
    |--------------------------------------------------------------------------
    | BUSCAR PACIENTE
    |--------------------------------------------------------------------------
    */


    public function buscarPaciente(Request $request): JsonResponse
    {

        $termino = mb_strtoupper(
            trim(
                (string) $request->input('q')
            ),
            'UTF-8'
        );


        if ($termino === '') {

            return response()->json([
                'patients'=>[]
            ]);

        }



        $isIdentitySearch = preg_match('/^\d[\d\s.\-]*$/u', $termino) === 1;
        $searchTerms = array_values(array_filter(
            preg_split('/\s+/u', mb_strtolower($termino, 'UTF-8')) ?: []
        ));

        $patients = Patient::query()
            ->where('is_active', true)
            ->when($isIdentitySearch, function (Builder $query) use ($termino): void {
                $query->whereRaw(
                    'LOWER(identity_document) LIKE ?',
                    ['%' . mb_strtolower($termino, 'UTF-8') . '%']
                );
            }, function (Builder $query) use ($searchTerms): void {
                foreach ($searchTerms as $term) {
                    $query->whereRaw('LOWER(full_name) LIKE ?', ['%' . $term . '%']);
                }
            })


            ->orderBy('full_name')


            ->limit(25)


            ->get([

                'id',
                'identity_document',
                'full_name',
                'birth_date',
                'sex',
                'phone'

            ]);

        return response()->json([

            'patients'=>$patients

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL DEL PACIENTE
    |--------------------------------------------------------------------------
    */


    public function historialPaciente(
        Patient $paciente
    ): JsonResponse {


        $requests = $paciente

            ->pathologyRequests()

            ->with([
                'healthFacility:id,name'
            ])

            ->latest('request_date')

            ->latest('id')

            ->paginate(20)
            ->withQueryString()
            ->through(function(
                PathologyRequest $request
            ){


                return [

                    'id'=>$request->id,


                    'request_number'=>
                        $request->request_number,


                    'module'=>
                        $request->module,


                    'request_date'=>
                        $request->request_date
                        ?->format('Y-m-d'),


                    'health_facility'=>
                        $request
                        ->healthFacility
                        ?->name,


                    'status'=>
                        $request->status,


                ];

            });


        return response()->json([

            'requests'=>$requests->items(),
            'pagination' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'total' => $requests->total(),
            ],

        ]);

    }

    /**
     * Módulo independiente de consulta del historial clínico-patológico.
     */
    public function historial(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $user = $request->user();
        $isAdministrator = $user?->role === 'administrator' || $user?->hasRole('administrator');
        $isOfficeAccount = in_array($user?->role, ['laboratory_office', 'cytology_office', 'anatomopathology_office'], true);
        $historyAccess = [
            'laboratory' => $isAdministrator || !$isOfficeAccount || $user?->role === 'laboratory_office' || $user?->can('manage laboratory workflow'),
            'cytology' => $isAdministrator || !$isOfficeAccount || $user?->role === 'cytology_office' || $user?->can('manage cytology reports'),
            'anatomopathology' => $isAdministrator || !$isOfficeAccount || $user?->role === 'anatomopathology_office' || $user?->can('manage anatomopathology reports'),
        ];
        $selectedPatient = $request->integer('paciente')
            ? Patient::query()->find($request->integer('paciente'))
            : null;

        $patients = Patient::query()
            ->withCount('pathologyRequests')
            ->withCount('cytologyReports')
            ->selectSub(
                AnatomopathologyReport::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('patient_name', 'patients.full_name'),
                'anatomopathology_reports_count'
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%' . mb_strtolower($search) . '%';
                $query->where(function (Builder $inner) use ($like): void {
                    $inner->whereRaw("LOWER(full_name) LIKE ?", [$like])
                        ->orWhereRaw("LOWER(identity_document) LIKE ?", [$like]);
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $patients->getCollection()->each(function (Patient $patient) use ($historyAccess): void {
            $patient->setAttribute('visible_history_count',
                ($historyAccess['laboratory'] ? (int) $patient->pathology_requests_count : 0)
                + ($historyAccess['cytology'] ? (int) $patient->cytology_reports_count : 0)
                + ($historyAccess['anatomopathology'] ? (int) $patient->anatomopathology_reports_count : 0)
            );
        });

        $requests = $selectedPatient && $historyAccess['laboratory']
            ? $selectedPatient->pathologyRequests()
                ->with(['healthFacility:id,name', 'samples.pathologyResult'])
                ->latest('request_date')
                ->latest('id')
                ->paginate(20, ['*'], 'requests_page')->withQueryString()
            : collect();

        $cytologyReports = $selectedPatient && $historyAccess['cytology']
            ? CytologyReport::query()
                ->where(function (Builder $query) use ($selectedPatient): void {
                    $query->where('patient_id', $selectedPatient->id)
                        ->orWhere(function (Builder $legacy) use ($selectedPatient): void {
                            // A report already linked to another patient must not match by name.
                            $legacy->whereNull('patient_id')
                                ->whereRaw('LOWER(patient_name) = ?', [mb_strtolower($selectedPatient->full_name)]);
                        });
                })
                ->latest('request_date')->latest('id')->paginate(20, ['*'], 'cytology_page')->withQueryString()
            : collect();

        $anatomopathologyReports = $selectedPatient && $historyAccess['anatomopathology']
            ? AnatomopathologyReport::query()
                ->whereRaw('LOWER(patient_name) = ?', [mb_strtolower($selectedPatient->full_name)])
                ->latest('report_date')->latest('id')->paginate(20, ['*'], 'anatomopathology_page')->withQueryString()
            : collect();

        return view('historial.index', compact(
            'patients', 'selectedPatient', 'requests', 'cytologyReports',
            'anatomopathologyReports', 'historyAccess', 'search'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | MUNICIPIOS
    |--------------------------------------------------------------------------
    */


    public function municipios(
        int $secretaria
    ): JsonResponse {

        return response()->json(

            Municipality::query()

            ->active()

            ->where(
                'regional_secretariat_id',
                $secretaria
            )

            ->orderBy('name')

            ->get([
                'id',
                'access_code',
                'name'
            ])

            ->map(fn (Municipality $municipality): array => [
                'id' => $municipality->id,
                'name' => $municipality->name,
            ])

        );

    }

    /*
    |--------------------------------------------------------------------------
    | DISTRITOS
    |--------------------------------------------------------------------------
    */
    public function distritos(
        int $municipio
    ): JsonResponse {

        return response()->json(

            District::query()

            ->active()

            ->where(
                'municipality_id',
                $municipio
            )

            ->orderBy('name')

            ->get([
                'id',
                'access_code',
                'name'
            ])

            ->map(fn (District $district): array => [
                'id' => $district->id,
                'name' => $district->name,
            ])

        );

    }

    /*
    |--------------------------------------------------------------------------
    | AREAS
    |--------------------------------------------------------------------------
    */

    public function areas(
        int $distrito
    ): JsonResponse {

        return response()->json(

            Area::query()

            ->active()

            ->where(
                'district_id',
                $distrito
            )

            ->orderBy('name')

            ->get([
                'id',
                'access_code',
                'name'
            ])

            ->map(fn (Area $area): array => [
                'id' => $area->id,
                'name' => $area->name,
            ])

        );

    }

    /*
    |--------------------------------------------------------------------------
    | SECTORES
    |--------------------------------------------------------------------------
    */

    public function sectores(
        int $area
    ): JsonResponse {

        return response()->json(

            Sector::query()

            ->active()

            ->where(
                'area_id',
                $area
            )

            ->orderBy('name')

            ->get([
                'id',
                'access_code',
                'name'
            ])

            ->map(fn (Sector $sector): array => [
                'id' => $sector->id,
                'name' => $sector->name,
            ])

        );

    }

    /*
    |--------------------------------------------------------------------------
    | ESTABLECIMIENTOS
    |--------------------------------------------------------------------------
    */

    public function establecimientos(
        Request $request
    ): JsonResponse {

        $query =
            HealthFacility::query()
            ->active();

        if($request->filled('municipality_id')){

            $query->where(
                'municipality_id',
                $request->municipality_id
            );

        }

        if($request->filled('district_id')){

            $query->where(
                'district_id',
                $request->district_id
            );

        }

        if($request->filled('area_id')){

            $query->where(function($facilityQuery) use ($request){

                $facilityQuery
                    ->where('area_id', $request->area_id)
                    ->orWhereNull('area_id');

            });

        }

        if($request->filled('sector_id')){

            $query->where(function($facilityQuery) use ($request){

                $facilityQuery
                    ->where('sector_id', $request->sector_id)
                    ->orWhereNull('sector_id');

            });

        }

        return response()->json(

            $query

            ->orderBy('name')

            ->get([
                'id',
                'access_code',
                'name'
            ])

            ->map(fn (HealthFacility $facility): array => [
                'id' => $facility->id,
                'name' => $facility->name,
            ])

        );

    }

    /*
    |--------------------------------------------------------------------------
    | VER SOLICITUD
    |--------------------------------------------------------------------------
    */

    public function show(
        PathologyRequest $solicitud
    ): View {

        $solicitud->load([

            'patient',

            'municipality',

            'district',

            'area',

            'sector',

            'healthFacility',

            'samples'

        ]);

        return view(

            'solicitudes.show',

            compact('solicitud')

        );

    }

    /*
    |--------------------------------------------------------------------------
    | BUSQUEDA GENERAL
    |--------------------------------------------------------------------------
    */

    public function buscar(
        Request $request
    ): View {

        $buscar =
            trim(
                (string)
                $request->input('buscar')
            );

        $requests =
            PathologyRequest::query()

            ->with([
                'patient',
                'healthFacility'
            ])

            ->when(
                $buscar,
                function($query) use($buscar){


                    $query->whereHas(
                        'patient',
                        function($q) use($buscar){


                            $q->where(
                                'identity_document',
                                'ILIKE',
                                "%{$buscar}%"
                            )

                            ->orWhere(
                                'full_name',
                                'ILIKE',
                                "%{$buscar}%"
                            );


                        }
                    );


                }
            )

            ->latest('id')

            ->paginate(20)->withQueryString();

        return view(
            'solicitudes.buscar',
            compact(
                'requests',
                'buscar'
            )
        );


    }

    /*
    |--------------------------------------------------------------------------
    | LIMPIEZA TEXTO
    |--------------------------------------------------------------------------
    */

    private function nullableTrim(
        ?string $value
    ): ?string {


        $value =
            trim(
                (string)$value
            );

        return $value === ''
            ? null
            : $value;


    }

}
