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
        Schema::create('demandes_vgt', function (Blueprint $table) {
            $table->id();
            // Ni BelongsToCommissariat ni BelongsToMairie seuls : une demande est visible du commissariat qui
            // l'a déposée ET de la mairie de retrait choisie. Comme User (§4.7), la visibilité passe par un
            // scope local dédié (DemandeVgt::visibleTo()), pas par le trait de cloisonnement générique.
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->foreignId('mairie_id')->constrained('mairies')->restrictOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->restrictOnDelete();
            $table->unsignedSmallInteger('vgt_year');
            $table->string('contact_phone', 20);
            $table->string('merchant_code', 50)->nullable();
            // Pas d'enum SQL (CLAUDE.md §4.1) : chaîne courte + enum PHP (App\Enums\DemandeVgtStatus).
            $table->string('status', 20)->default('en_attente');
            $table->text('rejection_reason')->nullable();
            // Tarif + majoration éventuelle (arriéré, cahier §9), figés au moment de la demande : un changement
            // ultérieur du tarif ne doit pas modifier rétroactivement une demande déjà déposée.
            $table->unsignedInteger('base_amount');
            $table->boolean('is_late')->default(false);
            $table->unsignedInteger('surcharge_amount')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('commissariat_id');
            $table->index('mairie_id');
            $table->index('moto_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_vgt');
    }
};
