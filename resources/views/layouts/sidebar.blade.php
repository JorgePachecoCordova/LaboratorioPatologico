<aside class="sidebar" id="app-sidebar">
    @php
        $sidebarUser = auth()->user();
        $isAdministrator = \App\Support\AccessRoles::admin($sidebarUser);
        $showLaboratory = \App\Support\AccessRoles::allows($sidebarUser, 'solicitudes.index');
        $showResults = \App\Support\AccessRoles::readsResults($sidebarUser);
        $showCytology = \App\Support\AccessRoles::allows($sidebarUser, 'citologia.index');
        $showAnatomopathology = $isAdministrator || $sidebarUser?->role === 'anatomopathology_office';
    @endphp
    <div class="sidebar-logo">
        <img src="{{ asset('img/logo-hospital-sidebar.png') }}" alt="Hospital Daniel Bracamonte">
    </div>

    <div class="sidebar-title">
        <span>Laboratorio de</span>
        <strong>PATOLOGÍA</strong>
    </div>

    <nav class="sidebar-menu" aria-label="Navegación principal">
        <a href="{{ route('inicio') }}" class="sidebar-btn waves-effect {{ request()->routeIs('inicio') ? 'active' : '' }}" @if(request()->routeIs('inicio')) aria-current="page" @endif>
            <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg></span>
            <span class="sidebar-label">Inicio</span>
        </a>

        @if($showLaboratory)
        <a href="{{ route('solicitudes.index') }}" class="sidebar-btn waves-effect {{ request()->routeIs('solicitudes.*') ? 'active' : '' }}" @if(request()->routeIs('solicitudes.*')) aria-current="page" @endif>
            <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2v2h1v5.59l-5.7 8.49A2.5 2.5 0 0 0 4.38 22h15.24a2.5 2.5 0 0 0 2.08-3.92L16 9.59V4h1V2zm3 8.2V4h4v6.2l2.55 3.8h-9.1z"/></svg></span>
            <span class="sidebar-label">Solicitudes / Muestras</span>
        </a>
        @else
        <x-sidebar-locked label="Solicitudes / Muestras" />
        @endif



        @if($showResults)
        <a href="{{ route('resultados.index') }}" class="sidebar-btn waves-effect {{ request()->routeIs('resultados.*') ? 'active' : '' }}" @if(request()->routeIs('resultados.*')) aria-current="page" @endif>
            <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm1 5V3.5L18.5 7zM8 13h8v2H8zm0 4h8v2H8zm0-8h5v2H8z"/></svg></span>
            <span class="sidebar-label">{{ $sidebarUser?->role === 'anatomopathology_office' ? 'Reportes de Resultados' : 'Registro de Resultados' }}</span>
        </a>
        @else
        <x-sidebar-locked label="Registro de Resultados" />
        @endif

        @if($showCytology)
        <a href="{{ route('citologia.index') }}" class="sidebar-btn waves-effect {{ request()->routeIs('citologia.*') ? 'active' : '' }}" @if(request()->routeIs('citologia.*')) aria-current="page" @endif>
            <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.46 6.28 11.5 8.32l-5.67 5.67-2.04-2.04zM12.2 1.6l2.04 2.04-1.41 1.42 1.06 1.06a5 5 0 0 1 .83 5.95l1.46 1.46A7 7 0 0 0 12.47 2.3zM7 18a4 4 0 0 0 4-4H9a2 2 0 0 1-4 0H3a4 4 0 0 0 4 4m-5 2v2h18v-2z"/></svg></span>
            <span class="sidebar-label">Informes citológicos</span>
        </a>
        @else
        <x-sidebar-locked label="Informes citológicos" />
        @endif

        @if($showAnatomopathology)
        <a href="{{ route('anatomopatologia.index') }}" class="sidebar-btn waves-effect {{ request()->routeIs('anatomopatologia.*') ? 'active' : '' }}" @if(request()->routeIs('anatomopatologia.*')) aria-current="page" @endif>
            <span class="sidebar-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4.1 2.93 2.69 4.34l2.12 2.12a6 6 0 0 0 0 8.49l2.83 2.83a4 4 0 0 1 0 5.66l1.42 1.41a6 6 0 0 0 0-8.48L6.22 13.54a4 4 0 0 1 0-5.66zm10.84-3.78a6 6 0 0 0 0 8.48l2.84 2.83a4 4 0 0 1 0 5.66l2.12 2.12 1.41-1.41-2.12-2.12a6 6 0 0 0 0-8.49l-2.83-2.83a4 4 0 0 1 0-5.66zM8.5 9h7V7h-7zm0 8h7v-2h-7z"/></svg></span>
            <span class="sidebar-label">Anatomopatológico</span>
        </a>
        @else
        <x-sidebar-locked label="Anatomopatológico" />
        @endif

    </nav>

    @if(auth()->user()?->hasRole('administrator') || auth()->user()?->role === 'administrator')
        <a href="{{ route('configuracion.index') }}" class="sidebar-gear {{ request()->routeIs('configuracion.*') ? 'active' : '' }}" aria-label="Configuración" @if(request()->routeIs('configuracion.*')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.43 12.98q.04-.48 0-.96l2.11-1.65a.5.5 0 0 0 .12-.64l-2-3.46a.5.5 0 0 0-.61-.22l-2.49 1a7.5 7.5 0 0 0-.83-.48L15.35 3.9a.49.49 0 0 0-.49-.4h-4a.49.49 0 0 0-.49.4L10 6.55q-.45.19-.84.48l-2.49-1a.49.49 0 0 0-.61.22l-2 3.46a.5.5 0 0 0 .12.64L6.29 12q-.04.48 0 .96l-2.11 1.65a.5.5 0 0 0-.12.64l2 3.46a.5.5 0 0 0 .61.22l2.49-1q.4.29.84.48l.37 2.66a.49.49 0 0 0 .49.4h4a.49.49 0 0 0 .49-.4l.38-2.66q.44-.19.83-.48l2.49 1a.5.5 0 0 0 .61-.22l2-3.46a.5.5 0 0 0-.12-.64zM12.86 15.5a3 3 0 1 1 0-6 3 3 0 0 1 0 6"/></svg>
        </a>
    @endif
</aside>
