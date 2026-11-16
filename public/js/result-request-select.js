document.addEventListener('DOMContentLoaded', () => {
    const $ = window.jQuery;
    const select = document.getElementById('new-result-request');
    const panel = document.getElementById('new-result-panel');
    if (!select || !panel || !$?.fn.select2) return;

    $(select).select2({
        width: '100%',
        placeholder: 'Buscar solicitud, paciente o CI',
        allowClear: true,
        minimumResultsForSearch: 0,
        dropdownCssClass: 'result-request-dropdown',
        language: {
            noResults: () => 'Sin coincidencias en esta página. Utilice la búsqueda general para otras solicitudes.',
            searching: () => 'Buscando…',
        },
    });
    $(select).on('select2:open', () => {
        const search = document.querySelector('.result-request-dropdown .select2-search__field');
        if (search) {
            search.placeholder = 'Escriba un nombre, CI o correlativo';
            search.setAttribute('aria-label', 'Buscar solicitud por nombre, CI o correlativo');
            search.focus();
        }
    });
    document.getElementById('new-result-toggle')?.addEventListener('click', () => {
        if (panel.hidden) $(select).select2('close');
        else requestAnimationFrame(() => $(select).select2('open'));
    });
    select.addEventListener('invalid', () => {
        if (!panel.hidden) $(select).select2('open');
    });
    document.querySelector('.page-scroll')?.addEventListener('scroll', () => $(select).select2('close'), { passive: true });
});
