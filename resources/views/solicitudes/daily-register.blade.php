<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro diario — {{ $areas[$module] }} — {{ $date }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#edf3ef;color:#183d2c;font-family:Arial,sans-serif}
        .toolbar{padding:18px 24px;background:#fff;border-bottom:1px solid #c8d9cf;display:flex;gap:20px;align-items:center;justify-content:space-between;flex-wrap:wrap}
        a{color:#146d42}.filters{display:flex;gap:12px;align-items:end;flex-wrap:wrap}label{display:flex;gap:5px;flex-direction:column;font-size:13px;font-weight:bold}input,select,button{min-height:40px;padding:8px 12px;border:1px solid #b4cabd;border-radius:6px;font:inherit}button{background:#176d44;color:white;border:0;cursor:pointer}.print-button{background:#294a61}
        .toolbar .print-button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:12px 20px;border:1px solid #294a61;border-radius:7px;background:#294a61;color:#fff;font-size:16px;font-weight:700;line-height:1.25;text-decoration:none;box-shadow:0 2px 5px rgba(20,45,63,.18);cursor:pointer}.toolbar .print-button:hover{background:#1c3547;border-color:#1c3547}.toolbar .print-button:focus-visible{outline:3px solid #176d44;outline-offset:3px}
        .help{margin:16px 24px;color:#486453;font-size:14px}.errors{margin:16px 24px;color:#9b2525}.sheet{margin:20px 24px;padding:24px;background:white;border:1px solid #c7d7cd}
        .report-heading{display:flex;gap:12px;align-items:center;justify-content:center;margin-bottom:10px}.report-heading img{width:65px;height:60px;object-fit:contain}.report-heading h1{font-size:15px;line-height:1.15;text-align:center;margin:0}.report-heading p{margin:3px 0 0;text-align:center;font-size:12px}
        .table-wrap{overflow-x:auto}table{width:100%;min-width:1050px;border-collapse:collapse;table-layout:fixed;color:#152f21}th,td{border:1px solid #628772;padding:5px 6px;overflow-wrap:anywhere;font-size:12px;vertical-align:top}th{background:#e7f0e9;vertical-align:middle;text-align:center;font-size:11px}td{height:auto}.center{text-align:center}.result{white-space:pre-line}.empty{text-align:center;padding:30px}.total{font-size:13px;font-weight:bold;margin-top:14px}thead{display:table-header-group}tr{break-inside:avoid}
        .toolbar .back-button{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:44px;padding:11px 16px;border:1px solid #b9d3c4;border-radius:8px;background:#f0f7f3;color:#155c3a;font-size:15px;font-weight:700;line-height:1.25;text-decoration:none;transition:background .15s,border-color .15s}.toolbar .back-button:hover{background:#e1efe7;border-color:#6d9f82}.toolbar .back-button:focus-visible{outline:3px solid #176d44;outline-offset:3px}.back-button svg{width:19px;height:19px;flex-shrink:0}
        .sample-result{line-height:1.25;print-color-adjust:exact;-webkit-print-color-adjust:exact}.sample-result+.sample-result{margin-top:2px}.positive-codes{width:fit-content;max-width:100%;padding:0 4px;border-left:3px solid #c62828;background:#fde7e7;font-weight:700}
        @page{size:A4 landscape;margin:10mm}
        @media print{body{background:white;color:black}.toolbar,.help,.errors{display:none}.sheet{margin:0;padding:0;border:0}.table-wrap{overflow:visible}table{min-width:0}th,td{font-size:9px;padding:5px 4px;color:black}th{font-size:9px;print-color-adjust:exact}.report-heading h1{font-size:12px}.report-heading p{font-size:10px}.report-heading img{width:50px;height:45px}.report-heading{margin-bottom:7px}}
        @media(max-width:600px){.toolbar{padding:12px}.sheet{margin:12px;padding:12px}.report-heading{gap:10px}.report-heading h1{font-size:13px}.report-heading img{width:60px;height:60px}.filters{width:100%}.filters label{flex:1}.help{margin:12px}}
    </style>
</head>
<body>
    <nav class="toolbar" aria-label="Opciones del registro diario">
        <a class="back-button" href="{{ route('solicitudes.index') }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7M5 12h14"/></svg><span>Solicitudes / Muestras</span></a>
        <form method="GET" action="{{ route('solicitudes.registro-diario') }}" class="filters">
            <label>Área<select name="module" required>@foreach($areas as $code => $name)<option value="{{ $code }}" @selected($module === $code)>{{ $name }}</option>@endforeach</select></label>
            <label>Fecha de solicitud<input type="date" name="date" min="2025-01-01" value="{{ $date }}" required></label>
            <button type="submit"><x-ui-icon name="search" /> Consultar</button>
        </form>
        @if($printing)
            <button type="button" class="print-button" onclick="window.print()"><x-ui-icon name="print" /> Imprimir / Guardar PDF</button>
        @else
            <a class="print-button" target="_blank" href="{{ route('solicitudes.registro-diario', ['date' => $date, 'module' => $module, 'print' => 1]) }}">Imprimir / Guardar PDF</a>
        @endif
    </nav>
    @if($errors->any())<p class="errors" role="alert">{{ $errors->first() }}</p>@endif
    <p class="help">Registro por fecha de solicitud. Para descargarlo, pulse «Imprimir / Guardar PDF» y elija «Guardar como PDF». Los resultados pendientes se actualizan al volver a consultar.</p>
    <main class="sheet">
        <header class="report-heading">
            <img src="{{ asset('img/logo-hospital-sidebar.png') }}" alt="Hospital Daniel Bracamonte">
            <div><h1>HOSPITAL DANIEL BRACAMONTE<br>SERVICIO DE ANATOMÍA PATOLÓGICA Y CITOLOGÍA<br>PAPANICOLAOU — {{ $areas[$module] }}</h1><p>REGISTRO DIARIO · {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p></div>
        </header>
        <div class="table-wrap">
            <table>
                <colgroup><col style="width:8%"><col style="width:7%"><col style="width:4%"><col style="width:14%"><col style="width:8%"><col style="width:8%"><col style="width:10%"><col style="width:6%"><col style="width:22%"><col style="width:13%"></colgroup>
                <thead><tr><th>CÓDIGO</th><th>FECHA</th><th>EDAD</th><th>NOMBRES Y APELLIDOS</th><th>FECHA DE NACIMIENTO</th><th>MUNICIPIO</th><th>PUESTO DE SALUD</th><th>N.º DE ESTADO</th><th>RESULTADOS</th><th>OBSERVACIONES</th></tr></thead>
                <tbody>
                @forelse($records as $record)
                    @php
                        $birth = $record->patient?->birth_date;
                        $age = $birth && $birth->lte($record->request_date) ? (int) $birth->diffInYears($record->request_date) : null;
                        $resultLines = $record->samples->sortBy('sample_number')->map(function ($sample) use ($record) {
                            $result = $sample->pathologyResult;
                            $text = $result ? ($result->diagnoses->pluck('code')->filter()->implode(' / ') ?: 'SIN CÓDIGO') : null;
                            $text = $text ?: 'PENDIENTE';
                            if ($result && !in_array($result->status, ['validated', 'delivered'], true)) $text .= ' (SIN VALIDAR)';
                            $reader = $result?->reader ?? $record->reader;
                            return [
                                'text' => $text,
                                'color' => \App\Models\PathologyResult::READERS[$reader] ?? '#152f21',
                                'reader' => $reader,
                                'positive' => (bool) $result?->is_positive,
                            ];
                        });
                        $observations = collect([$record->notes])->filter()->merge(
                            $record->samples->sortBy('sample_number')->map(function ($sample) use ($record) {
                                $note = trim((string) $sample->pathologyResult?->notes);
                                return $note === '' ? null : $note;
                            })->filter()
                        )->implode("\n");
                    @endphp
                    <tr>
                        <td>{{ $record->request_number }}</td>
                        <td class="center">{{ $record->request_date?->format('d/m/Y') }}</td>
                        <td class="center">{{ $age ?? '—' }}</td>
                        <td>{{ $record->patient?->full_name ?? '—' }}</td>
                        <td class="center">{{ $birth?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ ($module === 'G' ? $record->residenceMunicipality?->name : null) ?? $record->municipality?->name ?? '—' }}</td>
                        <td>{{ $record->healthFacility?->name ?? ($module === 'G' ? 'HOSPITAL DANIEL BRACAMONTE' : '—') }}</td>
                        <td></td>
                        <td>
                            @if($record->status === 'cancelled')
                                SOLICITUD ANULADA
                            @else
                                @forelse($resultLines as $line)
                                    <div @class(['result', 'sample-result', 'positive-codes' => $line['positive']]) style="color:{{ $line['color'] }}">{{ $line['text'] }}</div>
                                @empty
                                    PENDIENTE
                                @endforelse
                            @endif
                        </td>
                        <td class="result">{{ $observations }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="empty">No hay solicitudes para esta fecha y área.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="total">Total de solicitudes: {{ $printing ? $records->count() : $records->total() }}</p>
        @if($printing)<x-report-signatures :signers="['ELABORADO POR', 'RESPONSABLE DEL SERVICIO']" />@endif
        @unless($printing)
            <div class="toolbar">{{ $records->links('pagination.compact') }}</div>
        @endunless
    </main>
</body>
</html>

