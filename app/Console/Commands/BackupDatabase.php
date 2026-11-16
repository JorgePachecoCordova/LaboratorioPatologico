<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'patologia:backup';
    protected $description = 'Respalda PostgreSQL en formato pg_dump custom y registra su SHA256';

    public function handle(): int
    {
        $partial = null;
        $previousMask = umask(0077);
        try {
            $connection = DB::connection()->getConfig();
            if ($connection['driver'] !== 'pgsql') {
                throw new RuntimeException('Este comando requiere una conexión PostgreSQL.');
            }
            $directory = (string) config('backup.directory');
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('No se pudo crear el directorio privado de respaldos.');
            }
            $directory = realpath($directory);
            $public = realpath(public_path());
            $normalized = str_replace('\\', '/', $directory).'/';
            if ($public && str_starts_with(strtolower($normalized), strtolower(str_replace('\\', '/', $public)).'/')) {
                throw new RuntimeException('El directorio de respaldos no puede estar dentro de public.');
            }
            $name = 'patologia-'.now()->format('Ymd-His').'-'.Str::uuid().'.dump';
            $target = $directory.DIRECTORY_SEPARATOR.$name;
            $partial = $target.'.partial';
            $process = new Process([
                (string) config('backup.pg_dump'), '--format=custom', '--no-owner', '--no-acl',
                '--no-password', '--file='.$partial,
            ], base_path(), [
                'PGHOST' => (string) ($connection['host'] ?? '127.0.0.1'),
                'PGPORT' => (string) ($connection['port'] ?? 5432),
                'PGDATABASE' => (string) $connection['database'],
                'PGUSER' => (string) $connection['username'],
                'PGPASSWORD' => (string) ($connection['password'] ?? ''),
                'PGSSLMODE' => (string) ($connection['sslmode'] ?? 'prefer'),
            ]);
            $process->setTimeout((int) config('backup.timeout'));
            // Never log stderr or command environments: they can contain connection details.
            $process->disableOutput();
            $process->run();
            if (!$process->isSuccessful() || !is_file($partial) || filesize($partial) === 0) {
                throw new RuntimeException('pg_dump no completó el respaldo. Revise conexión, permisos y versión de PostgreSQL.');
            }
            chmod($partial, 0600);
            if (!rename($partial, $target)) {
                throw new RuntimeException('No se pudo finalizar el respaldo.');
            }
            $hash = hash_file('sha256', $target);
            if ($hash === false || file_put_contents($target.'.sha256', $hash.'  '.$name.PHP_EOL, LOCK_EX) === false) {
                throw new RuntimeException('El respaldo se creó pero no se pudo guardar su verificación SHA256.');
            }
            chmod($target.'.sha256', 0600);
            Log::info('Respaldo PostgreSQL completado', ['file' => $name, 'bytes' => filesize($target), 'sha256' => $hash]);
            $this->info('Respaldo creado y verificación SHA256 guardada: '.$name);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if ($partial && is_file($partial)) {
                unlink($partial);
            }
            Log::error('Falló el respaldo PostgreSQL', ['exception_type' => $exception::class]);
            $this->error('No se completó el respaldo. Compruebe pg_dump, conexión, permisos y espacio en disco.');

            return self::FAILURE;
        } finally {
            umask($previousMask);
        }
    }
}
