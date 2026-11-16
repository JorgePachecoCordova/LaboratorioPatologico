<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Sistema Patología')</title>

    <script>
        try {
            document.documentElement.dataset.theme = localStorage.getItem('patologia-theme') === 'dark' ? 'dark' : 'light';
            if (localStorage.getItem('patologia-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch (error) {
            // La navegación sigue funcionando aunque el almacenamiento esté bloqueado.
        }
    </script>
    <style>
        html[data-theme="dark"], html[data-theme="dark"] body { background:#121a1e; color-scheme:dark; }
    </style>

    @vite(['resources/js/app.js'])

    <link
        rel="stylesheet"
        href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}"
    >
</head>

<body>
<a class="skip-to-content" href="#main-content">Ir al contenido principal</a>

<div class="navigation-progress" id="navigation-progress" aria-hidden="true"></div>

<div class="app-container">

    @include('layouts.sidebar')

    <div class="dashboard-main">

        @php
            $section = match (true) {
                request()->routeIs('inicio') => 'Inicio',
                request()->routeIs('solicitudes.*') => 'Solicitudes / Muestras',
                request()->routeIs('resultados.*') => 'Registro de Resultados',
                request()->routeIs('citologia.*') => 'Informes citológicos',
                request()->routeIs('anatomopatologia.*') => 'Anatomopatológico',
                request()->routeIs('historial.*') => 'Historial',
                request()->routeIs('configuracion.*') => 'Configuración',
                request()->routeIs('perfil.*') => 'Mi perfil',
                default => 'Panel',
            };
        @endphp

        <header class="global-topbar">
            <div class="topbar-context">
                <button class="topbar-mobile-menu" id="topbar-mobile-menu" type="button" aria-label="Ocultar menú lateral" aria-controls="app-sidebar" aria-expanded="true" title="Ocultar menú lateral">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="topbar-breadcrumb" aria-label="Ruta de navegación">
                    <a href="{{ route('inicio') }}">Patología</a>
                    <span aria-hidden="true">/</span>
                    <strong>{{ $section }}</strong>
                </div>
            </div>

            <div class="topbar-actions">
                <button class="topbar-icon-button" id="theme-toggle" type="button" aria-label="Activar modo oscuro" title="Cambiar tema">
                    <svg class="theme-icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.4 15.5A8.5 8.5 0 0 1 8.5 3.6 8.5 8.5 0 1 0 20.4 15.5Z"/></svg>
                    <svg class="theme-icon-sun" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4V2m0 20v-2M4 12H2m20 0h-2m-2.34-5.66 1.42-1.42M4.92 19.08l1.42-1.42m0-11.32L4.92 4.92m14.16 14.16-1.42-1.42M16 12a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
                </button>
                <a class="global-topbar-user" href="{{ route('perfil.edit') }}" title="Editar mi perfil" aria-label="Editar mi perfil" style="text-decoration:none;color:inherit">
                    <span class="topbar-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->username ?? auth()->user()->name ?? 'U', 0, 1)) }}</span>
                    <span class="topbar-user-copy">
                        <strong>{{ auth()->user()->username ?? auth()->user()->name ?? 'Usuario' }}</strong>
                        <small>{{ \App\Support\AccessRoles::label(auth()->user()) }}</small>
                    </span>
                </a>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="global-logout waves-effect waves-light" type="submit" aria-label="Cerrar sesión" title="Cerrar sesión">
                    <span aria-hidden="true">↪</span> Cerrar sesión
                </button>
            </form>
        </header>

        <main class="page-scroll" id="main-content" tabindex="-1">
            @yield('content')
        </main>

        <footer class="site-footer" role="contentinfo">
            <div class="site-footer-institution">
                <svg class="site-footer-hospital" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 21V7h14v14M9 7V3h6v4M3 21h18M10 21v-4h4v4M9 10h1m4 0h1m-6 3h1m4 0h1M12 4v2m-1-1h2"/></svg>
                <span>© {{ now()->year }} Hospital Daniel Bracamonte — Todos los derechos reservados</span>
            </div>
            <div class="site-footer-meta"><span>Unidad de Informática y Sistemas</span><span>Teléfono Interno <strong>151</strong></span></div>
        </footer>

    </div>

</div>

<button class="sidebar-overlay" id="sidebar-overlay" type="button" aria-label="Cerrar menú"></button>

<div class="command-palette" id="command-palette" hidden>
    <button class="command-palette-backdrop" type="button" data-command-close aria-label="Cerrar búsqueda rápida"></button>
    <section class="command-palette-dialog" role="dialog" aria-modal="true" aria-labelledby="command-palette-title">
        <header><div><strong id="command-palette-title">Búsqueda rápida</strong><span>Vaya directamente a una sección del sistema</span></div><button type="button" data-command-close aria-label="Cerrar">×</button></header>
        <div class="command-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-4-4"></path></svg><input id="command-search-input" type="search" autocomplete="off" placeholder="BUSCAR MÓDULO O ACCIÓN"><kbd>ESC</kbd></div>
        <nav class="command-results" aria-label="Accesos rápidos">
            @php
                $paletteAdmin = \App\Support\AccessRoles::admin(auth()->user());
                $paletteLaboratory = \App\Support\AccessRoles::readsResults(auth()->user());
                $paletteCytology = \App\Support\AccessRoles::allows(auth()->user(), 'citologia.index');
                $paletteAnatomy = $paletteAdmin || auth()->user()?->role === 'anatomopathology_office';
            @endphp
            <a href="{{ route('inicio') }}" data-command-item data-search="inicio panel resumen dashboard"><span>⌂</span><div><strong>Inicio</strong><small>Resumen y actividad del laboratorio</small></div><b>→</b></a>
@if($paletteAdmin)            <a href="{{ route('historial.index') }}" data-command-item data-search="historial paciente buscar"><span>⌕</span><div><strong>Historial de pacientes</strong><small>Consultar antecedentes y solicitudes</small></div><b>→</b></a>@endif
            @if($paletteLaboratory)
@if(\App\Support\AccessRoles::allows(auth()->user(), 'solicitudes.index'))            <a href="{{ route('solicitudes.index') }}" data-command-item data-search="solicitudes muestras nueva registrar"><span>▦</span><div><strong>Solicitudes / Muestras</strong><small>Buscar o registrar una solicitud</small></div><b>→</b></a>@endif
            <a href="{{ route('resultados.index') }}" data-command-item data-search="resultados informes validar"><span>✓</span><div><strong>Registro de Resultados</strong><small>Registrar, validar e imprimir resultados</small></div><b>→</b></a>
            @endif
            @if($paletteCytology)
            <a href="{{ route('citologia.index') }}" data-command-item data-search="citologia citologico informes"><span>◉</span><div><strong>Informes citológicos</strong><small>Solicitudes e informes de citología</small></div><b>→</b></a>
            @endif
            @if($paletteAnatomy)
            <a href="{{ route('anatomopatologia.index') }}" data-command-item data-search="anatomopatologico anatomia informes"><span>⌁</span><div><strong>Anatomopatológico</strong><small>Registro de informes anatomopatológicos</small></div><b>→</b></a>
            @endif
            @if(auth()->user()?->role === 'administrator' || auth()->user()?->hasRole('administrator'))
                <a href="{{ route('configuracion.index') }}" data-command-item data-search="configuracion ajustes usuarios catalogos"><span>⚙</span><div><strong>Configuración</strong><small>Usuarios y catálogos del sistema</small></div><b>→</b></a>
            @endif
            <p class="command-empty" id="command-empty" hidden>No se encontraron opciones.</p>
        </nav>
        <footer><span>↑ ↓ para recorrer</span><span>Enter para abrir</span></footer>
    </section>
</div>

<link rel="stylesheet" href="{{ asset('css/responsive.css') }}?v={{ filemtime(public_path('css/responsive.css')) }}">
<link rel="stylesheet" href="{{ asset('css/ui-icons.css') }}?v={{ filemtime(public_path('css/ui-icons.css')) }}">
</body>
</html>

