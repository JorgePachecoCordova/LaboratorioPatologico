<?php
// Current policy: only the Resultados module writes an audit trail.
require __DIR__.'/ResultProfessionalCheck.php';
use App\Models\{SystemAudit,ResultAudit,Patient,User};
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
function auditScopeCheck($ok,$message){if(!$ok)throw new RuntimeException($message);}
$initial=SystemAudit::count();
Patient::create(['identity_document'=>'NO-GLOBAL-LOG','full_name'=>'PRUEBA SIN AUDITORIA GLOBAL','is_active'=>true]);
$editor->update(['name'=>'EDITOR RENOMBRADO']);Auth::logout();Auth::login($editor);
auditScopeCheck(SystemAudit::count()===$initial,'Global auditing still active');
$record->update(['status'=>'validated']);
$beforeCount=ResultAudit::count();
$controller->store($make(array_replace($payload,['expected_revision'=>2,'notes'=>'CORRECCION'])),$record);
auditScopeCheck(ResultAudit::count()===$beforeCount && $result->fresh()->revision===2,'Unchanged save created a version');
$controller->store($make(array_replace($payload,['expected_revision'=>2,'notes'=>'NUEVA CORRECCION'])),$record);
auditScopeCheck(ResultAudit::count()===$beforeCount+1 && $result->fresh()->revision===3,'Real correction not audited');
$audit=ResultAudit::latest('id')->firstOrFail();
auditScopeCheck($audit->before['result']['notes']==='CORRECCION' && $audit->after['result']['notes']==='NUEVA CORRECCION','Before/after missing');
auditScopeCheck($audit->actor_id===$editor->id,'Wrong actor');
auditScopeCheck(SystemAudit::count()===$initial,'Duplicate global log produced');
$changes=App\Support\ResultAuditPresentation::changes($audit);
auditScopeCheck(count($changes)===1 && $changes[0]['field']==='Comentario','Display contains unchanged fields');
$readerRequest=Request::create('/configuracion/auditoria');$readerRequest->setUserResolver(fn()=>$editor);
try{app(App\Http\Controllers\SystemAuditController::class)->index($readerRequest);throw new RuntimeException('Audit exposed');}catch(Symfony\Component\HttpKernel\Exception\HttpException $e){auditScopeCheck($e->getStatusCode()===403,'Wrong access denial');}
$admin=User::create(['name'=>'Administrador','username'=>'audit_scope_admin','role'=>'administrator','is_active'=>true,'password'=>'Prueba_123']);
Auth::login($admin);$r=Request::create('/configuracion/auditoria');$r->setUserResolver(fn()=>$admin);app()->instance('request',$r);
$html=app(App\Http\Controllers\SystemAuditController::class)->index($r)->render();
auditScopeCheck(str_contains($html,'Historial de resultados') && str_contains($html,'PACIENTE PRUEBA'),'Result audit view failed');
echo "PASS: result-only audit, no duplicate global logs, no-op saves, changed-fields display, author, old/new data and admin access.\n";
