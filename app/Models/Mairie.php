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
#[Fillable(['name', 'code', 'is_active', 'card_template'])]
class Mairie extends Model
{
    /** @use HasFactory<MairieFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'card_template' => VgtCardTemplate::class];
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
