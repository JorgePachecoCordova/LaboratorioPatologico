<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('system_audits', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('actor_name',180)->nullable();
            $table->string('actor_username',80)->nullable();
            $table->string('actor_role',80)->nullable();
            $table->string('action',40)->index();
            $table->string('subject_type',180)->nullable();
            $table->string('subject_id',80)->nullable()->index();
            $table->string('route',180)->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->index();
        });
    }
    public function down(): void { Schema::dropIfExists('system_audits'); }
};
