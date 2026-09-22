<?php

namespace App\Enums;

/**
 * Genre d'un propriétaire (W7, cahier §5 Cas 1 : « son genre »). Pas d'enum SQL (CLAUDE.md §4.1) : colonne
 * string, ce type PHP.
 */
enum Genre: string
{
    case Homme = 'homme';
    case Femme = 'femme';

    public function label(): string
    {
        return match ($this) {
            self::Homme => 'Homme',
            self::Femme => 'Femme',
        };
    }
}
