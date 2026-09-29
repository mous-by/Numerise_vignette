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
            // Cahier §4, §9 : la mairie « reçoit les preuves de paiement ». Le paiement vit sur la demande
            // elle-même (W12, PROPOSITION TECHNIQUE) — le cahier ne montre pas d'écran Paiements séparé, le
            // montant et le code marchand sont déjà sur la demande (W11).
            $table->date('payment_confirmed_at')->nullable()->after('surcharge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('demandes_vgt', function (Blueprint $table) {
            $table->dropColumn('payment_confirmed_at');
        });
    }
};
