<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->string('request_number', 40)->nullable()->unique()->after('patient_id');
            $table->date('request_date')->nullable()->after('request_number');
            $table->char('patient_sex', 1)->nullable()->after('patient_age');
            $table->string('care_type', 30)->nullable()->after('patient_sex');
            $table->string('bed_number', 30)->nullable()->after('care_type');
            $table->string('registration_number', 50)->nullable()->after('bed_number');
            $table->string('clinical_history_number', 50)->nullable()->after('registration_number');
            $table->time('sample_taken_at')->nullable()->after('clinical_data');
            $table->time('sample_received_at')->nullable()->after('sample_taken_at');
            $table->string('sample_type_code', 60)->nullable()->after('sample_received_at');
            $table->string('sample_type_other', 180)->nullable()->after('sample_type_code');
        });

        $existing = DB::table('cytology_reports')->whereNotNull('report_number')->orderBy('id')->get();
        foreach ($existing as $index => $report) {
            $year = $report->report_date ? substr((string) $report->report_date, 0, 4) : now()->format('Y');
            DB::table('cytology_reports')->where('id', $report->id)->update([
                'request_number' => 'SC-' . str_pad((string) ($index + 1), 6, '0', STR_PAD_LEFT) . '-' . $year,
                'request_date' => $report->report_date,
                'status' => $report->description && $report->conclusion ? 'reported' : 'requested',
                'sample_type_code' => 'OTROS',
                'sample_type_other' => mb_strtoupper((string) $report->sample_type),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->dropColumn([
                'request_number', 'request_date', 'patient_sex', 'care_type', 'bed_number',
                'registration_number', 'clinical_history_number', 'sample_taken_at',
                'sample_received_at', 'sample_type_code', 'sample_type_other',
            ]);
        });
    }
};
