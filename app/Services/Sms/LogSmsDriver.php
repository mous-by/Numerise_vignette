<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Pilote par défaut : n'envoie rien, écrit le message dans le journal Laravel (jamais de vrai SMS tant que
 * l'opérateur n'est pas choisi avec le client).
 */
class LogSmsDriver implements SmsDriver
{
    public function name(): string
    {
        return 'log';
    }

    public function send(string $phone, string $message): string
    {
        Log::info('SMS simulé', ['to' => $phone, 'message' => $message]);

        return 'log-'.Str::uuid();
    }
}
