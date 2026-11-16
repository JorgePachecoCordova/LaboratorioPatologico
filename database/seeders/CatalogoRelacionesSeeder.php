<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CatalogoRelacionesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $districts = DB::table('districts')->get();
            $municipalities = DB::table('municipalities')->get();
            $areas = DB::table('areas')->get();
            $sectors = DB::table('sectors')->get();

            $this->linkDistrictsToMunicipalities($districts, $municipalities);
            $this->linkAreasToDistricts($areas, $districts, $municipalities);
            $this->linkSectorsToAreas($sectors, $areas);
            $this->linkFacilities($municipalities, $districts, $areas, $sectors);
        }, 5);
    }

    private function linkDistrictsToMunicipalities(
        Collection $districts,
        Collection $municipalities
    ): void {
        foreach ($districts as $district) {
            $districtName = $this->normalize($district->name);

            $municipality = $municipalities->first(function ($item) use ($districtName): bool {
                return $this->districtPart($item->name) === $districtName
                    || $this->municipalityPart($item->name) === $districtName;
            });

            if ($municipality) {
                DB::table('districts')
                    ->where('id', $district->id)
                    ->update([
                        'municipality_id' => $municipality->id,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function linkAreasToDistricts(
        Collection $areas,
        Collection $districts,
        Collection $municipalities
    ): void {
        foreach ($areas as $area) {
            $areaName = $this->cleanFacilityName($area->name);

            $municipality = $municipalities->first(function ($item) use ($areaName): bool {
                return $this->municipalityPart($item->name) === $areaName
                    || $this->districtPart($item->name) === $areaName;
            });

            $district = null;

            if ($municipality) {
                $districtName = $this->districtPart($municipality->name);
                $district = $districts->first(
                    fn ($item): bool => $this->normalize($item->name) === $districtName
                );
            }

            $district ??= $districts->first(
                fn ($item): bool => $this->normalize($item->name) === $areaName
            );

            if ($district) {
                DB::table('areas')
                    ->where('id', $area->id)
                    ->update([
                        'district_id' => $district->id,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function linkSectorsToAreas(Collection $sectors, Collection $areas): void
    {
        foreach ($sectors as $sector) {
            $sectorName = $this->cleanFacilityName($sector->name);

            $area = $areas->first(
                fn ($item): bool => $this->cleanFacilityName($item->name) === $sectorName
            );

            if ($area) {
                DB::table('sectors')
                    ->where('id', $sector->id)
                    ->update([
                        'area_id' => $area->id,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function linkFacilities(
        Collection $municipalities,
        Collection $districts,
        Collection $areas,
        Collection $sectors
    ): void {
        DB::table('health_facilities')
            ->orderBy('id')
            ->chunk(200, function ($facilities) use (
                $municipalities,
                $districts,
                $areas,
                $sectors
            ): void {
                foreach ($facilities as $facility) {
                    $prefix = $this->facilityPrefix($facility->name);

                    if ($prefix === '') {
                        continue;
                    }

                    $municipality = $municipalities->first(function ($item) use ($prefix): bool {
                        return $this->municipalityPart($item->name) === $prefix
                            || $this->districtPart($item->name) === $prefix;
                    });

                    $area = $areas->first(
                        fn ($item): bool => $this->cleanFacilityName($item->name) === $prefix
                    );

                    $sector = $sectors->first(
                        fn ($item): bool => $this->cleanFacilityName($item->name) === $prefix
                    );

                    $district = null;

                    if ($municipality) {
                        $districtName = $this->districtPart($municipality->name);
                        $district = $districts->first(
                            fn ($item): bool => $this->normalize($item->name) === $districtName
                        );
                    }

                    $district ??= $area
                        ? $districts->firstWhere('id', $area->district_id)
                        : null;

                    DB::table('health_facilities')
                        ->where('id', $facility->id)
                        ->update([
                            'municipality_id' => $municipality?->id ?? $facility->municipality_id,
                            'district_id' => $district?->id ?? $facility->district_id,
                            'area_id' => $area?->id ?? $facility->area_id,
                            'sector_id' => $sector?->id ?? $facility->sector_id,
                            'classification' => $facility->classification ?: 'R',
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    private function facilityPrefix(string $value): string
    {
        $normalized = $this->normalize($value);
        $parts = preg_split('/\s+-\s+/', $normalized, 2);

        if (is_array($parts) && trim($parts[0]) !== '') {
            return trim($parts[0]);
        }

        return trim(
            preg_replace('/\s+(CS|PS|HOSP|CENTRO|PUESTO|POSTA)\b.*$/u', '', $normalized)
                ?? $normalized
        );
    }

    private function districtPart(string $value): string
    {
        $parts = preg_split('/\s+-\s+/', $this->normalize($value), 2);

        return trim($parts[0] ?? $this->normalize($value));
    }

    private function municipalityPart(string $value): string
    {
        $parts = preg_split('/\s+-\s+/', $this->normalize($value), 2);

        return trim($parts[1] ?? $parts[0] ?? $this->normalize($value));
    }

    private function cleanFacilityName(string $value): string
    {
        $normalized = $this->normalize($value);

        return trim(
            preg_replace('/^(CS|PS|HOSP)\s+/', '', $normalized)
                ?? $normalized
        );
    }

    private function normalize(string $value): string
    {
        $normalized = Str::upper(Str::ascii(trim($value)));
        $normalized = preg_replace('/\bC\s*\.?\s*S\s*\.?\b/u', 'CS', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bP\s*\.?\s*S\s*\.?\b/u', 'PS', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bPUESTO\s+DE\s+SALUD\b/u', 'PS', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bCENTRO\s+DE\s+SALUD\b/u', 'CS', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bHOSPITAL\b/u', 'HOSP', $normalized) ?? $normalized;
        $normalized = preg_replace('/[^A-Z0-9]+/u', ' ', $normalized) ?? $normalized;

        return trim(preg_replace('/\s+/u', ' ', $normalized) ?? $normalized);
    }
}
