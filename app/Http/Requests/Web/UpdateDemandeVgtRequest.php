<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandeVgtRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demandeVgt = $this->route('demandeVgt');

        return $demandeVgt instanceof DemandeVgt && ($this->user()?->can('update', $demandeVgt) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'moto_id' => [
                'required',
                Rule::exists('motos', 'id')->where('commissariat_id', $this->user()?->commissariat_id)->whereNull('deleted_at'),
            ],
            'mairie_id' => ['required', Rule::exists('mairies', 'id')->where('is_active', true)->whereNull('deleted_at')],
            'vgt_year' => ['required', 'integer', 'min:2000', 'max:'.((int) date('Y') + 1)],
            'contact_phone' => ['required', 'string', 'regex:'.PhoneNumber::E164_PATTERN],
            'merchant_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'moto_id.required' => 'La moto est obligatoire.',
            'moto_id.exists' => 'Cette moto n\'existe pas dans votre commissariat.',
            'mairie_id.required' => 'La mairie de retrait est obligatoire.',
            'mairie_id.exists' => 'Mairie invalide ou inactive.',
            'contact_phone.regex' => 'Le contact SMS n\'est pas un numéro valide (exemple : 70 00 00 01).',
        ];
    }

    public function attributes(): array
    {
        return [
            'moto_id' => 'moto', 'mairie_id' => 'mairie de retrait', 'vgt_year' => 'année de la vignette',
            'contact_phone' => 'contact SMS', 'merchant_code' => 'code marchand',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'contact_phone' => $this->filled('contact_phone') ? (PhoneNumber::normalize($this->input('contact_phone')) ?? $this->input('contact_phone')) : null,
            'merchant_code' => $this->filled('merchant_code') ? trim((string) $this->input('merchant_code')) : null,
        ]);
    }
}
