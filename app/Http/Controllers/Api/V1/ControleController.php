<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Moto;
use App\Services\Audit\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôle rapide d'une moto par la police en patrouille (W11, cahier §4, §8) : matricule → volée ou non,
 * vignette à jour ou non. Lecture nationale (§4.7, Moto::acrossCommissariats()) : contournement explicite du
 * cloisonnement, protégé par la permission `controles.check` et audité (contrairement aux lectures ordinaires,
 * non journalisées par défaut, §4.5) — c'est une action métier, pas une simple consultation.
 * Aucune donnée personnelle du propriétaire n'est renvoyée : seuls les deux statuts demandés par le cahier.
 */
class ControleController extends Controller
{
    public function show(Request $request, ActivityLogger $logger): JsonResponse
    {
        $plateNumber = strtoupper(trim((string) $request->query('matricule', '')));

        if ($plateNumber === '') {
            return response()->json(['message' => 'Le matricule est obligatoire.', 'errors' => ['matricule' => ['Le matricule est obligatoire.']]], 422);
        }

        $moto = Moto::query()->acrossCommissariats()->where('plate_number', $plateNumber)->first();

        if ($moto === null) {
            return response()->json(['message' => 'Aucune moto trouvée avec ce matricule.'], 404);
        }

        $logger->read('controles', "Contrôle — {$moto->plate_number}", $moto);

        return response()->json([
            'matricule' => $moto->plate_number,
            'volee' => $moto->is_stolen,
            'vgt_a_jour' => $moto->isVgtCurrent(),
        ]);
    }
}
