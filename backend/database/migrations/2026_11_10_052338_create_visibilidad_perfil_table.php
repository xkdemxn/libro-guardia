<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Qué datos del perfil del operador se muestran al público (relación 1 a 1 con users).
     */
    public function up(): void
    {
        Schema::create('visibilidad_perfil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('mostrar_nombre')->default(true);
            $table->boolean('mostrar_pais_ciudad')->default(true);
            $table->boolean('mostrar_equipo')->default(false);
            $table->boolean('mostrar_biografia_foto')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visibilidad_perfil');
    }
};
