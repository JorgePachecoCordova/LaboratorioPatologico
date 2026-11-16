<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

use App\Models\{Patient, PathologyRequest, ReceptionBatch, User};
use App\Services\{RequestNumberService, ReceptionNumberService};
use App\Http\Controllers\{SolicitudController, NumberingController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Validation\ValidationException;

function verifyNumbering(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$admin = User::create(['name' => 'Prueba', 'username' => 'numbering_admin', 'role' => 'administrator', 'password' => 'Test_12345', 'is_active' => true]);
Auth::login($admin);
$make = function (array $data) use ($admin) {
    $r = Request::create('/', 'POST', $data);
    $r->setUserResolver(fn () => $admin);
    $r->setLaravelSession(app('session')->driver());
    return $r;
};
$settings = app(NumberingController::class);
foreach (['request' => 987, 'exam' => 14342] as $kind => $last) {
    $settings->update($make(['module' => 'R', 'kind' => $kind, 'year' => 2026, 'last_number' => $last, 'expected_last' => 0, 'reason' => 'Continuidad de prueba Access']));
}
$patient = Patient::create(['identity_document' => 'NUMBER-TEST-1', 'full_name' => 'PACIENTE FICTICIO', 'is_active' => true]);
$payload = ['patient_id' => $patient->id, 'module' => 'R', 'request_date' => '2026-10-30', 'sample_quantity' => 1];
$store = app(SolicitudController::class);
$store->store($make($payload), app(RequestNumberService::class));
$first = PathologyRequest::firstOrFail();
verifyNumbering($first->request_number === '014343-R', 'No continuó el examen de Access');
verifyNumbering($first->receptionBatch->number === 988, 'No continuó la solicitud de Access');
verifyNumbering($first->samples()->count() === 1, 'No creó la muestra');
$secondPatient = Patient::create(['identity_document' => 'NUMBER-TEST-2', 'full_name' => 'SEGUNDO PACIENTE FICTICIO', 'is_active' => true]);
$store->store($make(array_replace($payload, ['patient_id' => $secondPatient->id, 'reception_batch_id' => $first->reception_batch_id])), app(RequestNumberService::class));
$second = PathologyRequest::latest('id')->first();
verifyNumbering($second->request_number === '014344-R' && $second->reception_batch_id === $first->reception_batch_id, 'No agrupó con examen independiente');
verifyNumbering(ReceptionBatch::count() === 1, 'Consumió un número de grupo al añadir paciente');
foreach ([['module' => 'U'], ['request_date' => '2026-10-31'], ['module' => 'G'], []] as $change) {
    try {
        $store->store($make(array_replace($payload, ['reception_batch_id' => $first->reception_batch_id], $change)), app(RequestNumberService::class));
        throw new RuntimeException('Permitió agrupación incompatible');
    } catch (ValidationException $e) { verifyNumbering(isset($e->errors()['reception_batch_id']), 'Error de validación inesperado'); }
}
verifyNumbering(PathologyRequest::count() === 2, 'Persistió un registro incompatible');
$store->store($make(array_replace($payload, ['request_date' => '2026-10-31'])), app(RequestNumberService::class));
verifyNumbering(PathologyRequest::latest('id')->first()->receptionBatch->number === 989, 'No creó un envío nuevo del mismo origen');
foreach (['U', 'G'] as $module) {
    $store->store($make(array_replace($payload, ['module' => $module])), app(RequestNumberService::class));
    $row = PathologyRequest::latest('id')->first();
    verifyNumbering($row->sequence_number === 1 && $row->receptionBatch->number === 1, 'Mezcló contadores entre áreas');
}
$gyn = PathologyRequest::where('module', 'G')->first();
$groupService = app(ReceptionNumberService::class);
verifyNumbering($groupService->assign(['module' => 'G', 'request_date' => '2026-10-01', 'reception_batch_id' => $gyn->reception_batch_id], $admin->id)->id === $gyn->reception_batch_id, 'No permite agrupación mensual ginecológica');
foreach ([['last_number' => 1, 'expected_last' => 14345], ['last_number' => 15000, 'expected_last' => 0]] as $change) {
    try {
        $settings->update($make($change + ['module' => 'R', 'kind' => 'exam', 'year' => 2026, 'reason' => 'Prueba de contador']));
        throw new RuntimeException('Aceptó reducción o actualización obsoleta');
    } catch (ValidationException $e) { }
}
try {
    DB::transaction(function () use ($groupService, $admin) {
        $groupService->assign(['module' => 'R', 'request_date' => '2027-01-01'], $admin->id);
        app(RequestNumberService::class)->next('R', 2027);
        throw new RuntimeException('rollback');
    });
} catch (RuntimeException $e) { if ($e->getMessage() !== 'rollback') throw $e; }
verifyNumbering($groupService->assign(['module' => 'R', 'request_date' => '2027-01-01'], $admin->id)->number === 1, 'Rollback consumió número de solicitud');
verifyNumbering(app(RequestNumberService::class)->next('R', 2027)['sequence_number'] === 1, 'Rollback consumió número de examen');
verifyNumbering(!App\Support\AccessRoles::allows(new User(['role' => 'reception', 'is_active' => true]), 'configuracion.numeracion.update'), 'Recepción puede modificar contadores');
view()->share('errors', new \Illuminate\Support\ViewErrorBag());
$results = app(App\Http\Controllers\ResultadoController::class);
foreach (['R', 'U', 'G'] as $module) {
    $record = PathologyRequest::where('module', $module)->first();
    $html = $results->create($make([]), $record)->render();
    verifyNumbering(str_contains($html, $record->request_number) && str_contains($html, $record->receptionBatch->display_number), 'Formulario no distingue números de '.$module);
    $results->store($make(['sample_id' => $record->samples()->first()->id, 'result_date' => '2026-10-30', 'reader' => 'ELENA', 'expected_revision' => 0, 'status' => 'validated', 'diagnosis_entries' => [['code' => 'TEST', 'description' => 'PRUEBA DE NUMERACION']]]), $record);
    $result = $record->samples()->first()->pathologyResult;
    $print = $results->imprimir($result)->render();
    verifyNumbering(str_contains($print, $record->request_number) && str_contains($print, $record->receptionBatch->display_number), 'Impresión no distingue números de '.$module);
    if ($module === 'U') {
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$print);
        $xpath = new DOMXPath($document);
        verifyNumbering($xpath->query('//table[contains(@class,"urban-meta")]//th')->length === 5, 'La tabla urbana debe conservar cinco columnas');
    }
    if (in_array('--visual-fixtures', $argv, true)) {
        $record->area()->associate(App\Models\Area::firstOrCreate(['name' => 'ALLINTARUWANA SAN ROQUE', 'normalized_name' => 'ALLINTARUWANA SAN ROQUE']));
        $record->save();
        $record->unsetRelation('area');
        $result->sample->setRelation('pathologyRequest', $record);
        $fixtureDir = dirname(__DIR__, 2).'/.private-report-review';
        if (!is_dir($fixtureDir)) mkdir($fixtureDir, 0777, true);
        file_put_contents($fixtureDir.'/informe-'.$module.'.html', str_replace(asset('img/logo-hospital.png'), '/logo-hospital.png', $results->imprimir($result)->render()));
        $record->reception_batch_id = null;
        $record->setRelation('receptionBatch', null);
        file_put_contents($fixtureDir.'/historico-'.$module.'.html', str_replace(asset('img/logo-hospital.png'), '/logo-hospital.png', $results->imprimir($result)->render()));
        $record->refresh();
    }
    verifyNumbering($record->fresh()->request_number === $record->request_number, 'Guardar resultado cambió el examen');
    $exporter = app(App\Support\PathologyResultExcelExporter::class);
    foreach ([$exporter->create($result), $exporter->createConsolidated(collect([$result]))] as $file) {
        $zip = new ZipArchive();
        verifyNumbering($zip->open($file) === true, 'Exportación inválida');
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        verifyNumbering(simplexml_load_string($xml) !== false, 'XML de exportación inválido');
        verifyNumbering(str_contains($xml, $record->request_number) && str_contains($xml, $record->receptionBatch->display_number), 'Exportación no distingue los números');
        $zip->close();
        unlink($file);
    }
}
$view = $settings->index($make(['year' => 2026]));
verifyNumbering(str_contains($view->render(), 'Correlativos de solicitudes y exámenes'), 'No renderiza configuración');
$filtered = $results->index(Request::create('/resultados', 'GET', ['batch' => $first->reception_batch_id]));
verifyNumbering($filtered->getData()['pathologyRequests']->total() === 2, 'La consulta de un grupo mezcla exámenes de otros envíos');
verifyNumbering(str_contains($filtered->render(), $first->receptionBatch->display_number), 'No identifica el grupo en la consulta');
verifyNumbering(str_contains($store->index($make([]))->render(), 'Nueva solicitud (número automático)'), 'No renderiza selección de grupos');
echo "PASS: continuidad Access, grupos y exámenes independientes, U/R/G, fechas, agrupación mensual, rollback, contadores obsoletos, permisos, consulta, formularios, impresión y Excel.\n";
