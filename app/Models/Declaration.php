<?php

namespace App\Models;

use App\Enums\DeclarationType;
use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DeclarationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Déclaration de vol, braquage ou autre (cahier §5 Cas 2, §9 Déclaration), saisie par le commissaire. Cloisonnée
 * par commissariat (BelongsToCommissariat) comme Proprietaire et Moto (W7, W8). L'identité de la victime n'est
 * pas ressaisie ici (le cahier la répète dans sa maquette) : PROPOSITION TECHNIQUE — elle est déjà portée par
 * Moto::proprietaire, une nouvelle saisie serait une duplication sans valeur ajoutée.
 */
#[Fillable(['commissariat_id', 'moto_id', 'type', 'location', 'occurred_at', 'description'])]
class Declaration extends Model
{
    /** @use HasFactory<DeclarationFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => DeclarationType::class,
            'occurred_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Moto, $this>
     */
    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class);
    }

    public function activityNoun(): string
    {
        return 'Déclaration';
    }

    public function activityLabel(): string
    {
        return "{$this->type->label()} — {$this->moto?->plate_number}";
    }
}
