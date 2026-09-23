<?php

namespace App\Http\Requests\Web;

use App\Enums\Genre;
use App\Models\Proprietaire;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProprietaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        $proprietaire = $this->route('proprietaire');

        return $proprietaire instanceof Proprietaire && ($this->user()?->can('update', $proprietaire) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(array_column(Genre::cases(), 'value'))],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:'.PhoneNumber::E164_PATTERN],
            'emergency_contact' => ['nullable', 'string', 'regex:'.PhoneNumber::E164_PATTERN],
        ];
    }

    public function messages(): array
    {
        return [
            'gender.required' => 'Le genre est obligatoire.',
            'gender.in' => 'Genre invalide.',
            'phone.regex' => 'Le numéro de téléphone n\'est pas valide (exemple : 70 00 00 01).',
            'emergency_contact.regex' => 'Le contact d\'urgence n\'est pas un numéro valide (exemple : 70 00 00 01).',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'prénom', 'last_name' => 'nom', 'gender' => 'genre', 'address' => 'adresse',
            'phone' => 'numéro de téléphone', 'emergency_contact' => 'contact en cas d\'urgence',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'address' => trim((string) $this->input('address')),
            'phone' => PhoneNumber::normalize($this->input('phone')) ?? $this->input('phone'),
            'emergency_contact' => $this->filled('emergency_contact') ? (PhoneNumber::normalize($this->input('emergency_contact')) ?? $this->input('emergency_contact')) : null,
        ]);
    }
}
