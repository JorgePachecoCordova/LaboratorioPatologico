<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$controller = app(App\Http\Controllers\AnatomopathologyReportController::class);
$payload = ['registration_number' => '125', 'report_date' => '2026-10-16', 'patient_name' => 'PRUEBA', 'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO'];
$make = function ($data) {
    $request = Illuminate\Http\Request::create('/anatomopatologico', 'POST', $data);
    $request->setLaravelSession(app('session')->driver());
    return $request;
};
$controller->store($make($payload));
$report = App\Models\AnatomopathologyReport::firstOrFail();
if ($report->registration_number !== '125-26' || $report->report_number !== '125-26') throw new RuntimeException('Manual number not used');
try { $controller->store($make($payload)); throw new RuntimeException('Duplicate accepted'); }
catch (Illuminate\Validation\ValidationException $e) { if (!isset($e->errors()['registration_number'])) throw $e; }
try { $controller->store($make(array_replace($payload, ['registration_number' => '']))); throw new RuntimeException('Empty number accepted'); }
catch (Illuminate\Validation\ValidationException $e) { if (!isset($e->errors()['registration_number'])) throw $e; }
$controller->update($make($payload), $report);
$controller->update($make(array_replace($payload, ['registration_number' => '209', 'report_date' => '2027-01-02'])), $report);
$report->refresh();
if ($report->report_number !== '209-27' || $report->registration_number !== '209-27') throw new RuntimeException('Edit/year mismatch');
$html = $controller->print($report)->render();
if (!str_contains($html, '209-27')) throw new RuntimeException('Printed manual number missing');
echo "PASS: número manual, gestión en el informe, edición, registro obligatorio y duplicados.\n";
