<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTarifVgtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageTarifs', DemandeVgt::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['amount' => ['required', 'integer', 'min:0']];
    }

    public function messages(): array
    {
        return ['amount.required' => 'Le montant est obligatoire.', 'amount.min' => 'Le montant ne peut pas être négatif.'];
    }

    public function attributes(): array
    {
        return ['amount' => 'montant'];
    }
}
