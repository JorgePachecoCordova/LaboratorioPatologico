const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element {
    constructor(tag = 'div') {
        this.tagName = tag; this.children = []; this.dataset = {}; this.listeners = {};
        this.attributes = new Set(); this.value = ''; this.textContent = ''; this.hidden = false;
    }
    addEventListener(name, callback) { this.listeners[name] = callback; }
    setAttribute(name) { this.attributes.add(name); }
    hasAttribute(name) { return this.attributes.has(name); }
    append(...children) { this.children.push(...children); }
    appendChild(child) { this.append(child); }
    focus() { this.focused = true; }
    setCustomValidity(message) { this.validationMessage = message; }
    close() { this.open = false; this.listeners.close?.(); }
    showModal() { this.open = true; }
    querySelector(selector) {
        const all = this.children.flatMap(child => [child, ...child.descendants()]);
        return all.find(child => selector === 'textarea' ? child.tagName === 'textarea' : child.hasAttribute(selector.slice(1, -1)));
    }
    descendants() { return this.children.flatMap(child => [child, ...child.descendants()]); }
    get options() { return this.children; }
}

function setup(existingTitle, response) {
    const ids = Object.fromEntries([
        'ap-cap-enabled', 'ap-cap-fields', 'ap-cap-element', 'ap-cap-custom-editor',
        'ap-cap-custom-title', 'ap-cap-custom-content', 'ap-cap-custom-error',
        'ap-cap-selection-error', 'ap-cap-open-custom', 'ap-cap-custom-cancel',
        'ap-cap-custom-add', 'ap-cap-selected-fields'
    ].map(id => [id, new Element()]));
    const items = [];
    if (existingTitle) {
        const item = new Element(); item.dataset.capKey = 'custom-0'; item.hidden = true;
        item.setAttribute('data-cap-custom');
        const input = new Element('input'); input.setAttribute('data-cap-custom-title');
        item.append(new Element('textarea'), input);
        items.push(item);
        const option = new Element('option'); option.value = 'custom-0'; option.textContent = existingTitle;
        ids['ap-cap-element'].append(option);
    }
    ids['ap-cap-fields'].querySelectorAll = () => items;
    ids['ap-cap-enabled'].checked = true;
    let ready;
    let requests = [];
    const document = {
        getElementById: id => ids[id], createElement: tag => new Element(tag),
        addEventListener: (_, callback) => { ready = callback; }
    };
    let script = fs.readFileSync('resources/views/anatomopatologia/cap-form.blade.php', 'utf8').split('<script>')[1].split('</script>')[0];
    script = script.replace(/@json\(route\('anatomopatologia.catalogos.store'\)\)/g, '"/catalog"')
        .replace(/@json\(csrf_token\(\)\)/g, '"token"')
        .replace(/@json\(view\('components.action-icon', \['name' => 'trash'\]\)->render\(\)\)/g, '""');
    vm.runInNewContext(script, { document, fetch: async (_, options) => {
        requests.push(JSON.parse(options.body));
        if (response instanceof Error) throw response;
        return response || { ok: true, json: async () => ({ option: { id: 42, name: 'CAMPO NUEVO' } }) };
    }});
    ready();
    ids['ap-cap-custom-title'].value = existingTitle ? '  mama  ' : 'Campo nuevo';
    ids['ap-cap-custom-content'].value = 'Contenido del informe';
    return { ids, items, requests, add: () => ids['ap-cap-custom-add'].listeners.click() };
}

(async () => {
    const fresh = setup();
    await fresh.add();
    assert.equal(fresh.requests.length, 1);
    const added = fresh.ids['ap-cap-selected-fields'].children[0];
    assert.equal(added.querySelector('[data-cap-custom-title]').value, 'CAMPO NUEVO');
    assert.equal(added.querySelector('textarea').value, 'Contenido del informe');
    assert.equal(added.querySelector('textarea').disabled, false);
    assert.equal(fresh.ids['ap-cap-custom-add'].disabled, false);

    const reused = setup('MAMA', { ok: true, json: async () => ({ option: { id: 8, name: 'MAMA' } }) });
    await reused.add();
    assert.equal(reused.items.length, 1);
    assert.equal(reused.items[0].hidden, false);
    assert.equal(reused.items[0].querySelector('textarea').value, 'Contenido del informe');
    assert.equal(reused.ids['ap-cap-selected-fields'].children.length, 0);
    reused.ids['ap-cap-custom-title'].value = 'mama';
    reused.ids['ap-cap-custom-content'].value = 'No sobrescribir';
    await reused.add();
    assert.equal(reused.requests.length, 1);
    assert.equal(reused.items[0].querySelector('textarea').value, 'Contenido del informe');

    const failed = setup(null, new Error('Sin conexión'));
    await failed.add();
    assert.equal(failed.ids['ap-cap-selected-fields'].children.length, 0);
    assert.equal(failed.ids['ap-cap-custom-content'].value, 'Contenido del informe');
    assert.equal(failed.ids['ap-cap-custom-error'].textContent, 'Sin conexión');
    assert.equal(failed.ids['ap-cap-custom-add'].disabled, false);
    assert.equal(failed.ids['ap-cap-custom-cancel'].disabled, false);

    const expired = setup(null, { ok: false, status: 419, json: async () => { throw new Error('HTML'); } });
    await expired.add();
    assert.match(expired.ids['ap-cap-custom-error'].textContent, /sesión ha caducado/);
    assert.equal(expired.ids['ap-cap-selected-fields'].children.length, 0);
    console.log('OK: añadir, reutilizar catálogo, evitar duplicados y conservar contenido ante errores.');
})().catch(error => { console.error(error); process.exitCode = 1; });
