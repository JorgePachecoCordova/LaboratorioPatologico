document.addEventListener('DOMContentLoaded', () => {
    const $ = window.jQuery;
    if (!$?.fn.select2) return;

    const normalize = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().trim();
    const compactCode = value => normalize(value).replace(/[\s.\-]/g, '');
    const details = item => ({
        code: item.element?.dataset.code || '',
        description: item.element?.dataset.description || item.text || '',
    });
    const renderDiagnosis = item => {
        if (!item.id) return item.text;
        const { code, description } = details(item);
        const row = document.createElement('span');
        row.className = 'diagnosis-choice';
        if (code) {
            const badge = document.createElement('span');
            badge.className = 'diagnosis-code';
            badge.textContent = code;
            row.append(badge);
        }
        const label = document.createElement('span');
        label.className = 'diagnosis-description';
        label.textContent = description;
        row.append(label);
        return $(row);
    };

    document.querySelectorAll('[data-diagnosis-table]').forEach(table => {
        const body = table.querySelector('.diagnosis-body');
        const options = () => table.querySelector('.diagnosis-options').innerHTML;
        const renumber = () => body.querySelectorAll('.diagnosis-line').forEach((line, index) => {
            line.querySelector('.line-number').textContent = index + 1;
            line.querySelector('select').name = `diagnosis_entries[${index}][pathology_type_id]`;
        });
        const bind = line => {
            const select = line.querySelector('select');
            select.setAttribute('aria-label', 'Buscar diagnóstico por código o descripción');
            $(select).select2({
                width: '100%',
                placeholder: 'Buscar diagnóstico por código o descripción',
                allowClear: true,
                dropdownCssClass: 'diagnosis-dropdown',
                templateResult: renderDiagnosis,
                templateSelection: renderDiagnosis,
                matcher: (params, data) => {
                    const term = normalize(params.term);
                    if (!term) return data;
                    if (!data.id) return null;
                    const { code, description } = details(data);
                    const codeQuery = compactCode(term);
                    const codeMatches = codeQuery !== '' && compactCode(code).includes(codeQuery);
                    const textMatches = term.split(/\s+/).every(word => normalize(`${code} ${description}`).includes(word));
                    if (!codeMatches && !textMatches) return null;
                    return { ...data, matchRank: compactCode(code) === codeQuery ? 0 : (codeMatches ? 1 : 2) };
                },
                sorter: items => items.sort((a, b) => (a.matchRank ?? 3) - (b.matchRank ?? 3)),
                language: {
                    noResults: () => 'Sin coincidencias. Revise el código o pruebe otra descripción.', searching: () => 'Buscando…',
                    errorLoading: () => 'No se pudieron cargar los resultados',
                    loadingMore: () => 'Cargando más resultados…',
                    inputTooShort: () => 'Escriba más caracteres para buscar',
                    inputTooLong: () => 'Elimine algunos caracteres',
                    maximumSelected: () => 'Alcanzó el máximo de opciones',
                    removeAllItems: () => 'Eliminar selección',
                },
            });
            select.addEventListener('invalid', event => {
                event.preventDefault();
                $(select).select2('open');
            });
            line.querySelector('.remove-line').addEventListener('click', () => {
                if (body.children.length === 1) $(select).val('').trigger('change');
                else {
                    $(select).select2('destroy');
                    line.remove();
                }
                renumber();
            });
        };
        body.querySelectorAll('.diagnosis-line').forEach(bind);
        table.querySelector('.add-line').addEventListener('click', () => {
            const line = document.createElement('div');
            line.className = 'diagnosis-line';
            line.innerHTML = `<span class="line-number"></span><select required>${options()}</select><button type="button" class="remove-line" aria-label="Eliminar fila">×</button>`;
            body.appendChild(line);
            bind(line);
            renumber();
            $(line.querySelector('select')).select2('open');
        });
    });
    $(document).on('select2:open', () => {
        const field = document.querySelector('.diagnosis-dropdown .select2-search__field');
        if (!field) return;
        field.setAttribute('autocapitalize', 'characters');
        field.setAttribute('placeholder', 'Buscar por código o descripción…');
        field.setAttribute('aria-label', 'Buscar diagnóstico por código o descripción');
        field.focus();
    });
    document.addEventListener('input', event => {
        const field = event.target;
        if (!field.matches('.diagnosis-dropdown .select2-search__field') || event.isComposing) return;
        const start = field.selectionStart;
        const end = field.selectionEnd;
        field.value = field.value.toLocaleUpperCase('es');
        field.setSelectionRange(start, end);
    }, true);
});
