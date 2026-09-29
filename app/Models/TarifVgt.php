<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\TarifVgtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Tarif de la VGT par genre ou marque de moto (cahier §9 : « le tarif dépend du genre de moto », PROPOSITION
 * TECHNIQUE — clé = motos.type_or_brand tel quel, réglage global non cloisonné, géré par le superadmin et
 * l'admin national depuis l'écran Demandes VGT (bouton « Tarifs »). Une valeur de genre jamais vue reçoit
 * DemandeVgtController::DEFAULT_AMOUNT à la volée (voir TarifVgt::forGenre()), modifiable ensuite ici.
 */
#[Fillable(['type_or_brand', 'amount'])]
class TarifVgt extends Model
{
    /** @use HasFactory<TarifVgtFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'tarifs_vgt';

    public const DEFAULT_AMOUNT = 6000;

    /** Regroupe l'audit des tarifs sous le même module que les demandes VGT (voir DemandeVgt::activityModule()). */
    public function activityModule(): string
    {
        return 'demandes-vgt';
    }

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    /** Tarif pour un genre donné ; le crée avec le tarif par défaut s'il n'existe pas encore. */
    public static function forGenre(string $typeOrBrand): self
    {
        return static::firstOrCreate(['type_or_brand' => $typeOrBrand], ['amount' => self::DEFAULT_AMOUNT]);
    }

    public function activityNoun(): string
    {
        return 'Tarif VGT';
    }

    public function activityLabel(): string
    {
        return $this->type_or_brand;
    }
}
