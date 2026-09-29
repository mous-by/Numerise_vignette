<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmRetraitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demandeVgt = $this->route('demandeVgt');

        return $demandeVgt instanceof DemandeVgt && ($this->user()?->can('confirmRetrait', $demandeVgt) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['retrait_date' => ['required', 'date', 'before_or_equal:today']];
    }

    public function messages(): array
    {
        return [
            'retrait_date.required' => 'La date de retrait est obligatoire.',
            'retrait_date.before_or_equal' => 'La date de retrait ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return ['retrait_date' => 'date de retrait'];
    }
}
