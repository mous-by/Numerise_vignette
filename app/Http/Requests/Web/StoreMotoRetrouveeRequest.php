<?php

namespace App\Http\Requests\Web;

use App\Models\Moto;
use App\Models\MotoRetrouvee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMotoRetrouveeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MotoRetrouvee::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Recherche nationale (§4.7, acrossCommissariats) : une moto volée peut être retrouvée dans un autre
            // commissariat que celui qui l'a enregistrée.
            'moto_id' => [
                'required',
                Rule::exists('motos', 'id')->where('is_stolen', true)->whereNull('deleted_at'),
            ],
            'location' => ['required', 'string', 'max:255'],
            'found_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'moto_id.required' => 'La moto est obligatoire.',
            'moto_id.exists' => 'Cette moto n\'est pas actuellement signalée volée.',
            'found_at.before_or_equal' => 'La date d\'arrêt ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return ['moto_id' => 'moto', 'location' => 'lieu', 'found_at' => 'date d\'arrêt'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['location' => trim((string) $this->input('location'))]);
    }

    /** Résolue hors cloisonnement (la moto peut appartenir à un autre commissariat). */
    public function moto(): ?Moto
    {
        return Moto::query()->acrossCommissariats()->find($this->validated('moto_id'));
    }
}
