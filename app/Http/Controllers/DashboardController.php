<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AnatomopathologyReport;
use App\Models\CytologyReport;
use App\Models\PathologyRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $today = CarbonImmutable::today();
        $period = in_array($request->string('period')->toString(), ['30', '180', '365'], true)
            ? $request->string('period')->toString()
            : '180';
        $monthCount = match ($period) {
            '30' => 1,
            '365' => 12,
            default => 6,
        };
        $pendingStatuses = ['registered', 'received', 'processing', 'reported'];
        $user = $request->user();
        $isAdministrator = $user?->role === 'administrator' || $user?->hasRole('administrator');
        $isOffice = in_array($user?->role, ['laboratory_office', 'cytology_office', 'anatomopathology_office'], true);
        $access = [
            'laboratory' => $isAdministrator || !$isOffice || $user?->role === 'laboratory_office',
            'cytology' => $isAdministrator || !$isOffice || $user?->role === 'cytology_office',
            'anatomopathology' => $isAdministrator || !$isOffice || $user?->role === 'anatomopathology_office',
        ];

        $moduleSummaries = collect([
            'laboratory' => $access['laboratory'] ? [
                'title' => 'Solicitudes y Resultados', 'class' => 'green', 'icon' => 'laboratory',
                'total' => PathologyRequest::query()->count(),
                'today' => PathologyRequest::query()->whereDate('request_date', $today)->count(),
                'pending' => PathologyRequest::query()->whereIn('status', $pendingStatuses)->count(),
                'completed' => PathologyRequest::query()->whereIn('status', ['validated', 'delivered'])->count(),
                'url' => route('solicitudes.index'),
            ] : null,
            'cytology' => $access['cytology'] ? [
                'title' => 'Citología', 'class' => 'blue', 'icon' => 'cytology',
                'total' => CytologyReport::query()->count(),
                'today' => CytologyReport::query()->whereDate('request_date', $today)->count(),
                'pending' => CytologyReport::query()->whereIn('status', ['requested', 'reported'])->count(),
                'completed' => CytologyReport::query()->where('status', 'validated')->count(),
                'url' => route('citologia.index'),
            ] : null,
            'anatomopathology' => $access['anatomopathology'] ? [
                'title' => 'Anatomopatología', 'class' => 'purple', 'icon' => 'anatomy',
                'total' => AnatomopathologyReport::query()->count(),
                'today' => AnatomopathologyReport::query()->whereDate('report_date', $today)->count(),
                'pending' => AnatomopathologyReport::query()->where('status', 'draft')->count(),
                'completed' => AnatomopathologyReport::query()->where('status', 'completed')->count(),
                'url' => route('anatomopatologia.index'),
            ] : null,
        ])->filter();

        $months = collect(range($monthCount - 1, 0))->map(function (int $monthsAgo) use ($today, $access): array {
            $month = $today->subMonths($monthsAgo);
            $start = $month->startOfMonth();
            $end = $month->endOfMonth();
            return [
                'label' => mb_strtoupper($month->locale('es')->translatedFormat('M')),
                'total' => ($access['laboratory'] ? PathologyRequest::query()->whereBetween('request_date', [$start, $end])->count() : 0)
                    + ($access['cytology'] ? CytologyReport::query()->whereBetween('request_date', [$start, $end])->count() : 0)
                    + ($access['anatomopathology'] ? AnatomopathologyReport::query()->whereBetween('report_date', [$start, $end])->count() : 0),
            ];
        });

        $maxMonthly = max(1, (int) $months->max('total'));
        $statusLabels = ['registered'=>'Registradas','received'=>'Recibidas','processing'=>'En proceso','requested'=>'Solicitadas','reported'=>'Informadas','draft'=>'Borradores','completed'=>'Completadas','validated'=>'Validadas','delivered'=>'Entregadas','cancelled'=>'Anuladas'];
        $statusGroups = collect([
            $access['laboratory'] ? ['title'=>'Solicitudes / Resultados','counts'=>PathologyRequest::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')] : null,
            $access['cytology'] ? ['title'=>'Citología','counts'=>CytologyReport::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')] : null,
            $access['anatomopathology'] ? ['title'=>'Anatomopatología','counts'=>AnatomopathologyReport::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status')] : null,
        ])->filter();

        $pendingItems = collect();
        if ($access['laboratory']) {
            $pendingItems = $pendingItems->concat(PathologyRequest::query()->with('patient')->whereIn('status', $pendingStatuses)->latest('id')->limit(6)->get()->map(fn ($item): array => [
                'area'=>'Solicitudes / Resultados','number'=>$item->request_number,'patient'=>$item->patient?->full_name ?? 'PACIENTE SIN NOMBRE','status'=>$item->status,'date'=>$item->request_date,'sort'=>$item->request_date?->format('Y-m-d') ?? '', 'url'=>route('solicitudes.show', $item),
            ]));
        }
        if ($access['cytology']) {
            $pendingItems = $pendingItems->concat(CytologyReport::query()->whereIn('status', ['requested', 'reported'])->latest('id')->limit(6)->get()->map(fn ($item): array => [
                'area'=>'Citología','number'=>$item->request_number ?? $item->report_number,'patient'=>$item->patient_name ?? 'PACIENTE SIN NOMBRE','status'=>$item->status,'date'=>$item->request_date ?? $item->report_date,'sort'=>($item->request_date ?? $item->report_date)?->format('Y-m-d') ?? '', 'url'=>route('citologia.index', ['registro'=>$item->id]),
            ]));
        }
        if ($access['anatomopathology']) {
            $pendingItems = $pendingItems->concat(AnatomopathologyReport::query()->where('status', 'draft')->latest('id')->limit(6)->get()->map(fn ($item): array => [
                'area'=>'Anatomopatología','number'=>$item->report_number,'patient'=>$item->patient_name,'status'=>$item->status,'date'=>$item->report_date,'sort'=>$item->report_date?->format('Y-m-d') ?? '', 'url'=>route('anatomopatologia.index', ['registro'=>$item->id]),
            ]));
        }
        $pendingItems = $pendingItems->sortByDesc('sort')->take(6)->values();

        return view('inicio', compact('moduleSummaries','months','maxMonthly','statusLabels','statusGroups','pendingItems','period'));
    }
}  