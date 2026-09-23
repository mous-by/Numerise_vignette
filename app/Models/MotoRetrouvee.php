<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\MotoRetrouveeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Moto retrouvée (cahier §5 Cas 2, §9 Moto retrouvée), enregistrée par le commissaire qui l'a retrouvée — pas
 * forcément celui où elle a été déclarée volée (une moto volée peut être retrouvée ailleurs). Cloisonnée par
 * commissariat « trouveur » (BelongsToCommissariat), mais `moto()` consulte nationalement
 * (`acrossCommissariats()`) : c'est le premier module à lire au-delà de son propre commissariat (avant W11).
 *
 * PROPOSITION TECHNIQUE — À VALIDER : la création marque immédiatement `Moto::is_stolen = false` (la moto est
 * localisée, plus une alerte active) ; le champ « récupérée » (le propriétaire est venu la chercher) est une
 * étape administrative séparée, sans autre effet. Le cahier ne précise pas ce point.
 */
#[Fillable(['commissariat_id', 'moto_id', 'found_at', 'location', 'recovered', 'recovered_at'])]
class MotoRetrouvee extends Model
{
    /** @use HasFactory<MotoRetrouveeFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'motos_retrouvees';

    /**
     * Le module (permissions, audit) reste « motos-retrouvees » — la clé déjà utilisée par
     * config/planned_modules.php (jamais modifié) — plutôt que le nom de la table (`activityModule()` suit par
     * défaut `getTable()`, en soulignés) : sans cette surcharge, l'audit et les permissions désigneraient le
     * même module par deux chaînes différentes.
     */
    public function activityModule(): string
    {
        return 'motos-retrouvees';
    }

    protected function casts(): array
    {
        return [
            'found_at' => 'date',
            'recovered' => 'boolean',
            'recovered_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Moto, $this>
     */
    public function moto(): BelongsTo
    {
        // Bypass volontaire (§4.7) : la moto retrouvée n'appartient pas forcément au commissariat qui la
        // retrouve.
        return $this->belongsTo(Moto::class)->withoutGlobalScope('commissariat');
    }

    public function activityNoun(): string
    {
        return 'Moto retrouvée';
    }

    public function activityLabel(): string
    {
        return $this->moto?->plate_number ?? (string) $this->moto_id;
    }
}
