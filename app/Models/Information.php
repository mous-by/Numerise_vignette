<?php

namespace App\Models;

use App\Enums\InformationFileType;
use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\InformationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Information publiée par un commissaire à l'intention de la population (cahier §8, §9). Public par nature (D32,
 * routes API publiques) : contrairement aux autres modules cloisonnés, sa liste se consulte à travers tous les
 * commissariats (acrossCommissariats()) ; seules la création et la modification restent liées au commissariat de
 * l'auteur (BelongsToCommissariat). Plusieurs images et plusieurs PDF par publication (PROPOSITION TECHNIQUE —
 * au-delà du cahier, qui n'en prévoit qu'un de chaque — À VALIDER AVEC LE CLIENT) : voir InformationFile.
 */
#[Fillable(['commissaire_id', 'commissariat_id', 'description', 'published_at'])]
class Information extends Model
{
    /** @use HasFactory<InformationFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    // « information » est indénombrable en anglais : Eloquent devinerait la table « information » (singulier).
    protected $table = 'informations';

    // Limites techniques (PROPOSITION TECHNIQUE, pas dans le cahier) : évitent qu'une publication devienne
    // ingérable, aussi bien côté formulaire que côté validation serveur.
    public const MAX_IMAGES = 5;

    public const MAX_DOCUMENTS = 3;

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function commissaire(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function files(): HasMany
    {
        return $this->hasMany(InformationFile::class)->orderBy('position');
    }

    public function images(): HasMany
    {
        return $this->files()->where('type', InformationFileType::Image);
    }

    public function documents(): HasMany
    {
        return $this->files()->where('type', InformationFileType::Document);
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return $this->images->map(fn (InformationFile $file) => $file->url())->all();
    }

    /**
     * @return list<string>
     */
    public function documentUrls(): array
    {
        return $this->documents->map(fn (InformationFile $file) => $file->url())->all();
    }

    public function activityNoun(): string
    {
        return 'Information';
    }

    public function activityLabel(): string
    {
        return $this->description ? Str::limit($this->description, 60) : 'Publication du '.$this->published_at?->format('d/m/Y');
    }
}
