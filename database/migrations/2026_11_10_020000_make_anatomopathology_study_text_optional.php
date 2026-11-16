<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anatomopathology_reports', function (Blueprint $table): void {
            $table->text('macroscopic_description')->nullable()->change();
            $table->text('microscopic_description')->nullable()->change();
            $table->text('diagnosis')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Preserve reports saved with empty study fields.
    }
};
