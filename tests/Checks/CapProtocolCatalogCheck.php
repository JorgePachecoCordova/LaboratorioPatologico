<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
view()->share('errors', new Illuminate\Support\ViewErrorBag());

use App\Models\{User, CapProtocolField, AnatomopathologyReport};
use App\Http\Controllers\{ConfiguracionController, AnatomopathologyReportController};
use App\Support\AnatomopathologyCapProtocol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

function catalogCheck(bool $value, string $message): void
{
    if (!$value) throw new RuntimeException($message);
    echo 'PASS: '.$message."\n";
}
function payloadRequest(array $data): Request
{
    $request = Request::create('/anatomopatologico', 'POST', $data);
    $request->setUserResolver(fn () => Auth::user());
    $request->setLaravelSession(app('session')->driver());
    return $request;
}
$admin = User::create(['name' => 'Administrador prueba', 'username' => 'catalog_admin',
    'password' => 'SoloPruebas123', 'role' => 'administrator', 'is_active' => true]);
Auth::login($admin);
$cfg = app(ConfiguracionController::class);
catalogCheck(CapProtocolField::count() === count(AnatomopathologyCapProtocol::FIELDS), 'Se conserva el catálogo inicial completo');
$cfg->storeProtocolo(payloadRequest(['name' => 'Respuesta terapéutica']));
$field = CapProtocolField::where('normalized_name', 'RESPUESTA TERAPEUTICA')->firstOrFail();
$form = view('anatomopatologia.cap-form', ['report' => null])->render();
catalogCheck(str_contains($form, '>RESPUESTA TERAPEUTICA</option>'), 'El protocolo creado aparece en el selector de informes nuevos');
$controller = app(AnatomopathologyReportController::class);
$payload = ['registration_number' => '401', 'report_date' => '2026-10-27',
    'patient_name' => 'PACIENTE DE PRUEBA', 'macroscopic_description' => 'TEXTO DE PRUEBA',
    'microscopic_description' => 'TEXTO DE PRUEBA', 'diagnosis' => 'CONCLUSION DE PRUEBA',
    'cap_protocol_enabled' => '1', 'cap_protocol' => ['specimen' => 'MUESTRA DE PRUEBA',
        'tumor_site' => 'SITIO DE PRUEBA', 'custom' => [5 => ['title' => 'RESPUESTA TERAPEUTICA', 'value' => 'CONTENIDO HISTORICO']]]];
$controller->store(payloadRequest($payload));
$report = AnatomopathologyReport::firstOrFail();
$before = $report->getRawOriginal('cap_protocol');
$cfg->toggleProtocolo($field);
$site = CapProtocolField::where('field_key', 'tumor_site')->firstOrFail();
$cfg->toggleProtocolo($site);
$form = view('anatomopatologia.cap-form', ['report' => null])->render();
catalogCheck(!str_contains($form, '>RESPUESTA TERAPEUTICA</option>') && !str_contains($form, '<option value="tumor_site">'), 'Los campos inactivos desaparecen de la lista nueva');
$print = view('anatomopatologia.print', ['report' => $report->fresh()])->render();
$readonly = view('anatomopatologia.cap-readonly', ['report' => $report->fresh()])->render();
catalogCheck(str_contains($print, 'CONTENIDO HISTORICO') && str_contains($print, 'SITIO DE PRUEBA')
    && str_contains($readonly, 'CONTENIDO HISTORICO') && $report->fresh()->getRawOriginal('cap_protocol') === $before,
    'Los informes históricos conservan datos, consulta e impresión');
$payload['registration_number'] = '402';
try {
    $controller->store(payloadRequest($payload));
    throw new RuntimeException('Se aceptó un campo inactivo');
} catch (ValidationException $exception) {
    catalogCheck(isset($exception->errors()['cap_protocol.tumor_site']), 'Se rechazan campos iniciales desactivados en un formulario antiguo');
}
unset($payload['cap_protocol']['tumor_site']);
try {
    $controller->store(payloadRequest($payload));
    throw new RuntimeException('Se aceptó un protocolo inactivo');
} catch (ValidationException $exception) {
    catalogCheck(isset($exception->errors()['cap_protocol.custom.5.title']), 'No se elude la desactivación copiando el título en un campo personalizado');
}
try {
    $cfg->storeProtocolo(payloadRequest(['name' => ' respuesta TERAPEUTICA ']));
    throw new RuntimeException('Se creó un duplicado');
} catch (ValidationException $exception) {
    catalogCheck(isset($exception->errors()['normalized_name']), 'Se detectan duplicados aunque cambien mayúsculas y tildes');
}
$cfg->toggleProtocolo($field);
$controller->store(payloadRequest($payload));
catalogCheck(AnatomopathologyReport::count() === 2, 'Al reactivar el protocolo se puede utilizar de nuevo');
$specimen = CapProtocolField::where('field_key', 'specimen')->firstOrFail();
$cfg->toggleProtocolo($specimen);
unset($payload['cap_protocol']['specimen']);
$payload['registration_number'] = '403';
$controller->store(payloadRequest($payload));
catalogCheck(AnatomopathologyReport::count() === 3, 'La obligatoriedad de Muestra se ajusta cuando administración la desactiva');
$cfg->toggleProtocolo($specimen);
try {
    $payload['registration_number'] = '404';
    $controller->store(payloadRequest($payload));
    throw new RuntimeException('No se restableció la obligatoriedad');
} catch (ValidationException $exception) {
    catalogCheck(isset($exception->errors()['cap_protocol.specimen']), 'Al reactivar Muestra vuelve su validación obligatoria');
}
$nonAdmin = User::create(['name' => 'Oficina prueba', 'username' => 'catalog_office',
    'password' => 'SoloPruebas123', 'role' => 'anatomopathology_office', 'is_active' => true]);
$request = Request::create('/configuracion/protocolos', 'POST');
$request->setUserResolver(fn () => $nonAdmin);
try {
    (new App\Http\Middleware\AdminOnly())->handle($request, fn () => response('ok'));
    throw new RuntimeException('Acceso no administrativo permitido');
} catch (HttpException $exception) {
    catalogCheck($exception->getStatusCode() === 403, 'Solo administración puede gestionar el catálogo');
}
foreach (['configuracion.protocolos.store', 'configuracion.protocolos.estado'] as $name) {
    catalogCheck(in_array('patologia.admin', app('router')->getRoutes()->getByName($name)->gatherMiddleware(), true), 'Ruta protegida: '.$name);
}
Auth::login($admin);
$cfgView = $cfg->index()->render();
catalogCheck(str_contains($cfgView, 'Protocolos de anatomopatología') && str_contains($cfgView, 'RESPUESTA TERAPEUTICA'), 'Configuración muestra catálogo, estados y acciones');
if (getenv('CAP_CATALOG_PREVIEW') === '1') {
    file_put_contents(base_path('.runtime/protocol-catalog-preview.html'), $cfgView);
}
