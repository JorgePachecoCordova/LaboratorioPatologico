<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pathology_requests', 'pathology_results'] as $table) {
            DB::table($table)->where('reader', 'YAQUE')->update(['reader' => 'JAQUE']);
        }
    }

    public function down(): void
    {
        foreach (['pathology_requests', 'pathology_results'] as $table) {
            DB::table($table)->where('reader', 'JAQUE')->update(['reader' => 'YAQUE']);
        }
    }
};
