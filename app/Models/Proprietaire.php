<?php

namespace App\Models;

use App\Enums\Genre;
use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\ProprietaireFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Propriétaire d'une (ou plusieurs, cf. l'ouvert client) moto, saisi par le commissaire lors de l'enregistrement
 * initial (cahier §5 Cas 1). Cloisonné par commissariat (BelongsToCommissariat) : contrairement aux informations
 * (W6), il n'y a aucune raison de le rendre visible en dehors de l'institution qui l'a enregistré.
 */
#[Fillable(['commissariat_id', 'first_name', 'last_name', 'gender', 'address', 'phone', 'emergency_contact'])]
class Proprietaire extends Model
{
    /** @use HasFactory<ProprietaireFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['gender' => Genre::class];
    }

    /** @return HasMany<Moto, $this> */
    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function activityNoun(): string
    {
        return 'Propriétaire';
    }

    public function activityLabel(): string
    {
        return $this->fullName();
    }
}
