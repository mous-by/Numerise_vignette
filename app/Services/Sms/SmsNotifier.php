<?php

namespace App\Services\Sms;

use App\Enums\SmsEvent;
use App\Enums\SmsStatus;
use App\Jobs\SendSmsJob;
use App\Models\DemandeVgt;
use App\Models\MotoRetrouvee;
use App\Models\SmsMessage;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SMS automatiques de la plateforme (W14) : moto retrouvée (au propriétaire, cahier §5 Cas 2), paiement confirmé
 * (au contact de la demande, cahier §9) et demande reçue (population, cahier §8). Chaque SMS est enregistré puis
 * envoyé en file d'attente. Un échec d'envoi ne casse jamais l'action métier : il est consigné et journalisé.
 * Textes : config/sms.php (PROPOSITION TECHNIQUE — À VALIDER AVEC LE CLIENT).
 */
class SmsNotifier
{
    public function motoRetrouvee(MotoRetrouvee $found): ?SmsMessage
    {
        $found->loadMissing(['moto' => fn ($query) => $query->with(['proprietaire' => fn ($q) => $q->acrossCommissariats()]), 'commissariat']);
        $moto = $found->moto;

        return $this->queue(SmsEvent::MotoRetrouvee, $moto?->proprietaire?->phone, [
            'matricule' => $moto?->plate_number, 'lieu' => $found->location, 'commissariat' => $found->commissariat?->name,
        ], $found);
    }

    public function paiementConfirme(DemandeVgt $demande): ?SmsMessage
    {
        $demande->loadMissing('mairie');

        return $this->queue(SmsEvent::PaiementConfirme, $demande->contact_phone, [
            'annee' => $demande->vgt_year, 'mairie' => $demande->mairie?->name,
        ], $demande);
    }

    public function demandeRecue(DemandeVgt $demande): ?SmsMessage
    {
        $demande->loadMissing('mairie');

        return $this->queue(SmsEvent::DemandeRecue, $demande->contact_phone, [
            'annee' => $demande->vgt_year, 'mairie' => $demande->mairie?->name,
            'reference' => 'VGT-'.$demande->vgt_year.'-'.str_pad((string) $demande->id, 6, '0', STR_PAD_LEFT),
        ], $demande);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function queue(SmsEvent $event, ?string $phone, array $values, Model $subject): ?SmsMessage
    {
        if (! config('sms.enabled')) {
            return null;
        }

        try {
            $normalized = PhoneNumber::normalize((string) $phone);

            if ($normalized === null) {
                return null;
            }

            $message = strtr((string) config("sms.templates.{$event->value}"), collect($values)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => (string) $value])->all());

            $sms = SmsMessage::create([
                'event' => $event, 'phone' => $normalized, 'message' => $message, 'status' => SmsStatus::Queued,
                'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            ]);

            SendSmsJob::dispatch($sms->id);

            return $sms;
        } catch (Throwable $exception) {
            Log::warning('SMS non mis en file', ['event' => $event->value, 'error' => $exception->getMessage()]);

            return null;
        }
    }
}
