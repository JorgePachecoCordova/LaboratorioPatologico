<?php

namespace App\Http\Controllers;

use App\Models\PathologyRequest;
use App\Models\ReceptionBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class NumberingController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate(['year' => ['nullable', 'integer', 'between:2025,2100']]);
        $year = (int) ($data['year'] ?? now()->year);
        $rows = [];
        foreach (['U' => 'Urbano', 'R' => 'Rural', 'G' => 'Ginecología'] as $module => $label) {
            foreach (['request' => 'Solicitud', 'exam' => 'Examen / informe'] as $kind => $caption) {
                $rows[] = compact('module', 'label', 'kind', 'caption') + ['last' => $this->last($module, $year, $kind)];
            }
        }
        return view('configuracion.numbering', compact('rows', 'year'));
    }

    private function last(string $module, int $year, string $kind): int
    {
        if ($kind === 'exam') {
            return max((int) DB::table('request_sequences')->where('sequence_type', $module)->where('sequence_year', $year)->value('last_number'),
                (int) PathologyRequest::withTrashed()->where('sequence_type', $module)->where('sequence_year', $year)->max('sequence_number'));
        }
        return max((int) DB::table('reception_sequences')->where('module', $module)->where('year', $year)->value('last_number'),
            (int) ReceptionBatch::where('module', $module)->where('year', $year)->max('number'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'module' => ['required', 'in:U,R,G'], 'kind' => ['required', 'in:request,exam'],
            'year' => ['required', 'integer', 'between:2025,2100'],
            'last_number' => ['required', 'integer', 'min:0', 'max:999998'],
            'expected_last' => ['required', 'integer', 'min:0'], 'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        DB::transaction(function () use ($data, $request) {
            $exam = $data['kind'] === 'exam';
            $table = $exam ? 'request_sequences' : 'reception_sequences';
            $keys = $exam ? ['sequence_type' => $data['module'], 'sequence_year' => $data['year']]
                : ['module' => $data['module'], 'year' => $data['year']];
            DB::table($table)->insertOrIgnore($keys + ['last_number' => 0, 'created_at' => now(), 'updated_at' => now()]);
            $counter = DB::table($table)->where($keys)->lockForUpdate()->first();
            $last = $this->last($data['module'], $data['year'], $data['kind']);
            if ($last !== (int) $data['expected_last']) {
                throw ValidationException::withMessages(['last_number' => 'El contador cambió. Recargue esta pantalla antes de actualizarlo.']);
            }
            if ((int) $data['last_number'] < $last) {
                throw ValidationException::withMessages(['last_number' => 'No puede reducir el contador ni reutilizar números reservados.']);
            }
            DB::table($table)->where('id', $counter->id)->update(['last_number' => $data['last_number'], 'updated_at' => now()]);
            Log::info('Configuración de correlativo de patología', ['actor_id' => $request->user()->id,
                'module' => $data['module'], 'year' => $data['year'], 'kind' => $data['kind'],
                'previous' => $last, 'last_number' => $data['last_number'], 'reason' => $data['reason']]);
        }, 5);
        return redirect()->route('configuracion.numeracion', ['year' => $data['year']])->with('success', 'Correlativo actualizado. El siguiente registro utilizará el número '.($data['last_number'] + 1).'.');
    }
}
