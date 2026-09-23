<?php

namespace App\Enums;

/**
 * Type d'acte d'une déclaration (W9, cahier §9 « Indique Acte » : Vol, Braquage, Autre). Pas d'enum SQL
 * (CLAUDE.md §4.1) : colonne string, ce type PHP.
 */
enum DeclarationType: string
{
    case Vol = 'vol';
    case Braquage = 'braquage';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Vol => 'Vol',
            self::Braquage => 'Braquage',
            self::Autre => 'Autre',
        };
    }

    /** Marque la moto « Volée » (cahier §5 Cas 2) : vol et braquage, pas les incidents « autre » (accident…). */
    public function marksMotoAsStolen(): bool
    {
        return $this === self::Vol || $this === self::Braquage;
    }
}
