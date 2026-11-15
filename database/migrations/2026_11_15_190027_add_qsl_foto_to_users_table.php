<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Foto personalizada de las tarjetas QSL del operador: a la derecha con degradado,
     * o de fondo en toda la tarjeta con una opacidad de 10 a 50 %.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qsl_foto_path')->nullable()->after('portada_opacidad');
            $table->string('qsl_foto_estilo', 10)->default('degradado')->after('qsl_foto_path');
            $table->unsignedTinyInteger('qsl_foto_opacidad')->default(35)->after('qsl_foto_estilo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qsl_foto_path', 'qsl_foto_estilo', 'qsl_foto_opacidad']);
        });
    }
};
