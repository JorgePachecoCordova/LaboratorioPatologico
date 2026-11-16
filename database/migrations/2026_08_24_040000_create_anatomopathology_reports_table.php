<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anatomopathology_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number', 40)->unique();
            $table->string('registration_number', 50)->nullable();
            $table->date('report_date');
            $table->string('patient_name', 180);
            $table->unsignedSmallInteger('patient_age')->nullable();
            $table->foreignId('hospital_service_id')->nullable()->constrained('hospital_services')->nullOnDelete();
            $table->string('service', 150)->nullable();
            $table->foreignId('requesting_physician_id')->nullable()->constrained('physicians')->nullOnDelete();
            $table->string('physician_name', 180)->nullable();
            $table->text('clinical_diagnosis')->nullable();
            $table->text('anatomical_data')->nullable();
            $table->text('important_data')->nullable();
            $table->text('macroscopic_description');
            $table->text('microscopic_description');
            $table->text('diagnosis');
            $table->string('status', 30)->default('draft');
            $table->timestamps();
            $table->index(['report_date', 'status']);
            $table->index('patient_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anatomopathology_reports');
    }
};
