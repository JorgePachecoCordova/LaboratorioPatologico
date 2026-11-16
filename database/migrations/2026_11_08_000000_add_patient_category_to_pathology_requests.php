<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->string('patient_category', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->dropColumn('patient_category');
        });
    }
};
