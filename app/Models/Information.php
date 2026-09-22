<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCommissariat;
use App\Models\Concerns\LogsActivity;
use Database\Factories\InformationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Information publiée par un commissaire à l'intention de la population (cahier §8, §9). Public par nature (D32,
 * routes API publiques) : contrairement aux autres modules cloisonnés, sa liste se consulte à travers tous les
 * commissariats (acrossCommissariats()) ; seules la création et la modification restent liées au commissariat de
 * l'auteur (BelongsToCommissariat).
 */
#[Fillable(['commissaire_id', 'commissariat_id', 'description', 'image_path', 'document_path', 'published_at'])]
class Information extends Model
{
    /** @use HasFactory<InformationFactory> */
    use BelongsToCommissariat, HasFactory, LogsActivity, SoftDeletes;

    // « information » est indénombrable en anglais : Eloquent devinerait la table « information » (singulier).
    protected $table = 'informations';

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function commissaire(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    public function documentUrl(): ?string
    {
        return $this->document_path ? Storage::disk('public')->url($this->document_path) : null;
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
