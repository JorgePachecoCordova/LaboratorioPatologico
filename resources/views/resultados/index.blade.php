@extends('layouts.app')

@section('title', $cytologyOnly ? 'Informes citológicos' : 'Registro de Resultados')

@section('content')
@php $canWriteResults = \App\Support\AccessRoles::writesResults(auth()->user()); @endphp

<div class="simple-page results-list-page">

    <div class="page-header">
        <div class="page-header-accent"></div>

        <div class="page-header-text">
            <h1>{{ request()->routeIs('solicitudes.index') ? 'Consulta de solicitudes' : ($cytologyOnly ? 'Informes citológicos' : 'Registro de Resultados') }}</h1>
            <p>{{ request()->routeIs('solicitudes.index') ? 'Búsqueda de solicitudes y consulta de informes disponibles.' : ($cytologyOnly ? 'Registro y emisión de resultados citológicos según el Sistema Bethesda' : 'Gestión y emisión de informes de resultados patológicos') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div style="margin-bottom:14px; padding:12px 14px; border-radius:10px; background:#ecfdf5; color:#166534; border:1px solid #bbf7d0;">
            {{ session('success') }}
        </div>
    @endif

    <div class="simple-card results-index-card">

        <div class="results-card-heading">
            <div>
                <h2>{{ isset($selectedBatch) ? 'Solicitud '.$selectedBatch->display_number : ($cytologyOnly ? 'Solicitudes citológicas' : 'Exámenes recibidos') }}</h2>
                <p>Localice una solicitud y continúe con el registro o validación del resultado.</p>
            </div>
            <div class="results-heading-actions">
                <span class="results-count">{{ $pathologyRequests->total() }} {{ $pathologyRequests->total() === 1 ? 'registro' : 'registros' }}</span>
                <button type="button" id="download-results-excel" data-export-url="{{ route('resultados.excel.consolidado') }}" class="result-action result-action-excel results-general-report" title="Reporte general" aria-label="Reporte general"><x-result-icon name="excel" /><span>Reporte general</span></button>
@if($canWriteResults)                <button type="button" class="result-action result-action-primary results-new-button" aria-expanded="false" aria-controls="new-result-panel" id="new-result-toggle"><span aria-hidden="true">＋</span> Añadir nuevo resultado</button>@endif
            </div>
        </div>

@if($canWriteResults)        <section id="new-result-panel" class="results-new-panel" hidden>
            <strong>Seleccione la solicitud del nuevo resultado</strong>
            <p>Busque por CI, paciente o correlativo entre las solicitudes de esta página. Para localizar otras, utilice la búsqueda general del listado.</p>
            <form id="new-result-form" method="GET">
                <label for="new-result-request">Solicitud / paciente</label>
                <select id="new-result-request" required>
                    <option value="">Seleccione una solicitud</option>
                    @foreach($pathologyRequests as $item)
                        <option value="{{ route('resultados.create', $item) }}">{{ $item->request_number }} · {{ $item->sequence_year ?? $item->request_date?->format('Y') }} — {{ $item->patient?->full_name ?? 'Sin paciente' }} · CI: {{ $item->patient?->identity_document ?? 'Sin registrar' }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="nuevo" value="1">
                <button type="submit" class="result-action result-action-primary">Abrir formulario</button>
            </form>
        </section>@endif

        @if($errors->any())
            <p role="alert" style="padding:0 20px;color:#b42318">{{ $errors->first() }}</p>
        @endif
        <form id="results-filter-form" method="GET" action="{{ route(request()->routeIs('solicitudes.index') ? 'solicitudes.index' : ($cytologyOnly ? 'citologia.index' : 'resultados.index')) }}" class="results-search-form">
            @if(request()->filled('batch'))<input type="hidden" name="batch" value="{{ request('batch') }}">@endif
            <span class="results-search-icon" aria-hidden="true">⌕</span>
            <input
                type="search"
                name="q"
                value="{{ $search }}"
                data-uppercase-search
                autocomplete="off"
                placeholder="BUSCAR POR CORRELATIVO, CI O PACIENTE"
            >

            <button
                type="submit"
                class="results-search-button"
            >
                Buscar
            </button>
            <select name="month" aria-label="Mes">
                <option value="">Todos los meses</option>
                @foreach([1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'] as $m => $monthName)
                    <option value="{{ $m }}" @selected((string)$month === (string)$m)>{{ $monthName }}</option>
                @endforeach
            </select>
            <input type="number" name="year" min="2025" max="2100" value="{{ $year }}" placeholder="Año" aria-label="Gestión desde 2025" style="width:100px">
            <select name="classification"><option value="">Todos</option><option value="positive" @selected($classification === 'positive')>Solo positivos</option></select>

            @if($search !== '' || request()->filled('batch'))
                <a
                    href="{{ route($cytologyOnly ? 'citologia.index' : 'resultados.index') }}"
                    class="results-clear-button"
                >
                    Limpiar
                </a>
            @endif
        </form>

        <p class="results-report-help" style="margin:0;padding:0 20px 12px;color:#53665b;font-size:13px">Para el reporte mensual, seleccione mes y año y pulse Reporte general. El período corresponde a la fecha de solicitud; el Excel incluye todos los resultados del período y la clasificación elegidos.</p>

        <div class="results-table-wrap">
            <table
                data-datatable
                data-datatable-professional
                data-empty-message="{{ $cytologyOnly
                    ? 'Aún no existen solicitudes ginecológicas o citológicas. Registre una solicitud en el módulo Ginecología para elaborar su informe.'
                    : 'No se encontraron solicitudes.' }}"
                class="results-table"
            >
                <thead>
                    <tr>
                        <th style="padding:12px; text-align:left;">Solicitud</th>
                        <th style="padding:12px; text-align:left;">Paciente</th>
                        <th style="padding:12px; text-align:left;">Establecimiento</th>
                        <th style="padding:12px; text-align:center;">Muestras</th>
                        <th style="padding:12px; text-align:left;">Estado</th>
                        <th style="padding:12px; text-align:left;">Fecha</th>
                        <th style="padding:12px; text-align:left;">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($pathologyRequests as $pathologyRequest)
                    @php
                        $samples = $pathologyRequest->samples;
                        $validatedResults = $samples->filter(
                            fn ($sample) => $sample->pathologyResult?->status === 'validated'
                        );
                        $firstValidatedResult = $validatedResults->first()?->pathologyResult;
                        $resultsCount = $samples->filter(fn ($sample) => $sample->pathologyResult !== null)->count();
                        $hasPendingResult = $resultsCount < $samples->count();
                        $isPositive = $samples->contains(fn ($sample) => (bool) $sample->pathologyResult?->is_positive);

                        $statusLabels = [
                            'registered' => 'Registrada',
                            'received' => 'Muestra recibida',
                            'processing' => 'En proceso',
                            'reported' => 'Resultado registrado',
                            'validated' => 'Validada',
                            'delivered' => 'Entregada',
                            'cancelled' => 'Anulada',
                        ];

                        $statusClasses = [
                            'registered' => 'status-badge status-registered',
                            'received' => 'status-badge status-received',
                            'processing' => 'status-badge status-processing',
                            'reported' => 'status-badge status-reported',
                            'validated' => 'status-badge status-validated',
                            'delivered' => 'status-badge status-delivered',
                            'cancelled' => 'status-badge status-cancelled',
                        ];
                    @endphp

                    <tr @class(['positive-result-row' => $isPositive])>
                        <td style="padding:12px;">
                            <strong>{{ $pathologyRequest->request_number }}</strong>
                            <div style="font-size:12px; color:#64748b;">
                                Gestión {{ $pathologyRequest->sequence_year }}
                            </div>
                        </td>

                        <td style="padding:12px;">
                            {{ $pathologyRequest->patient?->full_name ?? 'Sin paciente' }}
                            <div style="font-size:12px; color:#64748b;">
                                CI: {{ $pathologyRequest->patient?->identity_document ?? '-' }}
                            </div>
                        </td>

                        <td style="padding:12px;">
                            {{ $pathologyRequest->healthFacility?->name ?? 'Sin establecimiento' }}
                        </td>

                        <td style="padding:12px; text-align:center;">
                            {{ $samples->count() }} / {{ $pathologyRequest->sample_quantity }}
                        </td>

                        <td style="padding:12px;">
                            <span class="{{ $statusClasses[$pathologyRequest->status] ?? 'status-badge status-default' }}">
                                {{ $statusLabels[$pathologyRequest->status] ?? ucfirst($pathologyRequest->status) }}
                            </span>
                        </td>

                        <td style="padding:12px;">
                            {{ $pathologyRequest->request_date?->format('d/m/Y') ?? '-' }}
                        </td>

                        <td style="padding:12px;">
                            <div class="results-row-actions">
@if($canWriteResults)                                <a
                                    href="{{ route('resultados.create', $pathologyRequest) }}"
                                    class="result-action result-action-icon {{ $hasPendingResult ? 'result-action-register' : 'result-action-edit' }}"
                                    title="{{ $hasPendingResult ? 'Registrar resultado' : 'Editar resultado' }}"
                                    aria-label="{{ $hasPendingResult ? 'Registrar resultado' : 'Editar resultado' }}"
                                >
                                    <x-result-icon :name="$hasPendingResult ? 'add' : 'edit'" />
                                </a>@endif

                                @if($firstValidatedResult)
                                    <a
                                        href="{{ route('resultados.imprimir', $firstValidatedResult) }}"
                                        target="_blank"
                                        class="result-action result-action-print result-action-icon"
                                        title="Ver informe" aria-label="Ver informe"
                                    >
                                        <x-result-icon name="report" />
                                    </a>

                                    <a
                                        href="{{ route('resultados.excel', $firstValidatedResult) }}"
                                        class="result-action result-action-excel result-action-icon"
                                        title="Descargar Excel" aria-label="Descargar Excel"
                                    >
                                        <x-result-icon name="excel" />
                                    </a>

                                @endif

@if(\App\Support\AccessRoles::admin(auth()->user()) && $resultsCount === 0)                                <form
                                    method="POST"
                                    action="{{ route('solicitudes.destroy', $pathologyRequest) }}"
                                    onsubmit="return confirm('¿Eliminar la solicitud {{ $pathologyRequest->request_number }}? Se retirarán sus muestras sin resultados. El paciente se conservará.');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="result-action result-action-delete result-action-icon" title="Eliminar solicitud" aria-label="Eliminar solicitud">
                                        <x-result-icon name="trash" />
                                    </button>
                                </form>@endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top:18px;">
            {{ $pathologyRequests->links() }}
        </div>
    </div>
</div>
<style>
.results-list-page .result-action-request{background:#e0f2f1;color:#0f6663!important;border:1px solid #76b8b1}.results-list-page .result-action-request:hover{background:#c3e8e3}html[data-theme="dark"] .results-list-page .result-action-request{background:#193f42;color:#99e4dd!important;border-color:#407c80}html[data-theme="dark"] .results-list-page .result-action-request:hover{background:#235458}
html[data-theme="light"] .results-list-page .result-action-edit{color:#92400e!important}html[data-theme="light"] .results-list-page .result-action-register{color:#6d28d9!important}
.results-list-page .result-action-edit{background:#fef3c7;color:#92400e;border:1px solid #f59e0b}.results-list-page .result-action-register{background:#ede9fe;color:#6d28d9;border:1px solid #a78bfa}.results-list-page .result-action-excel{background:#e8f5ed;color:#185c37;border:1px solid #74b992}.results-list-page .result-action-edit:hover{background:#fde68a}.results-list-page .result-action-register:hover{background:#ddd6fe}.results-list-page .result-action-excel:hover{background:#ccebd8}html[data-theme="dark"] .results-list-page .result-action-edit{background:#49351a;color:#fcd34d;border-color:#b7791f}html[data-theme="dark"] .results-list-page .result-action-register{background:#352754;color:#d8b4fe;border-color:#8b5cf6}html[data-theme="dark"] .results-list-page .result-action-excel{background:#173c2b;border-color:#39835b}html[data-theme="dark"] .results-list-page .result-action-edit:hover{background:#60451e}html[data-theme="dark"] .results-list-page .result-action-register:hover{background:#49336f}html[data-theme="dark"] .results-list-page .result-action-excel:hover{background:#22563c}
.results-row-actions{display:flex;align-items:center;gap:8px;flex-wrap:nowrap}.results-row-actions form{margin:0}.results-list-page .result-action-icon{display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;min-width:42px;padding:0;box-sizing:border-box;cursor:pointer}.result-icon{width:24px;height:24px;flex-shrink:0}.results-list-page .result-action-icon:focus-visible{outline:3px solid #60a5fa;outline-offset:3px}
.positive-result-row{background:#fff1f2!important;box-shadow:inset 5px 0 #dc2626}.positive-result-row td:first-child strong,.positive-result-row td:nth-child(2){color:#b91c1c!important}html[data-theme="dark"] .positive-result-row{background:#402326!important}.results-search-form select,.results-search-form input[type=number]{min-height:44px;border:1px solid #405158;border-radius:8px;padding:0 10px;background:inherit;color:inherit}
.results-heading-actions{display:flex;align-items:center;justify-content:flex-end;gap:14px;flex-wrap:wrap}.results-new-button{border:0;cursor:pointer;font:inherit;font-weight:700;gap:6px}.results-new-panel{margin:18px 24px;padding:18px;border:1px solid #b9dbc9;border-radius:12px;background:#f2f8f5;color:#173a2d}.results-new-panel[hidden]{display:none}.results-new-panel p{margin:6px 0 14px;color:#64748b}.results-new-panel form{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.results-new-panel label{font-weight:700}.results-new-panel select{flex:1;min-width:220px;max-width:100%;padding:11px;border:1px solid #cbd8d1;border-radius:8px;background:#fff;color:#173a2d;font:inherit}html[data-theme="dark"] .results-new-panel{background:#202d31;border-color:#405158;color:#f3faf6}html[data-theme="dark"] .results-new-panel p{color:#afc1b8}html[data-theme="dark"] .results-new-panel select{background:#172125;border-color:#405158;color:#f3faf6}@media(max-width:760px){.results-heading-actions{justify-content:flex-start}.results-new-panel{margin:14px}.results-new-panel form{align-items:stretch;flex-direction:column}.results-new-panel select{width:100%;min-width:0}}
.status-badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.status-registered {
    background: #e0f2fe;
    color: #075985;
}

.status-received {
    background: #ede9fe;
    color: #5b21b6;
}

.status-processing {
    background: #fef3c7;
    color: #92400e;
}

.status-reported {
    background: #dbeafe;
    color: #1e40af;
}

.status-validated {
    background: #dcfce7;
    color: #166534;
}

.status-delivered {
    background: #d1fae5;
    color: #065f46;
}

.status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}
.status-default {
    background: #f1f5f9;
    color: #334155;
}

.result-action-delete {
    border: 1px solid #dc2626;
    background: #dc2626;
    color: #fff !important;
    cursor: pointer;
}

.result-action-delete:hover {
    background: #b91c1c;
}

html[data-theme="dark"] .result-action-delete {
    border-color: #f87171;
    background: #b91c1c;
    color: #fff !important;
}
.results-heading-actions .results-general-report span{color:inherit!important}.results-heading-actions .results-general-report:hover{background:#d7ecdf!important;color:#103f24!important}.results-heading-actions .results-general-report:focus-visible{outline:2px solid #16824e;outline-offset:3px}html[data-theme="dark"] .results-heading-actions .results-general-report{background:#203e30!important;color:#b7edcc!important;border-color:#417558}html[data-theme="dark"] .results-heading-actions .results-general-report:hover{background:#2b503b!important;color:#e0f8e9!important}html[data-theme="dark"] .results-report-help{color:#afc3b7!important}</style>
<script>
document.getElementById('download-results-excel').addEventListener('click', function () {
    const filters = document.getElementById('results-filter-form');
    if (!filters.reportValidity()) return;
    const url = new URL(this.dataset.exportUrl, window.location.href);
    for (const name of ['month', 'year', 'classification']) {
        const value = filters.elements.namedItem(name).value;
        if (value) url.searchParams.set(name, value);
    }
    window.location.assign(url.href);
});
document.getElementById('new-result-toggle')?.addEventListener('click', function () {
    const panel = document.getElementById('new-result-panel');
    panel.hidden = !panel.hidden;
    this.setAttribute('aria-expanded', String(!panel.hidden));
    if (!panel.hidden) document.getElementById('new-result-request').focus();
});
document.getElementById('new-result-form')?.addEventListener('submit', function (event) {
    const url = document.getElementById('new-result-request').value;
    if (!url) { event.preventDefault(); return; }
    this.action = url;
});
</script>

@if($canWriteResults)
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/result-request-select.css') }}?v={{ filemtime(public_path('css/result-request-select.css')) }}">
<script src="{{ asset('vendor/select2/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('js/result-request-select.js') }}?v={{ filemtime(public_path('js/result-request-select.js')) }}" defer></script>
@endif
<style>.results-heading-actions .results-general-report{color:#14532d!important;background:#e8f4ec!important;border:1px solid #7bb490;display:inline-flex;align-items:center;justify-content:center;gap:9px;width:auto;min-height:42px;padding:10px 15px;border-radius:8px;font-family:inherit;font-size:13px;font-weight:600;white-space:nowrap}.results-heading-actions .results-general-report span{color:inherit!important}.results-heading-actions .results-general-report:hover{background:#d7ecdf!important;color:#103f24!important}.results-heading-actions .results-general-report:focus-visible{outline:2px solid #16824e;outline-offset:3px}html[data-theme="dark"] .results-heading-actions .results-general-report{background:#203e30!important;color:#b7edcc!important;border-color:#417558}html[data-theme="dark"] .results-heading-actions .results-general-report:hover{background:#2b503b!important;color:#e0f8e9!important}html[data-theme="dark"] .results-report-help{color:#afc3b7!important}</style>
<style>
html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row>td{background:#402328!important;border-color:#68404a!important;color:#f7e8ec!important;box-shadow:none!important}
html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row:hover>td{background:#512b33!important}
html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row>td:first-child{box-shadow:inset 5px 0 #ff6378!important}
html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row>td:first-child strong,html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row>td:nth-child(2){color:#ffabb7!important;font-weight:600}
html[data-theme="dark"] .dashboard-main .results-table tbody tr.positive-result-row>td div[style*="color"]{color:#d9b7c1!important}
</style>
@endsection
