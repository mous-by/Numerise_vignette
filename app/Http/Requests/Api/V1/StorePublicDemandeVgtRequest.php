<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

/**
 * Demande de VGT de la population, sans compte (D32, M4).
 */
class StorePublicDemandeVgtRequest extends PublicVgtIdentityRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'mairie_id' => ['required', Rule::exists('mairies', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'vgt_year' => ['required', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'mairie_id.required' => 'La mairie de retrait est obligatoire.',
            'mairie_id.exists' => 'Mairie invalide ou inactive.',
            'vgt_year.required' => 'L\'année de la vignette est obligatoire.',
            'vgt_year.min' => 'L\'année de la vignette n\'est pas valide.',
            'vgt_year.max' => 'L\'année de la vignette ne peut pas dépasser l\'année prochaine.',
        ];
    }
}
