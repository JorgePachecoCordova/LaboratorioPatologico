@if($report->cap_protocol_enabled)
<section class="ap-cap" aria-label="Protocolo CAP guardado">
    <h2>Protocolo CAP</h2>
    <div class="ap-grid clinical">
    @foreach(\App\Support\AnatomopathologyCapProtocol::FIELDS as $key => $label)
        @if(filled($report->cap_protocol[$key] ?? null))
        <label>{{ $label }}<textarea readonly>{{ $report->cap_protocol[$key] }}</textarea></label>
        @endif
    @endforeach
    @foreach($report->cap_protocol['custom'] ?? [] as $item)
        <label>{{ $item['title'] }}<textarea readonly>{{ $item['value'] }}</textarea></label>
    @endforeach
    </div>
</section>
@endif
