<?php

namespace App\Models\Scopes;

use App\Enums\RoleName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Cloisonnement institutionnel FAIL-CLOSED (D8) : l'absence d'institution ne signifie jamais « tout voir » :
 *  - superadmin et admin national : aucun filtre ;
 *  - utilisateur avec une institution : filtré sur son institution ;
 *  - population, compte sans institution, ou requête sans utilisateur : zéro résultat.
 * Console et files d'attente ne sont pas filtrées (hors tests, qui se comportent comme des requêtes).
 */
class InstitutionScope implements Scope
{
    public function __construct(private readonly string $column) {}

    public function apply(Builder $builder, Model $model): void
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $user = Auth::user();

        if ($user === null) {
            $builder->whereRaw('1 = 0');

            return;
        }

        if (in_array($user->roleName(), [RoleName::Superadmin, RoleName::AdminNational], true)) {
            return;
        }

        $institutionId = $user->{$this->column};

        $institutionId === null
            ? $builder->whereRaw('1 = 0')
            : $builder->where($model->qualifyColumn($this->column), $institutionId);
    }
}
