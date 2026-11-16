@extends('layouts.app')
@section('content')
@php
    $moduleName = match ($pathologyRequest->module) { 'R' => 'Rural', 'G' => 'Ginecología', default => 'Urbano' };
    $district = $pathologyRequest->district?->name ?? mb_strtoupper($moduleName);
    $patient = $pathologyRequest->patient;
    $displayRequestNumber = filled($pathologyRequest->request_number) && $pathologyRequest->request_number !== '/'
        ? $pathologyRequest->request_number
        : str_pad((string) ($pathologyRequest->sequence_number ?: $pathologyRequest->id), 6, '0', STR_PAD_LEFT).'-'.$pathologyRequest->module;
@endphp
<div class="simple-page access-results-page">
    <header class="access-page-head">
        <div><span>Registro de Resultados</span><h1>Resultado {{ $moduleName }}</h1><p>Examen {{ $displayRequestNumber }} · {{ $patient?->full_name ?? 'paciente' }}@if($pathologyRequest->receptionBatch) · Solicitud {{ $pathologyRequest->receptionBatch->display_number }}@endif</p></div>
        <span class="module-badge">Serie {{ $pathologyRequest->module }}</span>
    </header>
    @if(session('success'))<div class="form-alert success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="form-alert error"><strong>Revise la información:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @php
        $sample = $pathologyRequest->samples->firstWhere('id', (int) old('sample_id'))
            ?? $pathologyRequest->samples->firstWhere('id', $selectedSampleId)
            ?? $pathologyRequest->samples->first(fn ($item) => !$item->pathologyResult || $item->pathologyResult->status !== 'validated')
            ?? $pathologyRequest->samples->first();
        $savedResult = $sample?->pathologyResult;
        $result = $newResult ? null : $savedResult;
        $isOldSample = $sample && (string) old('sample_id') === (string) $sample->id;
        $rows = $isOldSample ? collect(old('diagnosis_entries', [])) : ($result?->diagnoses ?? collect())->map(fn($item) => ['code' => $item->code, 'description' => $item->description]);
        if ($rows->isEmpty()) $rows = collect(array_fill(0, 2, ['pathology_type_id' => null]));
    @endphp
    @if($sample)
        <nav class="access-actions" aria-label="Seleccionar muestra">
            @foreach($pathologyRequest->samples as $item)
                <a href="{{ route('resultados.create', ['solicitud' => $pathologyRequest, 'sample_id' => $item->id, 'nuevo' => $newResult ? 1 : 0]) }}" @if($item->id === $sample->id) aria-current="page" class="save" @endif>Muestra {{ $item->sample_number }}</a>
            @endforeach
        </nav>
        @if($newResult)
            <div class="form-alert success">Añadir nuevo resultado: complete nuevamente la fecha, los diagnósticos y el comentario.@if($savedResult) Al guardar se reemplazará el resultado de esta muestra. <x-back-link :href="route('resultados.create', ['solicitud' => $pathologyRequest, 'sample_id' => $sample->id])">Volver al resultado anterior</x-back-link>@endif</div>
        @endif
        <section class="access-result-window">
            <div class="window-title"><strong>Formulario de resultados</strong><span>Muestra {{ $sample->sample_number }} de {{ $pathologyRequest->sample_quantity }}</span></div>
            <div class="request-data-grid {{ $pathologyRequest->isUrban() ? 'request-data-urban' : '' }}">
                @if($pathologyRequest->isGynecological())
                    <div><label>Número solicitud</label><b>{{ $displayRequestNumber }}</b></div>
                    <div><label>Fecha de solicitud</label><b>{{ $pathologyRequest->request_date?->format('d/m/Y') ?? '—' }}</b></div>
                    <div><label>Núm. examen patológico</label><b>{{ $displayRequestNumber }}</b></div>
                    <div class="patient-name"><label>Nombre del paciente</label><b>{{ $patient?->full_name ?? '—' }}</b></div>
                    <div><label>Dirección / procedencia</label><b>GINECOLOGÍA</b></div>
                    <div><label>Fecha de nacimiento</label><b>{{ $patient?->birth_date?->format('d/m/Y') ?? '—' }}</b></div>
                    <div><label>Edad a la fecha del resultado</label><b data-result-patient-age data-birth-date="{{ $patient?->birth_date?->format('Y-m-d') }}">{{ $patient?->ageAt($result?->result_date ?? now()) ?? '—' }}</b></div>
                @elseif($pathologyRequest->isRural())
                    <div><label>Número de informe</label><b>{{ $result?->rural_report_number ?? 'Ingreso manual en el formulario' }}</b></div>
                    <div><label>Fecha de solicitud</label><b>{{ $pathologyRequest->request_date?->format('d/m/Y') ?? '—' }}</b></div>
                    <div><label>Núm. examen patológico</label><b>{{ $displayRequestNumber }}</b></div>
                    <div><label>Secretaría Regional</label><b>{{ $pathologyRequest->regionalSecretariat?->name ?? '—' }}</b></div>
                    <div><label>Establecimiento</label><b>{{ $pathologyRequest->healthFacility?->name ?? '—' }}</b></div>
                    <div><label>Distrito</label><b>{{ $district }}</b></div>
                    <div class="patient-name"><label>Nombre del paciente</label><b>{{ $patient?->full_name ?? '—' }}</b></div>
                    <div><label>Edad a la fecha del resultado</label><b data-result-patient-age data-birth-date="{{ $patient?->birth_date?->format('Y-m-d') }}">{{ $patient?->ageAt($result?->result_date ?? now()) ?? '—' }}</b></div>
                @else
                <div><label>Número solicitud</label><b>{{ $displayRequestNumber }}</b></div>
                
                <div><label>Fecha solicitud</label><b>{{ $pathologyRequest->request_date?->format('d/m/Y') ?? '—' }}</b></div>
                <div class="patient-name"><label>Nombre y apellidos</label><b>{{ $patient?->full_name ?? '—' }}</b></div>
                <div><label>Distrito</label><b>{{ $district }}</b></div>
                <div><label>Sector</label><b>{{ $pathologyRequest->sector?->name ?? '—' }}</b></div>
                <div><label>Área</label><b>{{ $pathologyRequest->area?->name ?? '—' }}</b></div>
                <div class="urban-result-age"><label>Edad a la fecha del resultado</label><b data-result-patient-age data-birth-date="{{ $patient?->birth_date?->format('Y-m-d') }}">{{ $patient?->ageAt($result?->result_date ?? now()) ?? '—' }}</b></div>
                @endif
            </div>
            <form method="POST" action="{{ route('resultados.store', $pathologyRequest) }}" class="access-result-form">
                @php $mayEditResult = \App\Support\AccessRoles::writesResults(auth()->user()); @endphp
                <fieldset @disabled(!$mayEditResult) style="border:0;padding:0;margin:0;min-width:0">
                <input type="hidden" name="expected_revision" value="{{ old('expected_revision', $savedResult?->revision ?? 0) }}">
                <input type="hidden" name="_viewport" value="{{ session('result_viewport', '') }}">
                @csrf
                <input type="hidden" name="sample_id" value="{{ $sample->id }}"><input type="hidden" name="status" value="validated">
                <div class="result-details-grid">
                <div class="result-date-row"><label for="result-date">{{ $pathologyRequest->isUrban() ? 'Fecha de emisión del informe' : 'Fecha de resultado' }}</label><input id="result-date" type="date" name="result_date" required value="{{ $isOldSample ? old('result_date') : ($result?->result_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></div>
                <div class="result-date-row"><label>Clasificación</label><select name="is_positive" required><option value="0" @selected((string)old('is_positive', $result?->is_positive ? '1' : '0') === '0')>NEGATIVO</option><option value="1" @selected((string)old('is_positive', $result?->is_positive ? '1' : '0') === '1')>POSITIVO / PATOLOGÍA GRAVE</option></select></div>
                <div class="result-date-row">
                    <label for="result-reader">Lector *</label>
                    <select id="result-reader" name="reader" required @error('reader') aria-invalid="true" @enderror>
                        <option value="">Seleccione un lector</option>
                        @foreach(\App\Models\PathologyResult::READERS as $reader => $color)
                            <option value="{{ $reader }}" @selected(($isOldSample ? old('reader') : ($result?->reader ?? $pathologyRequest->reader)) === $reader)>{{ $reader }}</option>
                        @endforeach
                    </select>
                    @error('reader')<small class="text-danger" role="alert">{{ $message }}</small>@enderror
                </div>
                @if($pathologyRequest->isRural())
                @if($pathologyRequest->receptionBatch)
                <div class="result-date-row"><label for="rural-report-number">N.º de solicitud (automático)</label><input id="rural-report-number" readonly value="{{ $pathologyRequest->receptionBatch->display_number }}"></div>
                @else
                <div class="result-date-row"><label for="rural-report-number">Número de informe histórico</label><input id="rural-report-number" name="rural_report_number" maxlength="50" placeholder="Número del registro anterior" value="{{ $isOldSample ? old('rural_report_number') : $result?->rural_report_number }}"></div>
                @endif
                <div class="result-date-row"><label for="insurance-number">Núm. asegurado</label><input id="insurance-number" name="insurance_number" maxlength="80" value="{{ $isOldSample ? old('insurance_number') : $result?->insurance_number }}"></div>
                @endif
                </div>
                <div class="diagnosis-table" data-diagnosis-table>
                    <div class="diagnosis-title diagnosis-title-actions"><span>DIAGNÓSTICOS · {{ mb_strtoupper($moduleName) }}</span><button type="button" class="new-diagnosis-button" data-new-diagnosis>＋ Añadir diagnóstico</button></div><p class="gyn-diagnosis-help">Seleccione los códigos correspondientes a la muestra, el diagnóstico, los hallazgos y la recomendación. La descripción se completa desde el catálogo; la fecha del resultado se aplica a todas las filas.</p>
                    <div class="diagnosis-header"><span>N.º</span><span>Diagnóstico</span><span></span></div>
                    <template class="diagnosis-options"><option value="">SELECCIONE UN DIAGNÓSTICO</option>@foreach($pathologyTypes as $type)<option value="{{ $type->id }}" data-code="{{ $type->code }}" data-description="{{ $type->name }}">{{ filled($type->code) ? $type->code.' — ' : '' }}{{ $type->name }}</option>@endforeach</template>
                    <div class="diagnosis-body">
                        @foreach($rows as $index => $row)
                            @php $selectedTypeId = $row['pathology_type_id'] ?? $pathologyTypes->firstWhere('code', $row['code'] ?? null)?->id; @endphp
                            <div class="diagnosis-line"><span class="line-number">{{ $loop->iteration }}</span><select name="diagnosis_entries[{{ $index }}][pathology_type_id]" ><option value="">SELECCIONE UN DIAGNÓSTICO</option>@foreach($pathologyTypes as $type)<option value="{{ $type->id }}" data-code="{{ $type->code }}" data-description="{{ $type->name }}" @selected((string) $selectedTypeId === (string) $type->id)>{{ filled($type->code) ? $type->code.' — ' : '' }}{{ $type->name }}</option>@endforeach</select><button type="button" class="remove-line" aria-label="Eliminar fila">×</button></div>
                        @endforeach
                    </div>
                    <button type="button" class="add-line"><x-ui-icon name="plus" /> Agregar resultado</button>
                </div>
                <div class="comment-row"><label>Comentario</label><textarea name="notes" rows="3" maxlength="10000" class="uppercase-comment" lang="es" spellcheck="true" autocapitalize="characters" autocomplete="off" placeholder="Escriba el comentario">{{ $isOldSample ? old('notes') : $result?->notes }}</textarea></div>
                <div class="access-actions"><button type="submit" class="save"><x-ui-icon name="save" /> Guardar</button>@if($result)<a href="{{ route('resultados.imprimir', $result) }}" target="_blank" class="print"><x-ui-icon name="print" /> Imprimir</a>@else<button type="button" class="print" disabled title="Guarde el resultado antes de imprimir"><x-ui-icon name="print" /> Imprimir</button>@endif<x-back-link :href="route('resultados.index')" class="back">Volver al listado</x-back-link></div>
                </fieldset>
            </form>
            @if($savedResult)
            <details style="padding:16px"><summary>Historial de cambios del resultado</summary>
                @forelse($savedResult->audits as $audit)
                <article style="padding:12px 0;border-bottom:1px solid #b8c9c0"><strong>Versión {{ $audit->revision }} · {{ $audit->actor_name }} ({{ $audit->actor_username }})</strong><p>{{ $audit->created_at->format('d/m/Y H:i:s') }} · {{ $audit->action === 'created' ? 'Registro inicial' : 'Corrección' }}</p>@if($audit->reason)<p>Motivo: {{ $audit->reason }}</p>@endif
                    <details><summary>Comparar contenido anterior y guardado</summary>
                    @foreach(['before' => 'Anterior', 'after' => 'Guardado'] as $side => $label)
                        <div><h3>{{ $label }}</h3>@if($audit->$side)
                        @foreach(['result_date' => 'Fecha', 'diagnosis' => 'Diagnóstico', 'notes' => 'Comentario', 'status' => 'Estado', 'is_positive' => 'Positivo'] as $key => $title)<p><strong>{{ $title }}:</strong> {{ data_get($audit->$side, 'result.'.$key) }}</p>@endforeach
                        @foreach(data_get($audit->$side, 'diagnoses', []) as $diagnosis)<p>{{ $diagnosis['code'] }} — {{ $diagnosis['description'] }}</p>@endforeach
                        @else<p>Sin versión anterior.</p>@endif</div>
                    @endforeach</details>
                </article>
                @empty<p>Resultado anterior a la activación del historial. Su próxima modificación conservará el contenido previo.</p>@endforelse
            </details>
            @endif
        </section>
    @endif
