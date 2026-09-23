<?php

namespace App\Http\Requests\Web;

use App\Models\Moto;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Moto::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $hasSale = $this->boolean('has_sale_certificate');
        $hasWitness = $hasSale && $this->boolean('has_witness');

        return [
            'proprietaire_id' => [
                'required',
                Rule::exists('proprietaires', 'id')->where('commissariat_id', $this->user()?->commissariat_id)->whereNull('deleted_at'),
            ],
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('motos', 'plate_number')],
            'color' => ['required', 'string', 'max:50'],
            'type_or_brand' => ['required', 'string', 'max:100'],
            'vgt_year' => ['required', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
            'has_sale_certificate' => ['boolean'],
            'seller_first_name' => $hasSale ? ['required', 'string', 'max:100'] : ['nullable'],
            'seller_last_name' => $hasSale ? ['required', 'string', 'max:100'] : ['nullable'],
            'seller_phone' => $hasSale ? ['required', 'string', 'regex:'.PhoneNumber::E164_PATTERN] : ['nullable'],
            'seller_address' => $hasSale ? ['required', 'string', 'max:255'] : ['nullable'],
            'has_witness' => ['boolean'],
            'witness_first_name' => $hasWitness ? ['required', 'string', 'max:100'] : ['nullable'],
            'witness_last_name' => $hasWitness ? ['required', 'string', 'max:100'] : ['nullable'],
            'witness_phone' => $hasWitness ? ['required', 'string', 'regex:'.PhoneNumber::E164_PATTERN] : ['nullable'],
            'witness_address' => $hasWitness ? ['required', 'string', 'max:255'] : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'proprietaire_id.required' => 'Le propriétaire est obligatoire.',
            'proprietaire_id.exists' => 'Ce propriétaire n\'existe pas dans votre commissariat.',
            'plate_number.unique' => 'Ce matricule est déjà enregistré.',
            'vgt_year.min' => 'Année de vignette invalide.',
            'vgt_year.max' => 'Année de vignette invalide.',
            'seller_first_name.required' => 'Le prénom du vendeur est obligatoire (attestation de vente).',
            'seller_last_name.required' => 'Le nom du vendeur est obligatoire (attestation de vente).',
            'seller_phone.required' => 'Le téléphone du vendeur est obligatoire (attestation de vente).',
            'seller_phone.regex' => 'Le numéro du vendeur n\'est pas valide (exemple : 70 00 00 01).',
            'seller_address.required' => 'L\'adresse du vendeur est obligatoire (attestation de vente).',
            'witness_first_name.required' => 'Le prénom du témoin est obligatoire.',
            'witness_last_name.required' => 'Le nom du témoin est obligatoire.',
            'witness_phone.required' => 'Le téléphone du témoin est obligatoire.',
            'witness_phone.regex' => 'Le numéro du témoin n\'est pas valide (exemple : 70 00 00 01).',
            'witness_address.required' => 'L\'adresse du témoin est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return [
            'proprietaire_id' => 'propriétaire', 'plate_number' => 'matricule', 'color' => 'couleur',
            'type_or_brand' => 'genre ou marque', 'vgt_year' => 'année de la vignette',
        ];
    }

    protected function prepareForValidation(): void
    {
        $hasSale = $this->boolean('has_sale_certificate');
        $hasWitness = $hasSale && $this->boolean('has_witness');

        $this->merge([
            'plate_number' => strtoupper(trim((string) $this->input('plate_number'))),
            'has_sale_certificate' => $hasSale,
            'has_witness' => $hasWitness,
            'seller_first_name' => $hasSale ? trim((string) $this->input('seller_first_name')) : null,
            'seller_last_name' => $hasSale ? trim((string) $this->input('seller_last_name')) : null,
            'seller_phone' => $hasSale && $this->filled('seller_phone') ? (PhoneNumber::normalize($this->input('seller_phone')) ?? $this->input('seller_phone')) : null,
            'seller_address' => $hasSale ? trim((string) $this->input('seller_address')) : null,
            'witness_first_name' => $hasWitness ? trim((string) $this->input('witness_first_name')) : null,
            'witness_last_name' => $hasWitness ? trim((string) $this->input('witness_last_name')) : null,
            'witness_phone' => $hasWitness && $this->filled('witness_phone') ? (PhoneNumber::normalize($this->input('witness_phone')) ?? $this->input('witness_phone')) : null,
            'witness_address' => $hasWitness ? trim((string) $this->input('witness_address')) : null,
        ]);
    }
}
