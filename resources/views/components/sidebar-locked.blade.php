@props(['label'])
<span class="sidebar-btn sidebar-module-locked" role="link" aria-disabled="true" tabindex="0" title="{{ $label }}: sin acceso para su rol. Solicite autorización al administrador.">
    <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm1 5V3.5L18.5 7zM8 12h8v2H8zm0 4h8v2H8z"/></svg></span>
    <span class="sidebar-label">{{ $label }}</span>
    <svg class="sidebar-access-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg>
</span>
