<?php

namespace App\Http\Requests\Web;

use App\Models\Commissariat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommissariatRequest extends FormRequest
{
    public function authorize(): bool
    {
        $commissariat = $this->route('commissariat');

        return $commissariat instanceof Commissariat && ($this->user()?->can('update', $commissariat) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $commissariat = $this->route('commissariat');

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('commissariats', 'name')->ignore($commissariat)],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('commissariats', 'code')->ignore($commissariat)],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du commissariat est obligatoire.',
            'name.unique' => 'Un commissariat porte déjà ce nom.',
            'code.unique' => 'Ce code est déjà utilisé par un autre commissariat.',
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
