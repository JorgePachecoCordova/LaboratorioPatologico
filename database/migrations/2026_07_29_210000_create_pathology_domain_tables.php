<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regional_secretariats', function (Blueprint $table) {
            $table->id();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::create('municipalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_secretariat_id')
                ->nullable()
                ->constrained('regional_secretariats')
                ->nullOnDelete();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['regional_secretariat_id', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_secretariat_id')
                ->nullable()
                ->constrained('regional_secretariats')
                ->nullOnDelete();
            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained('municipalities')
                ->nullOnDelete();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality_id', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['district_id', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 180);
            $table->string('normalized_name', 180)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['area_id', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('health_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regional_secretariat_id')
                ->nullable()
                ->constrained('regional_secretariats')
                ->nullOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();
            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained('municipalities')
                ->nullOnDelete();
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->foreignId('sector_id')
                ->nullable()
                ->constrained('sectors')
                ->nullOnDelete();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('name', 220);
            $table->string('normalized_name', 220)->unique();
            $table->char('classification', 1)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['municipality_id', 'is_active']);
            $table->index(['district_id', 'is_active']);
            $table->index(['classification', 'is_active']);
            $table->index(['is_active', 'name']);
        });

        Schema::create('physicians', function (Blueprint $table) {
            $table->id();
            $table->string('access_code', 30)->nullable()->unique();
            $table->string('full_name', 180);
            $table->string('professional_license', 60)->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'full_name']);
        });

        Schema::create('pathology_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name', 120);
            $table->string('normalized_name', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'name']);
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('identity_document', 30)->unique();
            $table->string('full_name', 180);
            $table->date('birth_date')->nullable();
            $table->char('sex', 1)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 180)->nullable();
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('full_name');
            $table->index(['is_active', 'full_name']);
        });

        Schema::create('request_sequences', function (Blueprint $table) {
            $table->id();
            $table->char('sequence_type', 1);
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(
                ['sequence_type', 'sequence_year'],
                'request_sequences_type_year_unique'
            );
        });

        Schema::create('pathology_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();
            $table->foreignId('regional_secretariat_id')
                ->nullable()
                ->constrained('regional_secretariats')
                ->nullOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->constrained('districts')
                ->nullOnDelete();
            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained('municipalities')
                ->nullOnDelete();
            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete();
            $table->foreignId('sector_id')
                ->nullable()
                ->constrained('sectors')
                ->nullOnDelete();
            $table->foreignId('health_facility_id')
                ->nullable()
                ->constrained('health_facilities')
                ->nullOnDelete();
            $table->foreignId('requesting_physician_id')
                ->nullable()
                ->constrained('physicians')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->char('sequence_type', 1);
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedInteger('sequence_number');
            $table->string('request_number', 30);

            $table->string('request_type', 100);
            $table->string('study_type', 150)->nullable();
            $table->string('service', 150)->nullable();
            $table->string('requesting_unit', 150)->nullable();
            $table->string('requesting_physician_name', 180)->nullable();
            $table->unsignedSmallInteger('sample_quantity')->default(1);
            $table->date('request_date');
            $table->date('sample_collection_date')->nullable();
            $table->string('status', 40)->default('registered');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['sequence_type', 'sequence_year', 'sequence_number'],
                'pathology_requests_annual_sequence_unique'
            );
            $table->unique(
                ['sequence_year', 'request_number'],
                'pathology_requests_year_number_unique'
            );
            $table->index(['patient_id', 'request_date']);
            $table->index(['status', 'request_date']);
            $table->index(['health_facility_id', 'request_date']);
        });

        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pathology_request_id')
                ->constrained('pathology_requests')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('sample_number')->default(1);
            $table->string('sample_type', 150);
            $table->string('organ', 150)->nullable();
            $table->date('collection_date')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->string('delivered_by', 180)->nullable();
            $table->string('received_by', 180)->nullable();
            $table->string('status', 30)->default('received');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                ['pathology_request_id', 'sample_number'],
                'samples_request_number_unique'
            );
            $table->index(['status', 'received_at']);
        });

        Schema::create('pathology_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sample_id')
                ->unique()
                ->constrained('samples')
                ->restrictOnDelete();
            $table->foreignId('pathologist_id')
                ->nullable()
                ->constrained('physicians')
                ->nullOnDelete();
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('validated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('result_date')->nullable();
            $table->text('macroscopic_description')->nullable();
            $table->text('microscopic_description')->nullable();
            $table->text('diagnosis');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'result_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pathology_results');
        Schema::dropIfExists('samples');
        Schema::dropIfExists('pathology_requests');
        Schema::dropIfExists('request_sequences');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('pathology_types');
        Schema::dropIfExists('physicians');
        Schema::dropIfExists('health_facilities');
        Schema::dropIfExists('sectors');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('municipalities');
        Schema::dropIfExists('regional_secretariats');
    }
};
