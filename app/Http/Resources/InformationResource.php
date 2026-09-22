<?php

namespace App\Http\Resources;

use App\Models\Information;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrat §8 (informations, W6, consommé par M2 et M5) : pas de titre, description/images/documents chacun
 * optionnel, URL absolues pour être joignables depuis le téléphone. `image_urls`/`document_urls` sont des
 * tableaux (PROPOSITION TECHNIQUE — plusieurs pièces jointes, au-delà du cahier qui n'en prévoit qu'une de
 * chaque) : toujours présents, vides si aucune pièce jointe de ce type.
 *
 * @mixin Information
 */
class InformationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commissaire_name' => $this->commissaire->name,
            'commissariat_name' => $this->commissariat->name,
            'description' => $this->description,
            'image_urls' => $this->imageUrls(),
            'document_urls' => $this->documentUrls(),
            'published_at' => $this->published_at->toIso8601String(),
        ];
    }
}
