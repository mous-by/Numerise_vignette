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
        Schema::create('motos_retrouvees', function (Blueprint $table) {
            $table->id();
            // Commissariat qui a RETROUVÉ la moto (§9 « Nom du commissariat ») : pas forcément celui qui l'a
            // enregistrée/déclarée volée, une moto volée peut être retrouvée ailleurs. C'est pourquoi ce module
            // consulte les motos nationalement (acrossCommissariats()), à la différence de W7/W8/W9.
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->restrictOnDelete();
            $table->date('found_at');
            $table->string('location', 255);
            $table->boolean('recovered')->default(false);
            $table->date('recovered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('commissariat_id');
            $table->index('moto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motos_retrouvees');
    }
};
