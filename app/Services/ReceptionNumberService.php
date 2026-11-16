<?php

namespace App\Services;

use App\Models\ReceptionBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReceptionNumberService
{
    public function assign(array $data, ?int $actor): ReceptionBatch
    {
        return DB::transaction(function () use ($data, $actor) {
            $year = (int) substr($data['request_date'], 0, 4);
            if (!empty($data['reception_batch_id'])) {
                $batch = ReceptionBatch::whereKey($data['reception_batch_id'])->lockForUpdate()->firstOrFail();
                if ($data['module'] === 'U' || $batch->module !== $data['module'] || $batch->year !== $year
                    || ($batch->module === 'R' && $batch->received_date->format('Y-m-d') !== $data['request_date'])
                    || ($batch->module === 'G' && $batch->received_date->format('Y-m') !== substr($data['request_date'], 0, 7))
                    || (int) $batch->municipality_id !== (int) ($data['municipality_id'] ?? 0)
                    || (int) $batch->health_facility_id !== (int) ($data['health_facility_id'] ?? 0)) {
                    throw ValidationException::withMessages(['reception_batch_id' => 'La solicitud debe coincidir con el área, municipio y establecimiento; en Rural también con la fecha y en Ginecología con el mes. Urbano registra solicitudes individuales.']);
                }
                if (isset($data['patient_id']) && \App\Models\PathologyRequest::withTrashed()->where('reception_batch_id', $batch->id)->where('patient_id', $data['patient_id'])->exists()) {
                    throw ValidationException::withMessages(['reception_batch_id' => 'Este paciente ya tiene un examen en la solicitud seleccionada. Consulte ese registro para evitar duplicarlo.']);
                }
                return $batch;
            }
            $stamp = now();
            DB::table('reception_sequences')->insertOrIgnore([
                'module' => $data['module'], 'year' => $year, 'last_number' => 0,
                'created_at' => $stamp, 'updated_at' => $stamp,
            ]);
            $counter = DB::table('reception_sequences')->where('module', $data['module'])->where('year', $year)->lockForUpdate()->first();
            $number = max($counter->last_number, (int) ReceptionBatch::where('module', $data['module'])->where('year', $year)->max('number')) + 1;
            DB::table('reception_sequences')->where('id', $counter->id)->update(['last_number' => $number, 'updated_at' => $stamp]);
            return ReceptionBatch::create([
                'module' => $data['module'], 'year' => $year, 'number' => $number,
                'received_date' => $data['request_date'], 'municipality_id' => $data['municipality_id'] ?? null,
                'health_facility_id' => $data['health_facility_id'] ?? null, 'created_by' => $actor,
            ]);
        }, 5);
    }
}
