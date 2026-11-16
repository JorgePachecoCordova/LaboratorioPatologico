<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('last_number')->default(0)->change();
        });
        Schema::table('pathology_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Conservar la capacidad: reducirla puede truncar correlativos emitidos.
    }
};
