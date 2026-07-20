<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes', function (Blueprint $table) {
            $table->increments('id_com')->comment('Identificador de la comuna');
            $table->unsignedInteger('id_reg')->comment('Región a la que pertenece la comuna');
            $table->string('description', 90)->comment('Nombre de la comuna');
            $table->enum('status', ['A', 'I', 'T'])->default('A')->index()
                ->comment('Estado del registro: A = activo, I = inactivo, T = eliminado (trash)');

            $table->foreign('id_reg')->references('id_reg')->on('regions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes');
    }
};
