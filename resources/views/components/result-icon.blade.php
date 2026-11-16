@props(['name'])
<svg {{ $attributes->merge(['class' => 'result-icon']) }} viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
    @switch($name)
        @case('excel')
            <path d="M9 3h12v18H9z" fill="#21a366"/>
            <path d="M14 3h7v18h-7z" fill="#107c41"/>
            <path d="M12 8h7M12 12h7M12 16h7M15 6v12" stroke="#fff" stroke-opacity=".65"/>
            <rect x="1" y="6" width="12" height="13" rx="1.5" fill="#185c37"/>
            <path d="m4.5 9 5 7m0-7-5 7" stroke="#fff" stroke-width="1.8"/>
            @break
        @case('trash')
            <g stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/></g>
            @break
        @case('request')
            <g stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="17" rx="2"/><rect x="9" y="2" width="6" height="4" rx="1" fill="currentColor"/><path d="M9 11h6M9 15h6M9 18h3"/></g>
            @break
        @case('report')
            <g stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H5v18h14V8zM14 3v5h5M8 12h8M8 16h6"/></g>
            @break
        @case('add')
            <g stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M7 12h10M12 7v10"/></g>
            @break
        @default
            <g stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 4 5 5M4 20l5-1L21 7a2.1 2.1 0 0 0-5-5L4 14zM4 14l5 5"/></g>
    @endswitch
</svg>
