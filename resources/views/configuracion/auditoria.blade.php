@extends('layouts.app')
@section('title','Historial de resultados')
@section('content')
<div class="audit-page">
    <header class="audit-header"><div><span class="audit-eyebrow">TRAZABILIDAD CLÍNICA</span><h1>Historial de resultados</h1><p>Quién registró o corrigió un resultado y qué datos cambió.</p></div><a class="audit-back" href="{{ route('configuracion.index') }}">← Configuración</a></header>
    <form method="GET" class="audit-filters">
        <label>Usuario<input name="actor" maxlength="80" placeholder="Ej.: PAT2026" value="{{ request('actor') }}"></label>
        <label>Acción<select name="action"><option value="">Todas</option><option value="created" @selected(request('action')==='created')>Registro inicial</option><option value="corrected" @selected(request('action')==='corrected')>Corrección del resultado</option></select></label>
        <label>Fecha<input type="date" min="0001-01-01" max="9999-12-31" data-audit-date name="date" value="{{ request('date') }}"></label>
        <button type="submit">Buscar en el historial</button><a href="{{ route('configuracion.auditoria') }}">Limpiar filtros</a>
    </form>
    <div class="audit-count"><strong>{{ $audits->total() }} registros</strong><span>Solo resultados de Rural, Urbano y Ginecología. No se registran visitas a pantallas.</span></div>
    <div class="audit-table-wrap"><table class="audit-table"><thead><tr><th>Cuándo</th><th>Quién</th><th>Acción</th><th>Solicitud / Paciente</th><th>Cambios del resultado</th></tr></thead>
    <tbody>@forelse($audits as $audit)
    @php
        $result=$audit->result;
        $requestRecord=$result?->sample?->pathologyRequest;
        $changes=\App\Support\ResultAuditPresentation::changes($audit);
    @endphp
    <tr>
        <td class="audit-when"><strong>{{ $audit->created_at->format('d/m/Y') }}</strong><small>{{ $audit->created_at->format('H:i:s') }}</small></td>
        <td><strong>{{ $audit->actor_name }}</strong><small>{{ $audit->actor_username }}</small></td>
        <td><span class="audit-badge audit-change">{{ $audit->action==='created'?'Registro inicial':'Corrección del resultado' }}</span><small>Versión {{ $audit->revision }}</small></td>
        <td><strong>{{ $requestRecord?->request_number ?? 'Resultado N.º '.$audit->pathology_result_id }}</strong><small>{{ $requestRecord?->patient?->full_name ?? 'Registro histórico' }}</small><small>{{ ['R'=>'Rural','U'=>'Urbano','G'=>'Ginecología'][$requestRecord?->module]??'' }} · Muestra {{ $result?->sample?->sample_number ?? '—' }}</small></td>
        <td class="audit-detail"><details><summary>Ver datos {{ $audit->action==='created'?'registrados':'modificados' }}</summary>
            @if(count($changes))<div class="audit-comparison"><table><thead><tr><th>Dato</th><th>Antes</th><th>Después</th></tr></thead><tbody>@foreach($changes as $change)<tr><td>{{ $change['field'] }}</td><td>{{ $change['before'] }}</td><td>{{ $change['after'] }}</td></tr>@endforeach</tbody></table></div>
            @else<p>Se guardó esta versión sin diferencias en los datos clínicos mostrados.</p>@endif
            @if($audit->reason)<p>Observación registrada: {{ $audit->reason }}</p>@endif
        </details></td>
    </tr>@empty<tr><td colspan="5" class="audit-empty">No hay registros de resultados para estos filtros.</td></tr>@endforelse</tbody></table></div>
    <div class="audit-pagination">{{ $audits->links() }}</div>
