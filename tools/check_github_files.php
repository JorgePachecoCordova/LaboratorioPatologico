<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$process = new Process(['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'], dirname(__DIR__));
$process->mustRun();
$blocked = [];
foreach (array_filter(explode("\0", $process->getOutput())) as $file) {
    if ($file === '.env.example' || str_ends_with($file, '/.gitignore')) continue;
    if (preg_match('~(^|/)\.env($|\.)|\.(sqlite3?|sql|dump|backup)(-|\.|$)|(^|/)auth\.json$|^storage/(logs/|framework/(sessions/|views/)|app/(private/|public/))|^backups/|^patologia_db($|/)~i', $file)) {
        $blocked[] = $file;
    }
}
if ($blocked) {
    fwrite(STDERR, "No publicar estos archivos:\n".implode("\n", array_unique($blocked))."\n");
    exit(1);
}
echo "PASS: no se detectan bases de datos, .env, respaldos ni archivos privados en los archivos candidatos a GitHub.\n";
echo "Revise también los cambios y documentos manualmente; esta comprobación por rutas no detecta secretos incrustados en el código ni datos en otros formatos.\n";
