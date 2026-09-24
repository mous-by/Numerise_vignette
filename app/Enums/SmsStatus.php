<?php

namespace App\Enums;

/**
 * Statut d'envoi d'un SMS (W14) : en file, envoyé (ou simulé par le pilote `log`), échec.
 */
enum SmsStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'En file',
            self::Sent => 'Envoyé',
            self::Failed => 'Échec',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Queued => 'bg-secondary-subtle',
            self::Sent => 'bg-success-subtle',
            self::Failed => 'bg-danger-subtle',
        };
    }
}
