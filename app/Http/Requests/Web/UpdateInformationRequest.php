<?php

namespace App\Http\Requests\Web;

use App\Models\Information;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Description, image ou fichier PDF (cahier §8) : au moins un des trois doit rester, en comptant les fichiers déjà
 * enregistrés (remplacés ou non) sauf coche « supprimer ».
 */
class UpdateInformationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $information = $this->route('information');

        return $information instanceof Information && ($this->user()?->can('update', $information) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Information $information */
        $information = $this->route('information');

        $keepsExisting = ($information->image_path && ! $this->boolean('remove_image'))
            || ($information->document_path && ! $this->boolean('remove_document'));

        return [
            'description' => ['nullable', 'string', 'max:5000', $this->hasFile('image') || $this->hasFile('document') || $keepsExisting ? 'nullable' : 'required'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'max:8192'],
            'remove_image' => ['boolean'],
            'remove_document' => ['boolean'],
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
