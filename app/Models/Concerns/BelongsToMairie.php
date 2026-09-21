<?php

namespace App\Models\Concerns;

use App\Models\Mairie;
use App\Models\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * À appliquer aux modèles métier rattachés à une mairie (colonne `mairie_id`). Voir BelongsToCommissariat.
 *
 * @mixin Model
 */
trait BelongsToMairie
{
    public static function bootBelongsToMairie(): void
    {
        static::addGlobalScope('mairie', new InstitutionScope('mairie_id'));

        static::creating(function (Model $model) {
            if ($model->getAttribute('mairie_id') === null && ($id = Auth::user()?->mairie_id)) {
                $model->setAttribute('mairie_id', $id);
            }
        });
    }

    public function mairie(): BelongsTo
    {
        return $this->belongsTo(Mairie::class)->withTrashed();
    }

    public function scopeAcrossMairies(Builder $query): Builder
    {
        return $query->withoutGlobalScope('mairie');
    }
}
