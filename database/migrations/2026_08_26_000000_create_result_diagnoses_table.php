<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('result_diagnoses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pathology_result_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('code', 30);
            $table->text('description');
            $table->timestamps();

            $table->unique(['pathology_result_id', 'position']);
            $table->index(['code', 'pathology_result_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_diagnoses');
    }
};
