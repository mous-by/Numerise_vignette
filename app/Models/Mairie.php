<?php

namespace App\Models;

use App\Enums\VgtCardTemplate;
use App\Models\Concerns\LogsActivity;
use Database\Factories\MairieFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Institution : se supprime en soft delete uniquement, pour ne jamais orpheliner l'historique d'audit.
 * Désactiver une institution bloque tous ses utilisateurs (ARCHITECTURE §11).
 */
#[Fillable(['name', 'code', 'is_active', 'card_template', 'logo_path', 'monument_path'])]
class Mairie extends Model
{
    /** @use HasFactory<MairieFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'card_template' => VgtCardTemplate::class];
    }

    /** Logo de la commune pour la carte VGT ; null = image par défaut (dessin neutre, `demandes-vgt._card_parts.arms`). */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    /** Image du monument pour la carte VGT ; null = dessin par défaut (`demandes-vgt._card_parts.tower`). */
    public function monumentUrl(): ?string
    {
        return $this->monument_path ? asset('storage/'.$this->monument_path) : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function activityNoun(): string
    {
        return 'Mairie';
    }
}
