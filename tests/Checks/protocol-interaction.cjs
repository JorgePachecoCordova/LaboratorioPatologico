const fs = require('fs');
const assert = require('node:assert/strict');
const { chromium } = require('C:/Users/USUARIO/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');

(async () => {
    const browser = await chromium.launch({ executablePath: 'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe', headless: true });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let fail = true;
        let requests = 0;
        await page.route('https://test.local/catalogs', route => {
            requests++;
            return route.fulfill({ status: fail ? 422 : 201, contentType: 'application/json', body: JSON.stringify(fail ? { errors: { name: ['No se pudo guardar: prueba de error'] } } : { option: { id: 99, name: 'MAMA' } }) });
        });
        let script = fs.readFileSync('resources/views/anatomopatologia/cap-form.blade.php', 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
        script = script.replace("@json(isset($availableCapFields['specimen']))", 'true')
            .replace("@json(route('anatomopatologia.catalogos.store'))", JSON.stringify('https://test.local/catalogs'))
            .replace('@json(csrf_token())', JSON.stringify('test-token'))
            .replace("@json(view('components.action-icon', ['name' => 'trash'])->render())", JSON.stringify('<span>Quitar</span>'));
        await page.setContent(`<form id="report"><section class="ap-cap"><input id="ap-cap-enabled" type="checkbox" checked><div id="ap-cap-fields">
          <button type="button" id="ap-cap-open-custom">Añadir</button><select id="ap-cap-element"><option value="">Seleccione</option><option value="specimen">Muestra</option></select>
          <p id="ap-cap-selection-error"></p><dialog id="ap-cap-custom-editor"><input id="ap-cap-custom-title"><textarea id="ap-cap-custom-content"></textarea><p id="ap-cap-custom-error"></p><button type="button" id="ap-cap-custom-cancel">Cancelar</button><button type="button" id="ap-cap-custom-add">Añadir al informe</button></dialog>
          <div id="ap-cap-selected-fields"><div data-cap-key="specimen" hidden><button type="button" data-cap-remove>Quitar</button><input data-cap-empty name="cap_protocol[specimen]" value="" type="hidden"><textarea name="cap_protocol[specimen]"></textarea></div></div>
          </div></section></form><script>${script}</script>`);
        await page.locator('#ap-cap-open-custom').click();
        await page.locator('#ap-cap-custom-cancel').click();
        assert.equal(await page.locator('dialog').evaluate(d => d.open), false);
        await page.locator('#ap-cap-open-custom').click();
        await page.locator('#ap-cap-custom-add').click();
        assert.match(await page.locator('#ap-cap-custom-error').textContent(), /título/);
        await page.locator('#ap-cap-custom-title').fill('MAMA');
        await page.locator('#ap-cap-custom-content').fill('CONTENIDO DE PRUEBA');
        await page.locator('#ap-cap-custom-add').click();
        await page.waitForFunction(() => document.querySelector('#ap-cap-custom-error').textContent.includes('prueba de error'));
        assert.equal(await page.locator('[data-cap-custom]').count(), 0);
        assert.equal(await page.locator('#ap-cap-custom-content').inputValue(), 'CONTENIDO DE PRUEBA');
        fail = false;
        await page.locator('#ap-cap-custom-add').click();
        await page.waitForFunction(() => !document.querySelector('dialog').open);
        assert.equal(requests, 2);
        const values = await page.locator('#report').evaluate(form => Object.fromEntries(new FormData(form)));
        assert.equal(values['cap_protocol[custom][0][title]'], 'MAMA');
        assert.equal(values['cap_protocol[custom][0][value]'], 'CONTENIDO DE PRUEBA');
        await page.locator('[data-cap-custom] [data-cap-remove]').click();
        assert.equal(await page.locator('#report').evaluate(form => new FormData(form).has('cap_protocol[custom][0][title]')), false);
        await page.locator('#ap-cap-element').selectOption('custom-0');
        assert.equal(await page.locator('[data-cap-custom] textarea').inputValue(), 'CONTENIDO DE PRUEBA');
        await page.locator('#ap-cap-enabled').uncheck();
        assert.equal(await page.locator('#ap-cap-fields').isVisible(), false);
        await page.locator('#ap-cap-enabled').check();
        await page.locator('#ap-cap-element').selectOption('specimen');
        await page.locator('[data-cap-key="specimen"] textarea').fill('BIOPSIA');
        assert.equal(await page.locator('#report').evaluate(form => form.checkValidity()), true);
        assert.deepEqual(errors, []);
        console.log('PASS: abrir, cancelar, validar, error de guardado, reintentar, añadir, enviar contenido, quitar, seleccionar e incluir protocolo.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
