<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());

use App\Models\{User, Patient, PathologyRequest, PathologyResult, ResultAudit};
use App\Support\AccessRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function verify($condition, $message): void { if (!$condition) throw new RuntimeException($message); }
$users = [];
foreach (['administrator','reception','result_reader','cytology_office','anatomopathology_office','technician'] as $role) {
    $users[$role] = User::create(['name'=>$role, 'username'=>$role, 'role'=>$role, 'password'=>'Test12345', 'is_active'=>true]);
}
$secondReader = User::create(['name'=>'Segundo lector', 'username'=>'segundo_lector', 'role'=>'result_reader', 'password'=>'Test12345', 'is_active'=>true]);
$matrix = [
    'reception' => ['solicitudes.index','solicitudes.store','muestras.store','resultados.index','resultados.imprimir','resultados.excel'],
    'result_reader' => ['resultados.index','resultados.create','resultados.store','resultados.imprimir','resultados.excel'],
    'cytology_office' => ['citologia.index','citologia.store','citologia.informe.update','citologia.imprimir','citologia.reportes.estadistico'],
    'anatomopathology_office' => ['anatomopatologia.index','anatomopatologia.store','anatomopatologia.update','anatomopatologia.imprimir'],
    'technician' => [],
];
$routes = array_unique(array_merge(array_merge(...array_values($matrix)), ['configuracion.index','solicitudes.destroy','resultados.destroy','historial.index']));
foreach ($matrix as $role => $allowed) {
    foreach ($routes as $name) {
        verify(AccessRoles::allows($users[$role], $name) === in_array($name, $allowed, true), 'Incorrect permission '.$role.' '.$name);
        $request = Request::create('/'); $request->setUserResolver(fn () => $users[$role]);
        $route = new Illuminate\Routing\Route('GET', '/', fn () => null); $route->name($name); $request->setRouteResolver(fn () => $route);
        try { (new App\Http\Middleware\ModuleAccess())->handle($request, fn () => response('ok')); verify(in_array($name, $allowed, true), 'Middleware allowed forbidden route'); }
        catch (HttpException $e) { verify($e->getStatusCode() === 403 && !in_array($name, $allowed, true), 'Unexpected denial'); }
    }
}
verify(!AccessRoles::allows(new User(['role'=>'result_reader','is_active'=>false]), 'resultados.store'), 'Inactive reader admitted');
$patient = Patient::create(['identity_document'=>'AUDIT-CHECK', 'full_name'=>'PACIENTE DE PRUEBA', 'is_active'=>true]);
$solicitud = PathologyRequest::create(['patient_id'=>$patient->id, 'module'=>'R', 'sequence_type'=>'R', 'sequence_year'=>2026, 'sequence_number'=>1, 'request_number'=>'000001-R', 'request_type'=>'CITOLOGIA', 'request_date'=>'2026-10-25', 'sample_quantity'=>1]);
$sample = $solicitud->samples()->create(['sample_number'=>1,'sample_type'=>'CITOLOGIA','status'=>'received']);
$controller = app(App\Http\Controllers\ResultadoController::class);
$payload = ['sample_id'=>$sample->id, 'result_date'=>'2026-10-25', 'status'=>'validated', 'expected_revision'=>0, 'diagnosis_entries'=>[['code'=>'TEST','description'=>'DIAGNOSTICO ORIGINAL']]];
$make = function ($user, $data) { Auth::login($user); $r = Request::create('/', 'POST', $data); $r->setUserResolver(fn () => $user); $r->setLaravelSession(app('session')->driver()); return $r; };
try { $controller->store($make($users['reception'], $payload), $solicitud); throw new RuntimeException('Reception wrote a result'); }
catch (HttpException $e) { verify($e->getStatusCode()===403, 'Wrong denial'); }
verify(PathologyResult::count()===0, 'Unauthorized write persisted');
$controller->store($make($users['result_reader'], $payload), $solicitud);
$result = PathologyResult::firstOrFail();
verify($result->revision===1 && $result->recorded_by===$users['result_reader']->id && ResultAudit::count()===1, 'Initial audit missing');
$edit = array_replace($payload, ['expected_revision'=>1, 'correction_reason'=>'Corrección de transcripción', 'diagnosis_entries'=>[['code'=>'CORR','description'=>'DIAGNOSTICO CORREGIDO']]]);
try { $controller->store($make($secondReader, $edit), $solicitud); throw new RuntimeException('Other reader edited result'); }
catch (HttpException $e) { verify($e->getStatusCode()===403, 'Wrong ownership denial'); }
try { $controller->store($make($users['result_reader'], array_replace($edit, ['correction_reason'=>null])), $solicitud); throw new RuntimeException('Correction without reason accepted'); }
catch (ValidationException $e) { verify(isset($e->errors()['correction_reason']), 'Missing reason validation'); }
$controller->store($make($users['administrator'], $edit), $solicitud);
$result->refresh();
verify($result->revision===2 && $result->recorded_by===$users['result_reader']->id, 'Author overwritten');
$audit = ResultAudit::latest('id')->first();
verify($audit->actor_id===$users['administrator']->id && $audit->before['result']['diagnosis']==='DIAGNOSTICO ORIGINAL' && $audit->after['result']['diagnosis']==='DIAGNOSTICO CORREGIDO', 'Audit attribution or snapshots incorrect');
try { $controller->store($make($users['result_reader'], $edit), $solicitud); throw new RuntimeException('Stale write accepted'); }
catch (ValidationException $e) { verify(isset($e->errors()['expected_revision']), 'Stale version not detected'); }
verify(ResultAudit::count()===2 && $result->fresh()->revision===2, 'Denied write changed result');
try { $audit->update(['reason'=>'ALTERED']); throw new RuntimeException('Audit was mutable'); } catch (LogicException $e) {}
app('events')->listen('eloquent.creating: '.ResultAudit::class, function () { throw new RuntimeException('SIMULATED_AUDIT_FAILURE'); });
try {
    $controller->store($make($users['result_reader'], array_replace($edit, ['expected_revision'=>2, 'notes'=>'NO DEBE GUARDARSE'])), $solicitud);
    throw new RuntimeException('Audit failure was ignored');
} catch (RuntimeException $e) { verify($e->getMessage()==='SIMULATED_AUDIT_FAILURE', 'Unexpected failure'); }
verify($result->fresh()->revision===2 && $result->fresh()->notes===null && ResultAudit::count()===2, 'Result persisted without audit');
Auth::login($users['result_reader']);
$view = $controller->create($make($users['result_reader'], []), $solicitud)->render();
verify(str_contains($view,'expected_revision') && str_contains($view,'Historial de cambios'), 'Audit form missing');
Auth::login($users['reception']);
$list = $controller->index($make($users['reception'], []))->render();
verify(!str_contains($list,'id="new-result-toggle"') && !str_contains($list,'resultados/'.$solicitud->id.'/crear'), 'Reception sees result editor');
foreach (['reception','result_reader','cytology_office','anatomopathology_office'] as $role) {
    Auth::login($users[$role]);
    app(App\Http\Controllers\DashboardController::class)->index($make($users[$role], []))->render();
}
Auth::login($users['administrator']);
$config = app(App\Http\Controllers\ConfiguracionController::class);
foreach (array_keys(AccessRoles::LABELS) as $role) {
    $config->storeUsuario($make($users['administrator'], ['name'=>'Cuenta '.$role, 'username'=>'new_'.$role, 'password'=>'Test12345', 'password_confirmation'=>'Test12345', 'role'=>$role]));
    $created = User::where('username','new_'.$role)->firstOrFail();
    verify($created->role===$role && $created->hasRole($role), 'Role assignment incorrect');
}
$consultUser = $users['cytology_office'];
$consultUser->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate(AccessRoles::CONSULT_LABORATORY,'web'));
foreach (['solicitudes.index','solicitudes.show','solicitudes.registro-diario','resultados.index','resultados.imprimir','resultados.excel','citologia.store'] as $route) verify(AccessRoles::allows($consultUser,$route),'Consultation denied: '.$route);
foreach (['solicitudes.store','solicitudes.destroy','muestras.store','pacientes.store','resultados.create','resultados.store','resultados.destroy'] as $route) verify(!AccessRoles::allows($consultUser,$route),'Consultation granted writing: '.$route);
$consultRequest=$make($consultUser,[]);
$route = new Illuminate\Routing\Route('GET','/solicitudes',fn()=>null); $route->name('solicitudes.index'); $consultRequest->setRouteResolver(fn()=>$route);
app()->instance('request',$consultRequest);
$consultView=app(App\Http\Controllers\SolicitudController::class)->index($consultRequest)->render();
verify(str_contains($consultView,'Consulta de solicitudes') && !str_contains($consultView,'id="patient_register_form"') && !str_contains($consultView,'id="new-result-toggle"'), 'Consultation opened an editor');
$consultUser->revokePermissionTo(AccessRoles::CONSULT_LABORATORY);
verify(!AccessRoles::allows($consultUser,'resultados.index') && AccessRoles::allows($consultUser,'citologia.store'),'Consultation revoke changed main role');
$catalogController=app(App\Http\Controllers\CytologyReportController::class);
foreach (['service'=>'SERVICIO DE PRUEBA','physician'=>'MARÍA QUISPE','care'=>'ATENCION DE PRUEBA','sample'=>'MUESTRA DE PRUEBA'] as $type=>$name) {
    verify($catalogController->storeCatalogOption($make($consultUser,['type'=>$type,'name'=>$name]))->getStatusCode()===201,'Catalog creation failed');
    try { $catalogController->storeCatalogOption($make($consultUser,['type'=>$type,'name'=>$name])); throw new RuntimeException('Duplicate catalog accepted'); }
    catch (ValidationException $e) { verify(isset($e->errors()['name']),'Wrong duplicate validation'); }
}
foreach (['unknown'] as $type) {
    try { $catalogController->storeCatalogOption($make($consultUser,['type'=>$type,'name'=>'PRUEBA'])); throw new RuntimeException('Other catalog allowed'); }
    catch (HttpException $e) { verify($e->getStatusCode()===403,'Wrong catalog denial'); }
}
try { $catalogController->storeCatalogOption($make($users['reception'],['type'=>'service','name'=>'NO PERMITIDO'])); throw new RuntimeException('Reception added cytology catalog'); }
catch (HttpException $e) { verify($e->getStatusCode()===403,'Wrong role denial'); }
echo "PASS: permisos, consulta adicional sin escritura, revocación, autor, correcciones, versiones e historial.\n";
