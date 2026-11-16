<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());

use App\Models\{User, AnatomopathologyReport};
use App\Http\Controllers\AnatomopathologyReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

Auth::login(User::factory()->create(['role' => 'administrator']));
$controller = app(AnatomopathologyReportController::class);
try {
    foreach (['2026-12-31', '2027-01-01'] as $date) {
        Carbon::setTestNow($date.' 12:00:00');
        $view = $controller->index(Request::create('/anatomopatologico?nuevo=1'))->render();
        if (!preg_match('/name="report_date"[^>]*value="'.preg_quote($date, '/').'"/', $view)) {
            throw new RuntimeException('La fecha inicial no corresponde al día actual.');
        }
        $request = Request::create('/anatomopatologico', 'POST', [
            'registration_number' => '1', 'report_date' => $date,
            'patient_name' => 'PACIENTE DE PRUEBA', 'macroscopic_description' => 'PRUEBA',
            'microscopic_description' => 'PRUEBA', 'diagnosis' => 'PRUEBA',
        ]);
        $request->setLaravelSession(app('session')->driver());
        $controller->store($request);
        $expected = '001-'.substr($date, 2, 2);
        if (AnatomopathologyReport::latest('id')->first()->report_number !== $expected) {
            throw new RuntimeException('Gestión de registro incorrecta.');
        }
        echo 'PASS: fecha automática '.$date.' y registro '.$expected."\n";
    }
    $legacy = AnatomopathologyReport::create([
        'registration_number' => '0001-2000', 'report_number' => '0001-2000', 'report_date' => '2000-12-12',
        'patient_name' => 'PACIENTE HISTORICO DE PRUEBA', 'macroscopic_description' => 'PRUEBA',
        'microscopic_description' => 'PRUEBA', 'diagnosis' => 'PRUEBA',
    ]);
    $print = $controller->print($legacy)->render();
    if (!str_contains($print, '0001-2000') || $legacy->fresh()->report_date->format('Y') !== '2000') {
        throw new RuntimeException('Se alteró la gestión histórica.');
    }
    echo "PASS: la reimpresión conserva la fecha y el número históricos.\n";
} finally {
    Carbon::setTestNow();
}
