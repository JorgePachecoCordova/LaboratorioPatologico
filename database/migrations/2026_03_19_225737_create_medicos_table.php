<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
            Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->string('ci')->nullable();
            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->integer('edad')->nullable();
            $table->string('direccion')->nullable();
            $table->string('especialidad')->nullable();
            $table->string('matricula_profesional')->nullable();
            $table->string('sexo')->nullable();
            $table->string('email')->nullable();
            $table->string('num_celular')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->unsignedBigInteger('creatoruser_id')->nullable();
            $table->unsignedBigInteger('updateduser_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};
