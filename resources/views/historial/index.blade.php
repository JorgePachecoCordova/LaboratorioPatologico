@extends('layouts.app')
@section('content')
<div class="history-page">
    <header class="history-header"><div><h1>Historial integral de pacientes</h1><p>Consulta separada por las áreas autorizadas para esta oficina</p></div></header>
    <div class="history-layout">
        <section class="history-panel patient-browser">
            <form method="GET" class="history-search"><input type="text" name="q" value="{{ mb_strtoupper($search, 'UTF-8') }}" placeholder="BUSCAR POR NOMBRE O C.I." data-uppercase-search aria-label="Buscar por nombre o C.I."><button><x-ui-icon name="search" /> Buscar</button></form>
            <div class="patient-list">
                @forelse($patients as $patient)
                    <a href="{{ route('historial.index',['paciente'=>$patient->id,'q'=>$search]) }}" class="patient-row {{ $selectedPatient?->id===$patient->id?'selected':'' }}">
                        <span class="avatar">{{ mb_substr($patient->full_name,0,1) }}</span><span><strong>{{ $patient->full_name }}</strong><small>C.I. {{ $patient->identity_document }} · {{ $patient->visible_history_count }} registros visibles</small></span>
                    </a>
                @empty<div class="empty-state">No se encontraron pacientes.</div>@endforelse
            </div>
            <div class="history-pagination">{{ $patients->links('pagination.compact') }}</div>
        </section>
        <section class="history-panel history-detail">
            @if($selectedPatient)
                <div class="patient-heading"><div><small>PACIENTE SELECCIONADO</small><h2>{{ $selectedPatient->full_name }}</h2><p>C.I. {{ $selectedPatient->identity_document }} · {{ $selectedPatient->birth_date?->format('d/m/Y') ?? 'Sin fecha de nacimiento' }}</p></div>@if($historyAccess['laboratory'])<a href="{{ route('solicitudes.index',['paciente'=>$selectedPatient->id]) }}" class="history-action">Nueva solicitud</a>@endif</div>
                <nav class="history-tabs" aria-label="Áreas del historial">
                    @if($historyAccess['laboratory'])<button type="button" data-history-tab="laboratory">Solicitudes y resultados <b>{{ $requests->total() }}</b></button>@endif
                    @if($historyAccess['cytology'])<button type="button" data-history-tab="cytology">Citología <b>{{ $cytologyReports->total() }}</b></button>@endif
                    @if($historyAccess['anatomopathology'])<button type="button" data-history-tab="anatomopathology">Anatomopatología <b>{{ $anatomopathologyReports->total() }}</b></button>@endif
                </nav>
                @if($historyAccess['laboratory'])<div class="timeline history-tab-panel" data-history-panel="laboratory">
                    @forelse($requests as $item)
                        @php($resultCount=$item->samples->filter(fn($sample)=>$sample->pathologyResult!==null)->count())
                        <article class="timeline-item"><div class="timeline-dot"></div><div class="timeline-card"><div class="request-top"><div><strong>{{ $item->request_number ?? 'SIN CORRELATIVO' }}</strong><span>{{ $item->request_date?->format('d/m/Y') }}</span></div><span class="status">{{ mb_strtoupper(__('workflow.'.$item->status)) }}</span></div><div class="request-data"><span><small>MÓDULO</small>{{ $item->module }}</span><span><small>ESTABLECIMIENTO</small>{{ $item->healthFacility?->name ?? '-' }}</span><span><small>MUESTRAS</small>{{ $item->samples->count() }}</span><span><small>RESULTADOS</small>{{ $resultCount }}</span></div><a href="{{ route('solicitudes.show',$item) }}">Ver solicitud completa →</a></div></article>
                    @empty<div class="empty-state large">Este paciente todavía no tiene solicitudes registradas.</div>@endforelse
                    {{ $requests->links('pagination.compact') }}
                </div>@endif
                @if($historyAccess['cytology'])<div class="timeline history-tab-panel" data-history-panel="cytology" hidden>
                    @forelse($cytologyReports as $item)
                        <article class="timeline-item"><div class="timeline-dot cytology"></div><div class="timeline-card"><div class="request-top"><div><strong>{{ $item->request_number ?? $item->report_number ?? 'SIN CORRELATIVO' }}</strong><span>{{ ($item->request_date ?? $item->report_date)?->format('d/m/Y') }}</span></div><span class="status">{{ mb_strtoupper(__('workflow.'.$item->status)) }}</span></div><div class="request-data three"><span><small>ÁREA</small>CITOLOGÍA</span><span><small>TIPO DE MUESTRA</small>{{ $item->sample_type ?? $item->sample_type_other ?? '-' }}</span><span><small>INFORME</small>{{ $item->report_number ?? 'Pendiente' }}</span></div><a href="{{ route('citologia.index',['registro'=>$item->id]) }}">Abrir registro citológico →</a></div></article>
                    @empty<div class="empty-state large">Este paciente todavía no tiene registros citológicos.</div>@endforelse
                    {{ $cytologyReports->links('pagination.compact') }}
                </div>@endif
                @if($historyAccess['anatomopathology'])<div class="timeline history-tab-panel" data-history-panel="anatomopathology" hidden>
                    @forelse($anatomopathologyReports as $item)
                        <article class="timeline-item"><div class="timeline-dot anatomy"></div><div class="timeline-card"><div class="request-top"><div><strong>{{ $item->report_number }}</strong><span>{{ $item->report_date?->format('d/m/Y') }}</span></div><span class="status">{{ mb_strtoupper(__('workflow.'.$item->status)) }}</span></div><div class="request-data three"><span><small>ÁREA</small>ANATOMOPATOLOGÍA</span><span><small>SERVICIO</small>{{ $item->service ?? '-' }}</span><span><small>N.º DE REGISTRO</small>{{ $item->registration_number ?? '-' }}</span></div><a href="{{ route('anatomopatologia.index',['registro'=>$item->id]) }}">Abrir informe anatomopatológico →</a></div></article>
                    @empty<div class="empty-state large">Este paciente todavía no tiene informes anatomopatológicos.</div>@endforelse
                    {{ $anatomopathologyReports->links('pagination.compact') }}
                </div>@endif
            @else<div class="empty-state large"><div class="empty-icon">📚</div><h2>Seleccione un paciente</h2><p>Su historial aparecerá aquí de forma separada.</p></div>@endif
        </section>
    </div>
