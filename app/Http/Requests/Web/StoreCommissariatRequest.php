<?php

namespace App\Http\Requests\Web;

use App\Models\Commissariat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommissariatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Commissariat::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('commissariats', 'name')],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('commissariats', 'code')],
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
        return ['name' => 'nom', 'code' => 'code'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => $this->filled('code') ? trim((string) $this->input('code')) : null,
        ]);
    }
}
