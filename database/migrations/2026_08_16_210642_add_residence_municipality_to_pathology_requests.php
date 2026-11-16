<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pathology_requests', 'residence_municipality_id')) {
            Schema::table('pathology_requests', function (Blueprint $table): void {
                $table->foreignId('residence_municipality_id')
                    ->nullable()
                    ->after('municipality_id')
                    ->constrained('municipalities')
                    ->nullOnDelete();
            });
        }

        // PostgreSQL: evita que dos usuarios obtengan el mismo correlativo.
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS pathology_requests_series_year_number_unique
             ON pathology_requests (sequence_type, sequence_year, sequence_number)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS pathology_requests_series_year_number_unique');

        if (Schema::hasColumn('pathology_requests', 'residence_municipality_id')) {
            Schema::table('pathology_requests', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('residence_municipality_id');
            });
        }
    }
};