<?php

namespace App\Models;

use App\Enums\SmsEvent;
use App\Enums\SmsStatus;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SMS envoyé par la plateforme (W14) : un enregistrement par message, de la mise en file au résultat. Sujet =
 * l'objet métier qui l'a déclenché (moto retrouvée, demande VGT). Le numéro et le texte sont des données
 * personnelles : ils ne sont jamais copiés dans l'audit (activityExcept), seul l'événement l'est.
 */
#[Fillable(['event', 'phone', 'message', 'status', 'driver', 'provider_reference', 'error', 'subject_type', 'subject_id', 'sent_at'])]
class SmsMessage extends Model
{
    use LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return ['event' => SmsEvent::class, 'status' => SmsStatus::class, 'sent_at' => 'datetime'];
    }

    public function activityExcept(): array
    {
        return ['created_at', 'updated_at', 'deleted_at', 'phone', 'message', 'error', 'provider_reference'];
    }

    public function activityNoun(): string
    {
        return 'SMS';
    }

    public function activityLabel(): string
    {
        return $this->event?->label() ?? 'SMS';
    }
}
