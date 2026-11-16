<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\CapProtocolField;
use App\Models\District;
use App\Models\HealthFacility;
use App\Models\Municipality;
use App\Models\PathologyType;
use App\Models\Physician;
use App\Models\RegionalSecretariat;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ConfiguracionController extends Controller
{
    private const DEFAULT_USER_ROLE = 'reception';

    public function index(): View
    {
        return view('configuracion.index', [
            'capProtocolFields' => CapProtocolField::query()->orderBy('name')
                ->paginate(20, ['*'], 'cap_protocols_page'),
            'users' => User::query()
                ->orderBy('name')
                ->paginate(15, ['*'], 'users_page'),
            'physicians' => Physician::query()
                ->orderBy('full_name')
                ->paginate(15, ['*'], 'physicians_page'),
            'municipalities' => Municipality::query()
                ->with('regionalSecretariat:id,name')
                ->orderBy('name')
                ->paginate(20, ['*'], 'municipalities_page'),
            'districts' => District::query()
                ->with([
                    'regionalSecretariat:id,name',
                    'municipality:id,name',
                ])
                ->orderBy('name')
                ->paginate(20, ['*'], 'districts_page'),
            'areas' => Area::query()
                ->with('district.municipality:id,name')
                ->orderBy('name')
                ->paginate(20, ['*'], 'areas_page'),
            'sectors' => Sector::query()
                ->with('area:id,name')
                ->orderBy('name')
                ->paginate(20, ['*'], 'sectors_page'),
            'healthFacilities' => HealthFacility::query()
                ->with([
                    'regionalSecretariat:id,name',
                    'municipality:id,name',
                    'district:id,name',
                    'area:id,name',
                    'sector:id,name',
                ])
                ->orderBy('name')
                ->paginate(25, ['*'], 'facilities_page'),
            'pathologyTypes' => PathologyType::query()
                ->orderBy('name')
                ->paginate(15, ['*'], 'pathology_types_page'),
            'regionalSecretariatsSelect' => RegionalSecretariat::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'municipalitiesSelect' => Municipality::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'districtsSelect' => District::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'areasSelect' => Area::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
            'sectorsSelect' => Sector::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function storeProtocolo(Request $request): RedirectResponse
    {
        $name = $request->input('name');
        $request->merge(['normalized_name' => is_string($name) ? CapProtocolField::normalize($name) : '']);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'normalized_name' => ['required', 'string', 'max:180', 'unique:cap_protocol_fields,normalized_name'],
        ], ['normalized_name.unique' => 'Ya existe un protocolo con ese título. Si está inactivo, puede volver a activarlo.']);
        CapProtocolField::create(['name' => $data['normalized_name'],
            'normalized_name' => $data['normalized_name'], 'is_active' => true]);

        return redirect()->route('configuracion.index', ['tab' => 'protocolos'])
            ->with('success', 'Protocolo añadido al catálogo de anatomopatología.');
    }

    public function toggleProtocolo(CapProtocolField $protocolo): RedirectResponse
    {
        $protocolo->update(['is_active' => !$protocolo->is_active]);

        return redirect()->route('configuracion.index', ['tab' => 'protocolos'])
            ->with('success', $protocolo->is_active
                ? 'Protocolo activado. Disponible para nuevos informes.'
                : 'Protocolo desactivado. Los informes guardados conservan su contenido.');
    }

    public function storeUsuario(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'username' => [
                'required',
                'string',
                'max:80',
                'regex:/^[\pL\pM\pN _-]+$/u',
                'unique:users,username',
            ],
            'email' => ['nullable', 'email', 'max:180', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'password' => ['required', 'string', 'min:8', new \App\Rules\PasswordCharacters(), 'confirmed'],
            'consult_laboratory_reports' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'required', Rule::in(array_keys(\App\Support\AccessRoles::LABELS))],
        ], $this->userValidationMessages());

        $user = User::query()->create([
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => $this->nullableTrim($validated['email'] ?? null),
            'phone' => $this->nullableTrim($validated['phone'] ?? null),
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'] ?? self::DEFAULT_USER_ROLE,
            'is_active' => true,
        ]);

        $user->syncRoles(Role::findOrCreate($user->role, 'web'));
        $this->setReportConsultation($user, $request);

        return back()->with('success', 'Usuario registrado correctamente.');
    }

    public function updateUsuario(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'username' => [
                'required',
                'string',
                'max:80',
                'regex:/^[\pL\pM\pN _-]+$/u',
                Rule::unique('users', 'username')->ignore($usuario->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:180',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'password' => ['nullable', 'string', 'min:8', new \App\Rules\PasswordCharacters(), 'confirmed'],
            'consult_laboratory_reports' => ['sometimes', 'boolean'],
            'role' => [
                'required',
                Rule::in(array_keys(\App\Support\AccessRoles::LABELS)),
            ],
        ], $this->userValidationMessages());

        $data = [
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => $this->nullableTrim($validated['email'] ?? null),
            'phone' => $this->nullableTrim($validated['phone'] ?? null),
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if ((int) $usuario->id === (int) Auth::id() && $data['role'] !== 'administrator') {
            throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'No puede quitarse el rol de administrador desde su propia sesión.']);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($usuario, $data, $request): void {
            $usuario->update($data);
            $usuario->syncRoles(Role::findOrCreate($data['role'], 'web'));
            $this->setReportConsultation($usuario, $request);
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleUsuario(User $usuario): RedirectResponse
    {
        if ($usuario->id === Auth::id()) {
            return back()->withErrors([
                'user' => 'No puede desactivar su propia cuenta mientras tiene una sesión activa.',
            ]);
        }

        $usuario->update([
            'is_active' => !$usuario->is_active,
        ]);

        return back()->with('success', 'Estado del usuario actualizado.');
    }

    private function setReportConsultation(User $user, Request $request): void
    {
        if (in_array($user->role, ['result_reader', 'pathologist'], true)) {
            $user->revokePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate(\App\Support\AccessRoles::CONSULT_LABORATORY, 'web'));
            return;
        }
        if (!$request->has('consult_laboratory_reports')) return;
        $permission = \Spatie\Permission\Models\Permission::findOrCreate(\App\Support\AccessRoles::CONSULT_LABORATORY, 'web');
        if ($request->boolean('consult_laboratory_reports')) $user->givePermissionTo($permission);
        else $user->revokePermissionTo($permission);
    }

    public function storeMedico(Request $request): RedirectResponse
    {
        $validated = $this->validatePhysician($request);

        Physician::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Médico registrado correctamente.');
    }

    public function updateMedico(Request $request, Physician $medico): RedirectResponse
    {
        $medico->update($this->validatePhysician($request, $medico));

        return back()->with('success', 'Médico actualizado correctamente.');
    }

    public function toggleMedico(Physician $medico): RedirectResponse
    {
        $medico->update([
            'is_active' => !$medico->is_active,
        ]);

        return back()->with('success', 'Estado del médico actualizado.');
    }

    public function storeMunicipio(Request $request): RedirectResponse
    {
        $validated = $this->validateMunicipality($request);

        Municipality::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Municipio registrado correctamente.');
    }

    public function updateMunicipio(
        Request $request,
        Municipality $municipio
    ): RedirectResponse {
        $municipio->update($this->validateMunicipality($request, $municipio));

        return back()->with('success', 'Municipio actualizado correctamente.');
    }

    public function toggleMunicipio(Municipality $municipio): RedirectResponse
    {
        $municipio->update([
            'is_active' => !$municipio->is_active,
        ]);

        return back()->with('success', 'Estado del municipio actualizado.');
    }

    public function storeDistrito(Request $request): RedirectResponse
    {
        $validated = $this->validateDistrict($request);

        District::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Distrito registrado correctamente.');
    }

    public function updateDistrito(Request $request, District $distrito): RedirectResponse
    {
        $distrito->update($this->validateDistrict($request, $distrito));

        return back()->with('success', 'Distrito actualizado correctamente.');
    }

    public function toggleDistrito(District $distrito): RedirectResponse
    {
        $distrito->update([
            'is_active' => !$distrito->is_active,
        ]);

        return back()->with('success', 'Estado del distrito actualizado.');
    }

    public function storeArea(Request $request): RedirectResponse
    {
        Area::query()->create([
            ...$this->validateArea($request),
            'is_active' => true,
        ]);

        return back()->with('success', 'Área registrada correctamente.');
    }

    public function updateArea(Request $request, Area $area): RedirectResponse
    {
        $area->update($this->validateArea($request, $area));

        return back()->with('success', 'Área actualizada correctamente.');
    }

    public function toggleArea(Area $area): RedirectResponse
    {
        $area->update([
            'is_active' => !$area->is_active,
        ]);

        return back()->with('success', 'Estado del área actualizado.');
    }

    public function storeSector(Request $request): RedirectResponse
    {
        Sector::query()->create([
            ...$this->validateSector($request),
            'is_active' => true,
        ]);

        return back()->with('success', 'Sector registrado correctamente.');
    }

    public function updateSector(Request $request, Sector $sector): RedirectResponse
    {
        $sector->update($this->validateSector($request, $sector));

        return back()->with('success', 'Sector actualizado correctamente.');
    }

    public function toggleSector(Sector $sector): RedirectResponse
    {
        $sector->update([
            'is_active' => !$sector->is_active,
        ]);

        return back()->with('success', 'Estado del sector actualizado.');
    }

    public function storeEstablecimiento(Request $request): RedirectResponse
    {
        $validated = $this->validateHealthFacility($request);

        HealthFacility::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Establecimiento registrado correctamente.');
    }

    public function updateEstablecimiento(
        Request $request,
        HealthFacility $establecimiento
    ): RedirectResponse {
        $establecimiento->update(
            $this->validateHealthFacility($request, $establecimiento)
        );

        return back()->with('success', 'Establecimiento actualizado correctamente.');
    }

    public function toggleEstablecimiento(
        HealthFacility $establecimiento
    ): RedirectResponse {
        $establecimiento->update([
            'is_active' => !$establecimiento->is_active,
        ]);

        return back()->with('success', 'Estado del establecimiento actualizado.');
    }

    public function storeTipoPatologia(Request $request): RedirectResponse
    {
        $validated = $this->validatePathologyType($request);

        PathologyType::query()->create([
            ...$validated,
            'is_active' => true,
        ]);

        return back()->with('success', 'Tipo de patología registrado correctamente.');
    }

    public function updateTipoPatologia(
        Request $request,
        PathologyType $patologia
    ): RedirectResponse {
        $patologia->update($this->validatePathologyType($request, $patologia));

        return back()->with('success', 'Tipo de patología actualizado correctamente.');
    }

    public function toggleTipoPatologia(
        PathologyType $patologia
    ): RedirectResponse {
        $patologia->update([
            'is_active' => !$patologia->is_active,
        ]);

        return back()->with('success', 'Estado del tipo de patología actualizado.');
    }

    /** @return array<string, mixed> */
    private function validatePhysician(
        Request $request,
        ?Physician $physician = null
    ): array {
        $validated = $request->validate([
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('physicians', 'access_code')->ignore($physician?->id),
            ],
            'full_name' => ['bail', 'required', 'string', 'max:180', new \App\Rules\PersonName()],
            'professional_license' => ['nullable', 'string', 'max:60'],
        ]);

        return [
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'full_name' => trim($validated['full_name']),
            'professional_license' => $this->nullableTrim(
                $validated['professional_license'] ?? null
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function validateMunicipality(
        Request $request,
        ?Municipality $municipality = null
    ): array {
        $validated = $request->validate([
            'regional_secretariat_id' => [
                'nullable',
                'integer',
                'exists:regional_secretariats,id',
            ],
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('municipalities', 'access_code')->ignore($municipality?->id),
            ],
            'name' => ['required', 'string', 'max:180'],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('municipalities', 'normalized_name')
                        ->ignore($municipality?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un municipio con ese nombre.']
        )->validate();

        return [
            'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
        ];
    }

    /** @return array<string, mixed> */
    private function validateDistrict(
        Request $request,
        ?District $district = null
    ): array {
        $validated = $request->validate([
            'regional_secretariat_id' => [
                'nullable',
                'integer',
                'exists:regional_secretariats,id',
            ],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('districts', 'access_code')->ignore($district?->id),
            ],
            'name' => ['required', 'string', 'max:180'],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('districts', 'normalized_name')->ignore($district?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un distrito con ese nombre.']
        )->validate();

        return [
            'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
            'municipality_id' => $validated['municipality_id'] ?? null,
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
        ];
    }

    /** @return array<string, mixed> */
    private function validateHealthFacility(
        Request $request,
        ?HealthFacility $healthFacility = null
    ): array {
        $validated = $request->validate([
            'regional_secretariat_id' => [
                'nullable',
                'integer',
                'exists:regional_secretariats,id',
            ],
            'municipality_id' => ['required', 'integer', 'exists:municipalities,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'sector_id' => ['nullable', 'integer', 'exists:sectors,id'],
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('health_facilities', 'access_code')
                    ->ignore($healthFacility?->id),
            ],
            'name' => ['required', 'string', 'max:220'],
            'classification' => ['required', Rule::in(['U', 'R'])],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('health_facilities', 'normalized_name')
                        ->ignore($healthFacility?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un establecimiento con ese nombre.']
        )->validate();

        return [
            'regional_secretariat_id' => $validated['regional_secretariat_id'] ?? null,
            'municipality_id' => $validated['municipality_id'],
            'district_id' => $validated['district_id'] ?? null,
            'area_id' => $validated['area_id'] ?? null,
            'sector_id' => $validated['sector_id'] ?? null,
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
            'classification' => strtoupper($validated['classification']),
        ];
    }

    /** @return array<string, mixed> */
    private function validateArea(Request $request, ?Area $area = null): array
    {
        $validated = $request->validate([
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('areas', 'access_code')->ignore($area?->id),
            ],
            'name' => ['required', 'string', 'max:180'],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('areas', 'normalized_name')->ignore($area?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un área con ese nombre.']
        )->validate();

        return [
            'district_id' => $validated['district_id'],
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
        ];
    }

    /** @return array<string, mixed> */
    private function validateSector(Request $request, ?Sector $sector = null): array
    {
        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'access_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('sectors', 'access_code')->ignore($sector?->id),
            ],
            'name' => ['required', 'string', 'max:180'],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('sectors', 'normalized_name')->ignore($sector?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un sector con ese nombre.']
        )->validate();

        return [
            'area_id' => $validated['area_id'],
            'access_code' => $this->nullableTrim($validated['access_code'] ?? null),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
        ];
    }

    /** @return array<string, mixed> */
    private function validatePathologyType(
        Request $request,
        ?PathologyType $pathologyType = null
    ): array {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:60',
                'alpha_dash',
                Rule::unique('pathology_types', 'code')->ignore($pathologyType?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $normalizedName = $this->normalizeName($validated['name']);

        validator(
            ['normalized_name' => $normalizedName],
            [
                'normalized_name' => [
                    Rule::unique('pathology_types', 'normalized_name')
                        ->ignore($pathologyType?->id),
                ],
            ],
            ['normalized_name.unique' => 'Ya existe un tipo de patología con ese nombre.']
        )->validate();

        return [
            'code' => strtolower(trim($validated['code'])),
            'name' => trim($validated['name']),
            'normalized_name' => $normalizedName,
            'description' => $this->nullableTrim($validated['description'] ?? null),
        ];
    }

    /** @return array<string, string> */
    private function userValidationMessages(): array
    {
        return [
            'username.regex' => 'El nombre de usuario solo puede contener letras, números, espacios, guiones y guiones bajos.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }

    private function normalizeName(string $value): string
    {
        $ascii = Str::ascii(trim($value));
        $uppercase = mb_strtoupper($ascii);
        $withoutPunctuation = preg_replace('/[^A-Z0-9]+/u', ' ', $uppercase) ?? $uppercase;

        return preg_replace('/\s+/u', ' ', trim($withoutPunctuation)) ?? trim($withoutPunctuation);
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
