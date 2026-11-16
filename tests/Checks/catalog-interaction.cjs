const fs=require('fs');
const {chromium}=require('C:/Users/USUARIO/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async()=>{
const browser=await chromium.launch({executablePath:'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',headless:true});
const page=await browser.newPage();
const source=fs.readFileSync('resources/views/anatomopatologia/catalog-add.blade.php','utf8');
const script=source.match(/<script>([\s\S]*?)<\/script>/)[1];
await page.route('https://test.local/catalogs',route=>route.fulfill({status:201,contentType:'application/json',body:JSON.stringify({option:{id:99,name:'NUEVA OPCION'}})}));
await page.setContent(`<div class="ap-page"><label>Servicio<select name="hospital_service_id"><option value="1">SERVICIO</option></select><span id="service-search">Buscar servicio</span></label><label>Medico<select name="requesting_physician_id"><option value="2">MEDICO</option></select><span id="physician-search">Buscar medico</span></label></div><dialog id="ap-catalog-dialog"><form id="ap-catalog-form" action="https://test.local/catalogs"><h2 id="ap-catalog-title"></h2><input name="type"><input name="name"><p role="alert"></p><button type="button" id="ap-catalog-cancel">Cancelar</button><button type="submit">Guardar</button></form></dialog><script>${script}</script>`);
for(const [key,name] of [['service','hospital_service_id'],['physician','requesting_physician_id']]){
 await page.locator('#'+key+'-search').click();
 if(await page.locator('dialog').evaluate(d=>d.open))throw Error('Search opened dialog');
 await page.locator('select[name="'+name+'"]').focus();
 if(await page.locator('dialog').evaluate(d=>d.open))throw Error('Focus opened dialog');
 await page.locator('select[name="'+name+'"]').locator('..').locator('.ap-catalog-add').click();
 if(!await page.locator('dialog').evaluate(d=>d.open))throw Error('Add failed');
 await page.locator('#ap-catalog-cancel').click();
 if(await page.locator('dialog').evaluate(d=>d.open))throw Error('Cancel failed');
 await page.locator('select[name="'+name+'"]').locator('..').locator('.ap-catalog-add').click();
 await page.locator('#ap-catalog-form input[name="name"]').fill('NUEVA OPCION');
 await page.locator('#ap-catalog-form button[type="submit"]').click();
 await page.waitForFunction(()=>!document.querySelector('dialog').open);
 if(await page.locator('select[name="'+name+'"]').inputValue()!=='99')throw Error('New option not selected');
}
console.log('PASS: ambos campos buscan sin abrir modal; Añadir, Cancelar y Guardar y seleccionar funcionan.');
await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
