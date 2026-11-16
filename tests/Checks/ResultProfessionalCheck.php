<?php
require __DIR__.'/ProfileCheck.php';

use App\Models\{User, Patient, PathologyRequest, PathologyResult, ResultAudit};
use App\Support\AccessRoles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

function checkResult($ok, $message) { if (!$ok) throw new RuntimeException($message); }
$editor = User::create(['name'=>'Editor', 'username'=>'professional_editor', 'role'=>'reception', 'is_active'=>true, 'password'=>'Test12345']);
Auth::login($editor);
$patient = Patient::create(['identity_document'=>'PROF-RESULT', 'full_name'=>'PACIENTE PRUEBA', 'is_active'=>true]);
$record = PathologyRequest::create(['patient_id'=>$patient->id,'module'=>'R','sequence_type'=>'R','sequence_year'=>2026,'sequence_number'=>1,'request_number'=>'000001-R','request_date'=>'2026-01-01','request_type'=>'CITOLOGIA','sample_quantity'=>1,'status'=>'received']);
$sample = $record->samples()->create(['sample_number'=>1,'sample_type'=>'CITOLOGIA','status'=>'received']);
$controller = app(App\Http\Controllers\ResultadoController::class);
$payload = ['sample_id'=>$sample->id,'result_date'=>'2026-01-02','reader'=>'ELENA','expected_revision'=>0,'status'=>'validated','diagnosis_entries'=>[['code'=>'TEST','description'=>'DIAGNOSTICO DE PRUEBA']]];
$make = function($data) use ($editor) { $r=Request::create('/', 'POST', $data);$r->setUserResolver(fn()=>$editor);$r->setLaravelSession(app('session')->driver());return $r; };
$invalid = function($data,$field) use($make,$controller,$record) {
    try {$controller->store($make($data),$record);throw new RuntimeException('Accepted invalid '.$field);}
    catch(ValidationException $e){checkResult(isset($e->errors()[$field]),'Wrong validation '.$field);}
};
$invalid(array_replace($payload,['result_date'=>'2025-12-31']),'result_date');
$invalid(array_replace($payload,['result_date'=>now()->addDay()->format('Y-m-d')]),'result_date');
$invalid(array_replace($payload,['diagnosis_entries'=>[$payload['diagnosis_entries'][0],$payload['diagnosis_entries'][0]]]),'diagnosis_entries');
checkResult(PathologyResult::count()===0,'Invalid data persisted');
$controller->store($make($payload),$record);
$result=PathologyResult::firstOrFail();
checkResult($result->revision===1 && ResultAudit::count()===1,'Missing initial audit');
checkResult(!$result->cytologyReport()->exists(),'Unentered clinical findings created');
$invalid($payload,'expected_revision');
$controller->store($make(array_replace($payload,['expected_revision'=>1,'notes'=>'CORRECCION'])),$record);
checkResult($result->fresh()->revision===2 && ResultAudit::count()===2,'Correction not audited');
$record->update(['status'=>'cancelled']);
$invalid(array_replace($payload,['expected_revision'=>2]),'sample_id');
checkResult($result->fresh()->revision===2,'Cancelled result changed');
foreach(['reception','result_reader'] as $role) checkResult(AccessRoles::allows(new User(['role'=>$role,'is_active'=>true]),'resultados.store'),'Editor access lost');
checkResult(!AccessRoles::allows(new User(['role'=>'cytology_office','is_active'=>true]),'solicitudes.show'),'Restricted request access');
$response=(new App\Http\Middleware\AuthSession())->handle($make([]), fn()=>response('ok'));
checkResult(str_contains($response->headers->get('Cache-Control'),'no-store'),'Clinical page cached');
checkResult($response->headers->get('X-Content-Type-Options')==='nosniff','Missing response protection');
echo "PASS: dates, duplicate codes, rollback, shared editing, audit, stale edits, cancelled requests, clinical defaults, permissions and private cache.\n";