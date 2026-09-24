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
        Schema::table('demandes_vgt', function (Blueprint $table) {
            // Cahier §9 Retrait VGT : « Date et jour du retrait ». Étape suivante du paiement (W12), sur la même
            // demande (le cahier ne montre pas d'écran Retrait VGT séparé de la liste des demandes).
            $table->date('retrait_date')->nullable()->after('payment_confirmed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes_vgt', function (Blueprint $table) {
            $table->dropColumn('retrait_date');
        });
    }
};
