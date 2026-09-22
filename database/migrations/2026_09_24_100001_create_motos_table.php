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
        Schema::create('motos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->foreignId('proprietaire_id')->constrained('proprietaires')->restrictOnDelete();
            // Identifiant national de la moto (cahier §5, §9 : seul le matricule apparaît, jamais de châssis) : unique
            // sur toute la base, pas seulement au sein d'un commissariat, puisque la police contrôle nationalement.
            $table->string('plate_number', 20)->unique();
            $table->string('color', 50);
            $table->string('type_or_brand', 100);
            $table->unsignedSmallInteger('vgt_year');
            // Attestation de vente (cahier §5 Cas 1, §9) : vendeur et témoin sont chacun un seul enregistrement
            // imbriqué (pas une liste), telle que la maquette du cahier les présente.
            $table->boolean('has_sale_certificate')->default(false);
            $table->string('seller_first_name', 100)->nullable();
            $table->string('seller_last_name', 100)->nullable();
            $table->string('seller_phone', 20)->nullable();
            $table->string('seller_address', 255)->nullable();
            $table->boolean('has_witness')->default(false);
            $table->string('witness_first_name', 100)->nullable();
            $table->string('witness_last_name', 100)->nullable();
            $table->string('witness_phone', 20)->nullable();
            $table->string('witness_address', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('commissariat_id');
            $table->index('proprietaire_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motos');
    }
};
