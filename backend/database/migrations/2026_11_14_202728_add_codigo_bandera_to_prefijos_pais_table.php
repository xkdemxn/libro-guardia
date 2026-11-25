<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código de la bandera (ISO 3166-1 alfa-2 en minúsculas, o gb-eng/gb-sct... para las naciones
     * del Reino Unido). Es el nombre del archivo en public/banderas.
     */
    public function up(): void
    {
        Schema::table('prefijos_pais', function (Blueprint $table) {
            $table->string('codigo_bandera', 6)->nullable()->after('pais');
        });
    }

    public function down(): void
    {
        Schema::table('prefijos_pais', function (Blueprint $table) {
            $table->dropColumn('codigo_bandera');
        });
    }
};
