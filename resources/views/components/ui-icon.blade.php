@props(['name'])
<svg class="ui-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
@if($name === 'phone')
<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7l.5 3a2 2 0 0 1-.6 1.7L7.7 9.7a16 16 0 0 0 6.6 6.6l1.3-1.3a2 2 0 0 1 1.7-.6l3 .5a2 2 0 0 1 1.7 2Z"/>
@elseif($name === 'mobile')
<rect x="6" y="2" width="12" height="20" rx="2"/><path d="M10 5h4M11 19h2"/>
@elseif($name === 'mail')
<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 6 9 7 9-7"/>
@elseif($name === 'location')
<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>
@elseif($name === 'user')
<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>
@elseif($name === 'lock')
<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V6a4 4 0 0 1 8 0v4M12 14v3"/>
@elseif($name === 'save')
<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2ZM7 3v6h10V3M7 21v-8h10v8"/>
@elseif($name === 'edit')
<path d="m16 3 5 5-12 12-6 1 1-6ZM14 5l5 5"/>
@elseif($name === 'search')
<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>
@elseif($name === 'print')
<path d="M6 9V3h12v6M6 18H3V9h18v9h-3M6 14h12v7H6ZM17 11h.01"/>
@elseif($name === 'plus')
<path d="M12 4v16M4 12h16"/>
@elseif($name === 'back')
<path d="m12 5-7 7 7 7M5 12h15"/>
@elseif($name === 'close')
<path d="m6 6 12 12M6 18 18 6"/>
@elseif($name === 'trash')
<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>
@elseif($name === 'refresh')
<path d="M20 7v5h-5M4 17v-5h5M5 8a8 8 0 0 1 13-3l2 3M4 16l2 3a8 8 0 0 0 13-3"/>
@elseif($name === 'file')
<path d="M14 2H5v20h14V7ZM14 2v6h5M8 12h8M8 16h8"/>
@elseif($name === 'hospital')
<path d="M4 22V5h16v17M9 22v-5h6v5M9 8h6M12 5v6"/>
@elseif($name === 'calendar')
<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18M7 15h2M13 15h2"/>
@elseif($name === 'sample')
<path d="M9 3h6M10 3v7l-5 8a2 2 0 0 0 1.7 3h10.6a2 2 0 0 0 1.7-3l-5-8V3M8 15h8"/>
@endif
</svg>
