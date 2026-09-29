<?php

namespace App\Http\Requests\Web;

use App\Models\DemandeVgt;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $demandeVgt = $this->route('demandeVgt');

        return $demandeVgt instanceof DemandeVgt && ($this->user()?->can('confirmPayment', $demandeVgt) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['payment_confirmed_at' => ['required', 'date', 'before_or_equal:today']];
    }

    public function messages(): array
    {
        return [
            'payment_confirmed_at.required' => 'La date de paiement est obligatoire.',
            'payment_confirmed_at.before_or_equal' => 'La date de paiement ne peut pas être dans le futur.',
        ];
    }

    public function attributes(): array
    {
        return ['payment_confirmed_at' => 'date de paiement'];
    }
}
