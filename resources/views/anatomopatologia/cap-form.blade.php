@php
    $capEnabled = (bool) old('cap_protocol_enabled', $report?->cap_protocol_enabled ?? false);
    $customFields = old('cap_protocol.custom', $report?->cap_protocol['custom'] ?? []);
    $customFields = is_array($customFields) ? $customFields : [];
    $availableCapFields = \App\Support\AnatomopathologyCapProtocol::activeFields();
    $usedTitles = collect($customFields)->filter(fn ($item) => is_array($item))->map(fn ($item) => \App\Models\CapProtocolField::normalize(is_string($item['title'] ?? null) ? $item['title'] : ''))->all();
    $catalogFields = \App\Models\CapProtocolField::query()->where('is_active', true)->whereNull('field_key')->orderBy('name')->get()
        ->reject(fn ($item) => in_array($item->normalized_name, $usedTitles, true));
    $nextCatalogIndex = max([-1, ...array_map('intval', array_keys($customFields))]) + 1;
    $catalogFields = $catalogFields->map(fn ($item, $offset) => ['index' => $nextCatalogIndex + $offset, 'title' => $item->name])->values();
@endphp
<section class="ap-cap" aria-labelledby="ap-cap-title">
    <div class="ap-cap-heading">
        <div class="ap-cap-intro"><span class="ap-cap-symbol"><x-ui-icon name="file" /></span><div><h2 id="ap-cap-title">Protocolo CAP</h2><p>Organice los campos complementarios del informe.</p></div></div>
        <input type="hidden" name="cap_protocol_enabled" value="0">
        <label class="ap-cap-toggle"><input id="ap-cap-enabled" type="checkbox" name="cap_protocol_enabled" value="1" @checked($capEnabled) aria-controls="ap-cap-fields"> Incluir protocolo</label>
    </div>
    <div id="ap-cap-fields" @if(!$capEnabled) hidden @endif>
        <div class="ap-cap-picker">
            <div class="ap-cap-picker-heading">
                <label for="ap-cap-element">Seleccione el campo que desea llenar</label>
                <button type="button" id="ap-cap-open-custom" aria-controls="ap-cap-custom-editor" aria-haspopup="dialog" aria-expanded="false"><x-ui-icon name="plus" /> Añadir protocolo personalizado</button>
            </div>
            <select id="ap-cap-element" aria-describedby="ap-cap-help" aria-controls="ap-cap-selected-fields">
                <option value="">Seleccione un título</option>
                @foreach($availableCapFields as $key => $label)
                    @if($key !== 'note')<option value="{{ $key }}">{{ $label }}</option>@endif
                @endforeach
                @foreach($customFields as $index => $item)
                    @if(is_array($item) && is_string($item['title'] ?? null))<option value="custom-{{ $index }}">{{ $item['title'] }}</option>@endif
                @endforeach
                @foreach($catalogFields as $item)
                    <option value="custom-{{ $item['index'] }}">{{ $item['title'] }}</option>
                @endforeach
            </select>
            <p id="ap-cap-help">Elija un título para mostrar su campo. Puede seleccionar varios. Muestra / procedimiento es opcional.</p>
            <p id="ap-cap-selection-error" class="ap-cap-error" role="alert"></p>
        </div>
        <dialog id="ap-cap-custom-editor" class="ap-cap-editor" aria-labelledby="ap-cap-editor-heading" aria-describedby="ap-cap-editor-help">
            <div class="ap-cap-editor-heading"><strong id="ap-cap-editor-heading">Nuevo protocolo personalizado</strong><p id="ap-cap-editor-help">El título se guardará en el catálogo para futuros informes. El contenido se conservará al guardar este informe.</p></div>
            <label for="ap-cap-custom-title">Título del nuevo protocolo</label>
            <div class="ap-cap-custom-actions">
                <input id="ap-cap-custom-title" type="text" maxlength="180" placeholder="Escriba el título que necesita" aria-describedby="ap-cap-custom-error" autocomplete="off">
            </div>
            <label class="ap-cap-content-label" for="ap-cap-custom-content">Contenido del protocolo</label>
            <textarea id="ap-cap-custom-content" maxlength="10000" placeholder="Escriba el contenido del nuevo protocolo" spellcheck="true" aria-describedby="ap-cap-custom-error"></textarea>
            <p id="ap-cap-custom-error" class="ap-cap-error" role="alert"></p>
            <div class="ap-cap-custom-actions ap-cap-modal-footer">
                <div class="ap-cap-editor-buttons">
                <button type="button" id="ap-cap-custom-cancel"><x-ui-icon name="close" /> Cancelar</button>
                <button type="button" id="ap-cap-custom-add"><x-ui-icon name="plus" /> Añadir al informe</button>
                </div>
            </div>
        </dialog>
        @error('cap_protocol.custom')<p class="ap-cap-error" role="alert">{{ $message }}</p>@enderror
        <div id="ap-cap-selected-fields" class="ap-cap-grid">
            @foreach(\App\Support\AnatomopathologyCapProtocol::FIELDS as $key => $label)
                @php
                    $value = old('cap_protocol.'.$key, $report?->cap_protocol[$key] ?? '');
                    $value = is_string($value) ? $value : '';
                    $selected = ($key === 'note' && isset($availableCapFields['note'])) || trim($value) !== '' || $errors->has('cap_protocol.'.$key);
                @endphp
                <div class="ap-cap-field {{ $key === 'note' ? 'ap-cap-note' : '' }}" data-cap-key="{{ $key }}" @if(!$selected) hidden @endif>
                    <div class="ap-cap-field-heading">
                        <label for="cap-{{ $key }}">{{ $label }}</label>
                        @if($key !== 'note')<button type="button" data-cap-remove aria-label="Quitar {{ $label }}"><x-action-icon name="trash" /> Quitar</button>@endif
                    </div>
                    <input type="hidden" name="cap_protocol[{{ $key }}]" value="" data-cap-empty @disabled($selected)>
                    <textarea id="cap-{{ $key }}" name="cap_protocol[{{ $key }}]" maxlength="10000" spellcheck="true" @disabled(!$selected)>{{ $value }}</textarea>
                    @error('cap_protocol.'.$key)<span class="ap-cap-error" role="alert">{{ $message }}</span>@enderror
                </div>
            @endforeach
            @foreach($customFields as $index => $item)
                @if(is_array($item))
                <div class="ap-cap-field" data-cap-key="custom-{{ $index }}" data-cap-custom>
                    <div class="ap-cap-field-heading">
                        <label for="cap-custom-{{ $index }}">{{ is_string($item['title'] ?? null) ? $item['title'] : 'Protocolo personalizado' }}</label>
                        <button type="button" data-cap-remove><x-action-icon name="trash" /> Quitar</button>
                    </div>
                    <input type="hidden" name="cap_protocol[custom][{{ $index }}][title]" value="{{ is_string($item['title'] ?? null) ? $item['title'] : '' }}" data-cap-custom-title>
                    <textarea id="cap-custom-{{ $index }}" name="cap_protocol[custom][{{ $index }}][value]" maxlength="10000" @required($capEnabled)>{{ is_string($item['value'] ?? null) ? $item['value'] : '' }}</textarea>
                    @error('cap_protocol.custom.'.$index.'.title')<span class="ap-cap-error" role="alert">{{ $message }}</span>@enderror
                    @error('cap_protocol.custom.'.$index.'.value')<span class="ap-cap-error" role="alert">{{ $message }}</span>@enderror
                </div>
                @endif
            @endforeach
            @foreach($catalogFields as $item)
                <div class="ap-cap-field" data-cap-key="custom-{{ $item['index'] }}" data-cap-custom hidden>
                    <div class="ap-cap-field-heading"><label for="cap-custom-{{ $item['index'] }}">{{ $item['title'] }}</label><button type="button" data-cap-remove><x-action-icon name="trash" /> Quitar</button></div>
                    <input type="hidden" name="cap_protocol[custom][{{ $item['index'] }}][title]" value="{{ $item['title'] }}" data-cap-custom-title disabled>
                    <textarea id="cap-custom-{{ $item['index'] }}" name="cap_protocol[custom][{{ $item['index'] }}][value]" maxlength="10000" disabled></textarea>
                </div>
            @endforeach
        </div>
    </div>