</div>
<style>
html[data-theme="dark"] .access-results-page .form-alert.success{border-color:#365d4b;background:#203b30;color:#b5efd0}
html[data-theme="dark"] .access-results-page .form-alert.success a{color:#8ce5b7;text-decoration:underline;text-underline-offset:3px;font-weight:700}
html[data-theme="dark"] .access-results-page .form-alert.success a:hover{color:#dcfce7}
html[data-theme="dark"] .access-results-page .form-alert.error{border-color:#754343;background:#40292c;color:#fecaca}
html[data-theme="dark"] .access-results-page .module-badge{border-color:#365d4b;background:#203b30;color:#b5efd0}
.access-results-page{box-sizing:border-box;width:calc(100% - 38px);max-width:1380px;margin:0 auto;padding:14px 0 20px}.access-page-head{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:12px}.access-page-head span{color:#16824e;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.access-page-head h1{margin:3px 0 2px;color:#14392c;font-size:27px}.access-page-head p{margin:0;color:#64748b}.module-badge{border:1px solid #b9dbc9;border-radius:999px;padding:7px 12px;background:#edf8f2}.form-alert{margin-bottom:12px;border-radius:9px;padding:10px 13px}.form-alert.success{border:1px solid #b9e6cd;background:#ecfdf5;color:#166534}.form-alert.error{border:1px solid #fecaca;background:#fef2f2;color:#991b1b}.form-alert ul{margin:5px 0 0 18px}.access-result-window{overflow:hidden;margin-bottom:14px;border:1px solid #d5e1db;border-radius:12px;background:#fff;box-shadow:0 10px 28px rgba(20,63,43,.08)}.window-title{display:flex;align-items:center;justify-content:space-between;padding:11px 15px;border-bottom:1px solid #d9e4de;background:#f2f8f5;color:#173a2d}.window-title span{color:#657970;font-size:12px}.request-data-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 14px;padding:14px 16px;border-bottom:1px solid #dce6e1}.request-data-grid .patient-name{grid-column:1/-1}.request-data-grid label{display:block;margin-bottom:3px;color:#657970;font-size:11px;font-weight:700;text-transform:uppercase}.request-data-grid b{display:block;min-height:34px;padding:8px 10px;border:1px solid #d8e2dd;border-radius:7px;background:#fbfdfc;color:#17382c;font-size:13px}.access-result-form{padding:14px 16px}.result-date-row{display:grid;grid-template-columns:160px minmax(180px,280px);align-items:center;gap:10px;margin-bottom:12px}.result-date-row label,.comment-row label{font-weight:700;color:#17382c}.result-date-row input{height:38px;border:1px solid #cfdcd5;border-radius:7px;padding:0 10px}.diagnosis-table{overflow:hidden;border:1px solid #cad8d1;border-radius:9px}.diagnosis-title{padding:9px;text-align:center;background:#eaf5ef;color:#126d42;font-weight:900}.diagnosis-header,.diagnosis-line{display:grid;grid-template-columns:42px minmax(130px,190px) 1fr 40px;align-items:center}.diagnosis-header{min-height:34px;border-top:1px solid #cad8d1;border-bottom:1px solid #cad8d1;background:#f7faf8;color:#4b6358;font-size:11px;font-weight:800;text-transform:uppercase}.diagnosis-header span{padding:0 9px}.diagnosis-line{gap:7px;padding:7px;border-bottom:1px solid #e4ebe7}.diagnosis-line input{width:100%;height:38px;border:1px solid #d0dcd6;border-radius:6px;padding:0 9px;font:inherit;text-transform:uppercase}.line-number{display:grid;place-items:center;width:26px;height:26px;margin:auto;border-radius:50%;background:#e7f5ed;color:#137544;font-weight:800}.remove-line{width:30px;height:30px;border:0;border-radius:6px;background:#fbeaea;color:#a32929;font-size:19px;cursor:pointer}.add-line{margin:9px;border:0;border-radius:7px;padding:8px 12px;background:#e8f6ee;color:#126d42;font-weight:800;cursor:pointer}.comment-row{display:grid;grid-template-columns:160px 1fr;align-items:start;gap:10px;margin-top:12px}.comment-row textarea{width:100%;min-height:70px;border:1px solid #cfdcd5;border-radius:7px;padding:9px;resize:vertical;text-transform:uppercase}.access-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:13px}.access-actions :is(button,a){display:inline-flex;min-width:90px;min-height:39px;align-items:center;justify-content:center;border:1px solid #cbd8d1;border-radius:7px;padding:0 15px;background:#fff;color:#244638;font:700 13px inherit;text-decoration:none;cursor:pointer}.access-actions .save{border-color:#16824e;background:#16824e;color:#fff}.access-actions .print{border-color:#2563eb;background:#2563eb;color:#fff}.access-actions button:disabled{cursor:not-allowed;opacity:.5}
html[data-theme="dark"] .access-page-head h1,html[data-theme="dark"] .result-date-row label,html[data-theme="dark"] .comment-row label{color:#f3faf6}html[data-theme="dark"] .access-page-head p{color:#afc1b8}html[data-theme="dark"] .access-result-window{border-color:#405158;background:#1b272b}html[data-theme="dark"] .window-title,html[data-theme="dark"] .diagnosis-title{border-color:#405158;background:#263b33;color:#a2edc4}html[data-theme="dark"] .request-data-grid{border-color:#405158}html[data-theme="dark"] .request-data-grid label{color:#abc0b6}html[data-theme="dark"] .request-data-grid b,html[data-theme="dark"] .diagnosis-header{border-color:#405158;background:#202d31;color:#f3faf6}html[data-theme="dark"] .diagnosis-table,html[data-theme="dark"] .diagnosis-line{border-color:#405158}html[data-theme="dark"] .diagnosis-line input,html[data-theme="dark"] .result-date-row input,html[data-theme="dark"] .comment-row textarea{border-color:#405158;background:#172125;color:#f3faf6}html[data-theme="dark"] .access-actions :is(button,a){border-color:#52645c;background:#26343a;color:#f3faf6}html[data-theme="dark"] .access-actions .save{background:#16824e}html[data-theme="dark"] .access-actions .print{background:#2563eb}
.diagnosis-header,.diagnosis-line{grid-template-columns:42px 1fr 40px}.diagnosis-line select{width:100%;height:42px;border:1px solid #d0dcd6;border-radius:6px;padding:0 38px 0 10px;background:#fff;color:#17382c;font:inherit}html[data-theme="dark"] .diagnosis-line select{border-color:#405158;background:#172125;color:#f3faf6}
@media(max-width:760px){.access-results-page{width:100%;padding:12px}.request-data-grid{grid-template-columns:1fr}.request-data-grid .patient-name{grid-column:auto}.result-date-row,.comment-row{grid-template-columns:1fr}.diagnosis-header{display:none}.diagnosis-line{grid-template-columns:34px 1fr 34px}.access-actions{justify-content:stretch;flex-wrap:wrap}.access-actions :is(button,a){flex:1}}
.gyn-diagnosis-help{margin:0;padding:12px 15px;border-top:1px solid #dce6e1;color:#536b5c;font-size:13px;line-height:1.6}html[data-theme=dark] .gyn-diagnosis-help{color:#b8cdbf}
/* Datos superiores con columnas alineadas y controles de igual tamaño. */
.access-results-page .request-data-grid{gap:16px;padding:20px}
.access-results-page .request-data-grid>div{min-width:0;display:flex;flex-direction:column}
.access-results-page .request-data-grid label{margin-bottom:6px;font-size:11px;line-height:1.4;letter-spacing:.025em}
.access-results-page .request-data-grid b{display:flex;align-items:center;flex:1;min-height:42px;padding:10px 12px;font-size:13px;line-height:1.45;overflow-wrap:anywhere}
.access-results-page .request-data-urban{grid-template-columns:repeat(6,minmax(0,1fr))}
.access-results-page .request-data-urban>div:nth-child(1),.access-results-page .request-data-urban>div:nth-child(2){grid-column:span 3}
.access-results-page .request-data-urban>.patient-name{grid-column:1/-1}
.access-results-page .request-data-urban>div:nth-child(n+4){grid-column:span 2}
.access-results-page .access-result-form{padding:20px}
.access-results-page .access-result-form .result-date-row{grid-template-columns:220px minmax(200px,340px);gap:16px;margin-bottom:14px;align-items:center}
.access-results-page .result-date-row label{font-size:14px;line-height:1.5}
.access-results-page .access-result-form .result-date-row :is(input,select){box-sizing:border-box;width:100%;height:42px;min-height:42px;padding:0 12px;font-family:inherit;font-size:14px;border-radius:8px}
.access-results-page .comment-row{grid-template-columns:220px minmax(0,1fr);gap:16px}
@media(max-width:760px){.access-results-page .request-data-grid,.access-results-page .request-data-urban{grid-template-columns:minmax(0,1fr);padding:16px;gap:12px}.access-results-page .request-data-urban>div:nth-child(n){grid-column:1/-1}.access-results-page .access-result-form{padding:16px}.access-results-page .access-result-form .result-date-row,.access-results-page .comment-row{grid-template-columns:minmax(0,1fr);gap:7px}}
/* Registro del resultado: aprovecha el ancho y mantiene etiquetas alineadas. */
.access-results-page .result-details-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 22px;padding-bottom:22px}
.access-results-page .access-result-form .result-details-grid .result-date-row{display:flex;flex-direction:column;align-items:stretch;justify-content:flex-start;gap:7px;margin:0;min-width:0}
.access-results-page .result-details-grid .result-date-row label{margin:0;min-height:21px;font-size:13px;line-height:1.5;font-weight:600;color:#345342}
.access-results-page .result-details-grid .result-date-row :is(input,select){width:100%;max-width:none;min-width:0;height:44px;min-height:44px;background:#fff;border:1px solid #cfdcd5;border-radius:8px;padding:0 12px}
html[data-theme="dark"] .access-results-page .result-details-grid .result-date-row label{color:#d4e7dc}
html[data-theme="dark"] .access-results-page .result-details-grid .result-date-row :is(input,select){background:#172125;border-color:#405158;color:#f3faf6}
@media(max-width:1100px){.access-results-page .result-details-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:650px){.access-results-page .result-details-grid{grid-template-columns:minmax(0,1fr);gap:14px}}
</style>
<script>
document.querySelectorAll('.uppercase-comment').forEach(field=>field.addEventListener('input',()=>{const start=field.selectionStart;const end=field.selectionEnd;field.value=field.value.toLocaleUpperCase('es');field.setSelectionRange(start,end)}));
</script>
<script src="{{ asset('js/anatomopathology-position.js') }}?v={{ filemtime(public_path('js/anatomopathology-position.js')) }}" defer></script>
<link rel="stylesheet" href="{{ asset('vendor/select2/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/result-diagnosis-select.css') }}?v={{ filemtime(public_path('css/result-diagnosis-select.css')) }}">
<script src="{{ asset('vendor/select2/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
<script src="{{ asset('js/result-diagnosis-select.js') }}?v={{ filemtime(public_path('js/result-diagnosis-select.js')) }}" defer></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const age=document.querySelector('[data-result-patient-age]');
    const date=document.querySelector('[name="result_date"]');
    if(!age||!date)return;
    const update=()=>{
        const birth=age.dataset.birthDate;
        if(!birth||!date.value||date.value<birth){age.textContent='—';return;}
        const [by,bm,bd]=birth.split('-').map(Number),[y,m,d]=date.value.split('-').map(Number);
        age.textContent=String(y-by-((m<bm||(m===bm&&d<bd))?1:0));
    };
    date.addEventListener('change',update);update();
});
</script>
@include('resultados.diagnosis-catalog')
@endsection
