<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\{User,HospitalService,CytologyReport};
use App\Http\Requests\SaveCytologyRequest;
use Illuminate\Support\Facades\Auth;
$u=User::create(['name'=>'Citologia','username'=>'photo_cytology','role'=>'cytology_office','is_active'=>true,'password'=>'Prueba_123']);Auth::login($u);
$catalogRequest=Illuminate\Http\Request::create('/informes-citologicos/catalogos','POST',['type'=>'sample','name'=>'MUESTRA NUEVA DE PRUEBA']);
$catalogRequest->setUserResolver(fn()=>$u);
$catalogController=app(App\Http\Controllers\CytologyReportController::class);
if($catalogController->storeCatalogOption($catalogRequest)->getStatusCode()!==201)throw new RuntimeException('Sample creation failed');
if(!App\Models\CytologyCatalogOption::where('type','sample')->where('name','MUESTRA NUEVA DE PRUEBA')->exists())throw new RuntimeException('Sample not persisted');
try{$catalogController->storeCatalogOption($catalogRequest);throw new RuntimeException('Duplicate sample accepted');}catch(Illuminate\Validation\ValidationException $e){}
$service=HospitalService::firstOrCreate(['normalized_name'=>'MEDICINA INTERNA'],['name'=>'MEDICINA INTERNA','is_active'=>true]);
$data=['patient_name'=>'MARGARITA JUAREZ','patient_age'=>21,'request_date'=>'2026-07-07','report_date'=>'2026-07-07','hospital_service_id'=>$service->id,'sample_type_code'=>'ESPUTO','clinical_diagnosis'=>'DIAGNOSTICO CLINICO','clinical_data'=>'DATOS CLINICOS','description'=>'DESCRIPCION DE PRUEBA','conclusion'=>'CONCLUSION DE PRUEBA','note'=>'NOTA DE PRUEBA','report_action'=>'validate'];
$r=SaveCytologyRequest::create('/informes-citologicos','POST',$data);$r->setUserResolver(fn()=>$u);$r->setLaravelSession(app('session')->driver());
$v=validator($data,$r->rules());if($v->fails())throw new RuntimeException(json_encode($v->errors()->messages()));$r->setValidator($v);
app(App\Http\Controllers\CytologyReportController::class)->store($r);
$report=CytologyReport::where('patient_name','MARGARITA JUAREZ')->firstOrFail();
if(!$report->report_number||$report->status!=='validated')throw new RuntimeException('Report not saved');
if(!preg_match('/^\d{6}$/', $report->registration_number))throw new RuntimeException('Missing automatic registration');
$html=app(App\Http\Controllers\CytologyReportController::class)->print($report)->render();
if(!str_contains($html, 'N.º '.$report->registration_number))throw new RuntimeException('Missing printed registration');
$numbers=app(App\Services\CytologyNumberService::class);
$next=$numbers->next('registration',2027);
if((int)$next !== (int)$report->registration_number+1)throw new RuntimeException('Registration must continue across years');
try { Illuminate\Support\Facades\DB::transaction(function()use($numbers){$numbers->next('registration',2027);throw new RuntimeException('rollback');}); }catch(RuntimeException $e){if($e->getMessage()!=='rollback')throw $e;}
if((int)$numbers->next('registration',2027)!==(int)$next+1)throw new RuntimeException('Registration rollback failed');
if(!validator(['registration_number'=>'003007'],(new SaveCytologyRequest())->rules())->errors()->has('registration_number'))throw new RuntimeException('Manual registration allowed');
foreach(['Fecha Solicitud:','DESCRIPCIÓN:','CONCLUSIÓN:','DATOS CLINICOS','DESCRIPCION DE PRUEBA','CONCLUSION DE PRUEBA','ANATOMOPATÓLOGO'] as $text)if(!str_contains($html,$text))throw new RuntimeException('Missing '.$text);
foreach(['care_type','sample_taken_at','sample_received_at','clinical_history_number'] as $key)if($report->$key!==null)throw new RuntimeException('Invented field '.$key);
$search=Illuminate\Http\Request::create('/informes-citologicos','GET',['q'=>'MARIA INEXISTENTE']);$search->setUserResolver(fn()=>$u);$search->setLaravelSession(app('session')->driver());app()->instance('request',$search);
$searchHtml=app(App\Http\Controllers\CytologyReportController::class)->index($search)->render();
if(!preg_match('/<section class="cy-card"\s*>/',$searchHtml)||!preg_match('/name="patient_name"[^>]*value=""/',$searchHtml)||!str_contains($searchHtml,'name="description"'))throw new RuntimeException('No-match form not visible');
echo "PASS: single cytology form saves only reference fields, automatic numbering and matching printable report.\n";
