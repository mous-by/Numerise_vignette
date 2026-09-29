<?php

namespace App\Http\Controllers\Web;

use App\Enums\DemandeVgtStatus;
use App\Http\Controllers\Controller;
use App\Services\Vgt\VignetteVerifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Page publique ouverte par le QR Code d'une carte VGT : sans compte, limitée par IP (throttle:public), sans aucune
 * donnée personnelle (matricule, genre, année, mairie, état). PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT.
 */
class VignetteVerificationController extends Controller
{
    public function show(Request $request, string $reference, VignetteVerifier $verifier): View
    {
        $demande = $verifier->find($reference, $request->query('s'));

        if ($demande === null) {
            return view('verification.show', ['state' => 'unknown'])->with('reference', $reference);
        }

        $delivered = in_array($demande->status, [DemandeVgtStatus::Payee, DemandeVgtStatus::Retiree], true);
        $expired = $demande->vgt_year < (int) date('Y');

        return view('verification.show', [
            'state' => ! $delivered ? 'pending' : ($expired ? 'expired' : 'valid'),
            'reference' => $reference,
            'demande' => $demande,
        ]);
    }
}
