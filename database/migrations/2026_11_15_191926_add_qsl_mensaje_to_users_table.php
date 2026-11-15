<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mensaje propio al pie de las tarjetas QSL; si es null se usa el mensaje por defecto.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qsl_mensaje', 100)->nullable()->after('qsl_foto_opacidad');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qsl_mensaje');
        });
    }
};
