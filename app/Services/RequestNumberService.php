<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\RequestSequence;
use App\Models\PathologyRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class RequestNumberService
{
    private const NUMBER_LENGTH = 6;

    /**
     * Reserva un correlativo continuo por tipo U/R/G, sin reinicio anual.
     * La gestión pertenece al documento; la fila 2000 bloquea toda la serie.
     *
     * @return array{sequence_type:string,sequence_year:int,sequence_number:int,request_number:string}
     */
    public function next(string $sequenceType, ?int $sequenceYear = null): array
    {
        $type = strtoupper(trim($sequenceType));
        $year = $sequenceYear ?? (int) now()->format('Y');

        $this->validate($type, $year);

        return DB::transaction(function () use ($type, $year): array {
            $timestamp = now();

            DB::table('request_sequences')->insertOrIgnore([
                'sequence_type' => $type,
                'sequence_year' => 2000,
                'last_number' => 0,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            $sequence = RequestSequence::query()
                ->where('sequence_type', $type)
                ->where('sequence_year', 2000)
                ->lockForUpdate()
                ->firstOrFail();

            // A restored/imported database may contain requests beyond its counter.
            // Include archived rows: their numbers must never be reused.
            $lastIssued = (int) PathologyRequest::withTrashed()
                ->where('sequence_type', $type)
                ->max('sequence_number');
            $lastReserved = (int) RequestSequence::where('sequence_type', $type)->max('last_number');
            $nextNumber = max((int) $sequence->last_number, $lastIssued, $lastReserved) + 1;
            $sequence->forceFill(['last_number' => $nextNumber])->save();

            return [
                'sequence_type' => $type,
                'sequence_year' => $year,
                'sequence_number' => $nextNumber,
                'request_number' => str_pad(
                    (string) $nextNumber,
                    self::NUMBER_LENGTH,
                    '0',
                    STR_PAD_LEFT
                ) . '-' . $type,
            ];
        }, 5);
    }

    private function validate(string $type, int $year): void
    {
        if (!in_array($type, [
            RequestSequence::URBAN,
            RequestSequence::RURAL,
            RequestSequence::GYNECOLOGICAL,
        ], true)) {
            throw new InvalidArgumentException('Tipo de correlativo inválido. Use U, R o G.');
        }

        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Gestión de correlativo inválida.');
        }
    }
}
