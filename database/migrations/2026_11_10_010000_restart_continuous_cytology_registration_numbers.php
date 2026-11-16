<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            // Include archived documents so their place is never reused.
            $reports = DB::table('cytology_reports')
                ->where(function ($query): void {
                    $query->whereNotNull('report_number')->orWhereNotNull('registration_number');
                })->orderBy('id')->lockForUpdate()->get();
            // Existing report numbers are already continuous between years.
            $reports = $reports->sortBy(fn ($report) =>
                preg_match('/^(\d+)-\d{4}$/', $report->report_number ?? '', $match)
                    ? (int) $match[1] : PHP_INT_MAX
            );
            $number = 0;
            $requestId = (string) Str::uuid();
            foreach ($reports as $report) {
                $registration = (string) ++$number;
                if ($report->registration_number === $registration) continue;
                DB::table('system_audits')->insert([
                    'request_id' => $requestId,
                    'action' => 'registration_renumbered',
                    'subject_type' => 'App\\Models\\CytologyReport',
                    'subject_id' => (string) $report->id,
                    'before' => json_encode(['registration_number' => $report->registration_number]),
                    'after' => json_encode(['registration_number' => $registration]),
                    'metadata' => json_encode(['source' => 'continuous_cytology_registration_migration']),
                    'created_at' => now(),
                ]);
                DB::table('cytology_reports')->where('id', $report->id)
                    ->update(['registration_number' => $registration]);
            }
            DB::table('annual_document_sequences')->where('document_type', 'cytology_registration')
                ->update(['last_number' => $number, 'updated_at' => now()]);
            DB::table('annual_document_sequences')->updateOrInsert([
                'document_type' => 'cytology_registration', 'sequence_year' => 2000,
            ], ['last_number' => $number, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        // Issued numbers must not be rolled back or reused after new reports exist.
        // Previous identifiers remain available in system_audits.
    }
};
