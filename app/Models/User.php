<?php

namespace App\Models;

use App\Enums\RoleName;
use App\Exceptions\LastActiveSuperadminException;
use App\Models\Concerns\LogsActivity;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Ce modèle n'utilise PAS les traits de cloisonnement automatique : un scope global dépendant de
 * l'utilisateur connecté créerait une boucle au chargement de cet utilisateur. La visibilité des comptes
 * passe par User::visibleTo($acteur) et UserPolicy (ARCHITECTURE §4).
 */
#[Fillable(['name', 'phone', 'password', 'is_active', 'must_change_password', 'password_changed_at', 'commissariat_id', 'mairie_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, SoftDeletes;

    /** Les rôles et permissions vivent sur le guard web ; les requêtes API (Sanctum) s'y résolvent aussi. */
    protected $guard_name = 'web';

    protected static function booted(): void
    {
        // Filet de sécurité modèle (D10) : le service applique la même règle ; les mises à jour de masse la contournent.
        static::updating(function (User $user) {
            if ($user->isDirty('is_active') && ! $user->is_active && $user->isLastActiveSuperadmin()) {
                throw new LastActiveSuperadminException;
            }
        });

        static::deleting(function (User $user) {
            if ($user->isLastActiveSuperadmin()) {
                throw new LastActiveSuperadminException;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** Le numéro est toujours stocké en E.164 (D26). Une saisie invalide reste telle quelle : la validation la refuse en amont. */
    protected function phone(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => PhoneNumber::normalize($value) ?? $value);
    }

    public function commissariat(): BelongsTo
    {
        return $this->belongsTo(Commissariat::class)->withTrashed();
    }

    public function mairie(): BelongsTo
    {
        return $this->belongsTo(Mairie::class)->withTrashed();
    }

    /**
     * L'unique rôle de l'utilisateur, ou null. (Nommé roleName() : role() est le scope Spatie `User::role('nom')`.)
     */
    public function roleName(): ?RoleName
    {
        $name = $this->roles->first()?->name;

        return $name === null ? null : RoleName::tryFrom($name);
    }

    public function isSuperadmin(): bool
    {
        return $this->roleName() === RoleName::Superadmin;
    }

    /** Institution de rattachement : commissariat, mairie, ou null. */
    public function institution(): Commissariat|Mairie|null
    {
        return $this->commissariat_id ? $this->commissariat : ($this->mairie_id ? $this->mairie : null);
    }

    /** Vrai si l'utilisateur n'a pas d'institution, ou si celle-ci existe et est active. */
    public function institutionIsActive(): bool
    {
        $institution = $this->institution();

        return $institution === null
            ? ! $this->requiresInstitution()
            : ($institution->is_active && ! $institution->trashed());
    }

    public function requiresInstitution(): bool
    {
        return $this->roleName()?->institution() !== null;
    }

    /** Vrai si ce compte peut se connecter : actif, non supprimé, institution active. */
    public function canAuthenticate(): bool
    {
        return $this->is_active && ! $this->trashed() && $this->institutionIsActive();
    }

    /**
     * Évalué sur l'état persisté (valeur d'origine de is_active) : pendant un `updating`, l'attribut est déjà modifié.
     */
    public function isLastActiveSuperadmin(): bool
    {
        if (! $this->isSuperadmin() || ! $this->getOriginal('is_active', $this->is_active) || $this->trashed()) {
            return false;
        }

        return ! static::query()
            ->role(RoleName::Superadmin->value)
            ->where('is_active', true)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    /**
     * Comptes qu'un acteur peut voir (fail-closed : tout rôle non prévu ne voit personne).
     */
    public function scopeVisibleTo(Builder $query, self $actor): Builder
    {
        return match ($actor->roleName()) {
            RoleName::Superadmin => $query,
            RoleName::AdminNational => $query->whereDoesntHave('roles', fn ($roles) => $roles->where('name', RoleName::Superadmin->value)),
            RoleName::Commissaire => $actor->commissariat_id
                ? $query->where('commissariat_id', $actor->commissariat_id)->role(RoleName::Police->value)
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function activityNoun(): string
    {
        return 'Utilisateur';
    }

    public function activityExcept(): array
    {
        // last_login_at change à chaque connexion : bruit sans valeur (la connexion elle-même est auditée).
        return ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token', 'last_login_at'];
    }
}
