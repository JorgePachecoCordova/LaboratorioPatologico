@if($matches !== null && $matches->total() === 0)
<p class="ap-no-match" role="status">Paciente o registro no encontrado. Complete el nuevo informe.</p>
<style>
.ap-page .ap-no-match{margin:0 0 12px;padding:9px 14px;border-left:3px solid #83ad95;background:#eef5f0;color:#405c4c;font-size:13px;line-height:1.5;border-radius:4px}
html[data-theme="dark"] .ap-page .ap-no-match{background:#20342b;color:#c8dfd1;border-color:#6aaf89}
</style>
@if(!$report && !$errors->any())
<script>
window.addEventListener('load', () => {
    requestAnimationFrame(() => {
        const report = document.getElementById('ap-report');
        const field = report?.querySelector('input[name="registration_number"]');
        report?.scrollIntoView({behavior: 'smooth', block: 'start'});
        field?.focus({preventScroll: true});
    });
}, {once: true});
</script>
@endif
@elseif($matches !== null)
<section id="ap-search-results" class="ap-search-results" aria-labelledby="ap-search-heading" tabindex="-1">
    <h2 id="ap-search-heading">Resultados para «{{ $query }}»</h2>
    <p>{{ $matches->total() }} {{ $matches->total() === 1 ? 'informe encontrado' : 'informes encontrados' }}. @if($matches->total() > 1)Seleccione el informe por nombre, fecha y número de registro.@endif</p>
    @forelse($matches as $match)
    <div class="ap-search-row">
        <div><strong>{{ $match->patient_name }}</strong><small>Edad: {{ $match->patient_age ?? 'Sin registrar' }} · Servicio: {{ $match->service ?: 'Sin registrar' }}</small></div>
        <div><strong>Registro: {{ $match->registration_number ?: 'Sin registrar' }}</strong><small>{{ $match->report_date?->format('d/m/Y') }} · Informe {{ $match->report_number }}</small></div>
        <a class="ap-btn soft" href="{{ route('anatomopatologia.index', ['registro' => $match->id, 'q' => $query, 'page' => $matches->currentPage()]) }}#ap-report">{{ $report?->id === $match->id ? 'Ir al informe' : 'Abrir informe' }}</a>
    </div>
    @empty
    <p>No hay coincidencias. Pruebe con otro nombre, apellido o número de registro.</p>
    @endforelse
    @if($matches->hasPages())
    <nav class="ap-search-pages" aria-label="Páginas de resultados">
        @if($matches->previousPageUrl())<a href="{{ $matches->previousPageUrl() }}">Anterior</a>@endif
        <span>Página {{ $matches->currentPage() }} de {{ $matches->lastPage() }}</span>
        @if($matches->nextPageUrl())<a href="{{ $matches->nextPageUrl() }}">Siguiente</a>@endif
    </nav>
    @endif
</section>
<style>
.ap-search-results{padding:20px;margin-bottom:20px;border:1px solid #cdded4;border-radius:12px;background:#fff}
.ap-search-results h2{font-size:18px;margin:0 0 6px}.ap-search-results p{font-size:13px;color:#617569;margin:0 0 12px}
.ap-search-row{display:grid;grid-template-columns:1.3fr 1fr auto;align-items:center;gap:16px;padding:14px 0;border-top:1px solid #e1e9e4}
.ap-search-row strong{display:block;font-size:13px;overflow-wrap:anywhere}.ap-search-row small{display:block;margin-top:5px;color:#617569}.ap-search-pages{display:flex;gap:20px;margin-top:12px;flex-wrap:wrap}
html[data-theme="dark"] .ap-search-results{background:#182327;border-color:#405159;color:#e7f2ed}html[data-theme="dark"] .ap-search-results :is(p,small){color:#b9ccc3}
@media(max-width:700px){.ap-search-row{grid-template-columns:1fr;gap:10px}.ap-search-row a{justify-self:start}}
</style>
@endif
