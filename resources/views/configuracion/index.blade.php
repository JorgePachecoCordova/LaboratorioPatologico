@extends('layouts.app')

@section('content')

@include('configuracion.styles')

<div class="cfg-page">
    @php
        $roleLabels = \App\Support\AccessRoles::LABELS;
    @endphp

    <div class="cfg-header">
        <div class="cfg-header-accent"></div>
        <div>
            <span class="cfg-eyebrow">ADMINISTRACIÓN</span>
            <h1>CONFIGURACIÓN</h1><a href="{{ route('configuracion.auditoria') }}" style="display:inline-block;margin-top:8px;color:#16824e;font-weight:600">Ver historial de resultados</a>
            <p>USUARIOS, MÉDICOS Y CATÁLOGOS INTERNOS DEL SISTEMA</p>
        </div>
    </div>

    @if(session('success'))
        <div class="cfg-alert">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="cfg-alert error">
            <strong>No se pudo completar la operación.</strong>
            <ul style="margin:6px 0 0 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <nav class="cfg-nav" aria-label="Secciones de configuración">
        <a href="#usuarios" data-config-tab="usuarios">Usuarios</a>
        <a href="#medicos" data-config-tab="medicos">Médicos</a>
        <a href="#municipios" data-config-tab="municipios">Municipios</a>
        <a href="#distritos" data-config-tab="distritos">Distritos</a>
        <a href="#areas" data-config-tab="areas">Áreas</a>
        <a href="#sectores" data-config-tab="sectores">Sectores</a>
        <a href="#establecimientos" data-config-tab="establecimientos">Establecimientos</a>
        <a href="#tipos-patologia" data-config-tab="tipos-patologia">Tipos de patología</a>
        <a href="#protocolos" data-config-tab="protocolos">Protocolos de anatomopatología</a>
    </nav>

    <section class="cfg-section" id="usuarios">
        <div class="cfg-section-head">
            <div>
                <h2>Usuarios</h2>
                <small>Acceso, rol y estado de las cuentas del sistema.</small>
            </div>
        </div>

        <details class="cfg-create">
            <summary>+ Crear nuevo usuario</summary>
            <form method="POST" action="{{ route('configuracion.usuarios.store') }}" class="cfg-form cfg-user-form">
                @csrf
                <div class="cfg-field wide">
                    <label>Nombre completo *</label>
                    <input class="cfg-input" name="name" value="{{ old('name') }}" required maxlength="180">
                </div>
                <div class="cfg-field">
                    <label><span class="ui-field-caption"><x-ui-icon name="user" /> Usuario *</span></label>
                    <input class="cfg-input" name="username" value="{{ old('username') }}" required maxlength="80">
                </div>
                <div class="cfg-field">
                    <label>Número de celular</label>
                    <input class="cfg-input" type="tel" name="phone" value="{{ old('phone') }}" maxlength="30" inputmode="tel" placeholder="Ej. 72400000">
                </div>
                <div class="cfg-field wide">
                    <label><span class="ui-field-caption"><x-ui-icon name="mail" /> Correo electrónico </span><span class="cfg-optional">Opcional</span></label>
                    <input class="cfg-input" type="email" name="email" value="{{ old('email') }}" maxlength="180" placeholder="nombre@correo.com">
                </div>
                <div class="cfg-field">
                    <label><span class="ui-field-caption"><x-ui-icon name="lock" /> Contraseña *</span></label>
                    <div class="cfg-password-wrap">
                        <input class="cfg-input" type="password" name="password" required minlength="8">
                        <button class="cfg-password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña">&#128065;</button>
                    </div>
                    <small class="cfg-password-hint">8+ caracteres · Mayúscula · Símbolo (. _)</small>
                </div>
                <div class="cfg-field">
                    <label>Confirmar contraseña *</label>
                    <div class="cfg-password-wrap">
                        <input class="cfg-input" type="password" name="password_confirmation" required minlength="8">
                        <button class="cfg-password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña">&#128065;</button>
                    </div>
                </div>
                <div class="cfg-field wide"><label>Rol de acceso *</label><select class="cfg-select" name="role" required>@foreach($roleLabels as $role => $label)<option value="{{ $role }}" @selected(old('role', 'reception') === $role)>{{ $label }}</option>@endforeach</select></div>
                <div class="cfg-actions">
                    <input type="hidden" name="consult_laboratory_reports" value="0"><label data-report-consultation><input type="checkbox" name="consult_laboratory_reports" value="1" @checked(old('consult_laboratory_reports'))> Acceso adicional de consulta a solicitudes y resultados</label>
                    <button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar usuario</button>
                </div>
            </form>
        </details>

        <div class="cfg-table-wrap">
            <table class="cfg-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->username }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>{{ $roleLabels[$user->role] ?? ucfirst($user->role) }}</td>
                            <td>
                                <span class="cfg-status {{ $user->is_active ? 'active' : 'inactive' }}">
                                    {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="cfg-row-actions">
                                    <button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('user-{{ $user->id }}')">Editar</button>
                                    <form method="POST" action="{{ route('configuracion.usuarios.estado', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="cfg-btn {{ $user->is_active ? 'danger' : 'secondary' }}" type="submit">
                                            {{ $user->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <tr class="cfg-edit-row" id="user-{{ $user->id }}" hidden>
                            <td colspan="7">
                                <form method="POST" action="{{ route('configuracion.usuarios.update', $user) }}" class="cfg-edit-form">
                                    @csrf
                                    @method('PUT')
                                    <div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $user->name }}" required></div>
                                    <div class="cfg-field"><label><span class="ui-field-caption"><x-ui-icon name="user" /> Usuario</span></label><input class="cfg-input" name="username" value="{{ $user->username }}" required></div>
                                    <div class="cfg-field"><label>Correo</label><input class="cfg-input" type="email" name="email" value="{{ $user->email }}"></div>
                                    <div class="cfg-field"><label><span class="ui-field-caption"><x-ui-icon name="mobile" /> Celular</span></label><input class="cfg-input" type="tel" name="phone" value="{{ $user->phone }}" maxlength="30" inputmode="tel"></div>
                                    <div class="cfg-field"><label>Rol</label><select class="cfg-select" name="role" required>@if(!isset($roleLabels[$user->role]))<option value="" selected disabled>Seleccione un rol para esta cuenta</option>@endif @foreach($roleLabels as $role => $label)<option value="{{ $role }}" @selected($user->role === $role)>{{ $label }}</option>@endforeach</select></div>
                                    <div class="cfg-field"><label>Nueva contraseña</label><div class="cfg-password-wrap"><input class="cfg-input" type="password" name="password" minlength="8"><button class="cfg-password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña">&#128065;</button></div><small class="cfg-password-hint">8+ caracteres · Mayúscula · Símbolo (. _)</small><small class="cfg-password-hint">Vacío: conservar contraseña.</small></div>
                                    <div class="cfg-field"><label>Confirmar contraseña</label><div class="cfg-password-wrap"><input class="cfg-input" type="password" name="password_confirmation" minlength="8"><button class="cfg-password-toggle" type="button" aria-label="Mostrar contraseña" title="Mostrar contraseña">&#128065;</button></div></div>
                                    <div class="cfg-field wide"><input type="hidden" name="consult_laboratory_reports" value="0"><label data-report-consultation><input type="checkbox" name="consult_laboratory_reports" value="1" @checked(\App\Support\AccessRoles::consultsLaboratory($user))> Acceso adicional de consulta a solicitudes y resultados</label></div>
                                    <button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="cfg-empty">No existen usuarios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="cfg-pagination">{{ $users->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="medicos">
        <div class="cfg-section-head"><div><h2>Médicos</h2><small>Médicos solicitantes y patólogos.</small></div></div>
        <details class="cfg-create">
            <summary>+ Nuevo médico</summary>
            <form method="POST" action="{{ route('configuracion.medicos.store') }}" class="cfg-form">
                @csrf
                <div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code" maxlength="30"></div>
                <div class="cfg-field wide"><label>Nombre completo *</label><input class="cfg-input" name="full_name" required maxlength="180"></div>
                <div class="cfg-field"><label>Matrícula profesional</label><input class="cfg-input" name="professional_license" maxlength="60"></div>
                <div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar médico</button></div>
            </form>
        </details>
        <div class="cfg-table-wrap">
            <table class="cfg-table">
                <thead><tr><th>Código</th><th>Nombre</th><th>Matrícula</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                    @forelse($physicians as $physician)
                        <tr>
                            <td>{{ $physician->access_code ?? '-' }}</td><td>{{ $physician->full_name }}</td><td>{{ $physician->professional_license ?? '-' }}</td>
                            <td><span class="cfg-status {{ $physician->is_active ? 'active' : 'inactive' }}">{{ $physician->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('physician-{{ $physician->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.medicos.estado', $physician) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $physician->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $physician->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td>
                        </tr>
                        <tr class="cfg-edit-row" id="physician-{{ $physician->id }}" hidden><td colspan="5"><form method="POST" action="{{ route('configuracion.medicos.update', $physician) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $physician->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="full_name" value="{{ $physician->full_name }}" required></div><div class="cfg-field"><label>Matrícula</label><input class="cfg-input" name="professional_license" value="{{ $physician->professional_license }}"></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
                    @empty
                        <tr><td colspan="5" class="cfg-empty">No existen médicos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="cfg-pagination">{{ $physicians->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="municipios">
        <div class="cfg-section-head"><div><h2>Municipios</h2><small>Catálogo territorial de municipios.</small></div></div>
        <details class="cfg-create"><summary>+ Nuevo municipio</summary><form method="POST" action="{{ route('configuracion.municipios.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Secretaría regional</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}">{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code" maxlength="30"></div><div class="cfg-field wide"><label>Nombre *</label><input class="cfg-input" name="name" required maxlength="180"></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar municipio</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Municipio</th><th>Secretaría</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($municipalities as $municipality)
                <tr><td>{{ $municipality->access_code ?? '-' }}</td><td>{{ $municipality->name }}</td><td>{{ $municipality->regionalSecretariat?->name ?? '-' }}</td><td><span class="cfg-status {{ $municipality->is_active ? 'active' : 'inactive' }}">{{ $municipality->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('municipality-{{ $municipality->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.municipios.estado', $municipality) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $municipality->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $municipality->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="municipality-{{ $municipality->id }}" hidden><td colspan="5"><form method="POST" action="{{ route('configuracion.municipios.update', $municipality) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Secretaría</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}" @selected($municipality->regional_secretariat_id === $secretariat->id)>{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $municipality->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $municipality->name }}" required></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="5" class="cfg-empty">No existen municipios registrados.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $municipalities->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="distritos">
        <div class="cfg-section-head"><div><h2>Distritos</h2><small>Relación de distritos con municipios.</small></div></div>
        <details class="cfg-create"><summary>+ Nuevo distrito</summary><form method="POST" action="{{ route('configuracion.distritos.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Secretaría regional</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}">{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Municipio</label><select class="cfg-select" name="municipality_id"><option value="">Sin asignar</option>@foreach($municipalitiesSelect as $municipality)<option value="{{ $municipality->id }}">{{ $municipality->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code"></div><div class="cfg-field"><label>Nombre *</label><input class="cfg-input" name="name" required></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar distrito</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Distrito</th><th>Municipio</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($districts as $district)
                <tr><td>{{ $district->access_code ?? '-' }}</td><td>{{ $district->name }}</td><td>{{ $district->municipality?->name ?? '-' }}</td><td><span class="cfg-status {{ $district->is_active ? 'active' : 'inactive' }}">{{ $district->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('district-{{ $district->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.distritos.estado', $district) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $district->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $district->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="district-{{ $district->id }}" hidden><td colspan="5"><form method="POST" action="{{ route('configuracion.distritos.update', $district) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Secretaría</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}" @selected($district->regional_secretariat_id === $secretariat->id)>{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Municipio</label><select class="cfg-select" name="municipality_id"><option value="">Sin asignar</option>@foreach($municipalitiesSelect as $municipality)<option value="{{ $municipality->id }}" @selected($district->municipality_id === $municipality->id)>{{ $municipality->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $district->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $district->name }}" required></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="5" class="cfg-empty">No existen distritos registrados.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $districts->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="areas">
        <div class="cfg-section-head"><div><h2>Áreas</h2><small>Áreas dependientes de cada distrito.</small></div></div>
        <details class="cfg-create"><summary>+ Nueva área</summary><form method="POST" action="{{ route('configuracion.areas.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Distrito *</label><select class="cfg-select" name="district_id" required><option value="">Seleccione</option>@foreach($districtsSelect as $district)<option value="{{ $district->id }}">{{ $district->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code" maxlength="30"></div><div class="cfg-field wide"><label>Nombre *</label><input class="cfg-input" name="name" required maxlength="180"></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar área</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Área</th><th>Distrito</th><th>Municipio</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($areas as $area)
                <tr><td>{{ $area->access_code ?? '-' }}</td><td>{{ $area->name }}</td><td>{{ $area->district?->name ?? '-' }}</td><td>{{ $area->district?->municipality?->name ?? '-' }}</td><td><span class="cfg-status {{ $area->is_active ? 'active' : 'inactive' }}">{{ $area->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('area-{{ $area->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.areas.estado', $area) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $area->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $area->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="area-{{ $area->id }}" hidden><td colspan="6"><form method="POST" action="{{ route('configuracion.areas.update', $area) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Distrito</label><select class="cfg-select" name="district_id" required>@foreach($districtsSelect as $district)<option value="{{ $district->id }}" @selected($area->district_id === $district->id)>{{ $district->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $area->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $area->name }}" required></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="6" class="cfg-empty">No existen áreas registradas.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $areas->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="sectores">
        <div class="cfg-section-head"><div><h2>Sectores</h2><small>Sectores dependientes de cada área.</small></div></div>
        <details class="cfg-create"><summary>+ Nuevo sector</summary><form method="POST" action="{{ route('configuracion.sectores.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Área *</label><select class="cfg-select" name="area_id" required><option value="">Seleccione</option>@foreach($areasSelect as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code" maxlength="30"></div><div class="cfg-field wide"><label>Nombre *</label><input class="cfg-input" name="name" required maxlength="180"></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar sector</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Sector</th><th>Área</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($sectors as $sector)
                <tr><td>{{ $sector->access_code ?? '-' }}</td><td>{{ $sector->name }}</td><td>{{ $sector->area?->name ?? '-' }}</td><td><span class="cfg-status {{ $sector->is_active ? 'active' : 'inactive' }}">{{ $sector->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('sector-{{ $sector->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.sectores.estado', $sector) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $sector->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $sector->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="sector-{{ $sector->id }}" hidden><td colspan="5"><form method="POST" action="{{ route('configuracion.sectores.update', $sector) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Área</label><select class="cfg-select" name="area_id" required>@foreach($areasSelect as $area)<option value="{{ $area->id }}" @selected($sector->area_id === $area->id)>{{ $area->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $sector->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $sector->name }}" required></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="5" class="cfg-empty">No existen sectores registrados.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $sectors->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="establecimientos">
        <div class="cfg-section-head"><div><h2>Establecimientos</h2><small>Establecimientos urbanos y rurales.</small></div></div>
        <details class="cfg-create"><summary>+ Nuevo establecimiento</summary><form method="POST" action="{{ route('configuracion.establecimientos.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Secretaría regional</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}">{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Municipio *</label><select class="cfg-select" name="municipality_id" required><option value="">Seleccione</option>@foreach($municipalitiesSelect as $municipality)<option value="{{ $municipality->id }}">{{ $municipality->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Distrito</label><select class="cfg-select" name="district_id"><option value="">Sin asignar</option>@foreach($districtsSelect as $district)<option value="{{ $district->id }}">{{ $district->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Área</label><select class="cfg-select" name="area_id"><option value="">Sin asignar</option>@foreach($areasSelect as $area)<option value="{{ $area->id }}">{{ $area->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Sector</label><select class="cfg-select" name="sector_id"><option value="">Sin asignar</option>@foreach($sectorsSelect as $sector)<option value="{{ $sector->id }}">{{ $sector->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código Access</label><input class="cfg-input" name="access_code"></div><div class="cfg-field wide"><label>Nombre *</label><input class="cfg-input" name="name" required></div><div class="cfg-field"><label>Clasificación *</label><select class="cfg-select" name="classification" required><option value="U">Urbano</option><option value="R">Rural</option></select></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar establecimiento</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Establecimiento</th><th>Municipio</th><th>Distrito</th><th>Clase</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($healthFacilities as $facility)
                <tr><td>{{ $facility->access_code ?? '-' }}</td><td>{{ $facility->name }}</td><td>{{ $facility->municipality?->name ?? '-' }}</td><td>{{ $facility->district?->name ?? '-' }}</td><td>{{ $facility->classification === 'R' ? 'Rural' : 'Urbano' }}</td><td><span class="cfg-status {{ $facility->is_active ? 'active' : 'inactive' }}">{{ $facility->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('facility-{{ $facility->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.establecimientos.estado', $facility) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $facility->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $facility->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="facility-{{ $facility->id }}" hidden><td colspan="7"><form method="POST" action="{{ route('configuracion.establecimientos.update', $facility) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Secretaría</label><select class="cfg-select" name="regional_secretariat_id"><option value="">Sin asignar</option>@foreach($regionalSecretariatsSelect as $secretariat)<option value="{{ $secretariat->id }}" @selected($facility->regional_secretariat_id === $secretariat->id)>{{ $secretariat->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Municipio</label><select class="cfg-select" name="municipality_id" required>@foreach($municipalitiesSelect as $municipality)<option value="{{ $municipality->id }}" @selected($facility->municipality_id === $municipality->id)>{{ $municipality->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Distrito</label><select class="cfg-select" name="district_id"><option value="">Sin asignar</option>@foreach($districtsSelect as $district)<option value="{{ $district->id }}" @selected($facility->district_id === $district->id)>{{ $district->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Área</label><select class="cfg-select" name="area_id"><option value="">Sin asignar</option>@foreach($areasSelect as $area)<option value="{{ $area->id }}" @selected($facility->area_id === $area->id)>{{ $area->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Sector</label><select class="cfg-select" name="sector_id"><option value="">Sin asignar</option>@foreach($sectorsSelect as $sector)<option value="{{ $sector->id }}" @selected($facility->sector_id === $sector->id)>{{ $sector->name }}</option>@endforeach</select></div><div class="cfg-field"><label>Código</label><input class="cfg-input" name="access_code" value="{{ $facility->access_code }}"></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $facility->name }}" required></div><div class="cfg-field"><label>Clasificación</label><select class="cfg-select" name="classification"><option value="U" @selected($facility->classification === 'U')>Urbano</option><option value="R" @selected($facility->classification === 'R')>Rural</option></select></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="7" class="cfg-empty">No existen establecimientos registrados.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $healthFacilities->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="protocolos">
        <div class="cfg-section-head"><div><h2>Protocolos de anatomopatología</h2><small>Administre los títulos disponibles en el protocolo CAP. Los campos desactivados se ocultan en informes nuevos y se conservan en los informes guardados.</small></div></div>
        <details class="cfg-create" @if($errors->has('normalized_name')) open @endif>
            <summary>+ Nuevo protocolo</summary>
            <form method="POST" action="{{ route('configuracion.protocolos.store') }}" class="cfg-form">
                @csrf
                <div class="cfg-field wide"><label for="cfg-protocol-name">Título del protocolo *</label><input id="cfg-protocol-name" class="cfg-input" name="name" maxlength="180" required placeholder="Escriba el título que aparecerá en la lista" value="{{ old('name') }}"></div>
                <div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="plus" /> Añadir protocolo</button></div>
            </form>
        </details>
        <div class="cfg-table-wrap"><table class="cfg-table">
            <thead><tr><th>Título</th><th>Tipo de campo</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody>@foreach($capProtocolFields as $protocol)
                <tr><td>{{ $protocol->name }}</td><td>{{ $protocol->field_key ? 'Campo inicial del sistema' : 'Añadido por administración' }}@if($protocol->field_key === 'specimen')<br><small>Campo opcional al incluir el protocolo.</small>@elseif($protocol->field_key === 'note')<br><small>Se muestra al final mientras esté activo.</small>@endif</td><td><span class="cfg-status {{ $protocol->is_active ? 'active' : 'inactive' }}">{{ $protocol->is_active ? 'Activo' : 'Inactivo' }}</span></td><td>
                    <form method="POST" action="{{ route('configuracion.protocolos.estado', $protocol) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $protocol->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $protocol->is_active ? 'Desactivar' : 'Activar' }}</button></form>
                </td></tr>
            @endforeach</tbody>
        </table></div>
        <div class="cfg-pagination">{{ $capProtocolFields->withQueryString()->links() }}</div>
    </section>

    <section class="cfg-section" id="tipos-patologia">
        <div class="cfg-section-head"><div><h2>Tipos de patología</h2><small>Opciones disponibles en el formulario de solicitud.</small></div></div>
        <details class="cfg-create"><summary>+ Nuevo tipo de patología</summary><form method="POST" action="{{ route('configuracion.patologias.store') }}" class="cfg-form">@csrf<div class="cfg-field"><label>Código interno *</label><input class="cfg-input" name="code" placeholder="ej. biopsy" required></div><div class="cfg-field wide"><label>Nombre visible *</label><input class="cfg-input" name="name" required></div><div class="cfg-field full"><label>Descripción</label><textarea class="cfg-textarea" name="description"></textarea></div><div class="cfg-actions"><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar tipo</button></div></form></details>
        <div class="cfg-table-wrap"><table class="cfg-table"><thead><tr><th>Código</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
            @forelse($pathologyTypes as $type)
                <tr><td>{{ $type->code }}</td><td>{{ $type->name }}</td><td>{{ $type->description ?? '-' }}</td><td><span class="cfg-status {{ $type->is_active ? 'active' : 'inactive' }}">{{ $type->is_active ? 'Activo' : 'Inactivo' }}</span></td><td><div class="cfg-row-actions"><button class="cfg-btn secondary" type="button" onclick="toggleConfigRow('type-{{ $type->id }}')">Editar</button><form method="POST" action="{{ route('configuracion.patologias.estado', $type) }}">@csrf @method('PATCH')<button class="cfg-btn {{ $type->is_active ? 'danger' : 'secondary' }}" type="submit">{{ $type->is_active ? 'Desactivar' : 'Activar' }}</button></form></div></td></tr>
                <tr class="cfg-edit-row" id="type-{{ $type->id }}" hidden><td colspan="5"><form method="POST" action="{{ route('configuracion.patologias.update', $type) }}" class="cfg-edit-form">@csrf @method('PUT')<div class="cfg-field"><label>Código</label><input class="cfg-input" name="code" value="{{ $type->code }}" required></div><div class="cfg-field"><label>Nombre</label><input class="cfg-input" name="name" value="{{ $type->name }}" required></div><div class="cfg-field wide"><label>Descripción</label><input class="cfg-input" name="description" value="{{ $type->description }}"></div><button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Actualizar</button></form></td></tr>
            @empty<tr><td colspan="5" class="cfg-empty">No existen tipos registrados.</td></tr>@endforelse
        </tbody></table></div><div class="cfg-pagination">{{ $pathologyTypes->withQueryString()->links() }}</div>
    </section>
</div>

<dialog id="cfg-edit-dialog" class="cfg-edit-dialog" aria-labelledby="cfg-edit-title" aria-describedby="cfg-edit-subtitle">
    <header class="cfg-edit-dialog-head"><div><span class="cfg-eyebrow">CONFIGURACIÓN</span><h2 id="cfg-edit-title">Editar registro</h2><p id="cfg-edit-subtitle">Actualice los datos y guarde los cambios.</p></div><button type="button" class="cfg-dialog-close" data-close-config-dialog aria-label="Cerrar edición">×</button></header>
    <div id="cfg-edit-dialog-content" class="cfg-edit-dialog-content"></div>
    <footer class="cfg-edit-dialog-actions"><button type="button" class="cfg-btn secondary" data-close-config-dialog>Cancelar</button><button type="submit" class="cfg-btn" id="cfg-dialog-save"><x-ui-icon name="save" /> Guardar cambios</button></footer>
</dialog>
<script>
let configEditing = null;
function toggleConfigRow(id) {
    const row = document.getElementById(id);
    const form = row?.querySelector('form.cfg-edit-form');
    const dialog = document.getElementById('cfg-edit-dialog');
    if (!form || dialog.open) return;
    const titles = {user:'Editar usuario',physician:'Editar médico',municipality:'Editar municipio',district:'Editar distrito',area:'Editar área',sector:'Editar sector',facility:'Editar establecimiento',type:'Editar tipo de patología'};
    document.getElementById('cfg-edit-title').textContent = titles[id.split('-')[0]] || 'Editar registro';
    document.getElementById('cfg-edit-subtitle').textContent = form.querySelector('[name="name"],[name="full_name"]')?.value || 'Actualice los datos y guarde los cambios.';
    configEditing = {form,parent:form.parentNode,buttons:[...form.querySelectorAll('button[type="submit"]')]};
    form.id ||= 'cfg-form-'+id;
    let marker = form.querySelector('[name="_config_edit_id"]');
    if (!marker) { marker=document.createElement('input'); marker.type='hidden'; marker.name='_config_edit_id'; form.append(marker); }
    marker.value=id;
    configEditing.buttons.forEach(button=>button.hidden=true);
    form.querySelectorAll('.cfg-field').forEach((field,index)=>{const input=field.querySelector('input:not([type="hidden"]),select,textarea');const label=field.querySelector('label');if(input&&label&&input.type!=='checkbox'){input.id ||= 'cfg-'+id+'-'+index;label.htmlFor=input.id;}});
    document.getElementById('cfg-edit-dialog-content').append(form);
    document.getElementById('cfg-dialog-save').setAttribute('form',form.id);
    dialog.showModal();
    form.querySelector('input:not([type="hidden"]),select,textarea')?.focus();
}

document.addEventListener('DOMContentLoaded', () => {
    const editDialog=document.getElementById('cfg-edit-dialog');
    editDialog.querySelectorAll('[data-close-config-dialog]').forEach(button=>button.addEventListener('click',()=>editDialog.close()));
    editDialog.addEventListener('click',event=>{if(event.target===editDialog){const r=editDialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right||event.clientY<r.top||event.clientY>r.bottom)editDialog.close();}});
    editDialog.addEventListener('close',()=>{
        if(!configEditing)return;
        const {form,parent,buttons}=configEditing;
        form.reset();
        form.querySelectorAll('[name="password"],[name="password_confirmation"]').forEach(input=>{input.value='';input.type='password';});
        form.querySelectorAll('.cfg-password-toggle').forEach(button=>{button.setAttribute('aria-label','Mostrar contraseña');button.title='Mostrar contraseña';});
        buttons.forEach(button=>button.hidden=false);
        parent.append(form);
        form.querySelector('[name="role"]')?.dispatchEvent(new Event('change'));
        document.getElementById('cfg-edit-dialog-content').replaceChildren();
        configEditing=null;
    });
    document.querySelectorAll('select[name="role"]').forEach(role => {
        const option = role.form.querySelector('[data-report-consultation]');
        if (!option) return;
        const sync = () => {
            const restricted = ['result_reader', 'pathologist'].includes(role.value);
            option.hidden = restricted;
            option.style.display = restricted ? 'none' : '';
            const checkbox = option.querySelector('input[type="checkbox"]');
            checkbox.disabled = restricted;
            if (restricted) checkbox.checked = false;
        };
        role.addEventListener('change', sync);
        sync();
    });
    document.querySelectorAll('.cfg-password-toggle').forEach(toggle => {
        toggle.addEventListener('click', () => {
            const field = toggle.previousElementSibling;
            const showing = field.type === 'text';
            field.type = showing ? 'password' : 'text';
            toggle.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
            toggle.setAttribute('title', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });
    });

    const excludedNames = new Set(['username', 'email', 'password', 'password_confirmation', 'code']);
    const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase();
    document.querySelectorAll('.cfg-input, .cfg-textarea').forEach(field => {
        if (field.type === 'password' || field.type === 'email' || excludedNames.has(field.name)) return;
        field.classList.add('cfg-uppercase');
        field.addEventListener('input', () => {
            const start = field.selectionStart;
            field.value = normalize(field.value);
            if (typeof start === 'number') field.setSelectionRange(start, start);
        });
    });

    const tabs = [...document.querySelectorAll('[data-config-tab]')];
    const sections = [...document.querySelectorAll('.cfg-section')];
    const params = new URLSearchParams(window.location.search);
    const paginationTabs = {
        users_page: 'usuarios',
        physicians_page: 'medicos',
        municipalities_page: 'municipios',
        districts_page: 'distritos',
        areas_page: 'areas',
        sectors_page: 'sectores',
        facilities_page: 'establecimientos',
        pathology_types_page: 'tipos-patologia',
        cap_protocols_page: 'protocolos'
    };
    const paginatedTab = Object.entries(paginationTabs).find(([key]) => params.has(key))?.[1];
    const showTab = requested => {
        const id = document.getElementById(requested) ? requested : 'usuarios';
        sections.forEach(section => section.classList.toggle('active', section.id === id));
        tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.configTab === id));
        history.replaceState(null, '', `${window.location.pathname}${window.location.search}#${id}`);
        sessionStorage.setItem('configuracion_tab', id);
        document.getElementById(id)?.scrollTo({ top: 0 });
    };
    tabs.forEach(tab => tab.addEventListener('click', event => {
        event.preventDefault();
        showTab(tab.dataset.configTab);
    }));
    showTab(paginatedTab || params.get('tab') || window.location.hash.slice(1) || sessionStorage.getItem('configuracion_tab') || 'usuarios');
    const failedEdit=@json($errors->any() ? old('_config_edit_id') : null);
    if(failedEdit && document.getElementById(failedEdit)){
        const row=document.getElementById(failedEdit);
        showTab(row.closest('.cfg-section').id);
        const values=@json(collect(old())->except(['_token','password','password_confirmation'])->all());
        row.querySelectorAll('input:not([type="hidden"]):not([type="password"]),select,textarea').forEach(field=>{if(!Object.prototype.hasOwnProperty.call(values,field.name))return;if(field.type==='checkbox')field.checked=String(values[field.name])===String(field.value);else field.value=values[field.name] ?? '';});
        row.querySelector('[name="role"]')?.dispatchEvent(new Event('change'));
        toggleConfigRow(failedEdit);
        const errors=document.querySelector('.cfg-alert.error');
        if(errors)document.getElementById('cfg-edit-dialog-content').prepend(errors);
    }
});
</script>

@endsection
