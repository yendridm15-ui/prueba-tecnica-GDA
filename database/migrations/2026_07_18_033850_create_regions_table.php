<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->increments('id_reg')->comment('Identificador de la región');
            $table->string('description', 90)->comment('Nombre de la región');
            $table->enum('status', ['A', 'I', 'T'])->default('A')->index()
                ->comment('Estado del registro: A = activo, I = inactivo, T = eliminado (trash)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
