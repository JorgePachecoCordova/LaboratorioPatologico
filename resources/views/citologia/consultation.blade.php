@extends('layouts.app')
@section('content')
<div class="cy-consultation">
    <h1>Informes citológicos</h1>
    <p>Consulta de informes validados</p>
    <form method="GET" action="{{ route('citologia.index') }}" role="search">
        <label for="cy-consult-search">Buscar paciente o informe</label>
        <div class="cy-consult-search"><input id="cy-consult-search" name="q" value="{{ $query }}" placeholder="Nombre, solicitud o número de informe"><button><x-ui-icon name="search" /> Buscar</button></div>
    </form>
    <link rel="stylesheet" href="{{ asset('css/cytology-search-results.css') }}">
    @include('citologia.search-results')
    @if($report)
    <article class="cy-consult-report">
        <h2>{{ $report->patient_name }}</h2>
        <p>Informe {{ $report->report_number }} · {{ $report->report_date?->format('d/m/Y') }} · Solicitud {{ $report->request_number }}</p>
        @foreach(['description' => 'Descripción', 'conclusion' => 'Conclusión', 'note' => 'Nota'] as $field => $label)
            @if(filled($report->$field))<h3>{{ $label }}</h3><p class="cy-consult-text">{{ $report->$field }}</p>@endif
        @endforeach
        <div class="cy-consult-actions"><a href="{{ route('citologia.imprimir', $report) }}" target="_blank" rel="noopener"><x-ui-icon name="print" /> Imprimir informe</a><a href="{{ route('citologia.excel', $report) }}"><x-ui-icon name="file" /> Descargar Excel</a></div>
    </article>
    @endif
</div>
<style>
.cy-consultation{padding:24px;color:#244538}.cy-consultation h1{font-size:28px;margin:0}.cy-consultation>p{color:#526c5d}.cy-consultation>form,.cy-consult-report{background:#fff;border:1px solid #adc5b6;border-radius:12px;padding:20px;margin:20px 0}.cy-consult-search{display:flex;gap:10px;margin-top:8px}.cy-consult-search input{flex:1;min-width:0;padding:12px;border:1px solid #7f9e8c;border-radius:7px}.cy-consult-search button,.cy-consult-actions a{background:#147b49;color:#fff;border:0;border-radius:7px;padding:12px 18px;text-decoration:none}.cy-consult-actions{display:flex;gap:10px;flex-wrap:wrap}.cy-consult-text{white-space:pre-wrap;line-height:1.6}html[data-theme="dark"] .cy-consultation{color:#e1eee7}html[data-theme="dark"] .cy-consultation>p{color:#bdcec4}html[data-theme="dark"] :is(.cy-consultation>form,.cy-consult-report){background:#1c2a2e;border-color:#4c6258}html[data-theme="dark"] .cy-consult-search input{background:#111c20;color:#eef7f2}
</style>
@endsection
