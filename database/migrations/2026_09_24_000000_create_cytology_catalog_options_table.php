<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cytology_catalog_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('name', 60);
            $table->unique(['type', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cytology_catalog_options');
    }
};
