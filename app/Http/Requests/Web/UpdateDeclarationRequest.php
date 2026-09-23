<?php

namespace App\Http\Requests\Web;

use App\Enums\DeclarationType;
use App\Models\Declaration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeclarationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $declaration = $this->route('declaration');

        return $declaration instanceof Declaration && ($this->user()?->can('update', $declaration) ?? false);
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
            'type' => ['required', Rule::in(array_column(DeclarationType::cases(), 'value'))],
            'location' => ['required', 'string', 'max:255'],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'moto_id.required' => 'La moto est obligatoire.',
            'moto_id.exists' => 'Cette moto n\'existe pas dans votre commissariat.',
            'type.required' => 'Le type d\'acte est obligatoire.',
            'type.in' => 'Type d\'acte invalide.',
            'occurred_at.before_or_equal' => 'La date de l\'acte ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return [
            'moto_id' => 'moto', 'type' => 'type d\'acte', 'location' => 'lieu',
            'occurred_at' => 'date de l\'acte', 'description' => 'description',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['location' => trim((string) $this->input('location'))]);
    }
}
