<style>
/* Composición compacta, alineada y con un único desplazamiento de página. */
.page-scroll .ap-page { height: auto; min-height: 100%; width: 100%; }
.ap-page .ap-card { flex: none; overflow: visible; border-radius: 12px; }
.ap-page .ap-card > form { flex: none; overflow: visible; scrollbar-gutter: auto; }
.ap-page .ap-title { border-radius: 12px 12px 0 0; padding: 12px 18px; font-size: 13px; }
.ap-page .ap-title span { font-size: 11px; align-self: center; }
.ap-page .ap-grid { padding: 12px 18px; gap: 12px 16px; }
.ap-page .ap-grid label { position: relative; display: flex; flex-direction: column; line-height: 24px; font-size: 12px; }
.ap-page .ap-grid :is(input, select) { height: 36px; margin-top: 4px; }
.ap-page .ap-grid.identity { grid-template-columns: repeat(12, minmax(0, 1fr)); }
.ap-page .identity > label { grid-column: span 2; }
.ap-page .identity > label:has([name="report_date"]) { grid-column: span 3; }
.ap-page .identity > .patient { grid-column: span 7; }
.ap-page .identity > label:has([name="hospital_service_id"]) { grid-column: span 4; }
.ap-page .identity > .doctor { grid-column: span 6; }
.ap-page .clinical textarea { height: 64px; line-height: 1.45; resize: vertical; }
.ap-page .ap-results { grid-template-columns: 1.2fr 1.2fr 1fr; gap: 16px; padding: 14px 18px 18px; align-items: start; }
.ap-page .ap-results label { font-size: 13px; line-height: 24px; font-weight: 600 !important; }
.ap-page .ap-results textarea { height: 140px !important; min-height: 140px !important; margin-top: 4px !important; line-height: 1.5; padding: 10px 12px !important; }
.ap-page .ap-actions { position: static; justify-content: flex-end; box-shadow: none; padding: 12px 18px; gap: 10px; }
.ap-page .ap-record { padding: 10px 18px; border-radius: 0 0 12px 12px; }
.ap-page .ap-card :is(input, textarea):focus-visible { outline: 2px solid #16844e; outline-offset: 2px; }
@media(max-width: 900px) {
    .ap-page .ap-grid.identity { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .ap-page .identity > label, .ap-page .identity > .doctor, .ap-page .identity > label:has([name="hospital_service_id"]) { grid-column: auto; }
    .ap-page .identity > .patient { grid-column: 1 / -1; }
    .ap-page .identity > label:has([name="report_date"]) { grid-column: auto; }
    .ap-page .ap-results { grid-template-columns: minmax(0, 1fr); }
}
@media(max-width: 600px) {
    .ap-page .ap-grid.identity { grid-template-columns: minmax(0, 1fr); }
}
.ap-page .ap-results textarea[name="macroscopic_description"],
.ap-page .ap-results textarea[name="microscopic_description"] {
    height: 140px !important;
    min-height: 140px !important;
    line-height: 1.45;
    overflow-y: auto;
}
.ap-catalog-add { position: absolute; right: 0; top: 0; border: 0; background: transparent; color: #0d623c; border-radius: 5px; padding: 1px 7px; font-size: 12px; line-height: 22px; cursor: pointer; }
.ap-catalog-add:hover { background: #e5f6ec; }
.ap-page label:has(.ap-catalog-add) .select2-widget { padding-top: 0; }
.ap-page label:has(.ap-catalog-add) { padding-right: 0; }
.ap-catalog-dialog { position: fixed; inset: 0; margin: auto; width: min(440px, calc(100vw - 24px)); max-height: calc(100dvh - 24px); overflow: auto; padding: 22px; border: 1px solid #b9dcca; border-radius: 14px; background: #fff; color: #16392d; }
.ap-catalog-dialog::backdrop { background: #071e18a6; }
.ap-catalog-dialog h2 { margin: 0 0 18px; font-size: 21px; }
.ap-catalog-dialog label { display: block; }
.ap-catalog-dialog input { display: block; width: 100%; min-width: 0; margin-top: 8px; padding: 10px; font-size: 16px; border: 1px solid #b9dcca; border-radius: 7px; }
.ap-catalog-dialog footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
.ap-catalog-error { color: #b42318; margin-top: 10px; }
html[data-theme="dark"] .ap-catalog-dialog { background: #202d31; color: #edf6f1; border-color: #405158; }
html[data-theme="dark"] .ap-catalog-dialog input { background: #172125; color: #edf6f1; }
html[data-theme="dark"] .ap-catalog-add { background: #203e32; color: #9de7bd; border-color: #4c9d74; }
html[data-theme="dark"] .ap-catalog-error { color: #ffaaa3; }
@media(max-width: 700px) { .ap-catalog-add { min-height: 36px; } }
</style>
@if(\App\Support\AccessRoles::allows(auth()->user(), 'anatomopatologia.catalogos.store'))
<dialog id="ap-catalog-dialog" class="ap-catalog-dialog" aria-labelledby="ap-catalog-title">
    <form id="ap-catalog-form" action="{{ route('anatomopatologia.catalogos.store') }}" method="POST">
        @csrf
        <h2 id="ap-catalog-title">Añadir</h2>
        <input type="hidden" name="type">
        <label>Nombre <input name="name" required maxlength="200" autocomplete="off"></label>
        <p class="ap-catalog-error" role="alert" hidden></p>
        <footer><button type="button" class="ap-btn neutral" id="ap-catalog-cancel"><x-ui-icon name="close" /> Cancelar</button><button type="submit" class="ap-btn primary"><x-ui-icon name="save" /> Guardar y seleccionar</button></footer>
    </form>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('ap-catalog-dialog');
    const form = document.getElementById('ap-catalog-form');
    const error = form.querySelector('[role="alert"]');
    const save = form.querySelector('[type="submit"]');
    const cancel = document.getElementById('ap-catalog-cancel');
    const nameInput = form.elements.namedItem('name');
    let selectedControl;
    for (const [name, type, title] of [['hospital_service_id', 'service', 'Añadir servicio'], ['requesting_physician_id', 'physician', 'Añadir médico solicitante']]) {
        const select = document.querySelector(`.ap-page select[name="${name}"]`);
        if (!select || select.disabled) continue;
        // El label debe activar el selector, nunca el botón Añadir que contiene.
        select.id ||= `ap-${name}`;
        const label = select.closest('label');
        if (label) label.htmlFor = select.id;
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'ap-catalog-add'; button.textContent = '+ Añadir'; button.setAttribute('aria-label', title);
        select.before(button);
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            if (window.jQuery?.fn.select2 && window.jQuery(select).data('select2')) {
                window.jQuery(select).select2('close');
            }
            selectedControl = select;
            form.reset(); form.elements.namedItem('type').value = type; error.hidden = true;
            document.getElementById('ap-catalog-title').textContent = title;
            dialog.showModal(); nameInput.focus();
        });
    }
    document.getElementById('ap-catalog-cancel').addEventListener('click', () => dialog.close());
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled) return;
        if (!form.reportValidity() || !selectedControl) return;
        save.disabled = true; cancel.disabled = true; error.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
            const data = await response.json().catch(() => ({}));
            if (response.status === 419 || response.status === 401 || response.redirected) throw new Error('Su sesión ha caducado. Abra el sistema de nuevo antes de añadir la opción.');
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar. Intente nuevamente.');
            if (!data.option?.id || !data.option?.name) throw new Error('No se recibió la confirmación del catálogo. Intente nuevamente.');
            const option = new Option(data.option.name, data.option.id, true, true);
            selectedControl.add(option);
            const input = selectedControl.parentElement.querySelector('.select2-widget input');
            if (input) input.value = data.option.name;
            selectedControl.dispatchEvent(new Event('change', {bubbles: true}));
            dialog.close();
        } catch (exception) {
            error.textContent = exception.message; error.hidden = false;
        } finally { save.disabled = false; cancel.disabled = false; }
    });
});
</script>
@endif

