@extends('layouts.app')

@section('title', 'Solicitudes / Muestras')

@section('content')

<style>
.sol-validation{display:flex;align-items:flex-start;gap:14px;margin:0 0 20px;padding:18px 20px;border:1px solid #e6b4b4;border-left:5px solid #be3434;border-radius:10px;background:#fff5f5;color:#762727}
.sol-validation-icon{flex:0 0 24px;width:24px;height:24px;margin-top:1px;color:#be3434}
.sol-validation-content{min-width:0}
.sol-validation-title{margin:0 0 5px;color:inherit;font-size:16px;font-weight:700;line-height:1.5}
.sol-validation .sol-validation-list{margin:0;padding:0;list-style:none;font-size:14px;font-weight:400;line-height:1.6}
.sol-validation-list li+li{margin-top:5px}
html[data-theme="dark"] .sol-validation{background:#352326;border-color:#855054;border-left-color:#ff9393;color:#ffe2e2}
html[data-theme="dark"] .sol-validation-icon{color:#ff9393}
@media(max-width:600px){.sol-validation{padding:14px;gap:10px}}
#module_details_dialog .gyn-patient-category{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;height:44px;box-sizing:border-box;border:1px solid #cfddd4;border-radius:8px;background:#fff;overflow:hidden}
#module_details_dialog .gyn-patient-category label{display:flex;align-items:center;justify-content:center;gap:8px;min-width:0;height:100%;box-sizing:border-box;margin:0;padding:8px 10px;font-size:14px;font-weight:400;line-height:1.5;cursor:pointer;color:#4b6355;transition:background .15s,color .15s}
#module_details_dialog .gyn-patient-category label+label{border-left:1px solid #cfddd4}
#module_details_dialog .gyn-patient-category label:has(input:checked){background:#e8f4ed;color:#12804e;font-weight:600}
#module_details_dialog .gyn-patient-category label:has(input:focus-visible){outline:2px solid #159560;outline-offset:-3px}
#module_details_dialog .gyn-patient-category input[type=radio]{position:static!important;opacity:1!important;pointer-events:auto!important;appearance:auto;flex:0 0 16px;width:16px;height:16px;margin:0;accent-color:#12804e}
html[data-theme="dark"] #module_details_dialog .gyn-patient-category{background:#172125;border-color:#496052}
html[data-theme="dark"] #module_details_dialog .gyn-patient-category label{color:#aec3b6}
html[data-theme="dark"] #module_details_dialog .gyn-patient-category label+label{border-color:#496052}
html[data-theme="dark"] #module_details_dialog .gyn-patient-category label:has(input:checked){background:#223b30;color:#e4f1e9}
.sol-daily-register-link{order:2;display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:7px;background:#176d44;color:#fff;text-decoration:none;font-size:13px;font-weight:700;line-height:1.4}.sol-modern-header{gap:12px;flex-wrap:wrap}.sol-daily-register-link:hover{background:#115333;color:#fff}
/* Ventana de registro: medidas y jerarquía compartidas por las tres áreas. */
#module_details_dialog{box-sizing:border-box;width:min(960px,calc(100vw - 48px));padding:0;border:1px solid #dbe6df;border-radius:16px;background:#fff;box-shadow:0 24px 70px #102c2433}
#module_details_dialog .sol-card-header{margin:0;padding:22px 32px;gap:14px;background:#f5faf7;border-bottom:1px solid #e0eae4;align-items:center}
#module_details_dialog .sol-card-header-icon{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border:1px solid #cce5d7;border-radius:12px;background:#e8f4ed;color:#14804d}
#module_details_dialog .sol-card-header-text h3{margin:0 0 5px;font-size:18px;line-height:1.4;font-weight:700;color:#173d2e}
#module_details_dialog .sol-card-header-text small{font-size:12px;color:#6b8074}
#module_details_dialog .module-dialog-close{flex-shrink:0;width:32px;height:32px;min-height:32px;border-radius:8px;background:#fff;border:1px solid #dce7e0;color:#52695b}
#module_details_dialog>.sol-divider{display:none}
#module_details_dialog .modulo-form{padding:26px 32px 0}
#module_details_dialog .sol-form-grid-2,#module_details_dialog .sol-form-grid-3{gap:20px 28px;margin:0}
#module_details_dialog #form_gineco .sol-form-grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
#module_details_dialog #form_gineco .sol-form-group.full{grid-column:auto}

#module_details_dialog .sol-form-group{min-width:0;margin:0}
#module_details_dialog .sol-form-group label{margin-bottom:8px;font-size:13px;font-weight:600;line-height:1.5;color:#304d3f}
#module_details_dialog .smart-select-control,#module_details_dialog input.sol-input{box-sizing:border-box;min-height:44px;height:44px;border:1px solid #cfddd4;border-radius:8px;background:#fff;padding:10px 12px;color:#213d2e;font-family:inherit;font-size:14px;font-weight:400;line-height:1.5}
#module_details_dialog .smart-select-control:hover,#module_details_dialog input.sol-input:hover{border-color:#84ad95}
#module_details_dialog .catalog-add{padding:1px 5px;font-size:11px;font-weight:600;color:#16824e;background:transparent;border:0}
#module_details_dialog #sample_fields{padding:24px 32px 28px;grid-template-columns:repeat(2,minmax(0,1fr));margin:0}
#module_details_dialog .sol-actions{position:static;margin:0;padding:18px 32px;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #e0eae4;background:#f8fbf9;box-shadow:none}
#module_details_dialog .sol-actions button{min-height:42px;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600}
#module_details_dialog .sol-actions .btn-secondary{background:#fff;border:1px solid #d4e0d8;color:#4b6355;box-shadow:none}
#module_details_dialog .sol-actions .btn-primary{background:#12804e;border:1px solid #12804e;box-shadow:0 3px 8px #12804e1a}
html[data-theme="dark"] #module_details_dialog{background:#1b272b;border-color:#405158}
html[data-theme="dark"] #module_details_dialog .sol-card-header,html[data-theme="dark"] #module_details_dialog .sol-actions{background:#22332c;border-color:#405148}
html[data-theme="dark"] #module_details_dialog .sol-card-header-text h3,html[data-theme="dark"] #module_details_dialog .sol-form-group label{color:#e4f1e9}
html[data-theme="dark"] #module_details_dialog .sol-card-header-text small{color:#aec3b6}
html[data-theme="dark"] #module_details_dialog .smart-select-control,html[data-theme="dark"] #module_details_dialog input.sol-input,html[data-theme="dark"] #module_details_dialog .btn-secondary,html[data-theme="dark"] #module_details_dialog .module-dialog-close{background:#172125;color:#edf5f0;border-color:#496052}
@media(max-width:760px){#module_details_dialog{width:calc(100vw - 24px)}#module_details_dialog .sol-card-header{padding:18px}#module_details_dialog .modulo-form{padding:20px 18px 0}#module_details_dialog .sol-form-grid-2,#module_details_dialog .sol-form-grid-3,#module_details_dialog #form_gineco .sol-form-grid-2,#module_details_dialog #sample_fields{grid-template-columns:minmax(0,1fr)}#module_details_dialog #sample_fields{padding:20px 18px}#module_details_dialog .sol-actions{padding:14px 18px;flex-wrap:wrap}}
/* Rural: columnas amplias y alineación consistente con los datos de muestra. */
#module_details_dialog #form_rural .sol-form-grid-3{grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 28px}
#module_details_dialog #form_rural .smart-select-control{height:auto;min-height:44px;align-items:center}
#module_details_dialog #form_rural .smart-select-control>span:first-child{min-width:0;white-space:normal;overflow-wrap:anywhere;line-height:1.45}
#module_details_dialog #form_rural .smart-select-arrow{flex-shrink:0;color:#617b6b}
#module_details_dialog:has(#form_rural:not([style*="display:none"]):not([style*="display: none"])) .sol-card-header{padding-top:18px;padding-bottom:18px}
@media(max-width:760px){#module_details_dialog #form_rural .sol-form-grid-3{grid-template-columns:minmax(0,1fr);gap:16px}}
#module_details_dialog .smart-select-control>span:first-child{min-width:0;white-space:normal;overflow-wrap:anywhere;line-height:1.45}
#module_details_dialog .smart-select-control{height:auto;min-height:44px}
#module_details_dialog .smart-select-arrow{flex-shrink:0}
#module_details_dialog .smart-select-control:focus-visible,#module_details_dialog input.sol-input:focus-visible{outline:2px solid #16824e;outline-offset:2px}
</style>
<div class="sol-modern-wrapper">

    {{-- ==========================
        ENCABEZADO PRINCIPAL
    =========================== --}}

    <div class="sol-modern-header">

        <a href="{{ route('solicitudes.registro-diario') }}" class="sol-daily-register-link">Registro diario · Rural / Urbano / Ginecología</a>


        <div class="sol-header-left">


            <div>

                <h1>
                    Registro de Solicitud / Muestra
                </h1>

                <p>
                    Laboratorio de Patología - Hospital Daniel Bracamonte
                </p>


            </div>

        </div>


    </div>


    {{-- ==========================
        MENSAJES
    =========================== --}}


    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif


    @if($errors->any())

        <div class="sol-validation" role="alert" aria-labelledby="sol-validation-title">
            <svg class="sol-validation-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
                <circle cx="12" cy="12" r="9"/><path d="M12 7v6m0 4h.01"/>
            </svg>
            <div class="sol-validation-content">
                <h2 id="sol-validation-title" class="sol-validation-title">No se pudo guardar la solicitud</h2>
                <ul class="sol-validation-list">
                    @foreach($errors->getMessages() as $field => $messages)
                        @foreach($messages as $error)
                            <li>{{ $field === 'patient_id' && $error === __('validation.required', ['attribute' => 'paciente']) ? 'Seleccione un paciente en «Buscar paciente» para continuar.' : $error }}</li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>

    @endif



    {{-- ==========================
        FORMULARIO PRINCIPAL
    =========================== --}}

    <form
        id="patient_search_form"
        method="GET"
        action="{{ route('solicitudes.index') }}"
    ></form>


    <form

        id="form_solicitud"

        method="POST"

        action="{{ route('solicitudes.store') }}"

    >

        @csrf


        {{-- DATOS OCULTOS --}}


        <input

            type="hidden"

            name="patient_id"

            id="patient_id"

            value="{{ old('patient_id', $selectedPatient?->id) }}"

        >


        <input

            type="hidden"

            name="module"

            id="module"

            value="{{ old('module') }}"

        >

        <div class="sol-layout">

            {{-- ==========================
                COLUMNA PRINCIPAL
            =========================== --}}


            <div class="sol-main">


                {{-- BUSCADOR PACIENTE --}}


                <div class="sol-card sol-search-card">


                    <div class="sol-card-header">


                        <div class="sol-card-header-icon sol-step-icon">1</div>

                        <div class="sol-card-header-text">


                            <h3>
                                Buscar paciente
                            </h3>


                            <small>
                                Busque por CI, nombre completo o apellido
                            </small>


                        </div>


                    </div>

                    <div class="sol-search-row">

                        <input

                            type="text"

                            name="patient_query"

                            id="patient_search"

                            form="patient_search_form"

                            class="sol-input"
                            data-uppercase-search
                            autocomplete="off"
                            placeholder="INGRESE CI, NOMBRE O APELLIDO"

                            value="{{ old('patient_query', $patientQuery ?? '') }}"

                        >

                        <button

                            type="submit"


                            id="btn_search_patient"


                            class="sol-btn-primary"

                            form="patient_search_form"

                            formnovalidate

                        >

                            Buscar


                        </button>

                    </div>

                    <section id="patient-picker" class="patient-picker" aria-labelledby="patient-picker-title">
                        <div class="patient-picker-header">
                            <div>
                                <h3 id="patient-picker-title">Seleccionar paciente</h3>
                                <p>Compare el nombre y el CI antes de elegir.</p>
                            </div>
                        </div>
                    <div id="patient_results">
                        @if(!$selectedPatient && ($patientMatches ?? collect())->isNotEmpty())
                            <div class="patient-match-list">
                                <div class="patient-match-heading">Seleccione el paciente correcto:</div>
                                @foreach($patientMatches as $match)
                                    <a class="patient-match-item" href="{{ route('solicitudes.index', ['patient_query' => $patientQuery, 'selected_patient_id' => $match->id]) }}">
                                        <strong>{{ $match->full_name }}</strong>
                                        <span>CI: {{ $match->identity_document }}{{ $match->birth_date ? ' · Nac.: ' . $match->birth_date->format('d/m/Y') : '' }}</span>
                                    </a>
                                @endforeach
                                {{ $patientMatches->links('pagination.compact') }}
                            </div>
                        @elseif(($patientQuery ?? '') !== '' && !$selectedPatient)
                            <div class="patient-result-empty">
                                NO SE ENCONTRÓ PACIENTE CON: {{ $patientQuery }}
                            </div>
                        @endif
                    </div>
                    </section>

                </div>

                {{-- RESUMEN PACIENTE --}}


                <div

                    class="sol-card sol-patient-card"

                    id="patient_card"

                >

                    <div class="sol-card-header">


                        <div class="sol-card-header-icon sol-step-icon">2</div>


                        <div class="sol-card-header-text">


                            <h3>

                                Paciente seleccionado

                            </h3>


                        </div>

                        @if($selectedPatient)
                            <a href="{{ route('solicitudes.index') }}" class="change-patient-btn">Cambiar paciente</a>
                        @endif

                    </div>


                    <div class="patient-info">
                        <div id="patient_empty" class="patient-empty" @if($selectedPatient) style="display:none;" @endif>
                            Seleccione un paciente para ver sus datos.
                        </div>


                        <h2 id="patient_name">{{ $selectedPatient?->full_name }}</h2>


                        <div class="patient-data">

                            <span>
                                CI:
                                <b id="patient_ci">{{ $selectedPatient?->identity_document }}</b>
                            </span>


                            <span>
                                Fecha nac:
                                <b id="patient_birth">{{ optional($selectedPatient?->birth_date)->format('Y-m-d') }}</b></span><span>Edad actual: <b id="patient_age">{{ $selectedPatient?->current_age ?? '—' }}</b>
                            </span>


                            <span>
                                Sexo:
                                <b id="patient_gender">{{ $selectedPatient?->sex }}</b>
                            </span>


                            <span>
                                Tel:
                                <b id="patient_phone">{{ $selectedPatient?->phone }}</b>
                            </span>



                        </div>


                    </div>


                </div>
                {{-- ==========================
                    SELECTOR DE MODULO
                =========================== --}}


                <div class="sol-card sol-module-card">


                    <div class="sol-card-header">


                        <div class="sol-card-header-icon sol-step-icon">3</div>

                        <div class="sol-card-header-text">

                            <h3>

                                Tipo de solicitud

                            </h3>

                            <small>

                                Seleccione el módulo correspondiente

                            </small>

                        </div>

                    </div>

                    <div class="sol-divider"></div>

                    <script>
                    window.abrirAreaSolicitud = function (module) {
                        const config = { U: ['btn_urbano','form_urbano','Urbano'], R: ['btn_rural','form_rural','Rural'], G: ['btn_gineco','form_gineco','Ginecología'] };
                        const selected = config[module];
                        if (!selected || !document.getElementById('patient_id')?.value) {
                            document.getElementById('patient_search')?.focus();
                            return false;
                        }
                        document.getElementById('module').value = module;
                        document.getElementById('sequence_type').value = module;
                        Object.values(config).forEach(function (item) {
                            document.getElementById(item[0])?.classList.remove('active');
                            const section = document.getElementById(item[1]);
                            if (section) {
                                section.style.display = 'none';
                                section.querySelectorAll('input,select,textarea').forEach(field => field.disabled = true);
                            }
                        });
                        document.getElementById(selected[0]).classList.add('active');
                        const section = document.getElementById(selected[1]);
                        section.style.display = 'block';
                        section.querySelectorAll('input,select,textarea').forEach(field => field.disabled = false);
                        const samples = document.getElementById('sample_fields');
                        samples.style.display = 'grid';
                        samples.querySelectorAll('input,select,textarea').forEach(field => field.disabled = false);
                        document.getElementById('titulo_modulo').textContent = 'Información de solicitud - ' + selected[2];
                        document.getElementById('subtitulo_modulo').textContent = 'Serie asignada automáticamente: ' + module;
                        document.getElementById('module_summary_title').textContent = 'Información de solicitud - ' + selected[2];
                        document.getElementById('module_summary_text').textContent = 'Complete los datos requeridos en el panel inferior.';
                        document.getElementById('module_summary_edit').hidden = false;
                        const details = document.getElementById('module_details_dialog');
                        details.classList.remove('module-dialog-dismissed');
                        details.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        return false;
                    };
                    </script>

                    <div class="module-selector">

                        <button

                            type="button"

                            class="module-option"

                            id="btn_urbano"

                            onclick="return abrirAreaSolicitud('U')"

                        >

                            <span>

                                Urbano

                            </span>


                            <small>

                                Atención urbana

                            </small>
                        </button>

                        <button

                            type="button"

                            class="module-option"

                            id="btn_rural"

                            onclick="return abrirAreaSolicitud('R')"

                        >

                            <span>

                                Rural

                            </span>


                            <small>

                                Atención rural

                            </small>
                        </button>

                        <button

                            type="button"

                            class="module-option"

                            id="btn_gineco"

                            onclick="return abrirAreaSolicitud('G')"

                        >

                            <span>


                                Ginecología

                            </span>

                            <small>

                                Servicio ginecológico

                            </small>
                        </button>

                    </div>

                </div>

                {{-- ==========================
                    FORMULARIO SOLICITUD
                =========================== --}}

                <div class="sol-card module-summary-card" id="module_summary_card">
                    <div class="sol-card-header">
                        <div class="sol-card-header-icon sol-step-icon">4</div>
                        <div class="sol-card-header-text">
                            <h3 id="module_summary_title">Información de solicitud</h3>
                            <small id="module_summary_text">Seleccione Urbano, Rural o Ginecología para completar la información.</small>
                        </div>
                        <button type="button" class="module-summary-edit" id="module_summary_edit" onclick="return abrirAreaSolicitud(document.getElementById('module').value)" hidden>
                            Editar información
                        </button>
                    </div>
                </div>

                <div class="sol-card sol-request-card" id="module_details_dialog" role="dialog" aria-modal="true" aria-labelledby="titulo_modulo">

                    <div class="sol-card-header">


                        <div class="sol-card-header-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M7 3h7l4 4v14H7z" />
                                <path d="M14 3v5h5M10 12h5M10 16h5" />
                            </svg>
                        </div>

                        <div class="sol-card-header-text">

                            <h3 id="titulo_modulo">

                                Información de solicitud

                            </h3>

                            <small id="subtitulo_modulo">

                                Seleccione Urbano, Rural o Ginecología

                            </small>

                        </div>

                        <button type="button" class="module-dialog-close" id="module_dialog_close" onclick="document.getElementById('module_details_dialog').classList.add('module-dialog-dismissed')" aria-label="Cerrar ventana">×</button>

                    </div>

                    <div class="sol-divider"></div>

                    {{-- ==========================
                        URBANO
                    =========================== --}}

                    <div id="form_urbano" class="modulo-form" style="display:none;">
                        <div class="sol-form-grid-3">
                            <div class="sol-form-group">
                                <label>Distrito * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="district" data-target="district_id">＋ Añadir</button>@endif</label>
                                <select name="district_id" id="district_id" class="sol-select" required>
                                    <option value="">Seleccione</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->id }}">
                                            {{ $district->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Área * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="area" data-target="area_id_urban">＋ Añadir</button>@endif</label>
                                <select name="area_id" id="area_id_urban" class="sol-select" required>
                                    <option value="">Seleccione</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Sector * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="sector" data-target="sector_id_urban">＋ Añadir</button>@endif</label>
                                <select name="sector_id" id="sector_id_urban" class="sol-select" required>
                                    <option value="">Seleccione</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ==========================
                        RURAL
                    =========================== --}}

                    <div id="form_rural" class="modulo-form" style="display:none;">
                        <div class="sol-form-grid-3">
                            <div class="sol-form-group">
                                <label>Secretaría Regional *</label>
                                <select name="regional_secretariat_id" id="regional_secretariat_id_rural" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($regionalSecretariats as $regional)
                                        <option value="{{ $regional->id }}">{{ $regional->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Municipio * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="municipality" data-target="municipality_id">＋ Añadir</button>@endif</label>
                                <select name="municipality_id" id="municipality_id" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($municipalities as $municipality)
                                        <option value="{{ $municipality->id }}">
                                            {{ $municipality->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Distrito * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="district" data-target="district_id_rural">＋ Añadir</button>@endif</label>
                                <select name="district_id" id="district_id_rural" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($districts as $district)
                                        <option value="{{ $district->id }}">
                                            {{ $district->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Área * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="area" data-target="area_id">＋ Añadir</button>@endif</label>
                                <select name="area_id" id="area_id" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}">
                                            {{ $area->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Sector * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="sector" data-target="sector_id">＋ Añadir</button>@endif</label>
                                <select name="sector_id" id="sector_id" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector->id }}">
                                            {{ $sector->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sol-form-group">
                                <label>Establecimiento * @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="facility" data-target="health_facility_id_rural" data-classification="R">＋ Añadir</button>@endif</label>
                                <select name="health_facility_id" id="health_facility_id_rural" class="sol-select">
                                    <option value="">Seleccione</option>
                                    @foreach($healthFacilities as $facility)
                                        <option value="{{ $facility->id }}">
                                            {{ $facility->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- ==========================
                        GINECOLOGIA
                    =========================== --}}

                    <div

                        id="form_gineco"

                        class="modulo-form"

                        style="display:none;"

                    >

                        <div class="sol-form-grid-2">
                            <div class="sol-form-group full">
                                <label>Tipo de paciente *</label>
                                <div class="gyn-patient-category" role="group" aria-label="Tipo de paciente">
                                    <label><input type="radio" name="patient_category" value="insured" {{ old('patient_category', 'insured') === 'insured' ? 'checked' : '' }}> Asegurado</label>
                                    <label><input type="radio" name="patient_category" value="private" {{ old('patient_category') === 'private' ? 'checked' : '' }}> Particular (P)</label>
                                </div>
                            </div>

                            <div class="sol-form-group full">

                                <label>Secretaría Regional *</label>

                                <select
                                    name="regional_secretariat_id"
                                    id="regional_secretariat_id_gineco"
                                    class="sol-select"
                                >
                                    <option value="">Seleccione</option>
                                    @foreach($regionalSecretariats as $regional)
                                        <option value="{{ $regional->id }}">{{ $regional->name }}</option>
                                    @endforeach
                                </select>

                            </div>

                            <div class="sol-form-group full">

                                <label>
                                    Municipio de residencia del usuario *
                                    @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="municipality" data-target="residence_municipality_id">＋ Añadir</button>@endif
                                </label>

                                <select

                                    name="residence_municipality_id"

                                    id="residence_municipality_id"

                                    class="sol-select"

                                >

                                    <option value="">

                                        Seleccione

                                    </option>

                                    @foreach($municipalities as $municipality)

                                        <option value="{{ $municipality->id }}">

                                            {{ $municipality->name }}

                                        </option>

                                    @endforeach


                                </select>

                            </div>

                            <div class="sol-form-group full">


                                <label>
                                    Establecimiento *
                                    @if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))<button type="button" class="catalog-add" data-catalog="facility" data-target="health_facility_id_gineco" data-classification="U">＋ Añadir</button>@endif
                                </label>

                                <select

                                    name="health_facility_id"

                                    id="health_facility_id_gineco"

                                    class="sol-select"

                                >

                                    <option value="">


                                        Seleccione


                                    </option>

                                    @foreach($healthFacilities as $facility)

                                        <option value="{{ $facility->id }}">

                                            {{ $facility->name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                        </div>


                    </div>
            {{-- DATOS GENERALES DE LA MUESTRA --}}

<div id="sample_fields" class="sol-form-grid-3" style="display:none;">

    <div class="sol-form-group">
        <label>Fecha solicitud *</label>
        <input 
            type="date" 
            name="request_date" 
            min="2025-01-01"
            class="sol-input"
            value="{{ old('request_date', date('Y-m-d')) }}"
            disabled
            required
        >
    </div>




    <div class="sol-form-group">
        <label>Cantidad de muestras *</label>
        <input 
            type="number" 
            name="sample_quantity"
            class="sol-input"
            min="1"
            value="{{ old('sample_quantity',1) }}"
            disabled
            required
        >
    </div>

</div>



<input 
    type="hidden"
    name="sequence_type"
    id="sequence_type"
    value="{{ old('sequence_type') }}"
>



<div class="sol-actions">

    <button 
        type="reset"
        class="btn-secondary"
    >
        Limpiar
    </button>


    <button 
        type="submit"
        class="btn-primary"
    >
        Guardar solicitud
    </button>

</div>

</div> 
</div> {{-- cierre sol-main --}}

{{-- ==========================
    COLUMNA LATERAL
=========================== --}}

<div class="sol-sidebar">

    <div class="sol-card">

        <div class="sol-card-header">

            <div class="sol-card-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M4.5 21a7.5 7.5 0 0 1 15 0" />
                </svg>
            </div>

            <div class="sol-card-header-text">

                <h3>
                    Información
                </h3>

            </div>

        </div>

        <div class="sidebar-info">

            <p>
                <strong>
                    Paciente:
                </strong>
            </p>

            <span id="sidebar_patient_name">
                -
            </span>

            <p>
                <strong>
                    Última solicitud:
                </strong>
            </p>

            <span id="sidebar_last_request">
                -
            </span>

        </div>

    </div>

    <div class="sol-card">

        <div class="sol-card-header">

            <div class="sol-card-header-icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 11v6M12 7.5h.01" />
                </svg>
            </div>

            <div class="sol-card-header-text">

                <h3>
                    Ayuda
                </h3>

            </div>

        </div>

        <ul class="sol-help-list">

            <li>
                Seleccione primero el paciente.
            </li>

            <li>
                Elija Urbano, Rural o Ginecología.
            </li>

            <li>
                El número de solicitud se genera automáticamente.
            </li>

            <li>
                Verifique los datos antes de guardar.
            </li>

        </ul>

    </div>

</div>

</div> {{-- cierre sol-layout --}}

</form>

@if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))
<div class="sol-modal" id="quick_catalog_modal" aria-hidden="true">
    <div class="sol-modal-dialog quick-catalog-dialog" role="dialog" aria-modal="true" aria-labelledby="quick_catalog_title">
        <div class="sol-modal-title">
            <div>
                <h3 id="quick_catalog_title">Añadir opción</h3>
                <p id="quick_catalog_help">La nueva opción quedará seleccionada automáticamente.</p>
            </div>
            <button type="button" class="sol-modal-close" id="quick_catalog_close" aria-label="Cerrar">×</button>
        </div>
        <form id="quick_catalog_form">
            @csrf
            <input type="hidden" name="type" id="quick_catalog_type">
            <div class="sol-form-group">
                <label for="quick_catalog_name">Nombre *</label>
                <input class="sol-input uppercase-input" id="quick_catalog_name" name="name" maxlength="220" required autocomplete="off">
            </div>
            <p class="quick-catalog-message" id="quick_catalog_message" role="status"></p>
            <div class="sol-modal-actions">
                <button type="button" class="btn-secondary" id="quick_catalog_cancel"><x-ui-icon name="close" /> Cancelar</button>
                <button type="submit" class="sol-btn-primary"><x-ui-icon name="save" /> Guardar y seleccionar</button>
            </div>
        </form>
    </div>
</div>
@endif

@if(($patientQuery ?? '') !== '' && !$selectedPatient && ($patientMatches ?? collect())->isEmpty())
    @php
        $patientQueryIsCi = preg_match('/^\d[\d\s.\-]*$/u', trim($patientQuery)) === 1;
    @endphp
    <div class="sol-modal open" id="patient_register_modal">
        <div class="sol-modal-dialog">
            <div class="sol-modal-title">
                <div>
                    <h3>Registrar nuevo paciente</h3>
                    <p>
                        @if($patientQueryIsCi)
                            No se encontró un paciente con el CI {{ $patientQuery }}.
                        @else
                            No se encontró un paciente con el nombre {{ $patientQuery }}.
                        @endif
                        Complete los datos para registrarlo.
                    </p>
                </div>
                <a href="{{ route('solicitudes.index') }}" class="sol-modal-close">×</a>
            </div>

            <form id="patient_register_form">
                @csrf

                <div class="sol-form-grid-2">
                    <div class="sol-form-group">
                        <label>CI *</label>
                        <input
                            type="text"
                            name="identity_document" maxlength="30"
                            class="sol-input"
                            value="{{ $patientQueryIsCi ? $patientQuery : '' }}"
                            autocomplete="off"
                            {{ $patientQueryIsCi ? 'autofocus' : '' }}
                            required
                        >
                    </div>

                    <div class="sol-form-group">
                        <label>Fecha de nacimiento *</label>
                        <input type="date" name="birth_date" class="sol-input" min="{{ now()->subYears(130)->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" required>
                    </div>

                    <div class="sol-form-group full">
                        <label><span class="ui-field-caption"><x-ui-icon name="user" /> Nombres y apellidos *</span></label>
                        <input
                            type="text"
                            name="full_name" minlength="2" maxlength="180"
                            class="sol-input uppercase-input"
                            value="{{ $patientQueryIsCi ? '' : $patientQuery }}"
                            autocomplete="name"
                            {{ $patientQueryIsCi ? '' : 'autofocus' }}
                            required
                        >
                    </div>

                    <div class="sol-form-group">
                        <label>Teléfono (opcional)</label>
                        <input type="tel" name="phone" class="sol-input" maxlength="40" autocomplete="tel">
                    </div>

                    <div class="sol-form-group full">
                        <label>Dirección (opcional)</label>
                        <input type="text" name="address" class="sol-input uppercase-input" maxlength="255" autocomplete="street-address">
                    </div>
                </div>

                <div id="patient_register_errors" class="patient-result-empty" role="alert" aria-live="polite"></div>

                <div class="sol-modal-actions">
                    <a href="{{ route('solicitudes.index') }}" class="btn-secondary"><x-ui-icon name="close" /> Cancelar</a>
                    <button type="submit" class="btn-primary"><x-ui-icon name="save" /> Registrar paciente</button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>

document.addEventListener('DOMContentLoaded', function () {


/*
|--------------------------------------------------------------------------
| INICIALIZAR SELECT2
|--------------------------------------------------------------------------
*/

if (window.jQuery && $.fn.select2) {

    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Seleccione una opción',
        allowClear: true
    });

}


/*
|--------------------------------------------------------------------------
| VARIABLES GENERALES
|--------------------------------------------------------------------------
*/

let moduloActual = document.getElementById('module') 
    ? document.getElementById('module').value 
    : null;


let pacienteSeleccionado = null;


/*
|--------------------------------------------------------------------------
| ACTUALIZAR SELECT2 DESPUÉS DE AJAX
|--------------------------------------------------------------------------
*/

function actualizarSelect(id){

    let elemento = $('#' + id);

    if(elemento.length){

        elemento.trigger('change.select2');

    }

}



/*
|--------------------------------------------------------------------------
| LIMPIAR SELECT HIJOS
|--------------------------------------------------------------------------
*/

function limpiarSelect(id,texto='Seleccione'){

    let select = $('#' + id);

    if(select.length){

        select
        .empty()
        .append(
            `<option value="">${texto}</option>`
        )
        .trigger('change');

    }

}



/*
|--------------------------------------------------------------------------
| CAMBIO DE MÓDULO
|--------------------------------------------------------------------------
*/

window.cambiarModulo = function(modulo){

    $('#module').val(modulo);
    $('#sequence_type').val(modulo);
    moduloActual = modulo;

    $('.module-option').removeClass('active');
    $('.modulo-form').hide();
    $('.modulo-form :input').prop('disabled', true);

    if(modulo === 'U'){
        $('#btn_urbano').addClass('active');
        $('#form_urbano').show();
        $('#form_urbano :input').prop('disabled', false);
        $('#titulo_modulo').text('Información de solicitud - Urbano');
        $('#subtitulo_modulo').text('Serie asignada automáticamente: U');
    }

    if(modulo === 'R'){
        $('#btn_rural').addClass('active');
        $('#form_rural').show();
        $('#form_rural :input').prop('disabled', false);
        $('#titulo_modulo').text('Información de solicitud - Rural');
        $('#subtitulo_modulo').text('Serie asignada automáticamente: R');
    }

    if(modulo === 'G'){
        $('#btn_gineco').addClass('active');
        $('#form_gineco').show();
        $('#form_gineco :input').prop('disabled', false);
        $('#titulo_modulo').text('Información de solicitud - Ginecología');
        $('#subtitulo_modulo').text('Serie asignada automáticamente: G');
    }

};



/*
|--------------------------------------------------------------------------
| FECHA AUTOMÁTICA
|--------------------------------------------------------------------------
*/


let fechaSolicitud = document.getElementById('fecha_solicitud');


if(fechaSolicitud && !fechaSolicitud.value){

    let hoy = new Date();

    let fecha = hoy.toISOString().split('T')[0];

    fechaSolicitud.value = fecha;

}



let fechaMuestra = document.getElementById('fecha_toma_muestra');


if(fechaMuestra && !fechaMuestra.value){

    let hoy = new Date();

    let fecha = hoy.toISOString().split('T')[0];

    fechaMuestra.value = fecha;

}
/*
|--------------------------------------------------------------------------
| BUSQUEDA DE PACIENTE
|--------------------------------------------------------------------------
*/


$('#patient_search').on('input', function(){

    this.value = this.value.toUpperCase();

});


$('#btn_search_patient').on('click', function(){

    buscarPaciente();

});



$('#patient_search').on('keypress',function(e){

    if(e.which === 13){

        e.preventDefault();

        buscarPaciente();

    }

});





async function buscarPaciente()
{

    let texto = $('#patient_search')
        .val()
        .trim()
        .toUpperCase();

    $('#patient_search').val(texto);



    if(texto === ''){

        mostrarMensaje(
            'Ingrese CI, nombre o apellido del paciente'
        );

        return;

    }



    try{


        let respuesta = await fetch(

            "{{ route('pacientes.buscar') }}?q="
            +
            encodeURIComponent(texto)

        );



        if(!respuesta.ok){

            throw new Error(
                'Error en búsqueda'
            );

        }



        let data = await respuesta.json();



        mostrarResultadosPaciente(
            data.patients ?? data
        );



    }
    catch(error){

        console.error(error);

        mostrarMensaje(
            'No se pudo realizar la búsqueda'
        );

    }


}







/*
|--------------------------------------------------------------------------
| MOSTRAR RESULTADOS PACIENTE
|--------------------------------------------------------------------------
*/


function mostrarResultadosPaciente(data)
{


    let contenedor =
        $('#patient_results');


    contenedor.empty();



    if(!data || data.length === 0)
    {

        contenedor.html(`

            <div class="alert alert-warning">

                No se encontraron pacientes.

            </div>

        `);


        return;

    }





    data.forEach(function(paciente){



        let boton = $(`
        
            <button 
                type="button"
                class="paciente-item"
            >

                <strong>
                    ${paciente.full_name}
                </strong>

                <small>
                    CI: ${paciente.identity_document ?? 'SIN CI'}
                    ${paciente.sex ? ' · ' + paciente.sex : ''}
                    ${paciente.phone ? ' · TEL: ' + paciente.phone : ''}
                </small>

            </button>

        `);



        boton.on('click',function(){


            seleccionarPaciente(
                paciente
            );


        });



        contenedor.append(
            boton
        );


    });



}








/*
|--------------------------------------------------------------------------
| SELECCIONAR PACIENTE
|--------------------------------------------------------------------------
*/


function seleccionarPaciente(paciente)
{


    pacienteSeleccionado = paciente;



    $('#patient_id')
        .val(
            paciente.id
        );



    $('#paciente_resumen')
        .show();

    $('#patient_card')
        .show();

    $('#patient_empty')
        .hide();

    $('#patient_name')
        .text(
            paciente.full_name ?? '-'
        );

    $('#patient_ci')
        .text(
            paciente.identity_document ?? '-'
        );

    $('#patient_age').text(paciente.current_age ?? '—');
    $('#patient_birth')
        .text(
            paciente.birth_date ?? '-'
        );

    $('#patient_gender')
        .text(
            paciente.sex ?? '-'
        );

    $('#patient_phone')
        .text(
            paciente.phone ?? '-'
        );




    $('#resumen_nombre')
        .text(
            paciente.full_name ?? '-'
        );



    $('#resumen_ci')
        .text(
            paciente.identity_document ?? '-'
        );



    $('#resumen_fecha')
        .text(
            paciente.birth_date ?? '-'
        );



    $('#resumen_sexo')
        .text(
            paciente.sex ?? '-'
        );



    $('#resumen_telefono')
        .text(
            paciente.phone ?? '-'
        );



    $('#patient_search')
        .val('');



    $('#patient_results')
        .empty();





}









/*
|--------------------------------------------------------------------------
| HISTORIAL DEL PACIENTE
|--------------------------------------------------------------------------
*/


async function cargarHistorialPaciente(id) {
    return window.cargarHistorialPacienteFinal(id);
}
/*
|--------------------------------------------------------------------------
| CARGA DINÁMICA UBICACIÓN GEOGRÁFICA
|--------------------------------------------------------------------------
*/


async function cargarOpciones(
    url,
    selectId,
    texto='Seleccione'
)
{

    try{


        let respuesta =
            await fetch(url);



        if(!respuesta.ok){

            throw new Error(
                'Error cargando datos'
            );

        }



        let datos =
            await respuesta.json();



        let select =
            $('#' + selectId);



        select.empty();



        select.append(`

            <option value="">
                ${texto}
            </option>

        `);




        datos.forEach(function(item){


            select.append(`

                <option value="${item.id}">
                    ${item.name}
                </option>

            `);


        });



        select.trigger('change');



    }
    catch(error){

        console.error(error);


        $('#' + selectId)
        .empty()
        .append(`

            <option value="">
                Error al cargar
            </option>

        `)
        .trigger('change');


    }


}







/*
|--------------------------------------------------------------------------
| SECRETARÍA REGIONAL → MUNICIPIOS
|--------------------------------------------------------------------------
*/


$('#regional_secretariat_id')
.on(
'change',
function(){



    let id =
        $(this).val();



    limpiarSelect(
        'district_id'
    );


    limpiarSelect(
        'area_id_urban'
    );


    limpiarSelect(
        'sector_id_urban'
    );



    if(!id){

        limpiarSelect(
            'municipality_id'
        );

        return;

    }




    cargarOpciones(

        "{{ route('ubicacion.municipios',['secretaria'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),

        'municipality_id',

        'Seleccione municipio'

    );



});


$('#regional_secretariat_id_rural')
.on(
'change',
function(){

    let id =
        $(this).val();

    limpiarSelect(
        'municipality_id'
    );

    limpiarSelect(
        'district_id_rural'
    );

    limpiarSelect(
        'area_id'
    );

    limpiarSelect(
        'sector_id'
    );

    limpiarSelect(
        'health_facility_id_rural'
    );

    if(!id){

        return;

    }

    cargarOpciones(

        "{{ route('ubicacion.municipios',['secretaria'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),

        'municipality_id',

        'Seleccione municipio'

    );

});









/*
|--------------------------------------------------------------------------
| MUNICIPIO → DISTRITOS
|--------------------------------------------------------------------------
*/


$('#municipality_id')
.on(
'change',
function(){



    let id =
        $(this).val();



    limpiarSelect(
        'area_id'
    );


    limpiarSelect(
        'sector_id'
    );


    limpiarSelect(
        'health_facility_id_rural'
    );



    if(!id){

        limpiarSelect(
            'district_id'
        );

        return;

    }




    cargarOpciones(


        "{{ route('ubicacion.distritos',['municipio'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),


        'district_id',


        'Seleccione distrito'


    );



});


$('#municipality_id')
.on(
'change.rural',
function(){

    let id =
        $(this).val();

    limpiarSelect(
        'district_id_rural'
    );

    if(!id || moduloActual !== 'R'){

        return;

    }

    cargarOpciones(

        "{{ route('ubicacion.distritos',['municipio'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),

        'district_id_rural',

        'Seleccione distrito'

    );

});









/*
|--------------------------------------------------------------------------
| DISTRITO → ÁREAS
|--------------------------------------------------------------------------
*/


$('#district_id')
.on(
'change',
function(){



    let id =
        $(this).val();



    limpiarSelect(
        'sector_id_urban'
    );


    if(!id){

        limpiarSelect(
            'area_id_urban'
        );

        return;

    }




    cargarOpciones(


        "{{ route('ubicacion.areas',['distrito'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),


        'area_id_urban',


        'Seleccione área'


    );



});


$('#district_id_rural')
.on(
'change',
function(){

    let id =
        $(this).val();

    limpiarSelect(
        'area_id'
    );

    limpiarSelect(
        'sector_id'
    );

    limpiarSelect(
        'health_facility_id_rural'
    );

    if(!id){

        return;

    }

    cargarOpciones(

        "{{ route('ubicacion.areas',['distrito'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),

        'area_id',

        'Seleccione área'

    );

});









/*
|--------------------------------------------------------------------------
| ÁREA → SECTORES
|--------------------------------------------------------------------------
*/


$('#area_id')
.on(
'change',
function(){



    let id =
        $(this).val();



    limpiarSelect(
        'health_facility_id_rural'
    );



    if(!id){

        limpiarSelect(
            'sector_id'
        );

        return;

    }




    cargarOpciones(


        "{{ route('ubicacion.sectores',['area'=>'ID']) }}"
        .replace(
            'ID',
            id
        ),


        'sector_id',


        'Seleccione sector'


    );

    cargarOpciones(

        "{{ route('ubicacion.establecimientos') }}"
        +
        "?municipality_id="
        +
        encodeURIComponent($('#municipality_id').val() || '')
        +
        "&district_id="
        +
        encodeURIComponent($('#district_id_rural').val() || '')
        +
        "&area_id="
        +
        encodeURIComponent(id),

        'health_facility_id_rural',

        'Seleccione establecimiento'

    );



});









/*
|--------------------------------------------------------------------------
| SECTOR → ESTABLECIMIENTOS
|--------------------------------------------------------------------------
*/


$('#sector_id')
.on(
'change',
function(){



    let sector =
        $(this).val();



    cargarOpciones(

        "{{ route('ubicacion.establecimientos') }}"
        +
        "?municipality_id="
        +
        encodeURIComponent($('#municipality_id').val() || '')
        +
        "&district_id="
        +
        encodeURIComponent($('#district_id_rural').val() || '')
        +
        "&area_id="
        +
        encodeURIComponent($('#area_id').val() || '')
        +
        "&sector_id="
        +
        encodeURIComponent(sector || ''),


        'health_facility_id_rural',


        'Seleccione establecimiento'


    );



});







/*
|--------------------------------------------------------------------------
| GINECOLOGÍA
|--------------------------------------------------------------------------
*/


$('#residence_municipality_id')
.on(
'change',
function(){


    let id =
        $(this).val();



    if(!id){


        limpiarSelect(
            'health_facility_id_gineco'
        );


        return;

    }



    cargarOpciones(

        "{{ route('ubicacion.establecimientos') }}"
        +
        "?municipality_id="
        +
        id,

        'health_facility_id_gineco',

        'Seleccione establecimiento'

    );

});
/*
|--------------------------------------------------------------------------
| VALIDACIONES ANTES DE GUARDAR
|--------------------------------------------------------------------------
*/

$('#form_solicitud').on(
'submit',
function(e){

    let paciente =
        $('#patient_id').val();

    if(!paciente)
    {

        e.preventDefault();


        mostrarMensaje(
            'Debe seleccionar un paciente antes de guardar.'
        );


        return false;

    }

    let modulo =
        $('#module').val();


    if(!modulo)
    {

        e.preventDefault();


        mostrarMensaje(
            'Seleccione el tipo de solicitud.'
        );

        return false;

    }

    /*
    |--------------------------------------------------------------------------
    | ASIGNAR SERIE AUTOMÁTICA
    |--------------------------------------------------------------------------
    */

    $('#sequence_type').val(modulo);

});

/*
|--------------------------------------------------------------------------
| MENSAJES GENERALES
|--------------------------------------------------------------------------
*/

function mostrarMensaje(texto)
{

    let alerta =
        $('#alerta_busqueda');

    if(alerta.length)
    {

        alerta
        .removeClass()
        .addClass(
            'alert alert-danger'
        )
        .html(texto)
        .fadeIn();

        setTimeout(function(){

            alerta.fadeOut();

        },4000);

    }
    else
    {

        alert(texto);

    }

}

/*
|--------------------------------------------------------------------------
| CAMBIAR PACIENTE
|--------------------------------------------------------------------------
*/

window.cambiarPaciente = function()
{

    $('#patient_id')
    .val('');

    $('#paciente_resumen')
    .hide();

    $('#patient_search')
    .focus();
}

/*
|--------------------------------------------------------------------------
| OCULTAR ALERTAS
|--------------------------------------------------------------------------
*/

window.ocultarAlertaBusqueda = function()
{

    $('#alerta_busqueda')
    .hide();


}

/*
|--------------------------------------------------------------------------
| INICIALIZACIÓN FINAL
|--------------------------------------------------------------------------
*/

// Si existe módulo guardado lo recupera

if($('#module').val())
{
    let valor =
        $('#module').val();

    if(valor==='U')
    {

        cambiarModulo('U');
    }

    if(valor==='R')
    {

        cambiarModulo('R');

    }

    if(valor==='G')
    {

        cambiarModulo('G');

    }

}


});


$('#area_id_urban')
.on(
'change',
function(){

    let id = $(this).val();

    limpiarSelect('sector_id_urban');
    if(!id){
        return;
    }

    cargarOpciones(
        "{{ route('ubicacion.sectores',['area'=>'ID']) }}".replace('ID', id),
        'sector_id_urban',
        'Seleccione sector'
    );

});
</script>
<script>
(() => {
    function normalizeText(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toUpperCase();
    }

    function buildSmartSelect(select) {
        if (!select || select.dataset.smartSelectReady === '1') {
            return;
        }

        select.dataset.smartSelectReady = '1';
        select.classList.add('smart-select-native');

        const wrapper = document.createElement('div');
        wrapper.className = 'smart-select';

        const control = document.createElement('button');
        control.type = 'button';
        control.className = 'smart-select-control';

        const label = document.createElement('span');
        label.className = 'smart-select-label';

        const arrow = document.createElement('span');
        arrow.className = 'smart-select-arrow';
        arrow.textContent = '▾';

        const menu = document.createElement('div');
        menu.className = 'smart-select-menu';

        const search = document.createElement('input');
        search.type = 'text';
        search.className = 'smart-select-search';
        search.placeholder = 'BUSCAR...';

        const options = document.createElement('div');
        options.className = 'smart-select-options';

        control.append(label, arrow);
        menu.append(search, options);
        wrapper.append(control, menu);
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        const closeAll = () => {
            document.querySelectorAll('.smart-select.open').forEach((item) => {
                if (item !== wrapper) {
                    item.classList.remove('open');
                }
            });
        };

        const updateLabel = () => {
            const selected = select.options[select.selectedIndex];
            label.textContent = (selected?.textContent || 'Seleccione').toUpperCase();
        };

        const render = () => {
            const query = normalizeText(search.value);
            options.innerHTML = '';

            Array.from(select.options).forEach((option) => {
                const text = (option.textContent || '').trim();

                if (query && !normalizeText(text).includes(query)) {
                    return;
                }

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'smart-select-option';
                item.textContent = text.toUpperCase();

                if (option.value === select.value) {
                    item.classList.add('active');
                }

                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    wrapper.classList.remove('open');
                    updateLabel();
                });

                options.appendChild(item);
            });

            if (!options.children.length) {
                const empty = document.createElement('div');
                empty.className = 'smart-select-empty';
                empty.textContent = 'SIN RESULTADOS';
                options.appendChild(empty);
            }
        };

        control.addEventListener('click', () => {
            closeAll();
            wrapper.classList.toggle('open');
            search.value = '';
            render();

            if (wrapper.classList.contains('open')) {
                search.focus();
            }
        });

        search.addEventListener('input', () => {
            search.value = search.value.toUpperCase();
            render();
        });

        select.addEventListener('change', () => {
            updateLabel();
            render();
        });

        new MutationObserver(() => {
            updateLabel();
            render();
        }).observe(select, { childList: true, subtree: true });

        updateLabel();
        render();
    }

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.smart-select')) {
            document.querySelectorAll('.smart-select.open').forEach((item) => {
                item.classList.remove('open');
            });
        }
    });

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('select.sol-select').forEach(buildSmartSelect);
    });

    const moduleConfig = {
        U: {
            button: 'btn_urbano',
            form: 'form_urbano',
            title: 'Información de solicitud - Urbano',
            subtitle: 'Serie asignada automáticamente: U',
        },
        R: {
            button: 'btn_rural',
            form: 'form_rural',
            title: 'Información de solicitud - Rural',
            subtitle: 'Serie asignada automáticamente: R',
        },
        G: {
            button: 'btn_gineco',
            form: 'form_gineco',
            title: 'Información de solicitud - Ginecología',
            subtitle: 'Serie asignada automáticamente: G',
        },
    };

    window.cambiarModulo = function (module) {
        const config = moduleConfig[module];

        if (!config) {
            return;
        }

        const detailsDialog = document.getElementById('module_details_dialog');
        detailsDialog?.classList.remove('module-dialog-dismissed');

        const moduleInput = document.getElementById('module');
        const sequenceInput = document.getElementById('sequence_type');

        if (moduleInput) {
            moduleInput.value = module;
        }

        if (sequenceInput) {
            sequenceInput.value = module;
        }

        Object.values(moduleConfig).forEach((item) => {
            const button = document.getElementById(item.button);
            const form = document.getElementById(item.form);

            button?.classList.remove('active');

            if (form) {
                form.style.display = 'none';
                form.querySelectorAll('input, select, textarea, button').forEach((field) => {
                    field.disabled = true;
                });
            }
        });

        document.getElementById(config.button)?.classList.add('active');

        const activeForm = document.getElementById(config.form);

        if (activeForm) {
            activeForm.style.display = 'block';
            activeForm.querySelectorAll('input, select, textarea, button').forEach((field) => {
                field.disabled = false;
            });
        }

        const title = document.getElementById('titulo_modulo');
        const subtitle = document.getElementById('subtitulo_modulo');

        if (title) {
            title.textContent = config.title;
        }

        if (subtitle) {
            subtitle.textContent = config.subtitle;
        }

        detailsDialog?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const selectedModule = document.getElementById('module')?.value || '';

        Object.values(moduleConfig).forEach((item) => {
            document.getElementById(item.button)?.addEventListener('click', (event) => {
                event.preventDefault();
            });
        });

        if (selectedModule && moduleConfig[selectedModule]) {
            window.cambiarModulo(selectedModule);
        }
    });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const patientSearch = document.getElementById('patient_search');
    const patientButton = document.getElementById('btn_search_patient');
    const patientResults = document.getElementById('patient_results');

    const setText = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value || '-';
        }
    };

    const clearPatientResults = () => {
        if (patientResults) {
            patientResults.innerHTML = '';
        }
    };

    const renderPatientResults = (patients) => {
        if (!patientResults) {
            return;
        }

        patientResults.innerHTML = '';

        if (!patients.length) {
            patientResults.innerHTML = '<div class="patient-result-empty">NO SE ENCONTRARON PACIENTES</div>';
            return;
        }

        patients.forEach((patient) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'paciente-item';
            button.innerHTML = `
                <strong>${patient.full_name || '-'}</strong>
                <small>CI: ${patient.identity_document || 'SIN CI'}${patient.sex ? ' · ' + patient.sex : ''}${patient.phone ? ' · TEL: ' + patient.phone : ''}</small>
            `;

            button.addEventListener('click', () => {
                selectPatient(patient);
            });

            patientResults.appendChild(button);
        });
    };

    const loadPatientHistory = (patientId) => window.cargarHistorialPacienteFinal(patientId);

    const selectPatient = (patient) => {
        const patientId = document.getElementById('patient_id');
        const patientCard = document.getElementById('patient_card');
        const patientEmpty = document.getElementById('patient_empty');

        if (patientId) {
            patientId.value = patient.id;
        }

        if (patientCard) {
            patientCard.style.display = 'block';
        }

        if (patientEmpty) {
            patientEmpty.style.display = 'none';
        }

        setText('patient_name', patient.full_name);
        setText('patient_ci', patient.identity_document);
        setText('patient_birth', patient.birth_date);
        setText('patient_age', patient.current_age ?? '—');
        setText('patient_gender', patient.sex);
        setText('patient_phone', patient.phone);

        if (patientSearch) {
            patientSearch.value = '';
        }

        clearPatientResults();
    };

    const searchPatient = async () => {
        if (!patientSearch) {
            return;
        }

        const query = patientSearch.value.trim().toLocaleUpperCase('es-BO');
        patientSearch.value = query;

        if (!query) {
            renderPatientResults([]);
            return;
        }

        if (patientResults) {
            patientResults.innerHTML = '<div class="patient-result-empty">BUSCANDO...</div>';
        }

        try {
            const response = await fetch(`{{ route('pacientes.buscar') }}?q=${encodeURIComponent(query)}`, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Error al buscar paciente');
            }

            const data = await response.json();
            renderPatientResults(data.patients || []);
        } catch (error) {
            if (patientResults) {
                patientResults.innerHTML = '<div class="patient-result-empty">NO SE PUDO REALIZAR LA BÚSQUEDA</div>';
            }
        }
    };

    window.buscarPacienteFinal = searchPatient;

    if (patientSearch) {
        patientSearch.setAttribute('autocomplete', 'off');
        patientSearch.setAttribute('spellcheck', 'false');
        patientSearch.placeholder = 'CI, NOMBRE O APELLIDO DEL PACIENTE';

        patientSearch.addEventListener('input', function () {
            const start = this.selectionStart;
            const end = this.selectionEnd;
            this.value = this.value.toLocaleUpperCase('es-BO');
            this.setSelectionRange(start, end);
        });

    }

    setTimeout(function () {
        const config = {
            U: ['btn_urbano', 'form_urbano', 'Información de solicitud - Urbano', 'Serie asignada automáticamente: U'],
            R: ['btn_rural', 'form_rural', 'Información de solicitud - Rural', 'Serie asignada automáticamente: R'],
            G: ['btn_gineco', 'form_gineco', 'Información de solicitud - Ginecología', 'Serie asignada automáticamente: G'],
        };

        window.cambiarModulo = function (module) {
            if (!config[module]) {
                return false;
            }

            const detailsDialog = document.getElementById('module_details_dialog');
            detailsDialog?.classList.remove('module-dialog-dismissed');

            const moduleInput = document.getElementById('module');
            const sequenceInput = document.getElementById('sequence_type');

            if (moduleInput) {
                moduleInput.value = module;
            }

            if (sequenceInput) {
                sequenceInput.value = module;
            }

            Object.values(config).forEach(function (item) {
                const button = document.getElementById(item[0]);
                const form = document.getElementById(item[1]);

                if (button) {
                    button.classList.remove('active');
                }

                if (form) {
                    form.style.display = 'none';
                    form.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                        field.disabled = true;
                    });
                }
            });

            const selected = config[module];
            const selectedButton = document.getElementById(selected[0]);
            const selectedForm = document.getElementById(selected[1]);
            const title = document.getElementById('titulo_modulo');
            const subtitle = document.getElementById('subtitulo_modulo');
            const sampleFields = document.getElementById('sample_fields');

            if (selectedButton) {
                selectedButton.classList.add('active');
            }

            if (selectedForm) {
                selectedForm.style.display = 'block';
                selectedForm.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                    field.disabled = false;
                });
            }

            if (sampleFields) {
                sampleFields.style.display = 'grid';
                sampleFields.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                    field.disabled = false;
                });
            }

            if (title) {
                title.textContent = selected[2];
            }

            if (subtitle) {
                subtitle.textContent = selected[3];
            }

            const summaryTitle = document.getElementById('module_summary_title');
            const summaryText = document.getElementById('module_summary_text');
            const summaryEdit = document.getElementById('module_summary_edit');

            if (summaryTitle) {
                summaryTitle.textContent = selected[2];
            }

            if (summaryText) {
                summaryText.textContent = 'Complete los datos requeridos en la ventana.';
            }

            if (summaryEdit) {
                summaryEdit.hidden = false;
            }

            detailsDialog?.scrollIntoView({ behavior: 'smooth', block: 'start' });

            return false;
        };

        Object.entries(config).forEach(function ([module, item]) {
            const button = document.getElementById(item[0]);

            if (button) {
                button.onclick = function (event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    window.cambiarModulo(module);
                    return false;
                };
            }
        });

        const current = document.getElementById('module')?.value;

        if (current && config[current]) {
            window.cambiarModulo(current);
        }
    }, 0);
});
</script>
<script>
window.buscarPacienteFinal = async function (event) {
    if (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }

    const input = document.getElementById('patient_search');
    const results = document.getElementById('patient_results');

    if (!input) {
        return false;
    }

    const query = input.value.trim().toLocaleUpperCase('es-BO');
    input.value = query;

    if (!query) {
        if (results) {
            results.innerHTML = '<div class="patient-result-empty">INGRESE CI, NOMBRE O APELLIDO</div>';
        }

        return false;
    }

    if (results) {
        results.innerHTML = '<div class="patient-result-empty">BUSCANDO PACIENTE...</div>';
    }

    try {
        const response = await fetch(`{{ route('pacientes.buscar') }}?q=${encodeURIComponent(query)}`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const contentType = response.headers.get('content-type') || '';

        if (!response.ok || !contentType.includes('application/json')) {
            throw new Error('Respuesta inválida del servidor');
        }

        const data = await response.json();
        const patients = data.patients || [];

        if (!patients.length) {
            if (results) {
                results.innerHTML = '<div class="patient-result-empty">NO SE ENCONTRARON PACIENTES</div>';
            }

            return false;
        }

        if (patients.length === 1) {
            window.seleccionarPacienteFinal(patients[0]);
            return false;
        }

        if (results) {
            results.innerHTML = '';

            patients.forEach((patient) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'paciente-item';
                button.innerHTML = `
                    <strong>${patient.full_name || '-'}</strong>
                    <small>CI: ${patient.identity_document || 'SIN CI'}${patient.sex ? ' · ' + patient.sex : ''}${patient.phone ? ' · TEL: ' + patient.phone : ''}</small>
                `;
                button.onclick = () => window.seleccionarPacienteFinal(patient);
                results.appendChild(button);
            });
        }
    } catch (error) {
        if (results) {
            results.innerHTML = '<div class="patient-result-empty">NO SE PUDO REALIZAR LA BÚSQUEDA</div>';
        }
    }

    return false;
};

