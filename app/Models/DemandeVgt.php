<?php

namespace App\Models;

use App\Enums\DemandeVgtStatus;
use App\Enums\RoleName;
use App\Models\Concerns\LogsActivity;
use Database\Factories\DemandeVgtFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Demande de VGT (W11, cahier §9 Demande VGT) : déposée par le commissaire, validée ou rejetée par la mairie de
 * retrait choisie. Visible du commissariat qui l'a déposée ET de la mairie choisie (`scopeVisibleTo`, comme
 * `User` §4.7) : ni `BelongsToCommissariat` ni `BelongsToMairie` seuls ne conviennent puisque les deux
 * institutions doivent voir la même ligne.
 *
 * PROPOSITION TECHNIQUE — À VALIDER (le cahier ne précise pas ce workflow) : voir App\Enums\DemandeVgtStatus.
 * Majoration (arriéré) : montant fixe configurable (config('vgt.late_surcharge_amount')), appliquée quand
 * l'année demandée est déjà passée au moment du dépôt — pas de règle équivalente documentée dans la pratique
 * malienne actuelle (recherche du 2026-09-27), donc propre à VigiMoto.
 */
#[Fillable([
    'commissariat_id', 'mairie_id', 'moto_id', 'vgt_year', 'contact_phone', 'merchant_code',
    'status', 'rejection_reason', 'base_amount', 'is_late', 'surcharge_amount',
])]
class DemandeVgt extends Model
{
    /** @use HasFactory<DemandeVgtFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'demandes_vgt';

    /**
     * Le module (permissions, audit) reste « demandes-vgt » — la clé déjà utilisée par config/planned_modules.php
     * (jamais modifié) — plutôt que le nom de la table (activityModule() suit par défaut getTable(), en
     * soulignés) : voir MotoRetrouvee::activityModule() pour le même besoin (W10).
     */
    public function activityModule(): string
    {
        return 'demandes-vgt';
    }

    protected function casts(): array
    {
        return [
            'status' => DemandeVgtStatus::class,
            'vgt_year' => 'integer',
            'is_late' => 'boolean',
            'base_amount' => 'integer',
            'surcharge_amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Commissariat, $this>
     */
    public function commissariat(): BelongsTo
    {
        return $this->belongsTo(Commissariat::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Mairie, $this>
     */
    public function mairie(): BelongsTo
    {
        return $this->belongsTo(Mairie::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Moto, $this>
     */
    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class)->withoutGlobalScope('commissariat');
    }

    public function totalAmount(): int
    {
        return $this->base_amount + $this->surcharge_amount;
    }

    /**
     * Visible du commissariat qui l'a déposée et de la mairie de retrait choisie (§4.7, comme User::visibleTo).
     */
    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        return match ($actor->roleName()) {
            RoleName::Superadmin, RoleName::AdminNational => $query,
            RoleName::Commissaire => $actor->commissariat_id
                ? $query->where('commissariat_id', $actor->commissariat_id)
                : $query->whereRaw('1 = 0'),
            RoleName::Mairie => $actor->mairie_id
                ? $query->where('mairie_id', $actor->mairie_id)
                : $query->whereRaw('1 = 0'),
            default => $query->whereRaw('1 = 0'),
        };
    }

    public function activityNoun(): string
    {
        return 'Demande VGT';
    }

    public function activityLabel(): string
    {
        return "{$this->moto?->plate_number} ({$this->vgt_year})";
    }
}
