<?php

namespace App\Services\Vgt;

use App\Models\DemandeVgt;

/**
 * Vérification d'une vignette par son QR Code (PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT, le QR Code est un
 * module optionnel du cahier). Le QR encode une adresse publique `/verifier/{référence}?s={signature}` : la
 * signature (HMAC de la référence avec la clé de l'application) empêche de deviner une vignette en essayant des
 * numéros. La page ne montre aucune donnée personnelle.
 */
class VignetteVerifier
{
    public function reference(DemandeVgt $demande): string
    {
        return 'VGT-'.$demande->vgt_year.'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT);
    }

    public function signature(string $reference): string
    {
        return substr(hash_hmac('sha256', 'vgt:'.$reference, (string) config('app.key')), 0, 16);
    }

    public function url(DemandeVgt $demande): string
    {
        $reference = $this->reference($demande);

        return route('vignette.verify', ['reference' => $reference, 's' => $this->signature($reference)]);
    }

    /**
     * La demande correspondant à une référence signée, ou null (référence mal formée, signature fausse, inconnue).
     */
    public function find(string $reference, ?string $signature): ?DemandeVgt
    {
        if (! preg_match('/^VGT-(\d{4})-(\d{6})$/', $reference, $parts)) {
            return null;
        }

        if (! is_string($signature) || ! hash_equals($this->signature($reference), $signature)) {
            return null;
        }

        return DemandeVgt::query()->with(['mairie', 'moto'])
            ->where('id', (int) $parts[2])->where('vgt_year', (int) $parts[1])->first();
    }
}
