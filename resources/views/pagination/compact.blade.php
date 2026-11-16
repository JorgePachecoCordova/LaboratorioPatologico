@if($paginator->hasPages())
    <nav class="compact-pagination" aria-label="Paginación">
        @if($paginator->onFirstPage())
            <span aria-disabled="true">Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif
        <span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }} · {{ $paginator->total() }} registros</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>
        @else
            <span aria-disabled="true">Siguiente</span>
        @endif
    </nav>
@endif
@once
    <style>
        .compact-pagination{display:flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:10px;padding:12px 0;font-size:12px}
        .compact-pagination a{display:inline-block;padding:7px 12px;border:1px solid #16814d;border-radius:6px;color:#14764a;text-decoration:none}
        .compact-pagination a:hover{background:#edf8f2}
        .compact-pagination [aria-disabled]{opacity:.55}
        html[data-theme="dark"] .compact-pagination{color:#d8e7df}
        html[data-theme="dark"] .compact-pagination a{color:#80dfaa;border-color:#54c98b}
        html[data-theme="dark"] .compact-pagination a:hover{background:#263a32}
    </style>
@endonce
