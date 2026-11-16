<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annual_document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 40);
            $table->unsignedSmallInteger('sequence_year');
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['document_type', 'sequence_year'], 'annual_document_type_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annual_document_sequences');
    }
};
