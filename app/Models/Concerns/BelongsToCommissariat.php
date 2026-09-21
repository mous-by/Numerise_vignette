<?php

namespace App\Models\Concerns;

use App\Models\Commissariat;
use App\Models\Scopes\InstitutionScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * À appliquer aux modèles métier rattachés à un commissariat (colonne `commissariat_id`). Le contournement
 * `acrossCommissariats()` doit rester explicite et repérable : protégé par une permission et audité par le module
 * (ex. lecture nationale des motos volées au contrôle de police).
 *
 * @mixin Model
 */
trait BelongsToCommissariat
{
    public static function bootBelongsToCommissariat(): void
    {
        static::addGlobalScope('commissariat', new InstitutionScope('commissariat_id'));

        static::creating(function (Model $model) {
            if ($model->getAttribute('commissariat_id') === null && ($id = Auth::user()?->commissariat_id)) {
                $model->setAttribute('commissariat_id', $id);
            }
        });
    }

    public function commissariat(): BelongsTo
    {
        return $this->belongsTo(Commissariat::class)->withTrashed();
    }

    public function scopeAcrossCommissariats(Builder $query): Builder
    {
        return $query->withoutGlobalScope('commissariat');
    }
}
