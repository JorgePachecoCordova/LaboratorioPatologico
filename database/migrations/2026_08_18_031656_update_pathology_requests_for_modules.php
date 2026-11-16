<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::table('pathology_requests', function (Blueprint $table) {


            /*
            |--------------------------------------------------------------------------
            | MODULO
            | U = Urbano
            | R = Rural
            | G = Ginecologia
            |--------------------------------------------------------------------------
            */

            if (!Schema::hasColumn('pathology_requests','module')) {

                $table->string('module',20)
                    ->after('patient_id');

            }



            /*
            |--------------------------------------------------------------------------
            | CORRELATIVO AUTOMATICO
            |--------------------------------------------------------------------------
            */


            if (!Schema::hasColumn('pathology_requests','sequence_type')) {

                $table->char('sequence_type',1)
                    ->nullable();

            }


            if (!Schema::hasColumn('pathology_requests','sequence_year')) {

                $table->unsignedSmallInteger('sequence_year')
                    ->nullable();

            }


            if (!Schema::hasColumn('pathology_requests','sequence_number')) {

                $table->unsignedInteger('sequence_number')
                    ->nullable();

            }


            if (!Schema::hasColumn('pathology_requests','request_number')) {

                $table->string('request_number',30)
                    ->nullable();

            }





            /*
            |--------------------------------------------------------------------------
            | MUNICIPIO RESIDENCIA GINECOLOGIA
            |--------------------------------------------------------------------------
            */


            if (!Schema::hasColumn('pathology_requests','residence_municipality_id')) {


                $table->foreignId('residence_municipality_id')
                    ->nullable()
                    ->after('municipality_id')
                    ->constrained('municipalities')
                    ->nullOnDelete();

            }



        });


    }



    public function down(): void
    {

        Schema::table('pathology_requests', function (Blueprint $table) {


            if (Schema::hasColumn('pathology_requests','residence_municipality_id')) {

                $table->dropForeign([
                    'residence_municipality_id'
                ]);

                $table->dropColumn(
                    'residence_municipality_id'
                );

            }


            $columns = [
                'module',
                'sequence_type',
                'sequence_year',
                'sequence_number',
                'request_number'
            ];


            foreach($columns as $column){

                if(Schema::hasColumn('pathology_requests',$column)){

                    $table->dropColumn($column);

                }

            }


        });

    }

};