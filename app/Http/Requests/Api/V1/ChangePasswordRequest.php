<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            // D14 : 8 caractères, lettres et chiffres ; différent de l'actuel (en particulier du mot de passe temporaire).
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::defaults()],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ];
    }
}
