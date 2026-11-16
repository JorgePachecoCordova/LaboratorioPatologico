<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                CatalogosPatologiaSeeder::class,
                RegionalSecretariatSeeder::class,
                ClinicalCatalogSeeder::class,
                PathologyTypeSeeder::class,
                CytologyDiagnosisSeeder::class,
            ]);
        });
    }
}
