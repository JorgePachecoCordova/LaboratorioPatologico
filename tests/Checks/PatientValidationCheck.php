<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','database.connections.sqlite.url'=>null,'session.driver'=>'array','cache.default'=>'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$controller = app(App\Http\Controllers\PacienteController::class);
$base = ['identity_document'=>'1234567-1A','full_name'=>'MARCELA QUISPE','birth_date'=>'1990-05-12','phone'=>'+591 71234567'];
foreach (['MARCELA7','123456','<script>Marcela</script>','---','A','MARÍA 22 QUISPE'] as $name) {
    try { $controller->store(Request::create('/','POST',array_replace($base,['full_name'=>$name]))); throw new RuntimeException('Invalid name accepted'); }
    catch (ValidationException $e) { if(!isset($e->errors()['full_name'])) throw $e; }
}
foreach (['birth_date'=>now()->addDay()->format('Y-m-d'),'phone'=>'hola123456','identity_document'=>'123<script>'] as $field=>$value) {
    try { $controller->store(Request::create('/','POST',array_replace($base,[$field=>$value]))); throw new RuntimeException('Invalid field accepted'); }
    catch (ValidationException $e) { if(!isset($e->errors()[$field])) throw $e; }
}
if(Patient::count()!==0) throw new RuntimeException('Invalid patient was stored');
foreach (['MARÍA JOSÉ MUÑOZ','ANA-MARÍA QUISPE',"MARÍA O’CONNOR", "JUAN D'ANGELO", 'J. PÉREZ'] as $i=>$name) {
    $controller->store(Request::create('/','POST',array_replace($base,['identity_document'=>(string)(1000000+$i),'full_name'=>$name])));
}
$controller->store(Request::create('/','POST',array_replace($base,['identity_document'=>' 1234567-1a ','full_name'=>'  MARCELA   QUISPE  '])));
$patient=Patient::where('identity_document','1234567-1A')->firstOrFail();
if($patient->full_name!=='MARCELA QUISPE') throw new RuntimeException('Normalization failed');
try { $controller->store(Request::create('/','POST',$base)); throw new RuntimeException('Duplicate CI accepted'); }
catch (ValidationException $e) { if(!isset($e->errors()['identity_document'])) throw $e; }
echo "PASS: nombres inválidos rechazados; tildes, ñ y apóstrofos aceptados; fecha, teléfono, CI y duplicados comprobados sin usar pacientes reales.\n";
