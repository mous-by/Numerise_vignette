<?php

namespace App\Models;

use App\Enums\InformationFileType;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Une image ou un PDF rattaché à une information (W6). Module d'audit propre (`information_files`, la table par
 * défaut) plutôt que « informations » : sinon l'action porterait le même nom que celle de la publication elle-même
 * (`informations.deleted` pour les deux), indistinguable dans l'écran Audit (W3).
 */
#[Fillable(['information_id', 'type', 'path', 'position'])]
class InformationFile extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return ['type' => InformationFileType::class];
    }

    public function information(): BelongsTo
    {
        return $this->belongsTo(Information::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function activityNoun(): string
    {
        return $this->type === InformationFileType::Image ? 'Image' : 'Document PDF';
    }

    public function activityLabel(): string
    {
        return basename($this->path);
    }
}
