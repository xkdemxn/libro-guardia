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
        Schema::create('confirmaciones_qsl', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contacto_id')->constrained('contactos')->cascadeOnDelete();
            $table->string('tipo', 10);
            $table->boolean('enviada')->default(false);
            $table->date('fecha_envio')->nullable();
            $table->boolean('recibida')->default(false);
            $table->date('fecha_recepcion')->nullable();
            $table->timestamps();

            $table->unique(['contacto_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmaciones_qsl');
    }
};
