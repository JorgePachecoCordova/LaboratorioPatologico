<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pathology_results', fn (Blueprint $table) => $table->unsignedInteger('revision')->default(0));
        Schema::create('result_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pathology_result_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('actor_name', 180);
            $table->string('actor_username', 80);
            $table->string('action', 30);
            $table->unsignedInteger('revision');
            $table->text('reason')->nullable();
            $table->json('before')->nullable();
            $table->json('after');
            $table->timestamp('created_at');
            $table->unique(['pathology_result_id', 'revision']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('result_audits');
        Schema::table('pathology_results', fn (Blueprint $table) => $table->dropColumn('revision'));
    }
};
