<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pathology_results', fn (Blueprint $table) => $table->string('reader', 20)->nullable());
    }
    public function down(): void
    {
        Schema::table('pathology_results', fn (Blueprint $table) => $table->dropColumn('reader'));
    }
};
