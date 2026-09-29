<?php

namespace App\Jobs;

use App\Enums\SmsStatus;
use App\Models\SmsMessage;
use App\Services\Sms\SmsManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Envoie un SMS en file d'attente (W14) et consigne le résultat sur l'enregistrement. Trois essais ; l'échec
 * final est écrit sur le message, jamais remonté à l'action métier qui l'a déclenché.
 */
class SendSmsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 120];

    public function __construct(public int $smsMessageId) {}

    public function handle(SmsManager $manager): void
    {
        $sms = SmsMessage::find($this->smsMessageId);

        if ($sms === null || $sms->status === SmsStatus::Sent) {
            return;
        }

        $driver = $manager->driver();

        try {
            $reference = $driver->send($sms->phone, $sms->message);
        } catch (Throwable $exception) {
            $sms->update(['driver' => $driver->name(), 'status' => SmsStatus::Failed, 'error' => mb_substr($exception->getMessage(), 0, 250)]);

            throw $exception;
        }

        $sms->update(['driver' => $driver->name(), 'status' => SmsStatus::Sent, 'provider_reference' => $reference, 'error' => null, 'sent_at' => now()]);
    }
}
