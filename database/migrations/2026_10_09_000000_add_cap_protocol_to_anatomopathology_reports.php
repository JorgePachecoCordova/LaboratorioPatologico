<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anatomopathology_reports', function (Blueprint $table) {
            $table->boolean('cap_protocol_enabled')->default(false);
            $table->json('cap_protocol')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('anatomopathology_reports', function (Blueprint $table) {
            $table->dropColumn(['cap_protocol_enabled', 'cap_protocol']);
        });
    }
};
