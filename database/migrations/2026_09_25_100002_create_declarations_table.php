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
        Schema::create('declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->foreignId('moto_id')->constrained('motos')->restrictOnDelete();
            // Pas d'enum SQL (CLAUDE.md §4.1) : chaîne courte + enum PHP (App\Enums\DeclarationType).
            $table->string('type', 20);
            $table->string('location', 255);
            $table->date('occurred_at');
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();

            $table->index('commissariat_id');
            $table->index('moto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('declarations');
    }
};
