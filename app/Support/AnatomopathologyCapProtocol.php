<?php

declare(strict_types=1);

namespace App\Support;

final class AnatomopathologyCapProtocol
{
    /** Original labels remain stable for historical reports and printed annexes. */
    public static function activeFields(): array
    {
        $keys = \App\Models\CapProtocolField::query()->where('is_active', true)
            ->whereNotNull('field_key')->pluck('field_key')->all();

        return array_intersect_key(self::FIELDS, array_flip($keys));
    }

    public const FIELDS = [
        'specimen' => 'Muestra / procedimiento',
        'tumor_site' => 'Sitio del tumor',
        'tumor_size' => 'Tamaño del tumor',
        'histologic_type' => 'Tipo histológico',
        'histologic_grade' => 'Grado histológico',
        'stromal_invasion' => 'Invasión estromal en profundidad',
        'lymphovascular_invasion' => 'Invasión linfovascular',
        'margins' => 'Márgenes',
        'regional_nodes' => 'Ganglios linfáticos regionales',
        'pt_category' => 'Categoría pT',
        'staging' => 'Estadificación / sistema y edición',
        'additional_findings' => 'Hallazgos adicionales',
        'additional_elements' => 'Otros elementos del protocolo específico',
        'note' => 'Nota',
    ];
}
