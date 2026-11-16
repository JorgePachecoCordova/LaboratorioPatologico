<style>
.cy-catalog-add { float: right; border: 1px solid #b9dcca; background: #e5f6ec; color: #0d623c; border-radius: 6px; padding: 3px 9px; font-size: 12px; cursor: pointer; }
.cy-workspace label:has(.cy-catalog-add) :is(.select2-widget, select:not([hidden])) { clear: both; display: block; }
.cy-workspace label:has(.cy-catalog-add) .select2-widget { padding-top: 4px; }
.cy-workspace .top-grid > label { position:relative; line-height:28px; }
.cy-workspace .top-grid > label > :is(input,select) { display:block; margin-top:2px; line-height:normal; }
.cy-workspace .top-grid .cy-catalog-add { position:absolute; top:0; right:0; float:none; height:26px; min-height:26px; padding:2px 9px; line-height:20px; }
.cy-workspace .top-grid > label:has(.cy-catalog-add) { padding-right:88px; }
.cy-workspace .top-grid > label:has(.cy-catalog-add) > select { width:calc(100% + 88px); }
.cy-catalog-dialog { position: fixed; inset: 0; margin: auto; width: min(440px, calc(100vw - 24px)); max-height: calc(100dvh - 24px); overflow: auto; padding: 22px; border: 1px solid #b9dcca; border-radius: 14px; background: #fff; color: #16392d; }
.cy-catalog-dialog::backdrop { background: #071e18a6; }
.cy-catalog-dialog h2 { margin: 0 0 18px; font-size: 21px; }
.cy-catalog-dialog label { display: block; }
.cy-catalog-dialog input { display: block; width: 100%; min-width: 0; margin-top: 8px; padding: 10px; font-size: 16px; border: 1px solid #b9dcca; border-radius: 7px; }
.cy-catalog-dialog footer { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; margin-top: 18px; }
.cy-catalog-dialog button { min-height: 40px; padding: 8px 12px; border: 0; border-radius: 7px; cursor: pointer; }
.cy-catalog-dialog .primary { background: #16844e; color: white; }
.cy-catalog-error { color: #b42318; margin-top: 10px; }
html[data-theme="dark"] .cy-catalog-dialog { background: #202d31; color: #edf6f1; border-color: #405158; }
html[data-theme="dark"] .cy-catalog-dialog input { background: #172125; color: #edf6f1; }
html[data-theme="dark"] .cy-catalog-add { background: #203e32; color: #9de7bd; border-color: #4c9d74; }
html[data-theme="dark"] .cy-catalog-error { color: #ffaaa3; }
@media(max-width: 700px) { .cy-catalog-add { min-height: 36px; } }
</style>
@if(\App\Support\AccessRoles::allows(auth()->user(), 'citologia.catalogos.store'))
<dialog id="cy-catalog-dialog" class="cy-catalog-dialog" aria-labelledby="cy-catalog-title">
    <form id="cy-catalog-form" action="{{ route('citologia.catalogos.store') }}" method="POST">
        @csrf
        <h2 id="cy-catalog-title">Añadir</h2>
        <input type="hidden" name="type">
        <label>Nombre <input name="name" required maxlength="200" autocomplete="off"></label>
        <p class="cy-catalog-error" role="alert" hidden></p>
        <footer><button type="button" class="ap-btn neutral" id="cy-catalog-cancel"><x-ui-icon name="close" /> Cancelar</button><button type="submit" class="ap-btn primary"><x-ui-icon name="save" /> Guardar y seleccionar</button></footer>
    </form>
</dialog>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('cy-catalog-dialog');
    const form = document.getElementById('cy-catalog-form');
    const error = form.querySelector('[role="alert"]');
    const save = form.querySelector('[type="submit"]');
    let selectedControl;
    for (const [name, type, title] of [['hospital_service_id', 'service', 'Añadir servicio'], ['requesting_physician_id', 'physician', 'Añadir médico solicitante'], ['sample_type_code', 'sample', 'Añadir tipo de muestra'], ['care_type', 'care', 'Añadir atención']]) {
        if (!@json(\App\Support\AccessRoles::admin(auth()->user())) && !['service', 'physician', 'care', 'sample'].includes(type)) continue;
        const select = document.querySelector(`.cy-workspace select[name="${name}"]`);
        if (!select) continue;
        select.id ||= `cy-${name}`;
        const label = select.closest('label');
        if (label) label.htmlFor = select.id;
        const button = document.createElement('button');
        button.type = 'button'; button.className = 'cy-catalog-add'; button.textContent = '+ Añadir'; button.setAttribute('aria-label', title);
        const caption = label?.querySelector('.cy-field-caption');
        if (caption) caption.append(button);
        else select.before(button);
        button.addEventListener('click', event => {
            event.preventDefault();
            event.stopPropagation();
            selectedControl = select;
            form.reset(); form.elements.type.value = type; form.elements.name.maxLength = type === 'care' ? 30 : type === 'sample' ? 60 : 200; error.hidden = true;
            document.getElementById('cy-catalog-title').textContent = title;
            dialog.showModal(); form.elements.name.focus();
        });
    }
    document.getElementById('cy-catalog-cancel').addEventListener('click', () => dialog.close());
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled) return;
        save.disabled = true; error.hidden = true;
        try {
            const response = await fetch(form.action, {method: 'POST', body: new FormData(form), headers: {'Accept': 'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar. Intente nuevamente.');
            const option = new Option(data.option.name, data.option.id, true, true);
            selectedControl.add(option);
            const input = selectedControl.parentElement.querySelector('.select2-widget input');
            if (input) input.value = data.option.name;
            selectedControl.dispatchEvent(new Event('change', {bubbles: true}));
            dialog.close();
        } catch (exception) {
            error.textContent = exception.message; error.hidden = false;
        } finally { save.disabled = false; }
    });
});
</script>
@endif

