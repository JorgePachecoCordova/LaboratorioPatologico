<?php
require __DIR__.'/ProfileCheck.php';
use App\Models\User;
use App\Models\CytologyDiagnosis;
use App\Support\AccessRoles;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
$controller=app(App\Http\Controllers\ResultDiagnosisController::class);
foreach(['administrator','reception','result_reader','cytology_office','anatomopathology_office'] as $role){
 $actor=User::create(['name'=>$role,'username'=>'diagnosis_'.$role,'password'=>'Prueba123','role'=>$role,'is_active'=>true]);
 $make=function($data)use($actor){$r=Request::create('/resultados/diagnosticos','POST',$data);$r->setUserResolver(fn()=>$actor);return $r;};
 $allowed=in_array($role,['administrator','reception','result_reader']);
 if(AccessRoles::allows($actor,'resultados.diagnosticos.store')!==$allowed)throw new RuntimeException('Permisos incorrectos');
 try{$response=$controller->store($make(['code'=>' ZZ-'.$role[0].$actor->id.' ', 'description'=>'nuevo diagnostico']));if(!$allowed)throw new RuntimeException('Escritura indebida');}
 catch(Symfony\Component\HttpKernel\Exception\HttpException $e){if($allowed||$e->getStatusCode()!==403)throw $e;continue;}
 $data=$response->getData(true);if($response->status()!==201||$data['description']!=='NUEVO DIAGNOSTICO'||!CytologyDiagnosis::find($data['id'])->is_active)throw new RuntimeException('No guardado');
 foreach([['code'=>strtolower($data['code']),'description'=>'DUPLICADO'],['code'=>'','description'=>'FALTA CODIGO'],['code'=>'OTRO','description'=>''],['code'=>'CODIGO INVALIDO','description'=>'PRUEBA']] as $invalid){
  try{$controller->store($make($invalid));throw new RuntimeException('Aceptó datos inválidos');}catch(ValidationException $e){}
 }
}
echo "PASS: catálogo persistente, normalización, duplicados, campos obligatorios y permisos por rol.\n";