<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CytologyDiagnosis;
use Illuminate\Database\Seeder;

class CytologyDiagnosisSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/diagnosticos_citologicos.tsv');

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            [$code, $description] = array_pad(explode("\t", $line, 2), 2, '');
            $code = trim($code);
            $description = trim($description);

            if ($code === '' || $description === '') {
                continue;
            }

            CytologyDiagnosis::query()->firstOrCreate(
                ['code' => $code],
                ['description' => $description, 'is_active' => true]
            );
        }
    }
}
