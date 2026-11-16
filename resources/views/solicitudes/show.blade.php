@extends('layouts.app')

@section('title', 'Detalle de solicitud')

@section('content')
@php
    $patient = $solicitud->patient;
    $moduleNames = ['U' => 'Urbano', 'R' => 'Rural', 'G' => 'Ginecología'];
    $moduleName = $moduleNames[$solicitud->module] ?? $solicitud->module;
    $statusLabels = [
        'registered' => 'Registrada', 'received' => 'Muestra recibida',
        'processing' => 'En proceso', 'reported' => 'Resultado registrado',
        'validated' => 'Validada', 'delivered' => 'Entregada', 'cancelled' => 'Anulada',
    ];
    $sampleStatusLabels = [
        'received' => 'Recibida', 'rejected' => 'Rechazada',
        'processing' => 'En proceso', 'completed' => 'Completada',
    ];
@endphp

<div class="request-detail-page">
    <header class="request-detail-head">
        <div>
            <span>Examen de patología</span>
            <h1>{{ $solicitud->request_number ?? 'Sin correlativo' }}</h1>
            <p>Información completa de la solicitud y sus muestras asociadas.</p>
        </div>
        <span class="request-detail-status">{{ $statusLabels[$solicitud->status] ?? __('workflow.'.$solicitud->status) }}</span>
    </header>

    <section class="request-detail-card">
        <div class="request-detail-title"><strong>Datos del paciente</strong><span>Módulo {{ $moduleName }}</span></div>
        <div class="request-detail-grid patient-grid">
            <div class="wide"><small>Nombres y apellidos</small><strong>{{ $patient?->full_name ?? '—' }}</strong></div>
            <div><small>Documento de identidad</small><strong>{{ $patient?->identity_document ?? '—' }}</strong></div>
            <div><small>Fecha de nacimiento</small><strong>{{ $patient?->birth_date?->format('d/m/Y') ?? '—' }}</strong></div>
            <div><small>Sexo</small><strong>{{ ['M' => 'Masculino', 'F' => 'Femenino'][$patient?->sex] ?? '—' }}</strong></div>
        </div>
    </section>

    <section class="request-detail-card">
        <div class="request-detail-title"><strong>Datos de la solicitud</strong><span>Serie {{ $solicitud->module }}</span></div>
        <div class="request-detail-grid">
            <div><small>Fecha de solicitud</small><strong>{{ $solicitud->request_date?->format('d/m/Y') ?? '—' }}</strong></div>
            <div><small>Fecha de toma de muestra</small><strong>{{ $solicitud->sample_collection_date?->format('d/m/Y') ?? '—' }}</strong></div>
            <div><small>Cantidad de muestras</small><strong>{{ $solicitud->sample_quantity }}</strong></div>
            <div><small>Distrito</small><strong>{{ $solicitud->district?->name ?? '—' }}</strong></div>
            <div><small>Área</small><strong>{{ $solicitud->area?->name ?? '—' }}</strong></div>
            <div><small>Sector</small><strong>{{ $solicitud->sector?->name ?? '—' }}</strong></div>
            @if(!$solicitud->isUrban())
                <div><small>Municipio</small><strong>{{ $solicitud->municipality?->name ?? '—' }}</strong></div>
                <div><small>Establecimiento</small><strong>{{ $solicitud->healthFacility?->name ?? '—' }}</strong></div>
            @endif
        </div>
    </section>

    <section class="request-detail-card">
        <div class="request-detail-title"><strong>Muestras</strong><span>{{ $solicitud->samples->count() }} registradas</span></div>
        <div class="request-samples">
            @forelse($solicitud->samples as $sample)
                <div class="request-sample-row">
                    <span class="sample-number">{{ $sample->sample_number }}</span>
                    <div><small>Tipo de muestra</small><strong>{{ $sample->sample_type ?? 'Muestra citológica' }}</strong></div>
                    <div><small>Fecha de recolección</small><strong>{{ $sample->collection_date?->format('d/m/Y') ?? '—' }}</strong></div>
                    <span class="sample-status">{{ $sampleStatusLabels[$sample->status] ?? ucfirst($sample->status) }}</span>
                </div>
            @empty
                <p class="request-detail-empty">Esta solicitud todavía no tiene muestras registradas.</p>
            @endforelse
        </div>
    </section>

    <div class="request-detail-actions">
        <x-back-link :href="route('solicitudes.index')" class="detail-back">Volver a solicitudes</x-back-link>
        @if(\App\Support\AccessRoles::writesResults(auth()->user()))
        <a href="{{ route('resultados.create', $solicitud) }}" class="detail-primary"><x-ui-icon name="save" /> Registrar o editar resultado</a>
        @else
        <a href="{{ route('resultados.index', ['q' => $solicitud->request_number]) }}" class="detail-primary">Consultar e imprimir resultado</a>
        @endif
    </div>
