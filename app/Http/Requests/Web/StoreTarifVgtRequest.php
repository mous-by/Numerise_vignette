<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use Illuminate\Foundation\Http\FormRequest;

class StoreTarifVgtRequest extends FormRequest
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
        return [
            'type_or_brand' => ['required', 'string', 'max:100', 'unique:tarifs_vgt,type_or_brand'],
            'amount' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'type_or_brand.required' => 'Le genre ou la marque est obligatoire.',
            'type_or_brand.unique' => 'Ce genre a déjà un tarif.',
            'amount.required' => 'Le montant est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return ['type_or_brand' => 'genre ou marque', 'amount' => 'montant'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['type_or_brand' => trim((string) $this->input('type_or_brand'))]);
    }
}
