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
        Schema::create('informations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commissaire_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('commissariat_id')->constrained('commissariats')->restrictOnDelete();
            $table->text('description')->nullable();
            $table->dateTime('published_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commissariat_id', 'published_at']);
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('informations');
    }
};