window.seleccionarPacienteFinal = function (patient) {
    const setValue = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value || '-';
        }
    };

    const patientId = document.getElementById('patient_id');
    const patientCard = document.getElementById('patient_card');
    const patientEmpty = document.getElementById('patient_empty');
    const input = document.getElementById('patient_search');
    const results = document.getElementById('patient_results');

    if (patientId) {
        patientId.value = patient.id || '';
    }

    if (patientCard) {
        patientCard.style.display = 'block';
    }

    if (patientEmpty) {
        patientEmpty.style.display = 'none';
    }

    setValue('patient_name', patient.full_name);
    setValue('patient_ci', patient.identity_document);
    setValue('patient_birth', patient.birth_date ? String(patient.birth_date).slice(0, 10) : '');
    setValue('patient_age', patient.current_age ?? '—');
    setValue('patient_gender', patient.sex);
    setValue('patient_phone', patient.phone);

    if (input) {
        input.value = '';
    }

    if (results) {
        results.innerHTML = '';
    }

};

window.cargarHistorialPacienteFinal = async function (patientId, page = 1) {
    const body = document.getElementById('historial_body');

    if (!body || !patientId) {
        return;
    }

    body.innerHTML = '<tr><td colspan="6" class="text-center">CARGANDO HISTORIAL...</td></tr>';

    try {
        const response = await fetch(`/pacientes/${patientId}/solicitudes?page=${page}`, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const data = await response.json();
        const requests = data.requests || [];

        if (!requests.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center">NO EXISTEN SOLICITUDES ANTERIORES.</td></tr>';
            return;
        }

        body.innerHTML = requests.map((item) => `
            <tr>
                <td>${item.request_number || ''}</td>
                <td>${item.request_date || ''}</td>
                <td>${item.module || ''}</td>
                <td>${item.health_facility || '-'}</td>
                <td>${item.status || ''}</td>
                <td><a href="/solicitudes/${item.id}" class="btn btn-sm btn-primary">Ver</a></td>
            </tr>
        `).join('');
        if (data.pagination && data.pagination.last_page > 1) {
            const row = body.insertRow();
            const cell = row.insertCell();
            cell.colSpan = 6;
            const nav = document.createElement('nav');
            nav.className = 'compact-pagination';
            nav.setAttribute('aria-label', 'Páginas del historial');
            const addPage = (label, target, disabled) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = label;
                button.disabled = disabled;
                button.addEventListener('click', () => window.cargarHistorialPacienteFinal(patientId, target));
                nav.appendChild(button);
            };
            addPage('Anterior', page - 1, page <= 1);
            nav.appendChild(document.createTextNode(` Página ${data.pagination.current_page} de ${data.pagination.last_page} · ${data.pagination.total} registros `));
            addPage('Siguiente', page + 1, page >= data.pagination.last_page);
            cell.appendChild(nav);
        }
    } catch (error) {
        body.innerHTML = '<tr><td colspan="6" class="text-center">NO SE PUDO CARGAR EL HISTORIAL.</td></tr>';
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('patient_search');
    const registerForm = document.getElementById('patient_register_form');
    const registerErrors = document.getElementById('patient_register_errors');

    if (input) {
        input.addEventListener('input', function () {
            this.value = this.value.toLocaleUpperCase('es-BO');
        }, true);
    }

    document.querySelectorAll('.uppercase-input').forEach(function (field) {
        field.addEventListener('input', function () {
            this.value = this.value.toLocaleUpperCase('es-BO');
        });
    });

    if (registerForm) {
        registerForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            const submitButton = registerForm.querySelector('button[type="submit"]');
            if (submitButton.disabled) return;
            submitButton.disabled = true;
            registerForm.setAttribute('aria-busy', 'true');

            if (registerErrors) {
                registerErrors.textContent = 'REGISTRANDO PACIENTE...';
            }

            try {
                const response = await fetch(`{{ route('pacientes.store') }}`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(registerForm),
                });

                const data = await response.json();

                if (!response.ok) {
                    const errors = data.errors
                        ? Object.values(data.errors).flat().join(' ')
                        : (data.message || 'NO SE PUDO REGISTRAR EL PACIENTE.');

                    throw new Error(errors);
                }

                const modal = document.getElementById('patient_register_modal');

                if (modal) {
                    modal.classList.remove('open');
                }

                window.seleccionarPacienteFinal(data.patient);
            } catch (error) {
                if (registerErrors) {
                    registerErrors.textContent = error.message || 'NO SE PUDO REGISTRAR EL PACIENTE.';
                }
            } finally {
                submitButton.disabled = false;
                registerForm.removeAttribute('aria-busy');
            }
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const picker = document.getElementById('patient-picker');
    const results = document.getElementById('patient_results');
    if (!picker || !results) return;
    const syncPicker = () => {
        picker.hidden = !results.textContent.trim();
    };
    new MutationObserver(syncPicker).observe(results, { childList: true, subtree: true, characterData: true });
    syncPicker();
});
</script>
@if(\App\Support\AccessRoles::addsRequestCatalogs(auth()->user()))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('quick_catalog_modal');
    const form = document.getElementById('quick_catalog_form');
    const title = document.getElementById('quick_catalog_title');
    const nameInput = document.getElementById('quick_catalog_name');
    const typeInput = document.getElementById('quick_catalog_type');
    const message = document.getElementById('quick_catalog_message');
    let targetId = '';
    let classification = 'R';
    const labels = { municipality: 'municipio', district: 'distrito', area: 'área', sector: 'sector', facility: 'establecimiento' };
    const value = id => document.getElementById(id)?.value || '';
    const close = () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); };

    document.querySelectorAll('.catalog-add').forEach(button => button.addEventListener('click', function () {
        targetId = this.dataset.target;
        classification = this.dataset.classification || 'R';
        typeInput.value = this.dataset.catalog;
        title.textContent = 'Añadir ' + labels[typeInput.value];
        nameInput.value = '';
        message.textContent = '';
        message.classList.remove('error');
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        setTimeout(() => nameInput.focus(), 0);
    }));

    document.getElementById('quick_catalog_close')?.addEventListener('click', close);
    document.getElementById('quick_catalog_cancel')?.addEventListener('click', close);
    modal?.addEventListener('click', event => { if (event.target === modal) close(); });

    form?.addEventListener('submit', async function (event) {
        event.preventDefault();
        const type = typeInput.value;
        const data = new FormData(form);
        const rural = targetId.includes('rural') || targetId === 'municipality_id';

        data.set('regional_secretariat_id', value(rural ? 'regional_secretariat_id_rural' : 'regional_secretariat_id_gineco'));
        data.set('municipality_id', value(targetId === 'health_facility_id_gineco' ? 'residence_municipality_id' : 'municipality_id'));
        data.set('district_id', value(targetId.includes('urban') || targetId === 'district_id' ? 'district_id' : 'district_id_rural'));
        data.set('area_id', value(targetId.includes('urban') ? 'area_id_urban' : 'area_id'));
        data.set('sector_id', value(targetId.includes('urban') ? 'sector_id_urban' : 'sector_id'));
        data.set('classification', classification);
        message.textContent = 'Guardando...';
        message.classList.remove('error');

        try {
            const response = await fetch(`{{ route('solicitudes.catalogos.store') }}`, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: data,
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join(' ') : result.message);
            const select = document.getElementById(targetId);
            select.add(new Option(result.option.name, result.option.id, true, true));
            select.dispatchEvent(new Event('change', { bubbles: true }));
            close();
        } catch (error) {
            message.textContent = error.message || 'No se pudo añadir la opción.';
            message.classList.add('error');
        }
    });
});
</script>
@endif
<script src="{{ asset('js/patient-registration-validation.js') }}?v={{ filemtime(public_path('js/patient-registration-validation.js')) }}" defer></script>
@endsection


