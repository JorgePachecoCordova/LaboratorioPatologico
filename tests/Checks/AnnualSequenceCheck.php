<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null, 'cache.default' => 'array', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);

use App\Models\{Patient, PathologyRequest, CytologyReport};
use App\Services\{RequestNumberService, CytologyNumberService};
use Illuminate\Support\Facades\DB;

function ensure(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$requests = app(RequestNumberService::class);
foreach (['U', 'R', 'G'] as $type) {
    ensure($requests->next($type, 2026)['sequence_number'] === 1, 'Primera solicitud incorrecta');
    ensure($requests->next($type, 2026)['sequence_number'] === 2, 'Secuencia incorrecta');
    ensure($requests->next($type, 2027)['sequence_number'] === 3, 'Se reinició el correlativo entre gestiones');
    ensure($requests->next($type, 2026)['sequence_number'] === 4, 'Cambió el contador de la gestión anterior');
}
$patient = Patient::create(['full_name' => 'PACIENTE PRUEBA CORRELATIVO', 'identity_document' => 'TEST-ANUAL']);
$historical = PathologyRequest::create(['patient_id' => $patient->id, 'sequence_type' => 'U',
    'sequence_year' => 2026, 'sequence_number' => 100, 'request_number' => '000100-U',
    'module' => 'U', 'request_type' => 'CITOLOGIA', 'request_date' => '2026-12-31']);
$historical->delete();
ensure($requests->next('U', 2026)['sequence_number'] === 101, 'Reutilizó un número archivado');
try {
    DB::transaction(function () use ($requests): void {
        $requests->next('R', 2028);
        throw new RuntimeException('rollback intencional');
    });
} catch (RuntimeException) {}
ensure($requests->next('R', 2028)['sequence_number'] === 5, 'La transacción fallida consumió un número');

$cytology = app(CytologyNumberService::class);
$old = CytologyReport::create(['request_number' => 'SC-000155-2026', 'report_number' => '192-2026',
    'patient_name' => 'PACIENTE PRUEBA CITOLOGIA', 'status' => 'requested']);
$old->delete();
ensure($cytology->next('request', 2026) === 'SC-000156-2026', 'Reutilizó solicitud citológica archivada');
ensure($cytology->next('report', 2026) === '193-2026', 'Reutilizó informe citológico archivado');
ensure($cytology->next('request', 2027) === 'SC-000001-2027', 'Citología no reinició la gestión');
ensure($cytology->next('report', 2027) === '194-2027', 'Informe reinició su correlativo');
for ($i = 2; $i <= 1500; $i++) {
    ensure($cytology->next('request', 2027) === 'SC-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT).'-2027', 'Duplicado o salto inesperado');
}
ensure($requests->next('G', 2026)['sequence_number'] === 5, 'Cambió la secuencia ginecológica');
ensure(Patient::find($patient->id) !== null && PathologyRequest::withTrashed()->find($historical->id) !== null,
    'No se conservó el historial al cambiar de gestión');
echo "PASS: U/R/G continuos entre gestiones, 1500 solicitudes citológicas, archivo histórico, recuperación de contadores y rollback.\n";
