<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            // Logo de la commune imprimé sur la carte VGT (W13) ; null = image par défaut (armoiries neutres).
            $table->string('logo_path')->nullable()->after('card_template');
        });
    }

    public function down(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
