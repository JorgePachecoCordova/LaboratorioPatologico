<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#edf2ef;color:#182b22;font-family:Arial,sans-serif}
        .toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 24px;background:#fff;border-bottom:1px solid #cbd8d0}
        .toolbar p{margin:0;font-size:14px}.toolbar button{padding:12px 18px;border:0;border-radius:6px;background:#147747;color:#fff;font-weight:bold;cursor:pointer}
        main{margin:24px;padding:24px;background:#fff}h1{font-size:18px;text-align:center;line-height:1.5;margin:0 0 20px}
        .table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:11px}th,td{border:1px solid #809389;padding:7px 5px;overflow-wrap:anywhere}th{background:#e2eee7;text-align:center}td{vertical-align:top}thead{display:table-header-group}tr{break-inside:avoid}.empty{text-align:center;padding:24px}
        @page{size:A4 landscape;margin:12mm}
        @media print{body{background:#fff;color:#000}.toolbar{display:none}main{margin:0;padding:0}.table-wrap{overflow:visible}table{font-size:9px}th,td{padding:5px 3px}h1{font-size:14px}th{print-color-adjust:exact}}
        .institution{font-size:11px;line-height:1.5;margin-bottom:12px}.report-signatures{display:flex;justify-content:space-between;align-items:flex-end;gap:32px;margin-top:auto;padding-top:24mm;font-size:10px;break-inside:avoid;page-break-inside:avoid;flex-shrink:0}.signature-block{width:42%;max-width:90mm;text-align:center;line-height:1.5}.signature-line{width:75%;height:14mm;border-bottom:1px solid #405348;margin:0 auto 7px}.signature-label{display:block;min-height:30px}.age-section{margin:18px 0;break-inside:avoid}.age-section h2{font-size:12px;margin:0;padding:7px;border:1px solid #809389}.total-row{font-weight:bold;background:#e2eee7}
        @if(in_array($type ?? '', ['rural','age'], true))
        @page{size:A4 portrait;margin:12mm}
        @media print{table{font-size:8px}th,td{padding:3px 2px}h1{font-size:12px}}
        @endif
    </style>
</head>
<body>
    <div class="toolbar">
        <p>Para guardar el reporte en PDF, seleccione «Guardar como PDF» en el destino de impresión.</p>
        <button type="button" onclick="window.print()"><x-ui-icon name="print" /> Imprimir / Guardar PDF</button>
    </div>
    <main>
        <div class="institution" style="display:flex;align-items:center;gap:18px"><img src="{{ asset('img/logo-hospital.png') }}" alt="Hospital Daniel Bracamonte" style="width:85px;height:65px;object-fit:contain"><div>HOSPITAL DANIEL BRACAMONTE<br>LABORATORIO CITOPATOLÓGICO</div></div>
        <h1>{{ $title }}</h1>
        @if(($type ?? '') === 'age')
            @foreach(collect($rows)->groupBy(0) as $area => $areaRows)
            <section class="age-section">
                <h2>PREVENCIÓN DE CÁNCER DE CUELLO UTERINO · {{ $area }}</h2>
                <div class="table-wrap"><table><thead><tr>@foreach(array_slice($headers, 1) as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
                <tbody>@foreach($areaRows as $row)<tr>@foreach(array_slice($row, 1) as $value)<td>{{ $value }}</td>@endforeach</tr>@endforeach</tbody></table></div>
            </section>
            @endforeach
        @else
        <div class="table-wrap">
            <table>
                <thead><tr>@foreach($headers as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr @if($row[0] === 'TOTAL') class="total-row" @endif>
                        @foreach($row as $column => $value)
                            @if(($type ?? '') === 'rural' && $column === 0 && $value !== 'TOTAL')
                                @if($loop->parent->first || $rows[$loop->parent->index - 1][0] !== $value)
                                    @php($span = collect(array_slice($rows, $loop->parent->index))->takeWhile(fn ($item) => $item[0] === $value)->count())
                                    <td rowspan="{{ $span }}">{{ $value }}</td>
                                @endif
                            @else<td>{{ $value }}</td>@endif
                        @endforeach</tr>
                    @empty
                        <tr><td class="empty" colspan="{{ count($headers) }}">No hay registros para el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @endif
        <x-report-signatures :signers="['ELABORADO POR', 'V.º B.º JEFE DE SERVICIO DE ANATOMÍA PATOLÓGICA']" />
    </main>
</body>
</html>
