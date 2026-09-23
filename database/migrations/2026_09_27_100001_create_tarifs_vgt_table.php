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
        Schema::create('tarifs_vgt', function (Blueprint $table) {
            $table->id();
            // Clé = motos.type_or_brand tel quel (texte libre existant, W8) : PROPOSITION TECHNIQUE du
            // développeur (validée par l'utilisateur) plutôt qu'une nouvelle liste fermée de catégories.
            $table->string('type_or_brand', 100)->unique();
            $table->unsignedInteger('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifs_vgt');
    }
};
