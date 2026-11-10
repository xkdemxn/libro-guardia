<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contactos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('estacion_llamada', 20);
            $table->dateTime('inicio_utc');
            $table->dateTime('fin_utc')->nullable();
            $table->decimal('frecuencia_mhz', 10, 4);
            $table->foreignId('banda_id')->constrained('bandas');
            $table->foreignId('modo_id')->constrained('modos');
            $table->foreignId('prefijo_pais_id')->nullable()->constrained('prefijos_pais')->nullOnDelete();
            $table->string('reporte_enviado', 4);
            $table->string('reporte_recibido', 4);
            $table->decimal('potencia_w', 8, 2)->nullable();
            $table->string('grid_locator', 8)->nullable();
            $table->string('nombre_operador', 100)->nullable();
            $table->string('equipo')->nullable();
            $table->string('activacion', 30)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'inicio_utc']);
            $table->index(['user_id', 'estacion_llamada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contactos');
    }
};
