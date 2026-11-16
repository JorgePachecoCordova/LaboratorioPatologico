<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PathologyType;
use Illuminate\Database\Seeder;

class PathologyTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'biopsy',
                'name' => 'Biopsia',
                'normalized_name' => 'BIOPSIA',
                'description' => 'Estudio histopatológico de tejido obtenido por biopsia.',
                'is_active' => true,
            ],
            [
                'code' => 'cytology',
                'name' => 'Citología',
                'normalized_name' => 'CITOLOGIA',
                'description' => 'Evaluación citológica de células o fluidos corporales.',
                'is_active' => true,
            ],
            [
                'code' => 'histopathology',
                'name' => 'Histopatología',
                'normalized_name' => 'HISTOPATOLOGIA',
                'description' => 'Estudio microscópico de tejidos para diagnóstico patológico.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            PathologyType::query()->firstOrCreate(
                [
                    'code' => $type['code'],
                ],
                [
                    'name' => $type['name'],
                    'normalized_name' => $type['normalized_name'],
                    'description' => $type['description'],
                    'is_active' => $type['is_active'],
                ]
            );
        }
    }
}