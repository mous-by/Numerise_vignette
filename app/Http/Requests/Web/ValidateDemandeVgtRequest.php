<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidateDemandeVgtRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demandeVgt = $this->route('demandeVgt');

        return $demandeVgt instanceof DemandeVgt && ($this->user()?->can('validateRequest', $demandeVgt) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rejected = $this->input('decision') === 'rejetee';

        return [
            'decision' => ['required', Rule::in(['validee', 'rejetee'])],
            'rejection_reason' => $rejected ? ['required', 'string', 'max:1000'] : ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'La décision est obligatoire.',
            'rejection_reason.required' => 'Le motif de rejet est obligatoire.',
        ];
    }

    public function attributes(): array
    {
        return ['decision' => 'décision', 'rejection_reason' => 'motif de rejet'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('decision') !== 'rejetee') {
            $this->merge(['rejection_reason' => null]);
        }
    }
}
