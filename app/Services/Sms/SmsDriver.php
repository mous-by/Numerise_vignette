<?php

namespace App\Services\Sms;

/**
 * Pilote d'envoi de SMS (W14). L'opérateur réel est un point ouvert client (§5) : on l'ajoute en implémentant
 * cette interface, sans toucher au reste.
 */
interface SmsDriver
{
    /** Identifiant court du pilote, écrit dans `sms_messages.driver`. */
    public function name(): string;

    /**
     * Envoie le message. Renvoie la référence de l'opérateur, ou lève une exception en cas d'échec.
     */
    public function send(string $phone, string $message): string;
}
