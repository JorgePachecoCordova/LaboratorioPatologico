document.addEventListener('DOMContentLoaded', () => {
    const $ = window.jQuery;
    if (!$?.fn.select2) return;
    document.querySelectorAll('.ap-page .select2-control, #ap-cap-element').forEach(select => {
        if (select.disabled) return;
        $(select).select2({
            width: '100%',
            placeholder: select.dataset.placeholder || 'Seleccione un título',
            minimumResultsForSearch: 0,
            dropdownCssClass: 'ap-select-dropdown',
            language: { noResults: () => 'No se encontraron resultados', searching: () => 'Buscando…' }
        });
        // Conserva los eventos nativos del protocolo y de creación de catálogos.
        $(select).on('select2:select select2:clear', () => {
            select.dispatchEvent(new Event('change', { bubbles: true }));
            $(select).trigger('change.select2');
        });
        select.addEventListener('change', () => $(select).trigger('change.select2'));
    });
    $(document).on('select2:open', () => {
        document.querySelector('.ap-select-dropdown .select2-search__field')?.focus();
    });
});
