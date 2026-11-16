import 'materialize-css/dist/js/materialize.min.js';
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.min.css';
import 'datatables.net-responsive-dt';
import 'datatables.net-responsive-dt/css/responsive.dataTables.min.css';


document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('app-sidebar');
    const mobileMenu = document.getElementById('topbar-mobile-menu');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const themeToggle = document.getElementById('theme-toggle');
    const commandPalette = document.getElementById('command-palette');
    const commandTrigger = document.getElementById('global-search-trigger');
    const commandInput = document.getElementById('command-search-input');
    const commandEmpty = document.getElementById('command-empty');
    const navigationProgress = document.getElementById('navigation-progress');
    // El navegador puede bloquear localStorage; esto no debe impedir inicializar los módulos.
    const readPreference = (key) => {
        try { return localStorage.getItem(key); } catch { return null; }
    };
    const savePreference = (key, value) => {
        try { localStorage.setItem(key, value); } catch { /* Preferencia solo durante esta página. */ }
    };

    const showNavigationProgress = () => navigationProgress?.classList.add('active');
    const hideNavigationProgress = () => navigationProgress?.classList.remove('active');
    window.addEventListener('pageshow', hideNavigationProgress);
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const destination = new URL(link.href, window.location.href);
        if (destination.origin === window.location.origin &&
            (destination.pathname !== window.location.pathname || destination.search !== window.location.search)) showNavigationProgress();
    });
    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || event.target.target === '_blank') return;
        showNavigationProgress();
    });

    const themeStorageKey = 'patologia-theme';
    const applyTheme = (theme) => {
        document.documentElement.dataset.theme = theme;
        if (themeToggle) {
            const dark = theme === 'dark';
            themeToggle.setAttribute('aria-label', dark ? 'Activar modo claro' : 'Activar modo oscuro');
            themeToggle.title = dark ? 'Activar modo claro' : 'Activar modo oscuro';
        }
    };

    applyTheme(readPreference(themeStorageKey) === 'dark' ? 'dark' : 'light');
    themeToggle?.addEventListener('click', () => {
        const theme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        applyTheme(theme);
        savePreference(themeStorageKey, theme);
    });

    const setMobileMenu = (open) => {
        document.documentElement.classList.toggle('sidebar-mobile-open', open);
        mobileMenu?.setAttribute('aria-expanded', String(open));
        mobileMenu?.setAttribute('aria-label', open ? 'Cerrar menú lateral' : 'Mostrar menú lateral');
        if (mobileMenu) mobileMenu.title = open ? 'Cerrar menú lateral' : 'Mostrar menú lateral';
    };

    const sidebarStorageKey = 'patologia-sidebar-collapsed';
    const setCollapsed = (collapsed) => {
        document.documentElement.classList.toggle('sidebar-collapsed', collapsed);
        if (window.innerWidth > 1024) {
            mobileMenu?.setAttribute('aria-expanded', String(!collapsed));
            mobileMenu?.setAttribute('aria-label', collapsed ? 'Mostrar menú lateral' : 'Ocultar menú lateral');
            if (mobileMenu) mobileMenu.title = collapsed ? 'Mostrar menú lateral' : 'Ocultar menú lateral';
        }
    };

    const toggleDesktopSidebar = () => {
        const collapsed = !document.documentElement.classList.contains('sidebar-collapsed');
        setCollapsed(collapsed);
        savePreference(sidebarStorageKey, String(collapsed));
    };

    setCollapsed(readPreference(sidebarStorageKey) === 'true');
    mobileMenu?.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
            setMobileMenu(!document.documentElement.classList.contains('sidebar-mobile-open'));
            return;
        }

        toggleDesktopSidebar();
    });
    sidebar?.addEventListener('click', (event) => {
        if (window.innerWidth <= 1024) return;
        if (event.target.closest('a, button, input, select, textarea')) return;
        toggleDesktopSidebar();
    });
    sidebarOverlay?.addEventListener('click', () => setMobileMenu(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMobileMenu(false);
    });
    sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMobileMenu(false)));
    const syncSidebarViewport = () => {
        setMobileMenu(false);
        setCollapsed(document.documentElement.classList.contains('sidebar-collapsed'));
    };
    window.matchMedia('(max-width: 1024px)').addEventListener('change', syncSidebarViewport);
    syncSidebarViewport();

    let activeCommandIndex = 0;
    const visibleCommands = () => [...(commandPalette?.querySelectorAll('[data-command-item]') ?? [])]
        .filter((item) => !item.hidden);
    const markActiveCommand = (index = 0) => {
        const items = visibleCommands();
        activeCommandIndex = items.length ? (index + items.length) % items.length : 0;
        commandPalette?.querySelectorAll('[data-command-item]').forEach((item) => item.classList.remove('active'));
        items[activeCommandIndex]?.classList.add('active');
        items[activeCommandIndex]?.scrollIntoView({ block: 'nearest' });
    };
    const openCommandPalette = () => {
        if (!commandPalette) return;
        commandPalette.hidden = false;
        document.documentElement.classList.add('command-palette-open');
        commandInput.value = '';
        commandPalette.querySelectorAll('[data-command-item]').forEach((item) => { item.hidden = false; });
        if (commandEmpty) commandEmpty.hidden = true;
        markActiveCommand(0);
        requestAnimationFrame(() => commandInput.focus());
    };
    const closeCommandPalette = () => {
        if (!commandPalette) return;
        commandPalette.hidden = true;
        document.documentElement.classList.remove('command-palette-open');
        commandTrigger?.focus();
    };
    commandTrigger?.addEventListener('click', openCommandPalette);
    commandPalette?.querySelectorAll('[data-command-close]').forEach((button) => button.addEventListener('click', closeCommandPalette));
    commandInput?.addEventListener('input', () => {
        const query = commandInput.value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim().toLocaleLowerCase('es');
        commandPalette.querySelectorAll('[data-command-item]').forEach((item) => {
            item.hidden = !item.dataset.search.includes(query);
        });
        const items = visibleCommands();
        if (commandEmpty) commandEmpty.hidden = items.length > 0;
        markActiveCommand(0);
    });
    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            commandPalette?.hidden ? openCommandPalette() : closeCommandPalette();
            return;
        }
        if (commandPalette?.hidden) return;
        if (event.key === 'Escape') closeCommandPalette();
        if (event.key === 'ArrowDown') { event.preventDefault(); markActiveCommand(activeCommandIndex + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); markActiveCommand(activeCommandIndex - 1); }
        if (event.key === 'Enter' && document.activeElement === commandInput) {
            event.preventDefault();
            visibleCommands()[activeCommandIndex]?.click();
        }
    });

    document.querySelectorAll('input[data-uppercase-search]').forEach((input) => {
        input.addEventListener('input', () => {
            const cursor = input.selectionStart;
            input.value = input.value.toLocaleUpperCase('es');
            input.setSelectionRange?.(cursor, cursor);
        });
    });

    document.querySelectorAll('table[data-datatable]').forEach((table) => {
        if (table.dataset.datatableReady) return;

        const professional = table.hasAttribute('data-datatable-professional');
        const emptyMessage = table.dataset.emptyMessage || 'No existen registros disponibles';

        new DataTable(table, {
            responsive: true,
            autoWidth: false,
            paging: false,
            info: professional,
            searching: false,
            order: [],
            columnDefs: professional ? [{ targets: -1, orderable: false }] : [],
            language: {
                emptyTable: emptyMessage,
                zeroRecords: 'No se encontraron coincidencias',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros en esta página',
                infoEmpty: 'No hay registros para mostrar',
                loadingRecords: 'Cargando registros...',
                processing: 'Procesando...',
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                paginate: {first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior'},
                aria: {orderable: 'Activar para ordenar', orderableReverse: 'Activar para invertir el orden', orderableRemove: 'Activar para quitar el orden'},
            },
        });
        table.classList.toggle('datatable-professional', professional);
        table.dataset.datatableReady = 'true';
    });
});
