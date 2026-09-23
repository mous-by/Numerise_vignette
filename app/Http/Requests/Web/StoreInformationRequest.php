<?php

namespace App\Http\Requests\Web;

use App\Models\Information;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Description, images ou PDF (cahier §8) : au moins un des trois. Plusieurs fichiers de chaque
 * (PROPOSITION TECHNIQUE, plafonnée — Information::MAX_IMAGES / MAX_DOCUMENTS).
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
            'description' => ['nullable', 'string', 'max:5000', Rule::requiredIf(fn () => ! $this->hasFile('images') && ! $this->hasFile('documents'))],
            'images' => ['array', 'max:'.Information::MAX_IMAGES],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'documents' => ['array', 'max:'.Information::MAX_DOCUMENTS],
            'documents.*' => ['file', 'mimes:pdf', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Une description, une image ou un fichier PDF est obligatoire.',
            'images.max' => 'Cinq images au maximum.',
            'images.*.image' => 'Ce fichier n\'est pas une image valide.',
            'images.*.mimes' => 'Image au format JPEG, PNG ou WEBP seulement.',
            'images.*.max' => 'Image de 4 Mo maximum.',
            'documents.max' => 'Trois fichiers PDF au maximum.',
            'documents.*.mimes' => 'Fichier PDF seulement.',
            'documents.*.max' => 'Fichier PDF de 8 Mo maximum.',
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'description', 'images' => 'images', 'documents' => 'fichiers PDF'];
    }
}
