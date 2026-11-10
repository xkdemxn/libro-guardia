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
        Schema::create('prefijos_pais', function (Blueprint $table) {
            $table->id();
            $table->string('prefijo', 10)->unique();
            $table->string('pais', 100);
            $table->string('continente', 2);
            $table->decimal('latitud', 8, 5)->nullable();
            $table->decimal('longitud', 8, 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prefijos_pais');
    }
};
