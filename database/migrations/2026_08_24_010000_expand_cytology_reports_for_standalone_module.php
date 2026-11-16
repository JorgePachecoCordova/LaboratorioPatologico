<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->foreignId('pathology_request_id')->nullable()->after('id')
                ->constrained('pathology_requests')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->after('pathology_request_id')
                ->constrained('patients')->nullOnDelete();
            $table->string('report_number', 30)->nullable()->unique()->after('patient_id');
            $table->date('report_date')->nullable()->after('report_number');
            $table->string('patient_name', 180)->nullable()->after('report_date');
            $table->unsignedSmallInteger('patient_age')->nullable()->after('patient_name');
            $table->string('service', 150)->nullable()->after('patient_age');
            $table->string('physician_name', 180)->nullable()->after('service');
            $table->string('sample_type', 180)->nullable()->after('physician_name');
            $table->text('clinical_diagnosis')->nullable()->after('sample_type');
            $table->text('clinical_data')->nullable()->after('clinical_diagnosis');
            $table->text('description')->nullable()->after('clinical_data');
            $table->text('conclusion')->nullable()->after('description');
            $table->text('note')->nullable()->after('conclusion');
            $table->string('status', 30)->default('draft')->after('note');

            $table->foreignId('pathology_result_id')->nullable()->change();
            $table->string('sample_adequacy', 30)->nullable()->change();
            $table->string('general_category', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cytology_reports', function (Blueprint $table) {
            $table->dropForeign(['pathology_request_id']);
            $table->dropForeign(['patient_id']);
            $table->dropColumn([
                'pathology_request_id', 'patient_id', 'report_number', 'report_date',
                'patient_name', 'patient_age', 'service', 'physician_name', 'sample_type',
                'clinical_diagnosis', 'clinical_data', 'description', 'conclusion', 'note', 'status',
            ]);
        });
    }
};
