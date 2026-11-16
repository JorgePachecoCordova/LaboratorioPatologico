<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Preserve existing folios and report numbers. Only fill missing folios.
        DB::table('cytology_reports')->whereNotNull('report_number')
            ->where(fn ($q) => $q->whereNull('registration_number')->orWhere('registration_number', ''))
            ->orderBy('id')->eachById(function ($report) {
                DB::transaction(function () use ($report) {
                    $row = DB::table('cytology_reports')->where('id', $report->id)->lockForUpdate()->first();
                    if ($row->registration_number) return;
                    $year = (int) substr($row->report_date ?? $row->request_date ?? now()->format('Y-m-d'), 0, 4);
                    DB::table('cytology_reports')->where('id', $row->id)->update([
                        'registration_number' => app(\App\Services\CytologyNumberService::class)->next('registration', $year),
                    ]);
                }, 5);
            });
    }

    public function down(): void
    {
        // Issued document identifiers must not be removed or reused.
    }
};
