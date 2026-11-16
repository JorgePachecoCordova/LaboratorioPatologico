<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reception_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('module', 1);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['module', 'year']);
        });
        Schema::create('reception_batches', function (Blueprint $table) {
            $table->id();
            $table->string('module', 1);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('number');
            $table->date('received_date');
            $table->foreignId('municipality_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('health_facility_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['module', 'year', 'number']);
        });
        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->foreignId('reception_batch_id')->nullable()->constrained('reception_batches')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pathology_requests', fn (Blueprint $table) => $table->dropConstrainedForeignId('reception_batch_id'));
        Schema::dropIfExists('reception_batches');
        Schema::dropIfExists('reception_sequences');
    }
};
