<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id()->comment('Identificador interno del token');
            $table->foreignId('user_id')->comment('Usuario dueño del token')->constrained()->cascadeOnDelete();
            $table->string('token', 40)->unique()->comment('Token sha1 de 40 caracteres, único en toda la tabla');
            $table->dateTime('expires_at')->index()->comment('Fecha y hora en que vence el token');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
