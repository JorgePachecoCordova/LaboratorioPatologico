<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$user = App\Models\User::factory()->create(['role' => 'administrator']);
Illuminate\Support\Facades\Auth::login($user);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$patient = App\Models\Patient::create(['full_name' => 'PACIENTE PAGINACION', 'identity_document' => 'PAG-1', 'is_active' => true]);
for ($i = 1; $i <= 26; $i++) {
    App\Models\Patient::create(['full_name' => 'PACIENTE COINCIDENCIA '.$i, 'identity_document' => 'PAG-'.($i + 1), 'is_active' => true]);
    App\Models\PathologyRequest::create(['patient_id' => $patient->id, 'module' => 'R', 'request_date' => '2026-10-13', 'request_number' => 'R-'.$i, 'sequence_year' => 2026, 'sequence_number' => $i, 'status' => 'registered']);
    App\Models\CytologyReport::create(['patient_id' => $patient->id, 'patient_name' => $patient->full_name, 'request_number' => 'SC-'.$i.'-2026', 'request_date' => '2026-10-13']);
    App\Models\AnatomopathologyReport::create(['patient_name' => $patient->full_name, 'report_number' => 'AP-'.$i, 'report_date' => '2026-10-13', 'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO']);
}
function taskRequest(string $uri): Illuminate\Http\Request {
    $request = Illuminate\Http\Request::create($uri);
    $request->setUserResolver(fn () => Illuminate\Support\Facades\Auth::user());
    app()->instance('request', $request);
    Illuminate\Pagination\Paginator::currentPageResolver(fn ($name) => max(1, $request->integer($name, 1)));
    Illuminate\Pagination\Paginator::queryStringResolver(fn () => $request->query());
    return $request;
}
function taskCheck(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
    echo "PASS: $label\n";
}
$view = app(App\Http\Controllers\SolicitudController::class)->historial(taskRequest('/historial-pacientes?paciente='.$patient->id.'&requests_page=2&cytology_page=2&anatomopathology_page=2'));
$data = $view->getData();
foreach (['requests', 'cytologyReports', 'anatomopathologyReports'] as $key) taskCheck($data[$key]->total() === 26 && $data[$key]->count() === 6, "$key: page 2 has 6 of 26 records");
taskCheck(str_contains($view->render(), 'requests_page'), 'History renders pagination');
$view = app(App\Http\Controllers\CytologyReportController::class)->index(taskRequest('/informes-citologicos?q=PAGINACION&page=3'));
taskCheck($view->getData()['matches']->count() === 6 && $view->getData()['matches']->total() === 26, 'Cytology search page 3');
taskCheck(str_contains($view->render(), '26 coincidencias'), 'Cytology view renders');
$response = app(App\Http\Controllers\DailyRegisterController::class)(taskRequest('/solicitudes/registro-diario?date=2026-10-13&module=R&page=2'));
taskCheck($response->original->getData()['records']->count() === 1 && str_contains($response->getContent(), 'Total de solicitudes: 26'), 'Daily register page 2 and full total');
$response = app(App\Http\Controllers\DailyRegisterController::class)(taskRequest('/solicitudes/registro-diario?date=2026-10-13&module=R&print=1'));
taskCheck($response->original->getData()['records']->count() === 26, 'Printing includes all daily records');
$view = app(App\Http\Controllers\SolicitudController::class)->index(taskRequest('/solicitudes?patient_query=COINCIDENCIA&patients_page=2'));
taskCheck($view->getData()['patientMatches']->count() === 6 && $view->getData()['patientMatches']->total() === 26, 'Patient search page 2');
taskCheck(str_contains($view->render(), 'patients_page'), 'Patient search renders');
taskRequest('/pacientes/'.$patient->id.'/solicitudes?page=2');
$json = app(App\Http\Controllers\SolicitudController::class)->historialPaciente($patient)->getData(true);
taskCheck(count($json['requests']) === 6 && $json['pagination']['total'] === 26 && $json['pagination']['current_page'] === 2, 'JSON patient history pagination');
