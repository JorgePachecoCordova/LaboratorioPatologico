<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cytology_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pathology_result_id')
                ->unique()
                ->constrained('pathology_results')
                ->cascadeOnDelete();
            $table->string('sample_adequacy', 30);
            $table->text('sample_adequacy_notes')->nullable();
            $table->boolean('transformation_zone_present')->default(false);
            $table->string('general_category', 50);
            $table->json('microorganisms')->nullable();
            $table->json('benign_findings')->nullable();
            $table->string('epithelial_abnormality', 80)->nullable();
            $table->text('epithelial_abnormality_details')->nullable();
            $table->text('hormonal_evaluation')->nullable();
            $table->text('recommendations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cytology_reports');
    }
};
