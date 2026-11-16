<dialog id="new-diagnosis-dialog" aria-labelledby="new-diagnosis-title">
<form id="new-diagnosis-form" action="{{ route('resultados.diagnosticos.store') }}" method="POST">
@csrf
<header><div><h2 id="new-diagnosis-title">Añadir diagnóstico al catálogo</h2><p>Registre su código y descripción para utilizarlo en los resultados.</p></div><button type="button" data-close-diagnosis aria-label="Cerrar">×</button></header>
<label for="new-diagnosis-code">Código *</label><input id="new-diagnosis-code" name="code" required maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9./\-]*" placeholder="Ej.: GX6" autocomplete="off">
<label for="new-diagnosis-description">Descripción del diagnóstico *</label><textarea id="new-diagnosis-description" name="description" required maxlength="10000" rows="4"></textarea>
<p id="new-diagnosis-error" role="alert" hidden></p>
<footer><button type="button" data-close-diagnosis>Cancelar</button><button type="submit">Guardar y seleccionar</button></footer>
</form></dialog>
<style>
.access-results-page .diagnosis-title-actions{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 15px;text-align:left}
.new-diagnosis-button{display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;min-height:34px;padding:6px 12px;border:1px solid #9bc5ac;border-radius:7px;background:#fff;color:#196640;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer}
.new-diagnosis-button:hover{background:#def1e5;border-color:#448f64}
html[data-theme=dark] .new-diagnosis-button{background:#203a2d;border-color:#548367;color:#c3f1d4}
@media(max-width:600px){.access-results-page .diagnosis-title-actions{flex-wrap:wrap;gap:8px}.new-diagnosis-button{margin-left:auto}#new-diagnosis-dialog footer{flex-wrap:wrap}}
#new-diagnosis-dialog{position:fixed;inset:0;margin:auto;box-sizing:border-box;width:540px;overflow-y:auto;font-family:"Segoe UI",sans-serif;font-size:14px;max-width:calc(100vw - 32px);max-height:85vh;padding:24px;border:1px solid #cbdcd2;border-radius:14px;background:#fff;color:#244638;box-shadow:0 24px 70px #0004}
#new-diagnosis-dialog::backdrop{background:#10251bcc}#new-diagnosis-dialog header{display:flex;gap:16px;justify-content:space-between;margin-bottom:20px}#new-diagnosis-dialog h2{font-size:19px;margin:0 0 7px}#new-diagnosis-dialog header p{font-size:13px;margin:0;line-height:1.5;color:#60766a}#new-diagnosis-dialog header button{align-self:start;border:0;background:transparent;color:inherit;font-size:24px}
#new-diagnosis-dialog label{display:block;font-size:13px;font-weight:600;margin:14px 0 7px}#new-diagnosis-dialog :is(input,textarea){box-sizing:border-box;width:100%;padding:11px 12px;border:1px solid #cbdcd2;border-radius:8px;background:#fff;color:inherit;font:inherit;text-transform:uppercase}#new-diagnosis-dialog textarea{resize:vertical}#new-diagnosis-dialog footer{display:flex;justify-content:flex-end;gap:10px;margin-top:22px}#new-diagnosis-dialog footer button{border:1px solid #cbdcd2;border-radius:8px;padding:10px 15px;background:transparent;color:inherit;font:inherit;cursor:pointer}#new-diagnosis-dialog footer button[type=submit]{background:#12834d;border-color:#12834d;color:#fff}#new-diagnosis-error{font-size:13px;color:#b42330}#new-diagnosis-dialog button:disabled{opacity:.6;cursor:wait}
html[data-theme=dark] #new-diagnosis-dialog{background:#1d2b30;color:#e4f2eb;border-color:#465c51}html[data-theme=dark] #new-diagnosis-dialog :is(input,textarea){background:#142126;border-color:#465c51}html[data-theme=dark] #new-diagnosis-dialog header p{color:#b6ccc0}html[data-theme=dark] #new-diagnosis-error{color:#ffb2bc}
</style>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const dialog=document.getElementById('new-diagnosis-dialog'),form=document.getElementById('new-diagnosis-form'),error=document.getElementById('new-diagnosis-error');let table,opener;
 document.querySelectorAll('[data-new-diagnosis]').forEach(button=>button.addEventListener('click',()=>{opener=button;table=button.closest('[data-diagnosis-table]');form.reset();error.hidden=true;dialog.showModal();}));
 dialog.querySelectorAll('[data-close-diagnosis]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
 dialog.addEventListener('close',()=>opener?.focus());
 form.addEventListener('submit',async event=>{
  event.preventDefault();const submit=form.querySelector('[type=submit]');submit.disabled=true;error.hidden=true;
  try{
   const response=await fetch(form.action,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:new FormData(form)});
   const data=await response.json();if(!response.ok)throw new Error(Object.values(data.errors||{}).flat().join(' ')||'No se pudo guardar el diagnóstico. Revise su sesión e inténtelo nuevamente.');
   const option=()=>{const item=new Option(`${data.code} — ${data.description}`,String(data.id));item.dataset.code=data.code;item.dataset.description=data.description;return item;};
   document.querySelectorAll('[data-diagnosis-table]').forEach(current=>{current.querySelector('.diagnosis-options').content.append(option());current.querySelectorAll('.diagnosis-line select').forEach(select=>select.add(option()));});
   let select=[...table.querySelectorAll('.diagnosis-line select')].find(item=>!item.value);
   if(!select){table.querySelector('.add-line').click();select=table.querySelector('.diagnosis-line:last-child select');}
   window.jQuery(select).select2('close').val(String(data.id)).trigger('change');dialog.close();
  }catch(failure){error.textContent=failure.message||'No se pudo guardar. Inténtelo nuevamente.';error.hidden=false;}finally{submit.disabled=false;}
 });
});
</script>