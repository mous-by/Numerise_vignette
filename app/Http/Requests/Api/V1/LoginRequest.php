<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['phone' => 'numéro de téléphone', 'password' => 'mot de passe', 'device_name' => 'nom de l\'appareil'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['phone' => trim((string) $this->input('phone'))]);
    }
}
