<?php

namespace App\Http\Requests\Web;

use App\Models\Information;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Description, images ou PDF (cahier §8) : au moins un des trois doit rester, en comptant les pièces déjà
 * enregistrées (moins celles cochées à supprimer) plus les nouvelles. Plafonds : Information::MAX_IMAGES / MAX_DOCUMENTS.
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

        return [
            'description' => ['nullable', 'string', 'max:5000'],
            'images' => ['array'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'documents' => ['array'],
            'documents.*' => ['file', 'mimes:pdf', 'max:8192'],
            'remove_files' => ['array'],
            'remove_files.*' => ['integer', Rule::exists('information_files', 'id')->where('information_id', $information->id)],
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.image' => 'Ce fichier n\'est pas une image valide.',
            'images.*.mimes' => 'Image au format JPEG, PNG ou WEBP seulement.',
            'images.*.max' => 'Image de 4 Mo maximum.',
            'documents.*.mimes' => 'Fichier PDF seulement.',
            'documents.*.max' => 'Fichier PDF de 8 Mo maximum.',
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'description', 'images' => 'images', 'documents' => 'fichiers PDF'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Information $information */
            $information = $this->route('information');
            $removed = collect($this->input('remove_files', []))->map(fn ($id) => (int) $id);

            $remainingImages = $information->images()->whereNotIn('id', $removed)->count() + count($this->file('images', []));
            $remainingDocuments = $information->documents()->whereNotIn('id', $removed)->count() + count($this->file('documents', []));

            if ($remainingImages > Information::MAX_IMAGES) {
                $validator->errors()->add('images', 'Cinq images au maximum par publication.');
            }
            if ($remainingDocuments > Information::MAX_DOCUMENTS) {
                $validator->errors()->add('documents', 'Trois fichiers PDF au maximum par publication.');
            }

            $keepsAFile = $information->files()->whereNotIn('id', $removed)->exists() || $this->hasFile('images') || $this->hasFile('documents');
            if (! $keepsAFile && ! $this->filled('description')) {
                $validator->errors()->add('description', 'Une description, une image ou un fichier PDF est obligatoire.');
            }
        });
    }
}
