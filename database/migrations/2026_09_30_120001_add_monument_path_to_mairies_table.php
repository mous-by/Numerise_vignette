<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            // Image du monument imprimée sur les cartes VGT 2025 et 2026 (W13) ; null = dessin par défaut.
            $table->string('monument_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('mairies', function (Blueprint $table) {
            $table->dropColumn('monument_path');
        });
    }
};
