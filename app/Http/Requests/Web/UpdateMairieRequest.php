<?php

namespace App\Http\Requests\Web;

use App\Models\Mairie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMairieRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mairie = $this->route('mairie');

        return $mairie instanceof Mairie && ($this->user()?->can('update', $mairie) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mairie = $this->route('mairie');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('mairies', 'name')->ignore($mairie)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('mairies', 'code')->ignore($mairie)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la mairie est obligatoire.',
            'name.unique' => 'Une mairie porte déjà ce nom.',
            'code.unique' => 'Ce code est déjà utilisé par une autre mairie.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'code' => 'code', 'is_active' => 'statut'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $this->filled('code') ? trim((string) $this->input('code')) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
