<?php
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$controller = app(App\Http\Controllers\AnatomopathologyReportController::class);
$validate = new ReflectionMethod($controller, 'validated');
$payload = ['report_date' => '2026-10-13', 'patient_name' => 'DEMO', 'macroscopic_description' => 'MACRO', 'microscopic_description' => 'MICRO', 'diagnosis' => 'DIAGNOSTICO', 'cap_protocol_enabled' => '1', 'cap_protocol' => ['specimen' => 'BIOPSIA', 'custom' => [['title' => 'Respuesta terapéutica', 'value' => 'Detalle personalizado'], ['title' => '<script>dato</script>', 'value' => '<b>texto</b>']]]];
$request = Illuminate\Http\Request::create('/anatomopatologico', 'POST', $payload);
app()->instance('request', $request);
$data = $validate->invoke($controller, $request);
if ($data['cap_protocol']['custom'][0]['title'] !== 'RESPUESTA TERAPEUTICA') throw new RuntimeException('Normalization failed');
$report = App\Models\AnatomopathologyReport::create($data + ['report_number' => 'AP-CUSTOM']);
$report->refresh();
$form = view('anatomopatologia.cap-form', compact('report'))->render();
$print = view('anatomopatologia.print', compact('report'))->render();
if (!str_contains($form, 'Añadir más protocolos') || !str_contains($form, 'RESPUESTA TERAPEUTICA') || !str_contains($print, 'DETALLE PERSONALIZADO') || str_contains($print, '<SCRIPT>')) throw new RuntimeException('Rendering or escaping failed');
echo "PASS: custom fields validate, persist, reopen and print safely.\n";
$payload['cap_protocol']['custom'][0]['title'] = '';
try { $validate->invoke($controller, Illuminate\Http\Request::create('/', 'POST', $payload)); throw new RuntimeException('Empty title accepted'); }
catch (Illuminate\Validation\ValidationException $error) { if (!isset($error->errors()['cap_protocol.custom.0.title'])) throw $error; }
echo "PASS: empty custom titles rejected.\n";
$payload['cap_protocol']['custom'] = [];
$data = $validate->invoke($controller, Illuminate\Http\Request::create('/', 'POST', $payload));
$report->update($data);
if ($report->fresh()->cap_protocol['custom'] !== []) throw new RuntimeException('Removal failed');
echo "PASS: removing custom fields clears saved content.\n";
