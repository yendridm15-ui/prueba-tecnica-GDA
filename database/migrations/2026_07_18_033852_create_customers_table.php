<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id()->comment('Identificador interno del customer');
            $table->string('dni', 45)->unique()->comment('Documento de identidad, único');
            $table->unsignedInteger('id_reg')->comment('Región donde vive el customer');
            $table->unsignedInteger('id_com')->comment('Comuna donde vive el customer');
            $table->string('email', 120)->unique()->comment('Correo electrónico, único');
            $table->string('name', 45)->comment('Nombre');
            $table->string('last_name', 45)->comment('Apellido');
            $table->string('address', 255)->nullable()->comment('Dirección, puede venir vacía');
            $table->dateTime('date_reg')->comment('Fecha y hora en que se registró');
            $table->enum('status', ['A', 'I', 'T'])->default('A')->index()
                ->comment('Estado del registro: A = activo, I = inactivo, T = eliminado (trash)');

            $table->foreign('id_reg')->references('id_reg')->on('regions');
            $table->foreign('id_com')->references('id_com')->on('communes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
