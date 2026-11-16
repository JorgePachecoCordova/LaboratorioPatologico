<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PathologyRequest;
use App\Models\Sample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MuestraController extends Controller
{
    /**
     * Registra una muestra para una solicitud existente.
     *
     * La ruta conserva el nombre y la URL en español; internamente se trabaja
     * con los modelos y columnas en inglés.
     */
    public function store(
        Request $request,
        PathologyRequest $solicitud
    ): RedirectResponse {
        $validated = $request->validate([
            'sample_type' => ['required', 'string', 'max:150'],
            'organ' => ['nullable', 'string', 'max:150'],
            'collection_date' => ['nullable', 'date'],
            'received_at' => ['required', 'date'],
            'delivered_by' => ['nullable', 'string', 'max:180'],
            'received_by' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'sample_type.required' => 'El tipo de muestra es obligatorio.',
            'received_at.required' => 'La fecha y hora de recepción es obligatoria.',
        ]);

        DB::transaction(function () use ($solicitud, $validated): void {
            /** @var PathologyRequest $lockedRequest */
            $lockedRequest = PathologyRequest::query()
                ->whereKey($solicitud->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status === PathologyRequest::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'sample_type' => 'No se pueden registrar muestras en una solicitud anulada.',
                ]);
            }

            $registeredSamples = (int) $lockedRequest
                ->samples()
                ->count();

            if ($registeredSamples >= $lockedRequest->sample_quantity) {
                throw ValidationException::withMessages([
                    'sample_type' => 'Ya se registró la cantidad total de muestras indicada en la solicitud.',
                ]);
            }

            $lastSampleNumber = (int) $lockedRequest
                ->samples()
                ->max('sample_number');

            $lockedRequest->samples()->create([
                'sample_number' => $lastSampleNumber + 1,
                'sample_type' => trim($validated['sample_type']),
                'organ' => $this->nullableTrim($validated['organ'] ?? null),
                'collection_date' => $validated['collection_date']
                    ?? $lockedRequest->sample_collection_date?->format('Y-m-d'),
                'received_at' => $validated['received_at'],
                'delivered_by' => $this->nullableTrim($validated['delivered_by'] ?? null),
                'received_by' => $this->nullableTrim($validated['received_by'] ?? null),
                'status' => Sample::STATUS_RECEIVED,
                'notes' => $this->nullableTrim($validated['notes'] ?? null),
            ]);

            if (!in_array($lockedRequest->status, [
                PathologyRequest::STATUS_VALIDATED,
                PathologyRequest::STATUS_DELIVERED,
                PathologyRequest::STATUS_CANCELLED,
            ], true)) {
                $lockedRequest->forceFill([
                    'status' => PathologyRequest::STATUS_RECEIVED,
                ])->save();
            }
        }, 5);

        return redirect()
            ->route('resultados.create', $solicitud)
            ->with('success', 'Muestra registrada correctamente.');
    }

    private function nullableTrim(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
