<?php

namespace App\Services\Sms;

use InvalidArgumentException;

/**
 * Résout le pilote configuré (`config('sms.driver')`). Seul `log` existe tant que l'opérateur n'est pas choisi.
 */
class SmsManager
{
    public function driver(?string $name = null): SmsDriver
    {
        $name ??= (string) config('sms.driver', 'log');

        return match ($name) {
            'log' => new LogSmsDriver,
            default => throw new InvalidArgumentException("Pilote SMS inconnu : « {$name} »."),
        };
    }
}
