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
        Schema::table('motos', function (Blueprint $table) {
            // Cahier §5 Cas 2 : « la moto est marquée Volée dans la base consultable par tous les agents en
            // patrouille ». Colonne dénormalisée sur la moto (et non seulement déduite des déclarations actives) :
            // c'est ce que l'endpoint de contrôle de police (W11) consultera nationalement.
            $table->boolean('is_stolen')->default(false)->after('has_witness');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->dropColumn('is_stolen');
        });
    }
};