</div>
<style>
.history-page{height:100%;padding:18px;color:#273d32}.history-header{margin-bottom:14px}.history-header h1{margin:0;font-size:30px;font-weight:650;color:#173c2a}.history-header p{margin:4px 0 0;color:#687a71}.history-layout{display:grid;grid-template-columns:350px 1fr;gap:14px;height:calc(100% - 70px)}.history-panel{background:#fff;border:1px solid #dfe8e3;border-radius:14px;overflow:hidden;box-shadow:0 6px 20px rgba(20,65,42,.05)}.patient-browser{display:flex;flex-direction:column}.history-search{display:flex;gap:7px;padding:12px;border-bottom:1px solid #e6ece8}.history-search input{flex:1;height:37px;border:1px solid #cad9d1;border-radius:8px;padding:0 10px;font-weight:400}.history-search button,.history-action{border:0;border-radius:8px;background:#16814d;color:#fff;padding:0 14px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center}.patient-list{overflow:auto;flex:1}.patient-row{display:flex;align-items:center;gap:10px;padding:11px 13px;border-bottom:1px solid #eef2ef;text-decoration:none;color:#263e32}.patient-row:hover,.patient-row.selected{background:#edf8f2}.patient-row.selected{border-left:3px solid #16814d}.avatar{width:34px;height:34px;border-radius:50%;background:#dcefe4;color:#127044;display:grid;place-items:center;font-weight:600}.patient-row strong,.patient-row small{display:block}.patient-row strong{font-size:13px;font-weight:600}.patient-row small{font-size:11px;color:#718078;margin-top:3px}.history-pagination{padding:8px}.history-detail{padding:18px;overflow:auto}.patient-heading{display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e3ebe6;padding-bottom:13px}.patient-heading small{font-size:10px;color:#74837b}.patient-heading h2{margin:3px 0;font-size:22px;font-weight:600}.patient-heading p{margin:0;color:#6d7b74}.history-action{height:36px}.timeline{padding:16px 4px}.timeline-item{position:relative;padding-left:24px;padding-bottom:14px}.timeline-item:before{content:'';position:absolute;left:6px;top:15px;bottom:-5px;border-left:1px solid #cbdcd2}.timeline-dot{position:absolute;left:1px;top:9px;width:11px;height:11px;border-radius:50%;background:#16814d}.timeline-card{border:1px solid #e0e9e4;border-radius:10px;padding:12px}.request-top{display:flex;justify-content:space-between}.request-top strong,.request-top span{display:block}.request-top strong{font-weight:600}.request-top div>span{color:#718078;font-size:12px;margin-top:2px}.status{font-size:10px;background:#e6f5ed;color:#126b40;border-radius:20px;padding:5px 9px;height:max-content}.request-data{display:grid;grid-template-columns:.6fr 2fr .6fr .6fr;gap:8px;margin:12px 0;font-size:12px}.request-data span{font-weight:400}.request-data small{display:block;color:#78867f;font-size:9px;margin-bottom:3px}.timeline-card>a{color:#14764a;text-decoration:none;font-size:12px;font-weight:600}.empty-state{text-align:center;color:#718078;padding:22px}.empty-state.large{display:grid;place-items:center;align-content:center;height:70%}.empty-state h2{font-weight:600;color:#3d5147}.empty-icon{font-size:38px}@media(max-width:850px){.history-page{height:auto;padding:10px}.history-layout{grid-template-columns:1fr;height:auto}.patient-browser{max-height:420px}.history-detail{min-height:450px}.request-data{grid-template-columns:1fr 1fr}}
html[data-theme="dark"] .history-page{color:#d8e7df}
html[data-theme="dark"] .history-header h1{color:#f1faf5}
html[data-theme="dark"] .history-header p{color:#afc2b8}
html[data-theme="dark"] .history-panel{background:#1d272b;border-color:#405158;box-shadow:0 8px 24px rgba(0,0,0,.22)}
html[data-theme="dark"] .history-search{border-color:#405158}
html[data-theme="dark"] .history-search input{background:#172125;border-color:#4b6068;color:#f3faf6}
html[data-theme="dark"] .patient-row{border-color:#35464d;color:#e4f0ea}
html[data-theme="dark"] .patient-row:hover,html[data-theme="dark"] .patient-row.selected{background:#263a32}
html[data-theme="dark"] .avatar{background:#25513d;color:#9ce9bf}
html[data-theme="dark"] .patient-row small,html[data-theme="dark"] .patient-heading p,html[data-theme="dark"] .patient-heading small{color:#a9beb4}
html[data-theme="dark"] .patient-heading{border-color:#405158}
html[data-theme="dark"] .patient-heading h2,html[data-theme="dark"] .empty-state h2{color:#e8f4ee}
html[data-theme="dark"] .timeline-item:before{border-color:#4b6659}
html[data-theme="dark"] .timeline-card{background:#202d32;border-color:#405158;color:#d8e7df}
html[data-theme="dark"] .request-top div>span,html[data-theme="dark"] .request-data small,html[data-theme="dark"] .empty-state{color:#a9beb4}
html[data-theme="dark"] .status{background:#25513d;color:#9ce9bf}
html[data-theme="dark"] .timeline-card>a{color:#80dfaa}
.history-tabs{display:flex;gap:6px;padding:12px 0 0;overflow:auto}.history-tabs button{white-space:nowrap;border:1px solid #d8e5de;border-radius:9px;background:#f7faf8;color:#526a5e;padding:8px 11px;font-size:11px;font-weight:650;cursor:pointer}.history-tabs button b{display:inline-grid;place-items:center;min-width:18px;height:18px;margin-left:5px;border-radius:9px;background:#e3eee8;font-size:9px}.history-tabs button.active{border-color:#16814d;background:#16814d;color:#fff}.history-tabs button.active b{background:rgba(255,255,255,.2)}.history-tab-panel[hidden]{display:none}.request-data.three{grid-template-columns:.8fr 1.5fr .8fr}.timeline-dot.cytology{background:#2872ad}.timeline-dot.anatomy{background:#7652a3}
html[data-theme="dark"] .history-tabs button{border-color:#405158;background:#202d32;color:#bdd0c6}html[data-theme="dark"] .history-tabs button b{background:#354941}html[data-theme="dark"] .history-tabs button.active{border-color:#54c98b;background:#246e4a;color:#fff}
</style>
<script>document.addEventListener('DOMContentLoaded',()=>{const tabs=[...document.querySelectorAll('[data-history-tab]')];const panels=[...document.querySelectorAll('[data-history-panel]')];const activate=(name)=>{tabs.forEach(tab=>{const active=tab.dataset.historyTab===name;tab.classList.toggle('active',active);tab.setAttribute('aria-selected',String(active))});panels.forEach(panel=>{panel.hidden=panel.dataset.historyPanel!==name})};tabs.forEach(tab=>tab.addEventListener('click',()=>activate(tab.dataset.historyTab)));const params=new URLSearchParams(location.search);const preferred=params.has('anatomopathology_page')?'anatomopathology':params.has('cytology_page')?'cytology':params.has('requests_page')?'laboratory':null;if(tabs[0])activate(tabs.some(tab=>tab.dataset.historyTab===preferred)?preferred:tabs[0].dataset.historyTab);document.querySelectorAll('.history-tab-panel a').forEach(link=>{if(link.closest('nav')){const url=new URL(link.href);const panel=link.closest('[data-history-panel]');url.searchParams.set('history_tab',panel.dataset.historyPanel);link.href=url.href}});if(params.has('history_tab')&&tabs.some(tab=>tab.dataset.historyTab===params.get('history_tab')))activate(params.get('history_tab'))});</script>
@endsection
