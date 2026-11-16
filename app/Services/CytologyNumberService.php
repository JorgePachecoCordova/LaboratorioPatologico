<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CytologyNumberService
{
    public function next(string $kind, int $year): string
    {
        if (!in_array($kind, ['request', 'report', 'registration'], true) || $year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Serie o gestión citológica inválida.');
        }

        return DB::transaction(function () use ($kind, $year): string {
            // Informes y folios continúan entre gestiones; las solicitudes son anuales.
            $key = ['document_type' => 'cytology_'.$kind, 'sequence_year' => $kind !== 'request' ? 2000 : $year];
            $created = DB::table('annual_document_sequences')->insertOrIgnore($key + [
                'last_number' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $counter = DB::table('annual_document_sequences')->where($key)->lockForUpdate()->first();
            $last = (int) $counter->last_number;

            if ($created) {
                // Inicializa la serie conservando documentos existentes y archivados.
                // Stream the values so historical records do not fill application memory.
                $column = match ($kind) { 'request' => 'request_number', 'registration' => 'registration_number', default => 'report_number' };
                $pattern = match ($kind) { 'request' => '/^SC-(\d+)-'.$year.'$/', 'registration' => '/^(\d+)$/', default => '/^(\d+)-\d{4}$/' };
                $prefix = $kind === 'request' ? 'SC-%-' : '%-';
                foreach (DB::table('cytology_reports')->select('id', $column)
                    ->when($kind === 'request', fn ($q) => $q->where($column, 'like', $prefix.$year))->whereNotNull($column)->lazyById(1000) as $row) {
                    if (preg_match($pattern, $row->{$column}, $match)) {
                        $last = max($last, (int) $match[1]);
                    }
                }
            }

            if ($kind !== 'request') {
                $last = max($last, (int) DB::table('annual_document_sequences')
                    ->where('document_type', 'cytology_'.$kind)->max('last_number'));
            }
            $next = $last + 1;
            DB::table('annual_document_sequences')->where($key)->update([
                'last_number' => $next, 'updated_at' => now(),
            ]);

            if ($kind === 'registration') return (string) $next;
            return ($kind === 'request' ? 'SC-' : '')
                .str_pad((string) $next, $kind === 'request' ? 6 : 3, '0', STR_PAD_LEFT)
                .'-'.$year;
        }, 5);
    }
}
