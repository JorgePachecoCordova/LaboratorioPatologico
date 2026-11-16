<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'users', 'regional_secretariats', 'municipalities', 'districts', 'areas', 'sectors',
        'health_facilities', 'physicians', 'pathology_types', 'patients', 'pathology_requests',
        'samples', 'pathology_results', 'hospital_services', 'cytology_reports',
        'anatomopathology_reports',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->softDeletes());
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropSoftDeletes());
            }
        }
    }
};
