<?php

namespace App\Models;

use App\Enums\DeclarationType;
use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\MotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Moto enregistrée par le commissaire lors de l'enregistrement initial (cahier §5 Cas 1, §9 Accueil). Cloisonnée
 * par commissariat (BelongsToCommissariat) comme Proprietaire (W7) : la lecture inter-institutions (déclarations
 * de vol consultables par tous les agents, contrôle de police) viendra avec W9/W11 via acrossCommissariats().
 * Le matricule est l'identifiant national unique de la moto (cahier : jamais de châssis).
 */
#[Fillable([
    'commissariat_id', 'proprietaire_id', 'plate_number', 'color', 'type_or_brand', 'vgt_year',
    'has_sale_certificate', 'seller_first_name', 'seller_last_name', 'seller_phone', 'seller_address',
    'has_witness', 'witness_first_name', 'witness_last_name', 'witness_phone', 'witness_address',
])]
class Moto extends Model
{
    /** @use HasFactory<MotoFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'vgt_year' => 'integer',
            'has_sale_certificate' => 'boolean',
            'has_witness' => 'boolean',
            'is_stolen' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Proprietaire, $this>
     */
    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class);
    }

    /**
     * @return HasMany<Declaration, $this>
     */
    public function declarations(): HasMany
    {
        return $this->hasMany(Declaration::class);
    }

    /**
     * Recalcule `is_stolen` à partir des déclarations actives (cahier §5 Cas 2) : volée si au moins une
     * déclaration de vol ou de braquage n'a pas été supprimée. Appelé après toute création, modification ou
     * suppression d'une déclaration (W9) ; jamais modifiable depuis le formulaire Motos lui-même (pas dans
     * `Fillable`).
     */
    public function recalculateStolenStatus(): void
    {
        // acrossCommissariats() : la moto et ses déclarations partagent toujours le même commissariat (validé à
        // la création), mais on ne veut pas dépendre du contexte de l'utilisateur courant (Auth::user()) pour un
        // calcul qui doit rester correct quel que soit l'appelant.
        $stolen = Declaration::query()->acrossCommissariats()
            ->where('moto_id', $this->id)
            ->whereIn('type', [DeclarationType::Vol->value, DeclarationType::Braquage->value])
            ->exists();

        if ($this->is_stolen !== $stolen) {
            // `is_stolen` n'est pas dans Fillable (jamais modifiable depuis le formulaire Motos) : forceFill
            // contourne volontairement la protection en masse pour cette seule écriture système, calculée par W9.
            $this->forceFill(['is_stolen' => $stolen])->save();
        }
    }

    public function sellerFullName(): ?string
    {
        return $this->has_sale_certificate ? trim("{$this->seller_first_name} {$this->seller_last_name}") : null;
    }

    public function witnessFullName(): ?string
    {
        return $this->has_witness ? trim("{$this->witness_first_name} {$this->witness_last_name}") : null;
    }

    public function activityNoun(): string
    {
        return 'Moto';
    }

    public function activityLabel(): string
    {
        return $this->plate_number;
    }
}
