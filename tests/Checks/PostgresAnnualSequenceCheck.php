<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\{RequestNumberService, CytologyNumberService};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Symfony\Component\Process\Process;

if (config('database.default') !== 'pgsql') {
    throw new RuntimeException('Esta comprobación requiere PostgreSQL.');
}
$worker = ($argv[1] ?? '') === '--worker';
$schema = $worker ? ($argv[2] ?? '') : 'annual_check_'.bin2hex(random_bytes(8));
if (!preg_match('/^annual_check_[a-f0-9]{16}$/', $schema)) {
    throw new RuntimeException('Esquema de prueba inválido.');
}
if (!$worker) DB::statement('CREATE SCHEMA "'.$schema.'"');
config(['database.connections.pgsql.search_path' => $schema]);
DB::purge('pgsql');

if ($worker) {
    for ($i = 0; $i < 40; $i++) {
        DB::transaction(function (): void {
            $number = app(RequestNumberService::class)->next('U', 2098);
            DB::table('pathology_requests')->insert([
                'sequence_type' => 'U', 'sequence_year' => 2098,
                'sequence_number' => $number['sequence_number'],
            ]);
            DB::table('cytology_reports')->insert([
                'request_number' => app(CytologyNumberService::class)->next('request', 2098),
            ]);
        }, 5);
    }
    exit(0);
}

$workers = [];
try {
    Schema::create('request_sequences', function (Blueprint $table): void {
        $table->id(); $table->string('sequence_type'); $table->integer('sequence_year');
        $table->bigInteger('last_number')->default(0); $table->timestamps();
        $table->unique(['sequence_type', 'sequence_year']);
    });
    Schema::create('pathology_requests', function (Blueprint $table): void {
        $table->id(); $table->string('sequence_type'); $table->integer('sequence_year');
        $table->bigInteger('sequence_number'); $table->softDeletes();
        $table->unique(['sequence_type', 'sequence_year', 'sequence_number']);
    });
    Schema::create('annual_document_sequences', function (Blueprint $table): void {
        $table->id(); $table->string('document_type'); $table->integer('sequence_year');
        $table->bigInteger('last_number')->default(0); $table->timestamps();
        $table->unique(['document_type', 'sequence_year']);
    });
    Schema::create('cytology_reports', function (Blueprint $table): void {
        $table->id(); $table->string('request_number')->unique();
        $table->string('report_number')->nullable(); $table->softDeletes();
    });
    for ($i = 0; $i < 6; $i++) {
        $process = new Process([PHP_BINARY, __FILE__, '--worker', $schema], base_path());
        $process->setTimeout(90);
        $process->start();
        $workers[] = $process;
    }
    foreach ($workers as $process) {
        if ($process->wait() !== 0) {
            throw new RuntimeException('Falló un proceso concurrente: '.$process->getErrorOutput().$process->getOutput());
        }
    }
    if (DB::table('pathology_requests')->count() !== 240
        || (int) DB::table('pathology_requests')->max('sequence_number') !== 240
        || DB::table('cytology_reports')->count() !== 240
        || !DB::table('cytology_reports')->where('request_number', 'SC-000240-2098')->exists()
        || app(RequestNumberService::class)->next('U', 2099)['sequence_number'] !== 1
        || app(CytologyNumberService::class)->next('request', 2099) !== 'SC-000001-2099') {
        throw new RuntimeException('Los correlativos concurrentes no coinciden.');
    }
    echo "PASS PostgreSQL: 6 procesos, 240 solicitudes U y 240 citológicas sin duplicados; nueva gestión desde 1.\n";
} finally {
    foreach ($workers as $process) {
        if ($process->isRunning()) $process->stop();
    }
    // The generated isolated schema contains only synthetic test data.
    DB::statement('DROP SCHEMA "'.$schema.'" CASCADE');
}
