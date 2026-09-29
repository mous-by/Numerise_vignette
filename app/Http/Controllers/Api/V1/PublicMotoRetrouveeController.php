<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mairie;
use App\Models\MotoRetrouvee;
use Illuminate\Http\JsonResponse;

/**
 * Lecture publique des motos retrouvées et des mairies (D32, cahier §8, M5 et M4) : sans jeton, limitée par IP
 * (throttle:public). Aucune donnée personnelle : matricule, genre, couleur, lieu et date d'arrêt, commissariat.
 * Seules les motos non encore récupérées sont listées, la plus récente d'abord.
 */
class PublicMotoRetrouveeController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): JsonResponse
    {
        $page = MotoRetrouvee::query()->acrossCommissariats()
            ->with(['moto', 'commissariat'])
            ->where('recovered', false)
            ->orderByDesc('found_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $page->getCollection()->map(fn (MotoRetrouvee $found) => [
                'id' => $found->id,
                'matricule' => $found->moto?->plate_number,
                'genre' => $found->moto?->type_or_brand,
                'couleur' => $found->moto?->color,
                'lieu' => $found->location,
                'date_arret' => $found->found_at?->toDateString(),
                'commissariat' => $found->commissariat?->name,
            ])->all(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage()],
        ]);
    }

    /** Mairies actives, pour choisir la mairie de retrait d'une demande de VGT. */
    public function mairies(): JsonResponse
    {
        return response()->json([
            'data' => Mairie::active()->orderBy('name')->get(['id', 'name'])->map(fn (Mairie $mairie) => ['id' => $mairie->id, 'nom' => $mairie->name])->all(),
        ]);
    }
}
