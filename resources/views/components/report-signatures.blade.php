@props(['signers' => [], 'date' => null])
@once
<style>
/* Physical spacing is shared by all printed reports, independent of screen zoom. */
.print-signatures{margin-top:6mm;break-inside:avoid;page-break-inside:avoid;color:inherit;font:10pt/1.4 Arial,sans-serif}
.print-signatures-list{display:flex;justify-content:space-between;align-items:flex-start;gap:16mm}
.print-signature-block{flex:1;max-width:78mm;min-width:0;text-align:center;break-inside:avoid;page-break-inside:avoid}
.print-signatures-list.single{display:flex;justify-content:center}
.print-signature-space{height:22mm;border-bottom:1px solid #53665b}
.print-signature-role{padding-top:3mm;font-size:9pt;font-weight:700;line-height:1.4;overflow-wrap:anywhere}
.print-signature-hint{margin-top:1mm;font-size:8pt;font-weight:400}
.print-signatures.with-date{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,78mm);gap:12mm;align-items:end}
.print-signature-date{padding-bottom:5mm;font-size:10pt;line-height:1.5}
.print-signatures.with-date .print-signatures-list{justify-content:flex-end}
@media print{.print-signatures{break-inside:avoid-page;page-break-inside:avoid}}
@media screen and (max-width:600px){.print-signatures-list{gap:8mm}.print-signatures.with-date{grid-template-columns:minmax(0,1fr);gap:0}.print-signature-date{padding-bottom:0}}
</style>
@endonce
<footer class="print-signatures {{ $date ? 'with-date' : '' }}" aria-label="Firmas y sellos del reporte">
    @if($date)<div class="print-signature-date">{{ $date }}</div>@endif
    <div class="print-signatures-list {{ count($signers) === 1 ? 'single' : '' }}">
        @foreach($signers as $signer)
            <div class="print-signature-block">
                <div class="print-signature-space" aria-hidden="true"></div>
                <div class="print-signature-role">{{ $signer }}</div>
                <div class="print-signature-hint">Firma y sello</div>
            </div>
        @endforeach
    </div>
</footer>
