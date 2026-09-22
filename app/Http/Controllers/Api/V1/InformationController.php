<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InformationResource;
use App\Models\Information;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Lecture publique des informations (W6, D32, §8) : sans jeton, limitée par IP (throttle:public). Consommée par
 * la police (M2) et la population (M5). Renvoie les informations de tous les commissariats, la plus récente
 * d'abord.
 */
class InformationController extends Controller
{
    private const PER_PAGE = 20;

    public function index(): AnonymousResourceCollection
    {
        $informations = Information::acrossCommissariats()
            ->with(['commissaire', 'commissariat'])
            ->orderByDesc('published_at')
            ->paginate(self::PER_PAGE);

        return InformationResource::collection($informations);
    }
}
