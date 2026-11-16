<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cytology_diagnoses', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->text('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cytology_diagnoses');
    }
};
