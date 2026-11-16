<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {

            // Tipo de módulo
            $table->string('module',20)
                ->default('U')
                ->after('request_type');


            // Ginecología
            $table->foreignId('residence_municipality_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('municipalities')
                ->nullOnDelete();


        });
    }


    public function down(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {

            $table->dropForeign([
                'residence_municipality_id'
            ]);

            $table->dropColumn([
                'module',
                'residence_municipality_id'
            ]);

        });
    }
};