</div>

<style>
.request-detail-page{box-sizing:border-box;width:calc(100% - 44px);max-width:1380px;margin:0 auto;padding:18px 0 24px}.request-detail-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:14px}.request-detail-head>div>span{color:#087444;font-size:11px;font-weight:900;letter-spacing:.11em;text-transform:uppercase}.request-detail-head h1{margin:3px 0;color:#14392c;font-size:30px}.request-detail-head p{margin:0;color:#64748b}.request-detail-status{padding:7px 13px;border:1px solid #bfe4d0;border-radius:999px;background:#edf9f2;color:#087444;font-size:12px;font-weight:800}.request-detail-card{overflow:hidden;margin-bottom:12px;border:1px solid #d5e2dc;border-radius:12px;background:#fff;box-shadow:0 8px 24px rgba(18,66,43,.06)}.request-detail-title{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;border-bottom:1px solid #dce6e1;background:#f2f8f5;color:#173a2d}.request-detail-title span{color:#64786e;font-size:12px}.request-detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:11px 14px;padding:15px}.request-detail-grid .wide{grid-column:span 2}.request-detail-grid small,.request-sample-row small{display:block;margin-bottom:4px;color:#687c72;font-size:10px;font-weight:800;text-transform:uppercase}.request-detail-grid strong,.request-sample-row strong{display:block;color:#17382c;font-size:13px}.request-samples{padding:8px 14px}.request-sample-row{display:grid;grid-template-columns:34px 1.5fr 1fr auto;align-items:center;gap:13px;padding:9px 0;border-bottom:1px solid #e2ebe6}.request-sample-row:last-child{border-bottom:0}.sample-number{width:30px;height:30px;display:grid;place-items:center;border-radius:50%;background:#e7f5ed;color:#087444;font-weight:900}.sample-status{padding:5px 10px;border-radius:999px;background:#e9f3ff;color:#1d5f9b;font-size:11px;font-weight:800}.request-detail-empty{padding:15px;color:#64748b;text-align:center}.request-detail-actions{display:flex;justify-content:flex-end;gap:9px}.request-detail-actions a{min-height:40px;display:inline-flex;align-items:center;justify-content:center;padding:0 16px;border-radius:8px;text-decoration:none;font-weight:800}.detail-back{border:1px solid #cbd9d2;background:#fff;color:#244638}.detail-primary{border:1px solid #16824e;background:#16824e;color:#fff}
html[data-theme="dark"] .request-detail-head h1{color:#f3faf6}html[data-theme="dark"] .request-detail-head p{color:#a9beb4}html[data-theme="dark"] .request-detail-card{border-color:#405158;background:#1d272b}html[data-theme="dark"] .request-detail-title{border-color:#405158;background:#263b33;color:#f1faf5}html[data-theme="dark"] .request-detail-title span,html[data-theme="dark"] .request-detail-grid small,html[data-theme="dark"] .request-sample-row small{color:#a9beb4}html[data-theme="dark"] .request-detail-grid strong,html[data-theme="dark"] .request-sample-row strong{color:#f1faf5}html[data-theme="dark"] .request-sample-row{border-color:#405158}html[data-theme="dark"] .detail-back{border-color:#52645c;background:#26343a;color:#f3faf6}
@media(max-width:760px){.request-detail-page{width:100%;padding:12px}.request-detail-head{align-items:flex-start;flex-direction:column}.request-detail-grid{grid-template-columns:1fr}.request-detail-grid .wide{grid-column:auto}.request-sample-row{grid-template-columns:34px 1fr}.request-sample-row .sample-status{justify-self:start}.request-detail-actions{flex-direction:column}.request-detail-actions a{width:100%}}
</style>
@endsection
