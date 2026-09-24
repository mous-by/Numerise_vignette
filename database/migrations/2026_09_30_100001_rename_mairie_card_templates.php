<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Renomme les valeurs de `mairies.card_template` (modèles reconstruits sur les vraies vignettes, W13). */
    private const MAP = ['moderne' => 'rose', 'premium' => 'jaune', 'minimaliste' => 'ancien'];

    public function up(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('mairies')->where('card_template', $old)->update(['card_template' => $new]);
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $old => $new) {
            DB::table('mairies')->where('card_template', $new)->update(['card_template' => $old]);
        }
    }
};
