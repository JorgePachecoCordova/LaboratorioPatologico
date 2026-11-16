@if($matches && $matches->total() === 0)
<p class="cy-no-match-notice" role="status">Paciente no encontrado. Complete el informe a continuación.</p>
@elseif($matches)
<section class="cy-patient-matches patient-picker" aria-labelledby="cy-report-results-title">
    <div class="patient-picker-header"><div><h3 id="cy-report-results-title">Seleccionar paciente o informe</h3><p>Compare el nombre, el número y la fecha antes de elegir.</p></div><span class="cy-match-count">{{ $matches->total() }} coincidencias</span></div>
    <div class="patient-match-list">
        @forelse($matches as $match)
        <a class="patient-match-item" href="{{ route('citologia.index', ['registro'=>$match->id,'tab'=>'informe','q'=>$query,'page'=>$matches->currentPage()]) }}">
            <div><strong>{{ $match->patient_name }}</strong><span>Solicitud: {{ $match->request_number }} · Fecha: {{ $match->request_date?->format('d/m/Y') ?? 'Sin fecha' }} · {{ $match->report_number ? 'Informe: '.$match->report_number : 'Informe pendiente' }}</span></div><span class="cy-select-label">Seleccionar →</span>
        </a>
        @empty<div class="patient-result-empty">No se encontraron registros con «{{ $query }}». Complete el formulario de abajo para registrar al paciente y su informe.</div>@endforelse
    </div>
    @if($matches->hasPages())<footer>{{ $matches->links('pagination.compact') }}</footer>@endif
</section>
@endif