</section>
<style>
.ap-cap{flex:0 0 auto;padding:24px;border-bottom:1px solid #dce6e0;overflow-anchor:none;--cap-surface:#f6faf8;--cap-border:#d4e3da;--cap-ink:#244538;--cap-muted:#63776c;--cap-input:#fff}
.ap-cap [hidden]{display:none!important}
.ap-cap-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
.ap-cap-intro{display:flex;align-items:center;gap:12px;min-width:0}
.ap-cap-symbol{display:grid;place-items:center;width:40px;height:40px;flex:0 0 40px;border-radius:10px;background:#e9f4ed;color:#16844e}
.ap-cap-heading h2{margin:0;font-size:16px;letter-spacing:0;color:var(--cap-ink)}
.ap-cap-intro p{margin:4px 0 0;font-size:12px;line-height:1.5;color:var(--cap-muted)}
.ap-cap-toggle{display:flex;align-items:center;gap:8px;padding:9px 12px;border:1px solid var(--cap-border);border-radius:8px;background:var(--cap-surface);font-size:13px;cursor:pointer;white-space:nowrap}
.ap-cap-toggle input{width:18px;height:18px;accent-color:#16844e}
.ap-cap-picker{margin-bottom:18px;padding:16px;border:1px solid var(--cap-border);border-radius:10px;background:var(--cap-surface)}
.ap-cap-picker-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:6px}
.ap-cap-picker-heading button{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:7px 12px;border:1px solid #16844e;border-radius:7px;background:var(--cap-input);color:#137548;font:600 12px "Segoe UI",sans-serif;cursor:pointer}
html[data-theme="dark"] .ap-cap-picker-heading button{color:#a6e4c3;border-color:#608f79}
.ap-cap-picker label,.ap-cap-grid label,.ap-cap-editor>label{font-size:13px;font-weight:600;line-height:22px;color:var(--cap-ink)}
.ap-cap-picker p{margin:6px 0 0;font-size:12px;color:#63776c}
.ap-cap-picker select{display:block;width:100%;padding:10px;border:1px solid #cbdcd2;border-radius:7px;font:13px "Segoe UI",sans-serif;background:#fff;color:#233e31}
.ap-cap-editor{box-sizing:border-box;width:460px;max-width:calc(100vw - 32px);max-height:calc(100dvh - 40px);overflow:auto;padding:18px;margin:auto;border:1px solid #a8cbb8;border-radius:14px;background:var(--cap-surface);color:var(--cap-ink);box-shadow:0 24px 80px #102b2040}
.ap-cap-editor::backdrop{background:#10251dcc;backdrop-filter:blur(3px)}
.ap-cap-editor .ap-cap-custom-actions{grid-template-columns:minmax(0,1fr)}
.ap-cap-editor .ap-cap-editor-heading strong{font-size:16px}
.ap-cap-content-label{display:block;margin:12px 0 5px}
.ap-cap-editor>textarea{box-sizing:border-box;width:100%;height:100px;min-height:80px;padding:10px;border:1px solid var(--cap-border);border-radius:8px;background:var(--cap-input);color:var(--cap-ink);font:14px "Segoe UI",sans-serif;line-height:1.6;resize:vertical}
.ap-cap-modal-footer{padding-top:12px;margin-top:12px!important;border-top:1px solid var(--cap-border)}
.ap-cap-modal-footer .ap-cap-editor-buttons{justify-content:flex-end}
.ap-cap #ap-cap-custom-editor{width:380px;padding:14px;border-radius:10px}
.ap-cap #ap-cap-custom-editor .ap-cap-editor-heading{margin-bottom:10px}
.ap-cap #ap-cap-custom-editor .ap-cap-editor-heading strong{font-size:15px}
.ap-cap #ap-cap-custom-editor .ap-cap-editor-heading p{font-size:11px;line-height:1.4}
.ap-cap #ap-cap-custom-editor>label{font-size:12px;line-height:18px}
.ap-cap #ap-cap-custom-editor .ap-cap-custom-actions input{height:36px;padding:7px 9px;font-size:12px}
.ap-cap #ap-cap-custom-editor>textarea{height:80px;min-height:64px;padding:8px;font-size:12px}
.ap-cap #ap-cap-custom-editor .ap-cap-content-label{margin-top:10px}
.ap-cap #ap-cap-custom-editor .ap-cap-modal-footer{padding-top:10px;margin-top:10px!important}
.ap-cap #ap-cap-custom-editor button{min-height:36px;padding:7px 10px;font-size:12px}
.ap-cap-editor-heading{margin-bottom:14px;color:var(--cap-ink);font-size:14px}
.ap-cap-editor-heading p{margin:5px 0 0;color:var(--cap-muted);font-size:12px;line-height:1.5}
.ap-cap-custom-actions{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:start;gap:12px;margin-top:6px}
.ap-cap-custom-actions input{box-sizing:border-box;width:100%;min-width:0;height:44px;padding:10px 12px;border:1px solid var(--cap-border);border-radius:7px;color:var(--cap-ink);background:var(--cap-input);font:13px "Segoe UI",sans-serif}
.ap-cap-editor-buttons{display:flex;gap:8px}
.ap-cap-custom-actions button{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:44px;padding:9px 15px;border:1px solid #16844e;border-radius:7px;background:#16844e;color:white;font:600 13px "Segoe UI",sans-serif;cursor:pointer;white-space:nowrap}
.ap-cap-custom-actions #ap-cap-custom-cancel{background:var(--cap-input);color:var(--cap-ink);border-color:var(--cap-border)}
.ap-cap-grid{display:flex;flex-direction:column;gap:14px}
.ap-cap-field{min-width:0;padding:16px;border:1px solid var(--cap-border);border-radius:10px;background:var(--cap-input)}
.ap-cap-note{order:1;background:var(--cap-surface)}
.ap-cap-field-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:10px}
.ap-cap-field-heading label{overflow-wrap:anywhere}
.ap-cap-field-heading button{display:inline-flex;align-items:center;gap:5px;border:1px solid #cbdcd2;border-radius:5px;padding:3px 8px;background:transparent;color:inherit;font-size:12px;cursor:pointer}
.ap-cap-field-heading button{flex-shrink:0;min-height:32px}
.ap-cap-grid textarea{display:block;box-sizing:border-box;width:100%;height:104px;min-height:104px;padding:10px 12px;border:1px solid var(--cap-border);border-radius:7px;font:13px "Segoe UI",sans-serif;line-height:1.65;resize:vertical;background:var(--cap-input);color:var(--cap-ink)}
.ap-cap :is(select,textarea,button,input):focus-visible{outline:2px solid #16844e;outline-offset:2px}
.ap-cap button:hover{filter:brightness(.97)}
.ap-cap-error:empty{display:none}
.ap-cap-editor .ap-cap-error{margin:8px 0 0;font-size:12px}
.ap-cap-error{color:#b33139}
html[data-theme="dark"] .ap-cap{border-color:#405159;color:#d9e9e1;--cap-surface:#1b292e;--cap-border:#40575e;--cap-ink:#e1eee7;--cap-muted:#afc2b8;--cap-input:#111c20}
html[data-theme="dark"] .ap-cap-symbol{background:#203e32;color:#94dfb4}
html[data-theme="dark"] .ap-cap-editor{border-color:#4a8066}
html[data-theme="dark"] .ap-cap textarea,html[data-theme="dark"] .ap-cap select{background:#111c20;border-color:#4b6068;color:#f5faf8;color-scheme:dark}
html[data-theme="dark"] .ap-cap-picker p{color:#afc2b8}
html[data-theme="dark"] .ap-cap-field-heading button{display:inline-flex;align-items:center;gap:5px;border-color:#4b6068}
@media(max-width:760px){.ap-cap-custom-actions{grid-template-columns:minmax(0,1fr)}.ap-cap-editor-buttons{justify-content:flex-end}}
@media(max-width:600px){.ap-cap{padding:18px 16px}.ap-cap-heading{align-items:flex-start;flex-direction:column}.ap-cap-picker,.ap-cap-field,.ap-cap-editor{padding:14px}.ap-cap-picker-heading{align-items:stretch;flex-direction:column}.ap-cap-picker-heading button{width:100%;white-space:normal}.ap-cap-editor-buttons button{flex:1}.ap-cap-field-heading button{min-height:40px}}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('ap-cap-enabled');
    const fields = document.getElementById('ap-cap-fields');
    const picker = document.getElementById('ap-cap-element');
    const items = [...fields.querySelectorAll('[data-cap-key]')];
    const editor = document.getElementById('ap-cap-custom-editor');
    const titleInput = document.getElementById('ap-cap-custom-title');
    const contentInput = document.getElementById('ap-cap-custom-content');
    const customError = document.getElementById('ap-cap-custom-error');
    const selectionError = document.getElementById('ap-cap-selection-error');
    let customIndex = Math.max(-1, ...items.filter(item => item.hasAttribute('data-cap-custom')).map(item => Number(item.dataset.capKey.replace('custom-', '')))) + 1;
    const sync = () => {
        fields.hidden = !toggle.checked;
        items.forEach(item => {
            const selected = !item.hidden;
            item.querySelector('textarea').disabled = !selected;
            const empty = item.querySelector('[data-cap-empty]');
            if (empty) empty.disabled = selected;
            const customTitle = item.querySelector('[data-cap-custom-title]');
            if (customTitle) customTitle.disabled = !selected;
            item.querySelector('textarea').required = toggle.checked && selected && !!customTitle;
            const option = [...picker.options].find(option => option.value === item.dataset.capKey);
            if (option) option.disabled = selected;
        });
        picker.required = false;
        picker.setCustomValidity('');
    };
    const openCustomButton = document.getElementById('ap-cap-open-custom');
    const openCustomEditor = () => {
        if (!editor.open) editor.showModal();
        openCustomButton.setAttribute('aria-expanded', 'true');
        titleInput.focus({ preventScroll: true });
    };
    openCustomButton.addEventListener('click', openCustomEditor);
    picker.addEventListener('change', () => {
        const item = items.find(item => item.dataset.capKey === picker.value);
        if (!item) return;
        selectionError.textContent = '';
        if (item.hasAttribute('data-cap-custom') && item.hidden && items.filter(row => row.hasAttribute('data-cap-custom') && !row.hidden).length >= 50) {
            selectionError.textContent = 'Puede incluir hasta 50 campos personalizados por informe.';
            picker.value = ''; return;
        }
        item.hidden = false;
        picker.value = '';
        sync();
        item.querySelector('textarea').focus({ preventScroll: true });
    });
    const bindRemove = (item) => {
        item.querySelector('[data-cap-remove]')?.addEventListener('click', () => {
            item.hidden = true;
            sync();
            picker.focus({ preventScroll: true });
        });
    };
    items.forEach(bindRemove);
    document.getElementById('ap-cap-custom-cancel').addEventListener('click', () => {
        editor.close();
    });
    editor.addEventListener('close', () => {
        openCustomButton.setAttribute('aria-expanded', 'false');
        customError.textContent = '';
        openCustomButton.focus({ preventScroll: true });
    });
    const addButton = document.getElementById('ap-cap-custom-add');
    const addCustom = async () => {
        if (addButton.disabled) return;
        const normalizeTitle = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toUpperCase();
        let title = normalizeTitle(titleInput.value);
        const content = contentInput.value.trim();
        if (!title) { customError.textContent = 'Escriba el título del protocolo.'; titleInput.focus({ preventScroll: true }); return; }
        if (!content) { customError.textContent = 'Complete el contenido del protocolo.'; contentInput.focus({ preventScroll: true }); return; }
        const existingOption = [...picker.options].find(option => option.value && normalizeTitle(option.textContent.replace(/\s*\*$/, '')) === title);
        if (existingOption) {
            const existingItem = items.find(item => item.dataset.capKey === existingOption.value);
            if (!existingItem?.hasAttribute('data-cap-custom') || !existingItem.hidden) {
                customError.textContent = 'Ese campo ya existe en el informe o es un campo del protocolo. Utilice su campo correspondiente.'; return;
            }
        }
        if (items.filter(item => item.hasAttribute('data-cap-custom') && !item.hidden).length >= 50) {
            customError.textContent = 'Puede añadir hasta 50 protocolos personalizados por informe.'; return;
        }
        customError.textContent = '';
        addButton.disabled = true;
        document.getElementById('ap-cap-custom-cancel').disabled = true;
        try {
            const response = await fetch(@json(route('anatomopatologia.catalogos.store')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
                body: JSON.stringify({ type: 'protocol', name: title }),
            });
            const data = await response.json().catch(() => ({}));
            if (response.status === 419 || response.status === 401 || response.redirected) throw new Error('Su sesión ha caducado. Abra el sistema de nuevo antes de añadir el protocolo.');
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo guardar el protocolo. Intente nuevamente.');
            if (!data.option?.name || !data.option?.id) throw new Error('No se recibió la confirmación del catálogo. Intente nuevamente.');
            title = data.option.name;
        } catch (error) {
            customError.textContent = error.message || 'No se pudo guardar el protocolo. Intente nuevamente.';
            return;
        } finally {
            addButton.disabled = false;
            document.getElementById('ap-cap-custom-cancel').disabled = false;
        }
        if (existingOption) {
            const item = items.find(item => item.dataset.capKey === existingOption.value);
            item.hidden = false;
            item.querySelector('textarea').value = content;
            titleInput.value = ''; contentInput.value = ''; editor.close();
            sync(); item.querySelector('textarea').focus({ preventScroll: true });
            return;
        }
        const index = customIndex++;
        const key = `custom-${index}`;
        const item = document.createElement('div');
        item.className = 'ap-cap-field';
        item.dataset.capKey = key;
        item.setAttribute('data-cap-custom', '');
        const heading = document.createElement('div');
        heading.className = 'ap-cap-field-heading';
        const label = document.createElement('label');
        label.htmlFor = `cap-${key}`;
        label.textContent = title;
        const remove = document.createElement('button');
        remove.type = 'button'; remove.innerHTML = @json(view('components.action-icon', ['name' => 'trash'])->render()) + ' Quitar'; remove.setAttribute('data-cap-remove', '');
        heading.append(label, remove);
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = `cap_protocol[custom][${index}][title]`; input.value = title; input.setAttribute('data-cap-custom-title', '');
        const textarea = document.createElement('textarea');
        textarea.id = `cap-${key}`; textarea.name = `cap_protocol[custom][${index}][value]`; textarea.maxLength = 10000;
        textarea.value = content;
        item.append(heading, input, textarea);
        document.getElementById('ap-cap-selected-fields').appendChild(item);
        const option = document.createElement('option');
        option.value = key; option.textContent = title;
        picker.appendChild(option);
        items.push(item); bindRemove(item);
        titleInput.value = ''; contentInput.value = ''; editor.close(); customError.textContent = '';
        openCustomButton.setAttribute('aria-expanded', 'false');
        sync(); textarea.focus({ preventScroll: true });
    };
    document.getElementById('ap-cap-custom-add').addEventListener('click', addCustom);
    titleInput.addEventListener('keydown', event => { if (event.key === 'Enter') { event.preventDefault(); contentInput.focus(); } });
    toggle.addEventListener('change', () => {
        sync();
        if (!toggle.checked) return;
        requestAnimationFrame(() => {
            const section = toggle.closest('.ap-cap');
            const scroller = section.closest('.page-scroll');
            const behavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth';
            if (scroller) {
                // Align the protocol below the fixed top bar within the actual page scroller.
                const top = scroller.scrollTop + section.getBoundingClientRect().top
                    - scroller.getBoundingClientRect().top - 16;
                scroller.scrollTo({ top: Math.max(0, top), behavior });
            } else {
                section.scrollIntoView({ block: 'start', behavior });
            }
        });
    });
    sync();
});
</script>
