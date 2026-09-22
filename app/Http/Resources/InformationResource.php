<?php

namespace App\Http\Resources;

use App\Models\Information;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrat §8 (informations, W6, consommé par M2 et M5) : pas de titre, description/image/document chacun
 * optionnel, URL absolues pour être joignables depuis le téléphone.
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
            'image_url' => $this->imageUrl(),
            'document_url' => $this->documentUrl(),
            'published_at' => $this->published_at->toIso8601String(),
        ];
    }
}