</div>
<style>
.audit-page{max-width:1600px;margin:0 auto;padding:24px;color:#243f32}.audit-header{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:22px}.audit-eyebrow{font-size:11px;font-weight:700;letter-spacing:.1em;color:#16824e}.audit-header h1{font-size:28px;margin:5px 0}.audit-header p{margin:0;color:#62796b;font-size:14px}.audit-back{padding:10px 15px;border:1px solid #ccdcd2;border-radius:8px;color:#315d44;text-decoration:none;background:#fff;font-size:13px;white-space:nowrap}.audit-filters{display:flex;gap:14px;align-items:flex-end;flex-wrap:wrap;padding:20px;background:#fff;border:1px solid #d9e5de;border-radius:12px}.audit-filters label{display:flex;flex-direction:column;gap:7px;flex:1;min-width:160px;font-size:12px;font-weight:600}.audit-filters input,.audit-filters select{box-sizing:border-box;width:100%;height:42px;padding:0 12px;border:1px solid #ccdcd2;border-radius:7px;background:#fff;font:14px "Segoe UI",sans-serif;color:#243f32}.audit-filters button{height:42px;padding:0 18px;border:0;border-radius:7px;background:#12804e;color:white;font-weight:600;cursor:pointer}.audit-filters>a{font-size:12px;color:#426d54;padding:12px 0}.audit-count{display:flex;gap:16px;align-items:center;flex-wrap:wrap;padding:18px 0;font-size:12px;color:#617969}.audit-count strong{color:#245a3d}.audit-table-wrap{overflow:auto;border:1px solid #d9e5de;border-radius:12px;background:#fff}.audit-table{width:100%;min-width:950px;border-collapse:collapse;text-align:left;font-size:13px}.audit-table>thead{background:#edf5f0;color:#476652;font-size:11px;text-transform:uppercase;letter-spacing:.04em}.audit-table th{padding:13px 16px;font-weight:700;text-align:left}.audit-table>tbody>tr>td{padding:16px;vertical-align:top;border-top:1px solid #e2ebe5}.audit-table>tbody>tr:hover{background:#fbfdfb}.audit-table strong{font-size:13px;font-weight:600}.audit-table small{display:block;color:#708475;font-size:11px;margin-top:5px;line-height:1.5}.audit-when{white-space:nowrap}.audit-badge{display:inline-block;padding:5px 9px;border-radius:6px;background:#eef2f6;color:#4b6073;font-size:11px;font-weight:600;white-space:nowrap}.audit-change{background:#e6f4ec;color:#187044}.audit-detail{width:38%;min-width:260px}.audit-detail p{margin:0;font-size:12px;line-height:1.6;color:#62796b}.audit-detail summary{font-size:12px;color:#197448;cursor:pointer;font-weight:600}.audit-comparison{margin-top:12px;max-height:360px;overflow:auto;border:1px solid #dce6df;border-radius:7px}.audit-comparison table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:12px}.audit-comparison th,.audit-comparison td{padding:9px;border-bottom:1px solid #e4ece6;white-space:pre-wrap;overflow-wrap:anywhere;vertical-align:top}.audit-comparison th{background:#f2f7f4;font-size:11px}.audit-technical{margin-top:10px}.audit-technical summary{font-weight:400;font-size:11px;color:#788b7d}.audit-technical small{overflow-wrap:anywhere}.audit-empty{text-align:center;padding:40px!important;color:#6d8172}.audit-pagination{padding:20px 0}
html[data-theme=dark] .audit-page{color:#e0ece5}html[data-theme=dark] .audit-filters,html[data-theme=dark] .audit-table-wrap,html[data-theme=dark] .audit-back{background:#1b292d;border-color:#41564b;color:#e0ece5}html[data-theme=dark] .audit-filters input,html[data-theme=dark] .audit-filters select{background:#172125;color:#e0ece5;border-color:#41564b}html[data-theme=dark] .audit-table>thead,html[data-theme=dark] .audit-comparison th{background:#283d32;color:#c4ddcd}html[data-theme=dark] .audit-table>tbody>tr:hover{background:#20302a}html[data-theme=dark] .audit-table>tbody>tr>td{border-color:#354a3d}html[data-theme=dark] .audit-detail p,html[data-theme=dark] .audit-table small,html[data-theme=dark] .audit-header p,html[data-theme=dark] .audit-count{color:#acc1b3}html[data-theme=dark] .audit-count strong{color:#a2deb9}
@media(max-width:760px){.audit-page{padding:16px}.audit-header{align-items:flex-start;flex-direction:column}.audit-header h1{font-size:24px}.audit-filters{padding:15px}}
</style>
<script>
(() => {
    const input = document.querySelector('[data-audit-date]');
    if (!input) return;
    let previous = input.value;
    input.addEventListener('input', () => {
        const year = input.value.split('-')[0];
        if (year.length > 4) input.value = previous;
        else previous = input.value;
    });
})();
</script>
@endsection
