<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DemandeVgtStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PublicVgtIdentityRequest;
use App\Http\Requests\Api\V1\StorePublicDemandeVgtRequest;
use App\Models\DemandeVgt;
use App\Services\Vgt\DemandeVgtPricing;
use App\Services\Vgt\PublicOwnerIdentifier;
use Illuminate\Http\JsonResponse;

/**
 * Demande de VGT et suivi par la population, sans compte (D32, cahier §8, M4). PROPOSITION TECHNIQUE — À VALIDER
 * AVEC LE CLIENT : le propriétaire s'identifie par le matricule de sa moto et le téléphone enregistré au commissariat
 * (`PublicOwnerIdentifier`). Routes sans jeton, limitées par IP et par matricule (throttle:public-write). La demande
 * suit ensuite le même workflow que celle du commissaire (validation, paiement et retrait à la mairie choisie).
 * Aucune donnée personnelle n'est renvoyée.
 */
class PublicDemandeVgtController extends Controller
{
    private const UNKNOWN = 'Aucune moto ne correspond à ce matricule et à ce numéro de téléphone.';

    public function store(StorePublicDemandeVgtRequest $request, PublicOwnerIdentifier $identifier, DemandeVgtPricing $pricing): JsonResponse
    {
        $moto = $identifier->moto($request->validated('matricule'), $request->validated('phone'));

        if ($moto === null) {
            return $this->unknown();
        }

        $year = (int) $request->validated('vgt_year');

        if ($year <= (int) $moto->vgt_year) {
            return response()->json(['message' => "La vignette de cette moto est déjà à jour jusqu'en {$moto->vgt_year}.", 'errors' => ['vgt_year' => ['Vignette déjà à jour pour cette année.']]], 422);
        }

        $open = DemandeVgt::query()->where('moto_id', $moto->id)->where('vgt_year', $year)
            ->whereIn('status', [DemandeVgtStatus::EnAttente->value, DemandeVgtStatus::Validee->value, DemandeVgtStatus::Payee->value])->exists();

        if ($open) {
            return response()->json(['message' => "Une demande est déjà en cours pour l'année {$year}."], 409);
        }

        $demande = DemandeVgt::create($pricing->for($moto, $year) + [
            'commissariat_id' => $moto->commissariat_id,
            'mairie_id' => $request->validated('mairie_id'),
            'moto_id' => $moto->id,
            'vgt_year' => $year,
            'contact_phone' => $request->validated('phone'),
            'status' => DemandeVgtStatus::EnAttente,
        ]);

        return response()->json(['data' => $this->present($demande->load('mairie'))], 201);
    }

    public function suivi(PublicVgtIdentityRequest $request, PublicOwnerIdentifier $identifier): JsonResponse
    {
        $moto = $identifier->moto($request->validated('matricule'), $request->validated('phone'));

        if ($moto === null) {
            return $this->unknown();
        }

        $demandes = DemandeVgt::query()->with('mairie')->where('moto_id', $moto->id)->latest('id')->limit(20)->get();

        return response()->json([
            'matricule' => $moto->plate_number,
            'vgt_a_jour' => $moto->isVgtCurrent(),
            'data' => $demandes->map(fn (DemandeVgt $demande) => $this->present($demande))->all(),
        ]);
    }

    private function unknown(): JsonResponse
    {
        return response()->json(['message' => self::UNKNOWN, 'errors' => ['matricule' => [self::UNKNOWN]]], 422);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(DemandeVgt $demande): array
    {
        return [
            'reference' => 'VGT-'.$demande->vgt_year.'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT),
            'annee' => $demande->vgt_year,
            'statut' => ['code' => $demande->status->value, 'libelle' => $demande->status->label()],
            'motif_rejet' => $demande->rejection_reason,
            'montant' => ['base' => $demande->base_amount, 'majoration' => $demande->surcharge_amount, 'total' => $demande->totalAmount()],
            'mairie' => ['id' => $demande->mairie_id, 'nom' => $demande->mairie?->name],
            'paiement_confirme_le' => $demande->payment_confirmed_at?->toDateString(),
            'retrait_le' => $demande->retrait_date?->toDateString(),
            'cree_le' => $demande->created_at->toIso8601String(),
        ];
    }
}
