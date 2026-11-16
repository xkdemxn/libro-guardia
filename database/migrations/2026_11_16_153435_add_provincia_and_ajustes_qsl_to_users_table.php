<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Provincia (para operadores de Ecuador), mensaje de la tarjeta QSL de hasta 3 líneas
     * y foto de la tarjeta completa por defecto, como las tarjetas QSL clásicas.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provincia', 60)->nullable()->after('pais');
            $table->string('qsl_mensaje', 160)->nullable()->change();
            $table->string('qsl_foto_estilo', 10)->default('completa')->change();
            $table->unsignedTinyInteger('qsl_foto_opacidad')->default(100)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('provincia');
            $table->string('qsl_mensaje', 100)->nullable()->change();
            $table->string('qsl_foto_estilo', 10)->default('degradado')->change();
            $table->unsignedTinyInteger('qsl_foto_opacidad')->default(35)->change();
        });
    }
};
