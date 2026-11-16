<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClinicalCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('data/catalogos_clinicos.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data): void {
            foreach (['services' => 'hospital_services', 'physicians' => 'physicians'] as $key => $table) {
                foreach ($data[$key] as $item) {
                    $nameColumn = $key === 'services' ? 'name' : 'full_name';
                    $normalized = Str::upper(Str::ascii(trim($item['name'])));
                    $exists = DB::table($table)->where('access_code', $item['access_code'])
                        ->orWhereRaw("UPPER({$nameColumn}) = ?", [$normalized]);
                    if ($key === 'services') $exists->orWhere('normalized_name', $normalized);
                    if ($exists->exists()) continue;

                    $values = [
                        'access_code' => $item['access_code'], $nameColumn => $item['name'],
                        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                    ];
                    if ($key === 'services') $values['normalized_name'] = $normalized;
                    DB::table($table)->insert($values);
                }
            }
            foreach ($data['samples'] as $name) {
                DB::table('cytology_catalog_options')->insertOrIgnore(['type' => 'sample', 'name' => $name]);
            }
        });
    }
}
