<?php

namespace App\Services\Vgt;

use App\Models\Moto;
use App\Support\PhoneNumber;

/**
 * Identifie un propriétaire sans compte (D32, PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT) : le matricule de la
 * moto et le numéro de téléphone enregistré au commissariat pour son propriétaire doivent correspondre. Un matricule
 * inconnu et un mauvais numéro donnent le même résultat (null), pour ne rien révéler sur les motos enregistrées.
 */
class PublicOwnerIdentifier
{
    public function moto(string $plateNumber, string $phone): ?Moto
    {
        $normalized = PhoneNumber::normalize($phone);

        if ($normalized === null) {
            return null;
        }

        $moto = Moto::query()->acrossCommissariats()
            ->with(['proprietaire' => fn ($query) => $query->acrossCommissariats()])
            ->where('plate_number', strtoupper(trim($plateNumber)))
            ->first();

        return $moto !== null && $moto->proprietaire?->phone === $normalized ? $moto : null;
    }
}
