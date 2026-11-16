<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {

            $table->string('sequence_type')
                ->nullable()
                ->change();


            $table->integer('sequence_year')
                ->nullable()
                ->change();


            $table->integer('sequence_number')
                ->nullable()
                ->change();


            $table->string('request_number')
                ->nullable()
                ->change();

        });
    }


    public function down(): void
    {
        Schema::table('pathology_requests', function (Blueprint $table) {

            $table->string('sequence_type')
                ->nullable(false)
                ->change();


            $table->integer('sequence_year')
                ->nullable(false)
                ->change();


            $table->integer('sequence_number')
                ->nullable(false)
                ->change();


            $table->string('request_number')
                ->nullable(false)
                ->change();

        });
    }
};