<?php

namespace App\Http\Requests\Api\V1;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Identité sans compte de la population (D32) : matricule + téléphone enregistré du propriétaire.
 */
class PublicVgtIdentityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'matricule' => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::E164_PATTERN],
        ];
    }

    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide (exemple : 70 00 00 01).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'matricule' => strtoupper(trim((string) $this->input('matricule', ''))),
            'phone' => $this->filled('phone') ? (PhoneNumber::normalize($this->input('phone')) ?? $this->input('phone')) : null,
        ]);
    }
}
