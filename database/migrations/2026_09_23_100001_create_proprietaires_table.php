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
        Schema::create('proprietaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            // Pas d'enum SQL (CLAUDE.md §4.1) : chaîne courte + enum PHP (App\Enums\Genre).
            $table->string('gender', 10);
            $table->string('address', 255);
            $table->string('phone', 20);
            $table->string('emergency_contact', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('commissariat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proprietaires');
    }
};
