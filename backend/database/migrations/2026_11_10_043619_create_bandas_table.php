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
        Schema::create('bandas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 10)->unique();
            $table->decimal('frecuencia_min_mhz', 10, 4);
            $table->decimal('frecuencia_max_mhz', 10, 4);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bandas');
    }
};
