<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class PapReportGeography
{
    // Reporting groups transcribed from the institution's supplied paper form.
    public const RURAL = [
        'BETANZOS' => ['BETANZOS', 'YURA', 'CHAQUI BAÑOS'],
        'COTAGAITA' => ['COTAGAITA', 'VITICHI'],
        'OCURI' => ['OCURI', 'COLQUECHACA', 'POCOATA', 'RAVELO', 'SAN P. DE MACHA'],
        'POTOSI RURAL' => ['BELEN DE URMIRI', 'PORCO AGUA C.', 'TINGUIPAYA', 'TOMAVE', 'TACOBAMBA', 'YOCALLA'],
        'PUNA' => ['CKOCHAS', 'CAIZA D', 'PUNA TORUCES'],
        'SACACA' => ['ACACIO', 'ARAMPAMPA', 'CARIPUYO', 'SACACA', 'SAN P. B. VISTA', 'TORO TORO'],
        'TUPIZA' => ['ATOCHA', 'MOJINETE', 'SAN A. ESMORUCO', 'SAN P. DE LIPEZ', 'TUPIZA'],
        'UNCIA' => ['CHUQUIUTA', 'LLALLAGUA', 'CHAYANTA', 'UNCIA'],
        'UYUNI' => ['COLCHA K', 'LLICA', 'SAN AGUSTIN', 'SAN P. DE QUEMES', 'TAHUA', 'UYUNI'],
        'VILLAZON' => ['VILLAZON'],
    ];

    public static function municipality(string $name): string
    {
        $parts = preg_split('/\s*-\s*/', $name);
        $name = trim(end($parts));
        $normalized = self::normalize($name);
        $aliases = [
            'CHAQUI' => 'CHAQUI BAÑOS', 'CS CHAQUI' => 'CHAQUI BAÑOS',
            'PORCO' => 'PORCO AGUA C.', 'PUNA' => 'PUNA TORUCES',
            'SAN P DE MACHA' => 'SAN P. DE MACHA', 'SAN PEDRO DE MACHA' => 'SAN P. DE MACHA',
            'SAN PEDRO DE BUENA VISTA' => 'SAN P. B. VISTA',
            'SA ESMORUCO CS ESMORUCO' => 'SAN A. ESMORUCO', 'SAN ANTONIO DE ESMORUCO' => 'SAN A. ESMORUCO',
            'SAN PABLO DE LIPEZ' => 'SAN P. DE LIPEZ', 'SAN PEDRO DE QUEMES' => 'SAN P. DE QUEMES',
            'CHUQUIUTA CHUQUIUTA' => 'CHUQUIUTA',
        ];
        if (isset($aliases[$normalized])) return $aliases[$normalized];
        foreach (self::RURAL as $municipalities) {
            foreach ($municipalities as $label) {
                if (self::normalize($label) === $normalized) return $label;
            }
        }
        return $name;
    }

    public static function pair(?string $network, ?string $municipality): array
    {
        $municipality = self::municipality($municipality ?? 'SIN MUNICIPIO');
        foreach (self::RURAL as $red => $names) {
            if (in_array($municipality, $names, true)) return [$red, $municipality];
        }
        return [$network ?? 'SIN RED', $municipality];
    }

    private static function normalize(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9 ]/', '', strtoupper(Str::ascii($name)))));
    }
}
