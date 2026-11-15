<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imagen de fondo (portada) del perfil público: con degradado hasta la mitad de la tarjeta,
     * o en toda la tarjeta con una opacidad de 10 a 50 %.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('portada_path')->nullable()->after('foto_path');
            $table->string('portada_estilo', 10)->default('degradado')->after('portada_path');
            $table->unsignedTinyInteger('portada_opacidad')->default(35)->after('portada_estilo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['portada_path', 'portada_estilo', 'portada_opacidad']);
        });
    }
};
