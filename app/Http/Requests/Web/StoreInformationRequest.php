<?php

namespace App\Http\Requests\Web;

use App\Models\Information;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Description, image ou fichier PDF (cahier §8) : au moins un des trois, jamais aucun.
 */
class StoreInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Information::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => ! $this->hasFile('image') && ! $this->hasFile('document'))],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Une description, une image ou un fichier PDF est obligatoire.',
            'image.mimes' => 'Image au format JPEG, PNG ou WEBP seulement.',
            'image.max' => 'Image de 4 Mo maximum.',
            'document.mimes' => 'Fichier PDF seulement.',
            'document.max' => 'Fichier PDF de 8 Mo maximum.',
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'description', 'image' => 'image', 'document' => 'fichier PDF'];
    }
}
