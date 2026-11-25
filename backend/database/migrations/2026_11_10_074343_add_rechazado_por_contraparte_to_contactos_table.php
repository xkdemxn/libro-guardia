<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha en que la estación contactada dijo "no fui yo" a la solicitud de confirmación.
     */
    public function up(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->timestamp('rechazado_por_contraparte_at')->nullable()->after('observaciones');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contactos', function (Blueprint $table) {
            $table->dropColumn('rechazado_por_contraparte_at');
        });
    }
};
