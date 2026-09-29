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
        Schema::table('mairies', function (Blueprint $table) {
            // Modèle de carte VGT choisi par la mairie pour l'impression au retrait (W13, App\Enums\VgtCardTemplate).
            // Pas d'enum SQL (CLAUDE.md §4.1) : colonne string, enum PHP côté application.
            $table->string('card_template', 20)->default('officiel')->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('card_template');
        });
    }
};
