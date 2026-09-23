<?php

namespace App\Http\Requests\Web;

use App\Models\MotoRetrouvee;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMotoRetrouveeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $motoRetrouvee = $this->route('motoRetrouvee');

        return $motoRetrouvee instanceof MotoRetrouvee && ($this->user()?->can('update', $motoRetrouvee) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $recovered = $this->boolean('recovered');

        return [
            'location' => ['required', 'string', 'max:255'],
            'found_at' => ['required', 'date', 'before_or_equal:today'],
            'recovered' => ['boolean'],
            'recovered_at' => $recovered
                ? ['required', 'date', 'after_or_equal:found_at', 'before_or_equal:today']
                : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'found_at.before_or_equal' => 'La date d\'arrêt ne peut pas être dans le futur.',
            'recovered_at.required' => 'La date de récupération est obligatoire une fois « Récupérée » coché.',
            'recovered_at.after_or_equal' => 'La récupération ne peut pas précéder la date d\'arrêt.',
            'recovered_at.before_or_equal' => 'La date de récupération ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return ['location' => 'lieu', 'found_at' => 'date d\'arrêt', 'recovered_at' => 'date de récupération'];
    }

    protected function prepareForValidation(): void
    {
        $recovered = $this->boolean('recovered');

        $this->merge([
            'location' => trim((string) $this->input('location')),
            'recovered' => $recovered,
            'recovered_at' => $recovered ? $this->input('recovered_at') : null,
        ]);
    }
}
