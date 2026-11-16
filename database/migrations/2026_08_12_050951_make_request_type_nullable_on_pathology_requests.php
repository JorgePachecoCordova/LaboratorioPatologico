<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->string('request_type', 100)
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('pathology_requests')
            ->whereNull('request_type')
            ->update([
                'request_type' => 'NO_ESPECIFICADO',
            ]);

        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->string('request_type', 100)
                ->nullable(false)
                ->change();
        });
    }
};