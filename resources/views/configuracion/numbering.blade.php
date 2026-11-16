@extends('layouts.app')
@section('title', 'Configuración / Correlativos')
@section('content')
@include('configuracion.styles')
<style>
.cfg-numbering-filter{display:flex;gap:10px;align-items:end;margin:16px 0;flex-wrap:wrap}
.cfg-numbering-filter .cfg-field{width:130px}.cfg-numbering-note{font-size:13px;line-height:1.6;margin:12px 0;color:#53695d}
.cfg-numbering-form{display:grid;grid-template-columns:minmax(110px,150px) minmax(180px,1fr) auto;gap:12px;align-items:end;min-width:480px}
.cfg-numbering-table th:nth-child(1){width:20%}.cfg-numbering-table th:nth-child(2){width:18%}
.cfg-numbering-next{color:#0f7e48;font-weight:650;font-size:15px}.cfg-numbering-last{display:block;font-size:12px;color:#718096;margin-top:5px}
html[data-theme="dark"] .cfg-numbering-note{color:#b9cdc3}html[data-theme="dark"] .cfg-numbering-next{color:#8fe0b5}html[data-theme="dark"] .cfg-numbering-last{color:#b0c6bb}
@media(max-width:760px){.cfg-numbering-form{grid-template-columns:1fr;min-width:210px}.cfg-numbering-table td{vertical-align:top}}
</style>
<div class="cfg-page">
    <div class="cfg-header"><div class="cfg-header-accent"></div><div><span class="cfg-eyebrow">ADMINISTRACIÓN</span><h1>CONFIGURACIÓN</h1><p>USUARIOS, MÉDICOS Y CATÁLOGOS INTERNOS DEL SISTEMA</p></div></div>
    @if(session('success'))<div class="cfg-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="cfg-alert error" role="alert"><strong>Revise la información.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <nav class="cfg-nav" aria-label="Secciones de configuración">
        @foreach(['usuarios'=>'Usuarios','medicos'=>'Médicos','municipios'=>'Municipios','distritos'=>'Distritos','areas'=>'Áreas','sectores'=>'Sectores','establecimientos'=>'Establecimientos','tipos-patologia'=>'Tipos de patología','protocolos'=>'Protocolos de anatomopatología'] as $tab=>$label)
        <a href="{{ route('configuracion.index') }}#{{ $tab }}">{{ $label }}</a>
        @endforeach
        <a class="active" href="{{ route('configuracion.numeracion', ['year'=>$year]) }}" aria-current="page">Correlativos</a>
    </nav>
    <section class="cfg-section active" aria-labelledby="numbering-title">
        <div class="cfg-section-head"><div><h2 id="numbering-title">Correlativos de solicitudes y exámenes</h2><small>Numeración independiente de Urbano, Rural y Ginecología por gestión.</small></div></div>
        <form method="GET" class="cfg-numbering-filter">
            <div class="cfg-field"><label for="numbering-year">Gestión</label><input class="cfg-input" id="numbering-year" type="number" name="year" value="{{ $year }}" min="2025" max="2100" required></div>
            <button class="cfg-btn secondary" type="submit">Consultar gestión</button>
        </form>
        <p class="cfg-numbering-note">Para continuar desde Access, registre el <strong>último número utilizado</strong>, sin letras. Si el último examen fue 14342-R, ingrese 14342; el siguiente será 014343-R. Los números existentes se conservan.</p>
        <div class="cfg-table-wrap"><table class="cfg-table cfg-numbering-table">
            <thead><tr><th>Área y numeración</th><th>Próximo número</th><th>Ajustar continuidad</th></tr></thead>
            <tbody>@foreach($rows as $row)<tr>
                <td><strong>{{ $row['label'] }}</strong><br><span>{{ $row['caption'] }}</span></td>
                <td><span class="cfg-numbering-next">{{ $row['last'] + 1 }}</span><span class="cfg-numbering-last">Último utilizado: {{ $row['last'] }}</span></td>
                <td><form method="POST" action="{{ route('configuracion.numeracion.update') }}" class="cfg-numbering-form">
                    @csrf @method('PUT')
                    <input type="hidden" name="module" value="{{ $row['module'] }}"><input type="hidden" name="kind" value="{{ $row['kind'] }}"><input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="expected_last" value="{{ $row['last'] }}">
                    @php $fieldId = $row['module'].'-'.$row['kind']; @endphp
                    <div class="cfg-field"><label for="last-{{ $fieldId }}">Último utilizado</label><input class="cfg-input" id="last-{{ $fieldId }}" type="number" name="last_number" value="{{ $row['last'] }}" min="{{ $row['last'] }}" max="999998" required></div>
                    <div class="cfg-field"><label for="reason-{{ $fieldId }}">Motivo del ajuste</label><input class="cfg-input" id="reason-{{ $fieldId }}" name="reason" placeholder="Continuidad de Access" minlength="5" maxlength="500" required></div>
                    <button class="cfg-btn" type="submit"><x-ui-icon name="save" /> Guardar</button>
                </form></td>
            </tr>@endforeach</tbody>
        </table></div>
        <p class="cfg-numbering-note">Los ajustes requieren permisos de administración. El contador no puede reducirse ni reutilizar números reservados.</p>
    </section>
</div>
@endsection
