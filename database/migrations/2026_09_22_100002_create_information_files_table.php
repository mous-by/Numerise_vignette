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
        Schema::create('information_files', function (Blueprint $table) {
            $table->id();
            // cascadeOnDelete (pas restrict, à la différence des FK vers les institutions) : une pièce jointe n'a
            // aucun sens sans son information, et Information est soft-deleted (le cascade ne joue qu'à la
            // suppression physique, jamais déclenchée en usage normal).
            $table->foreignId('information_id')->constrained('informations')->cascadeOnDelete();
            // Pas d'enum SQL (CLAUDE.md §4.1) : chaîne courte + enum PHP (App\Enums\InformationFileType).
            $table->string('type', 20);
            $table->string('path');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['information_id', 'type', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('information_files');
    }
};
