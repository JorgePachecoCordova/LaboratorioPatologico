<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CatalogosPatologiaSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/catalogos_patologia.json');

        if (!is_file($path)) {
            throw new RuntimeException(
                'No se encontró database/data/catalogos_patologia.json.'
            );
        }

        $data = json_decode(
            (string) file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        DB::transaction(function () use ($data): void {
            $regionalSecretariatId = $this->seedRegionalSecretariats(
                $data['secretarias_regionales'] ?? []
            );

            $this->seedCatalog(
                table: 'municipalities',
                items: $data['municipios'] ?? [],
                extraValues: [
                    'regional_secretariat_id' => $regionalSecretariatId,
                ]
            );

            $this->seedCatalog(
                table: 'districts',
                items: $data['distritos'] ?? [],
                extraValues: [
                    'regional_secretariat_id' => $regionalSecretariatId,
                    'municipality_id' => null,
                ]
            );

            $this->seedCatalog(
                table: 'areas',
                items: $data['areas'] ?? [],
                extraValues: [
                    'district_id' => null,
                ]
            );

            $this->seedCatalog(
                table: 'sectors',
                items: $data['sectores'] ?? [],
                extraValues: [
                    'area_id' => null,
                ]
            );

            $this->seedCatalog(
                table: 'health_facilities',
                items: $data['establecimientos'] ?? [],
                extraValues: [
                    'regional_secretariat_id' => $regionalSecretariatId,
                    'district_id' => null,
                    'municipality_id' => null,
                    'area_id' => null,
                    'sector_id' => null,
                    'classification' => null,
                ]
            );
        }, 5);
    }

    private function seedRegionalSecretariats(array $items): ?int
    {
        $this->assertTableExists('regional_secretariats');

        $primaryId = null;

        foreach ($items as $item) {
            $name = $this->cleanName($item['nombre'] ?? null);

            if ($name === null) {
                continue;
            }

            $normalizedName = $this->normalize($name);
            $accessCode = $this->cleanCode($item['codigo_access'] ?? null);

            $record = DB::table('regional_secretariats')
                ->where('normalized_name', $normalizedName)
                ->first();

            if ($record) {
                if ($primaryId === null || $normalizedName === 'POTOSI') $primaryId = (int) $record->id;
                continue;
            }
            $resolvedAccessCode = $this->availableAccessCode(
                'regional_secretariats',
                $accessCode,
                $record?->id
            );

            if ($record && $resolvedAccessCode === null) {
                $resolvedAccessCode = $record->access_code;
            }

            $values = [
                'access_code' => $resolvedAccessCode,
                'name' => $name,
                'normalized_name' => $normalizedName,
                'is_active' => true,
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('regional_secretariats', 'deleted_at')) {
                $values['deleted_at'] = null;
            }

            if ($record) {
                DB::table('regional_secretariats')
                    ->where('id', $record->id)
                    ->update($values);
                $id = (int) $record->id;
            } else {
                $values['created_at'] = now();
                $id = (int) DB::table('regional_secretariats')
                    ->insertGetId($values);
            }

            if ($primaryId === null || $normalizedName === 'POTOSI') {
                $primaryId = $id;
            }
        }

        return $primaryId;
    }

    private function seedCatalog(
        string $table,
        array $items,
        array $extraValues = []
    ): void {
        $this->assertTableExists($table);

        foreach ($items as $item) {
            $name = $this->cleanName($item['nombre'] ?? null);

            if ($name === null) {
                continue;
            }

            $normalizedName = $this->normalize($name);
            $accessCode = $this->cleanCode($item['codigo_access'] ?? null);

            $record = DB::table($table)
                ->where('normalized_name', $normalizedName)
                ->first();

            if ($record) continue;

            $resolvedAccessCode = $this->availableAccessCode(
                $table,
                $accessCode,
                $record?->id
            );

            if ($record && $resolvedAccessCode === null) {
                $resolvedAccessCode = $record->access_code;
            }

            $values = array_merge($extraValues, [
                'access_code' => $resolvedAccessCode,
                'name' => $name,
                'normalized_name' => $normalizedName,
                'is_active' => true,
                'updated_at' => now(),
            ]);

            if ($record) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update($values);

                continue;
            }

            $values['created_at'] = now();
            DB::table($table)->insert($values);
        }
    }

    private function availableAccessCode(
        string $table,
        ?string $accessCode,
        ?int $currentId = null
    ): ?string {
        if ($accessCode === null) {
            return null;
        }

        $query = DB::table($table)
            ->where('access_code', $accessCode);

        if ($currentId !== null) {
            $query->where('id', '<>', $currentId);
        }

        return $query->exists() ? null : $accessCode;
    }

    private function assertTableExists(string $table): void
    {
        if (!Schema::hasTable($table)) {
            throw new RuntimeException(
                "No existe la tabla {$table}. Ejecute primero las migraciones."
            );
        }
    }

    private function cleanName(mixed $value): ?string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $value));
        $name = $name === false ? trim((string) $value) : $name;

        return $name === '' ? null : $name;
    }

    private function cleanCode(mixed $value): ?string
    {
        $code = trim((string) $value);

        return $code === '' ? null : $code;
    }

    private function normalize(string $value): string
    {
        $normalized = Str::upper(Str::ascii(trim($value)));
        $normalized = preg_replace(
            '/\bC\s*\.?\s*S\s*\.?\b/u',
            'CS',
            $normalized
        ) ?? $normalized;
        $normalized = preg_replace(
            '/\bP\s*\.?\s*S\s*\.?\b/u',
            'PS',
            $normalized
        ) ?? $normalized;
        $normalized = preg_replace(
            '/\bHOSPITAL\b/u',
            'HOSP',
            $normalized
        ) ?? $normalized;
        $normalized = preg_replace('/[^A-Z0-9]+/u', ' ', $normalized)
            ?? $normalized;

        return trim(
            preg_replace('/\s+/u', ' ', $normalized) ?? $normalized
        );
    }
}
